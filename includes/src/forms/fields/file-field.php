<?php

namespace MyListing\Src\Forms\Fields;

use MyListing\Src\Forms\Fields\Traits\Validation_Helpers;

if ( ! defined('ABSPATH') ) {
	exit;
}

class File_Field extends Base_Field {
	use Validation_Helpers;

	public function get_posted_value() {
		$form_key = 'current_'.$this->key;
		$files = isset( $_POST[ $form_key ] ) ? (array) $_POST[ $form_key ] : [];

		/**
		 * Some CDN and media offload plugins replace all occurrences of a local URL in a page
		 * with it's CDN value.
		 *
		 * This breaks file fields in the Add Listing form since the GUID is used as the field value,
		 * and if it's domain gets replaced with the CDN one, we no longer have a way to
		 * retrieve the attachment details.
		 *
		 * To avoid this, the GUID is displayed in the form field in base64 encoded format
		 * with "b64:" prepended to it.
		 *
		 * When the listing is saved, GUID gets decoded and the actual GUID is stored in
		 * the database. It is then re-encoded when displayed in the hidden input in
		 * Add Listing form so that it's not possible to match and replace it.
		 *
		 * @since 2.4.5
		 */
		$files = array_map( function( $value ) {
			if ( substr( $value, 0, 4 ) === 'b64:' ) {
				$value = base64_decode( str_replace( 'b64:', '', $value ), true );
			}

			if ( ! is_string( $value ) || empty( $value ) ) {
				return null;
			}

			return esc_url_raw( $value, [ 'http', 'https' ] );
		}, $files );

		return array_filter( $files );
	}

