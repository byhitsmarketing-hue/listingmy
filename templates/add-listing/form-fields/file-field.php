<?php
/**
 * Shows the `file` form field on listing forms.
 *
 * @since 2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// handles ajax uploads
wp_enqueue_script( 'mylisting-ajax-file-upload' );
$is_multiple = ! empty( $field['multiple'] );
$allowed_mime_types = array_keys( ! empty( $field['allowed_mime_types'] ) ? $field['allowed_mime_types'] : get_allowed_mime_types() );
$field_name = isset( $field['name'] ) ? $field['name'] : $key;
$field_name .= $is_multiple ? '[]' : '';
$uploaded_files = ! empty( $field['value'] ) ? (array) $field['value'] : [];
?>

<div class="file-upload-field <?php echo $is_multiple ? 'multiple-uploads' : 'single-upload' ?> form-group-review-gallery ajax-upload">
	<div class="uploaded-files-list review-gallery-images">
		<div class="upload-file review-gallery-add">
			<i class="mi file_upload"></i>
			<div class="content">
				<input
					type="file"
					class="input-text review-gallery-input wp-job-manager-file-upload"
					data-file_types="<?php echo esc_attr( implode( '|', $allowed_mime_types ) ) ?>"
					data-max_count="<?php echo ! empty( $field['file_limit'] ) && absint( $field['file_limit'] ) ? absint( $field['file_limit'] ) : '' ?>"
					data-min_count="<?php echo ! empty( $field['min_file_limit'] ) && absint( $field['min_file_limit'] ) ? absint( $field['min_file_limit'] ) : '' ?>"
					data-package-limits='<?php echo ! empty( $field['package_limits'] ) ? wp_json_encode( $field['package_limits'] ) : '[]'; ?>'
					<?php
						$all_size_configs = [
							'default_limit_kb' => !empty($field['file_size_limit']) ? absint($field['file_size_limit']) : 0,
							'package_rules'    => !empty($field['package_limits']) ? $field['package_limits'] : [],
							'server_max_kb'    => floor(wp_max_upload_size() / 1024),
						];
					?>
					data-all-size-configs='<?php echo wp_json_encode($all_size_configs); ?>'
					<?php if ( $is_multiple ) echo 'multiple'; ?>
					name="<?php echo esc_attr( $field_name ); ?>"
					id="<?php echo esc_attr( $key ); ?>"
					placeholder="<?php echo empty( $field['placeholder'] ) ? '' : esc_attr( $field['placeholder'] ) ?>"
				>
			</div>
		</div>


		<div class="job-manager-uploaded-files">
			<?php foreach ( $uploaded_files as $file ): ?>
				<?php mylisting_locate_template( 'templates/add-listing/form-fields/uploaded-file-html.php', [
					'key' => $key,
					'name' => 'current_'.$field_name,
					'value' => $file,
					'field' => $field
				] ) ?>
			<?php endforeach ?>
		</div>
	</div>

	<small class="description">
		<?php
			// Determine effective size limit (KB) for display purposes
			$display_effective_kb = 0;
			$field_default_kb = !empty($field['file_size_limit']) ? absint($field['file_size_limit']) : 0;
			// Check for package-specific size limit first
			if ( isset( $field['enable_package_limits'] ) && $field['enable_package_limits'] && isset($pckg_id)) {
				foreach ($field['package_limits'] as $package_rule) {
					if (
						isset($package_rule['package'], $package_rule['size_limit_kb'])
						&& absint($package_rule['package']) === absint($pckg_id)
						&& absint($package_rule['size_limit_kb']) > 0
					) {
						$display_effective_kb = absint($package_rule['size_limit_kb']);
						break;
					}
				}
			}

			// If no package-specific limit was found or was invalid, use the field's default limit
			if ($display_effective_kb <= 0 && $field_default_kb > 0) {
				$display_effective_kb = $field_default_kb;
			}

			// Display the determined limit or the server's max upload size as a fallback
			if ($display_effective_kb > 0) {
				printf( _x( 'Maximum file size: %d KB.', 'Add listing form', 'my-listing' ), $display_effective_kb );
			} else {
				printf( _x( 'Maximum file size: %s.', 'Add listing form', 'my-listing' ), size_format( wp_max_upload_size() ) );
			}
		?>
	</small>
	<?php
	c27()->ml_display_field_limits(
		$field,
		_x( 'Up to %d file allowed.', 'Add listing form', 'my-listing' ),
		_x( 'Up to %d files allowed.', 'Add listing form', 'my-listing' )
	);

	if ( ! empty( $field['min_file_limit'] ) && absint( $field['min_file_limit'] ) > 0 ) {
		echo '<small class="description">';
		printf(
			_n(
				'Minimum %s file required.',
				'Minimum %s files required.',
				absint( $field['min_file_limit'] ),
				'my-listing'
			),
			absint( $field['min_file_limit'] )
		);
		echo '</small>';
	}
	?>
</div>