	public function validate() {
		$value = $this->get_posted_value();

		// Determine limits to use
		$count_limit_to_use = $this->get_current_selection_limit();
		$size_limit_kb_to_use = ! empty( $this->props['file_size_limit'] ) ? absint( $this->props['file_size_limit'] ) : null;

		// Apply package-specific size limit if applicable
		$package_id = $this->get_package_id_for_validation();
		if ( isset( $this->props['enable_package_limits'] ) && $this->props['enable_package_limits'] ) {
			if ( $package_id ) {
				foreach ( $this->props['package_limits'] as $rule ) {
					if ( isset( $rule['package'] ) && $rule['package'] === (string) $package_id ) {
						if ( isset( $rule['size_limit_kb'] ) && is_numeric( $rule['size_limit_kb'] ) ) {
							// Override default individual size limit if package rule is set
							$size_limit_kb_to_use = absint( $rule['size_limit_kb'] );
						}
						break;
					}
				}
			}
		}

		// Validate file count using Validation_Helpers trait
		if ( $this->props['multiple'] ) {
			$this->validate_selection_count(
				$value,
				$count_limit_to_use,
				$this->props['label'],
				_x( 'You can only upload %1$d file for the %2$s field.', 'Add listing form', 'my-listing' ),
				_x( 'You can only upload %1$d files for the %2$s field.', 'Add listing form', 'my-listing' )
			);

			// Validate minimum file count
			$min_limit = ! empty( $this->props['min_file_limit'] ) ? absint( $this->props['min_file_limit'] ) : 0;
			if ( $min_limit > 0 && count( $value ) < $min_limit ) {
				// translators: %1$d is the minimum count; %2$s is the field label.
				throw new \Exception( sprintf(
					_n(
						'You must upload at least %1$d file for the %2$s field.',
						'You must upload at least %1$d files for the %2$s field.',
						$min_limit,
						'my-listing'
					),
					$min_limit,
					$this->props['label']
				) );
			}
		}

		$oversized_filenames = [];

		foreach ( (array) $value as $file_guid ) {
			$image_id = null;
			$filesize = null;
			$file_url = null;

			if ( is_numeric( $file_guid ) ) {
				// Existing attachment ID
				$image_id = absint( $file_guid );
				$img_data = wp_get_attachment_metadata( $image_id );
				// Need to get filesize even for existing attachments to validate individual size
				if ( ! $img_data || ! isset( $img_data['filesize'] ) ) {
					// Try getting filesize directly if metadata is missing
					$filepath = get_attached_file( $image_id );
					if ( $filepath && file_exists( $filepath ) ) {
						$filesize = filesize( $filepath );
					} else {
						// In switch package context, skip size validation for existing attachments with missing metadata
						$is_switch_context = isset( $_REQUEST['context'] ) && $_REQUEST['context'] === 'switch_package_edit';
						if ( $is_switch_context ) {
							// Skip file size validation for existing attachments in switch context
							$filesize = null;
						} else {
							throw new \Exception( sprintf(
								_x( 'Could not retrieve file size information for an existing attachment in %s.', 'Add listing form', 'my-listing' ),
								$this->props['label']
							) );
						}
					}
				} else {
					$filesize = $img_data['filesize'];
				}

			} else {
				// Validate attachment url
				$file_url = esc_url( $file_guid, [ 'http', 'https' ] );
				if ( empty( $file_url ) ) {
					// translators: %s is the field label.
					throw new \Exception( sprintf(
						_x( 'Invalid attachment provided for %s.', 'Add listing form', 'my-listing' ),
						$this->props['label']
					) );
				}

				$image_id = c27()->get_attachment_by_guid( $file_url );

				if ( ! $image_id ) {
					throw new \Exception( sprintf(
						_x( 'Could not find attachment for %s.', 'Add listing form', 'my-listing' ),
						$this->props['label']
					) );
				}

				$img_data = wp_get_attachment_metadata( $image_id );

				// Get filesize for validation
				if ( $img_data && isset( $img_data['filesize'] ) ) {
					$filesize = absint( $img_data['filesize'] );
				} else {
					// Try getting filesize directly if metadata is missing
					$filepath = get_attached_file( $image_id );
					if ( $filepath && file_exists( $filepath ) ) {
						$filesize = filesize( $filepath );
					} else {
						// In switch package context, skip size validation for existing attachments with missing metadata
						$is_switch_context = isset( $_REQUEST['context'] ) && $_REQUEST['context'] === 'switch_package_edit';
						if ( $is_switch_context ) {
							// Skip file size validation for existing attachments in switch context
							$filesize = null;
						} else {
							throw new \Exception( sprintf(
								_x( 'Could not retrieve file size information for a new attachment in %s.', 'Add listing form', 'my-listing' ),
								$this->props['label']
							) );
						}
					}
				}
			}

			// Validate individual file size (using determined limit)
			if ( $size_limit_kb_to_use !== null && $size_limit_kb_to_use > 0 && $filesize !== null ) {
				$img_size_kb = $filesize / 1024;
				if ( $img_size_kb > $size_limit_kb_to_use ) {
					$img_name = get_the_title( $image_id );
					$error_filename = $img_name ? : basename( get_attached_file( $image_id ) ?: $file_guid );
					$oversized_filenames[] = $error_filename; // Add filename to array
				}
			}

			// Validate attachment file types (existing check - needs $file_url)
			$check_url = $file_url ? : wp_get_attachment_url( $image_id );
			$file_url_clean = $check_url ? current( explode( '?', $check_url ) ) : null;
			$file_info = $file_url_clean ? wp_check_filetype( $file_url_clean ) : null;

			if (
				! empty( $this->props['allowed_mime_types'] ) && $file_info
				&& ! in_array( $file_info['type'], $this->props['allowed_mime_types'], true )
			) {
				// translators: Placeholder %1$s is the field label; %2$s is the file mime type; %3$s is the allowed mime-types.
				throw new \Exception( sprintf(
					_x( '"%1$s" (filetype %2$s) needs to be one of the following file types: %3$s', 'Add listing form', 'my-listing' ),
					$this->props['label'],
					$file_info['ext'],
					implode( ', ', array_keys( $this->props['allowed_mime_types'] ) )
				) );
			}
		}

		// Throw collected size errors, if any, with a consolidated message
		if ( ! empty( $oversized_filenames ) ) {
			$oversized_count = count( $oversized_filenames );
			$filenames_string = implode( ', ', array_map( function( $name ) {
				return sprintf( '"%s"', $name );
			}, $oversized_filenames ) );

			$error_message = sprintf(
				_n(
					'File %1$s in field "%3$s" exceeds the size limit of %2$d KB.',
					'Files %1$s in field "%3$s" exceed the size limit of %2$d KB.',
					$oversized_count,
					'my-listing'
				),
				$filenames_string,
				$size_limit_kb_to_use,
				$this->props['label']
			);

			throw new \Exception( $error_message );
		}
	}

	public function update() {
		global $wpdb;

		$value = $this->get_posted_value();
		$old_value = get_post_meta( $this->listing->get_id(), '_'.$this->key, true );
		$attachment_ids = [];
		$attachment_urls = [];

		/**
		 * Prepare the selected files to be saved in the listing meta. Maintains backward
		 * compatibility, handles external images and offloaded media.
		 */
		foreach ( (array) $value as $file_guid ) {
			// `guid` is a unique identifier that we can use to get attachments
			$row = $wpdb->get_row( $wpdb->prepare(
				"SELECT ID, post_parent, post_status FROM {$wpdb->posts}
					WHERE post_type = 'attachment' AND guid = %s LIMIT 1",
				$file_guid
			) );

			/**
			 * Check if this file exists as an attachment already. If it doesn't, then attempt
			 * to create the attachment, but only if the file is writable and within the `uploads/` directory.
			 *
			 * If the file isn't writable, it's an external image, in which case, we can't add it as an attachment, since
			 * it won't be possible to generate all the different image sizes.
			 */
			if ( ! is_object( $row ) || empty( $row->ID ) ) {
				$attachment_urls[] = $file_guid;
				if ( $attachment_id = $this->create_attachment( $file_guid ) ) {
					$attachment_ids[ $file_guid ] = $attachment_id;
				}
				continue;
			}

			/**
			 * If it's a new attachment, update it's status from `preview` to `inherit`,
			 * and set the `post_parent` to the listing it's being uploaded to.
			 */
			if ( $row->post_status === 'preview' ) {
				wp_update_post( [
					'ID' => $row->ID,
					'post_status' => 'inherit',
					'post_parent' => $this->listing->get_id(),
				] );
			}

			// attachment is valid, store it's ID
			$attachment_ids[ $file_guid ] = absint( $row->ID );
			$attachment_urls[] = $file_guid;
		}

		// update the field meta with the attachment urls
		update_post_meta( $this->listing->get_id(), '_'.$this->key, $attachment_urls );

		/**
		 * Delete unused attachments in $old_value. This behavior can be skipped using:
		 * `add_filter( 'mylisting/submission/delete-unused-attachments', '__return_false' );`
		 *
		 * @since 2.1
		 */
		foreach ( (array) $old_value as $attachment_url ) {
			if ( apply_filters( 'mylisting/submission/delete-unused-attachments', ! is_admin() ) !== true ) {
				continue;
			}

			$row = $wpdb->get_row( $wpdb->prepare(
				"SELECT ID, post_parent, post_status FROM {$wpdb->posts} WHERE post_type = 'attachment' AND guid = %s LIMIT 1",
				$attachment_url
			) );

			// validate attachment
			if ( ! is_object( $row ) || empty( $row->ID ) || absint( $row->post_parent ) !== $this->listing->get_id() ) {
				continue;
			}

			// if this attachment is not present in the new attachment ids list,
			// then it's been removed by the user, so we can delete it.
			if ( ! in_array( absint( $row->ID ), $attachment_ids ) ) {
				mlog()->warn( "Deleted attachment #{$row->ID} since it's no longer used by the listing." );
				wp_delete_attachment( absint( $row->ID ), true );
			}
		}
	}

	private function create_attachment( $file_url ) {
		include_once ABSPATH . 'wp-admin/includes/file.php';
		include_once ABSPATH . 'wp-admin/includes/media.php';
		include_once ABSPATH . 'wp-admin/includes/image.php';

		$upload_dir = wp_upload_dir();
		$filepath = str_replace(
			[ $upload_dir['baseurl'], $upload_dir['url'], WP_CONTENT_URL ],
			[ $upload_dir['basedir'], $upload_dir['path'], WP_CONTENT_DIR ],
			$file_url
		);

		// validate
		if (
			! strstr( $file_url, WP_CONTENT_URL )
			|| strstr( $filepath, 'http:' )
			|| strstr( $filepath, 'https:' )
			|| ! wp_is_writable( $filepath )
		) {
			mlog( sprintf(
				'External or non-writable image used, skipping attachment. '
				.'<a href="%s" target="_blank">[link]</a>',
				$file_url
			) );
			return false;
		}

		// create attachment
		$attachment_id = wp_insert_attachment( [
			'post_title' => basename( $filepath ),
			'post_content' => '',
			'post_status' => 'inherit',
			'post_parent' => $this->listing->get_id(),
			'post_mime_type' => wp_check_filetype( basename( $filepath ) )['type'],
			'guid' => $file_url,
		], $filepath );

		if ( ! $attachment_id || is_wp_error( $attachment_id ) ) {
			return false;
		}

		// generate attachment details and sizes
		wp_update_attachment_metadata(
			$attachment_id,
			wp_generate_attachment_metadata( $attachment_id, $filepath )
		);

		mlog( 'Generated attachment for writable file #'.$attachment_id );
		return $attachment_id;
	}

	protected function get_package_id_for_validation() {
		// Uses Validation_Helpers trait method
		return $this->_get_package_id_from_context( $this->listing );
	}

	private function get_package_product_id( $user_package_id ) {
		if ( ! $user_package_id || ! is_numeric( $user_package_id ) ) {
			return false;
		}

		$package = \MyListing\Src\Package::get( $user_package_id );
		if ( ! $package || ! $package->get_product_id() ) {
			return false;
		}

		return $package->get_product_id();
	}

	/**
	 * Calculate file count limit for the current field in the current context.
	 *
	 * @return int|null The file count limit, or null if no limit is set.
	 */

	public function get_current_selection_limit() {
		$package_id = $this->_get_package_id_from_context( $this->listing );
		return $this->_calculate_current_selection_limit( $this->props, $package_id );
	}

	public function get_value( $all = false ) {
		$value_from_parent = parent::get_value();
		$files_to_display = [];

		if ( is_array( $value_from_parent ) ) {
			$files_to_display = $value_from_parent;
		} elseif ( ! empty( $value_from_parent ) && is_string( $value_from_parent ) ) {
			$files_to_display = [ (string) $value_from_parent ];
		}

		$files_to_display = array_filter( $files_to_display );

		// When switching packages or duplicating a listing, show all previously uploaded files.
		// Validation will handle enforcing new limits upon saving.

		if ( $all ) {
			return $files_to_display;
		}

		if ( isset( $_REQUEST['action'] ) && ( $_REQUEST['action'] === 'switch' || $_REQUEST['action'] === 'duplicate' ) ) {
			return $files_to_display;
		}

		// Apply file count limit for display in other contexts.
		$limit = $this->get_current_selection_limit();

		if ( $this->props['multiple'] ) {
			if ( ! is_null( $limit ) && $limit >= 0 && count( $files_to_display ) > $limit ) {
				$files_to_display = array_slice( $files_to_display, 0, $limit );
			}
		} else {
			if ( count( $files_to_display ) > 1 ) {
				$files_to_display = array_slice( $files_to_display, 0, 1 );
			}
		}

		return $files_to_display;
	}

	public function field_props() {
		$this->props['type'] = 'file';
		$this->props['ajax'] = true;
		$this->props['multiple'] = false;
		$this->props['file_limit'] = '';
		$this->props['min_file_limit'] = '';
		$this->props['enable_package_limits'] = false;
		$this->props['package_limits'] = [];
		$this->props['file_size_limit'] = '';
		$this->props['allowed_mime_types'] = new \stdClass;
		$this->props['allowed_mime_types_arr'] = [];
	}

	public function get_editor_options() {
		$this->getLabelField();
		$this->getKeyField();
		$this->getPlaceholderField();
		$this->getDescriptionField();

		$this->getFileFieldSettings();

		$this->getRequiredField();
		$this->getShowInSubmitFormField();
		$this->getShowInAdminField();
		$this->getShowInCompareField();
	}

	/**
	 * Renders a "allowed mime types" setting in the field settings in the listing type editor.
	 *
	 * @since 1.0
	 */
	protected function getFileFieldSettings() {
		$allowed_mimes = (array) get_allowed_mime_types();
		$image_mimes = [];
		$gallery_mimes = [];

		foreach ( $allowed_mimes as $extension => $mime ) {
			if ( strpos( $mime, 'image/' ) === 0 ) {
				$image_mimes[ $extension ] = $mime;
			}

			if ( strpos( $mime, 'image/' ) === 0 || strpos( $mime, 'video/' ) === 0 ) {
				$gallery_mimes[ $extension ] = $mime;
			}
		}
		?>
		<div class="form-group">
			<label>Allowed file types</label>
			<select multiple="multiple" v-model="field.allowed_mime_types_arr" @change="editFieldMimeTypes($event, field)" v-if="['job_logo', 'job_cover'].indexOf(field.slug) >= 0">
				<?php foreach ( $image_mimes as $extension => $mime ): ?>
					<option value="<?php echo "{$extension} => {$mime}" ?>"><?php echo $mime ?></option>
				<?php endforeach ?>
			</select>
			<select multiple="multiple" v-model="field.allowed_mime_types_arr" @change="editFieldMimeTypes($event, field)" v-else-if="field.slug === 'job_gallery'">
				<?php foreach ( $gallery_mimes as $extension => $mime ): ?>
					<option value="<?php echo "{$extension} => {$mime}" ?>"><?php echo $mime ?></option>
				<?php endforeach ?>
			</select>
			<select multiple="multiple" v-model="field.allowed_mime_types_arr" @change="editFieldMimeTypes($event, field)" v-else>
				<?php foreach ( $allowed_mimes as $extension => $mime ): ?>
					<option value="<?php echo "{$extension} => {$mime}" ?>"><?php echo $mime ?></option>
				<?php endforeach ?>
			</select>
			<br><br>
			<label v-if="['job_logo', 'job_cover'].indexOf(field.slug) < 0"><input type="checkbox" v-model="field.multiple" class="form-checkbox"> Allow multiple files?</label>
		</div>
		<div class="form-group" v-if="['job_logo', 'job_cover'].indexOf(field.slug) < 0" v-show="field.multiple">
			<label>Minimum number of uploads required</label>
			<input type="number" v-model="field.min_file_limit" style="width: 100px; margin: 0;">
		</div>
		<div class="form-group" v-if="['job_logo', 'job_cover'].indexOf(field.slug) < 0" v-show="field.multiple">
			<label>Maximum number of uploads allowed</label>
			<input type="number" v-model="field.file_limit" style="width: 100px; margin: 0;">
		</div>
		<div class="form-group">
			<label>Maximum upload file size (KB)</label>
			<input type="number" v-model="field.file_size_limit" style="width: 100px; margin: 0;">
		</div>
		<?php $this->getPackageBasedFileLimitsSettings(); ?>
	<?php }
}
