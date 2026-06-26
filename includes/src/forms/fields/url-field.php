<?php

namespace MyListing\Src\Forms\Fields;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Url_Field extends Base_Field {

	public function get_current_selection_limit() {
		if ( ! $this->is_video_url_field() || empty( $this->props['allow_multiple'] ) ) {
			return null;
		}

		$package_id = $this->_get_package_id_from_context( $this->listing ?? null );
		return $this->_calculate_current_selection_limit( $this->props, $package_id );
	}

	protected function is_video_url_field(): bool {
		return $this->get_type() === 'video-url' || $this->props['slug'] === 'job_video_url';
	}

	public function get_posted_value() {
		$raw_value = $_POST[ $this->key ] ?? '';

		if ( $this->is_video_url_field() && $this->props['allow_multiple'] ) {
			$items = is_array( $raw_value ) ? $raw_value : [ $raw_value ];
			$urls = array_map( function( $item ) {
				if ( is_array( $item ) && isset( $item['url'] ) ) {
					$item = $item['url'];
				}

				$item = is_string( $item ) ? trim( $item ) : '';
				return $item !== '' ? esc_url_raw( $item ) : '';
			}, $items );

			return array_values( array_filter( $urls ) );
		}

		while ( is_array( $raw_value ) ) {
			$raw_value = isset( $raw_value['url'] ) ? $raw_value['url'] : reset( $raw_value );
		}

		return ! empty( $raw_value )
			? esc_url_raw( $raw_value )
			: '';
	}

	public function validate() {
		$value = $this->get_posted_value();
		$values = $this->props['allow_multiple'] ? (array) $value : [ $value ];
		$allowed_video_providers = $this->get_allowed_video_providers();

		if ( $this->is_video_url_field() && ! empty( $this->props['allow_multiple'] ) ) {
			$limit = $this->get_current_selection_limit();
			$this->validate_selection_count(
				$values,
				$limit,
				$this->props['label'],
				'You can only add <b>%1$d</b> video in the <b>%2$s</b> field.',
				'You can only add <b>%1$d</b> videos in the <b>%2$s</b> field.'
			);
		}

		foreach ( $values as $url ) {
			if ( empty( $url ) ) {
				continue;
			}

			if ( preg_match( '@^(https?|ftp)://[^\s/$.?#].[^\s]*$@iS', $url ) !== 1 ) {
				// translators: Placeholder %s is the label for the required field.
				throw new \Exception( sprintf( _x( '%s must be a valid url address.', 'Add listing form', 'my-listing' ), $this->props['label'] ) );
			}

			if ( $this->is_video_url_field() ) {
				if ( empty( $allowed_video_providers ) ) {
					// translators: Placeholder %s is the field label.
					throw new \Exception( sprintf( _x( '%s has no enabled video providers.', 'Add listing form', 'my-listing' ), $this->props['label'] ) );
				}

				$video = \MyListing\Helpers::get_video_embed_details( $url, $allowed_video_providers );
				if ( ! $video ) {
					$provider_labels = self::video_providers();
					$allowed_labels = array_intersect_key( $provider_labels, array_flip( $allowed_video_providers ) );
					$allowed_list = ! empty( $allowed_labels ) ? implode( ', ', $allowed_labels ) : '';

					// translators: Placeholder %1$s is the field label, %2$s is the allowed providers.
					throw new \Exception( sprintf(
						_x( '%1$s must be a supported video URL. Allowed providers: %2$s.', 'Add listing form', 'my-listing' ),
						$this->props['label'],
						$allowed_list
					) );
				}
			}
		}
	}

	public function field_props() {
		$this->props['type'] = 'url';
		$this->props['allow_multiple'] = false;
		$this->props['allowed_video_providers'] = [];
		$this->props['selection_limit'] = '';
		$this->props['enable_package_limits'] = false;
		$this->props['package_limits'] = [];
	}

	public function get_editor_options() {
		$this->getLabelField();
		$this->getKeyField();
		$this->getPlaceholderField();
		$this->getDescriptionField();
		?>
		<div class="form-group" v-if="field.type === 'video-url' || field.slug === 'job_video_url'">
			<label><?php esc_html_e( 'Allow multiple videos', 'my-listing' ); ?></label>
			<label class="form-switch mb0">
				<input type="checkbox" v-model="field.allow_multiple">
				<span class="switch-slider"></span>
			</label>
		</div>
		<div v-if="(field.type === 'video-url' || field.slug === 'job_video_url') && field.allow_multiple">
			<?php $this->get_selection_limit_editor_options(); ?>
		</div>
		<div class="form-group" v-if="field.type === 'video-url' || field.slug === 'job_video_url'">
			<label><?php esc_html_e( 'Allowed video providers', 'my-listing' ); ?></label>
			<?php foreach ( self::video_providers() as $provider_key => $provider_label ): ?>
				<label>
					<input type="checkbox" v-model="field.allowed_video_providers" value="<?php echo esc_attr( $provider_key ) ?>">
					<?php echo esc_html( $provider_label ) ?>
				</label>
			<?php endforeach ?>
		</div>
		<?php
		$this->getRequiredField();
		$this->getShowInSubmitFormField();
		$this->getShowInAdminField();
		$this->getShowInCompareField();
	}

	public function get_value( $all = false ) {
		$value = parent::get_value();

		if ( $all ) {
			return $value;
		}

		if ( ! $this->is_video_url_field() || empty( $this->props['allow_multiple'] ) ) {
			return $value;
		}

		$normalized_value = is_array( $value ) ? $value : ( ! empty( $value ) ? [ $value ] : [] );

		if ( isset( $_REQUEST['action'] ) && in_array( $_REQUEST['action'], [ 'switch', 'duplicate' ], true ) ) {
			return $normalized_value;
		}

		$limit = $this->get_current_selection_limit();
		if ( $limit !== null && $limit > 0 && count( $normalized_value ) > $limit ) {
			return array_slice( $normalized_value, 0, $limit );
		}

		return $normalized_value;
	}

	public function after_custom_props() {
		if ( ! $this->is_video_url_field() ) {
			$this->props['allow_multiple'] = false;
			$this->props['allowed_video_providers'] = [];
			return;
		}

		if ( empty( $this->props['allowed_video_providers'] ) ) {
			$this->props['allowed_video_providers'] = [ 'youtube', 'vimeo', 'dailymotion' ];
		}
	}

	public function string_value( $modifier = null ) {
		$value = $this->get_value();
		if ( is_array( $value ) ) {
			return join( ', ', array_filter( $value ) );
		}

		return $value;
	}

	public function get_allowed_video_providers() {
		if ( ! $this->is_video_url_field() ) {
			return null;
		}

		if ( empty( $this->props['allowed_video_providers'] ) ) {
			return [ 'youtube', 'vimeo', 'dailymotion' ];
		}

		if ( ! is_array( $this->props['allowed_video_providers'] ) ) {
			return [];
		}

		return array_values( array_filter( $this->props['allowed_video_providers'] ) );
	}

	public static function video_providers() {
		$providers = [
			'youtube' => _x( 'YouTube', 'Video provider', 'my-listing' ),
			'vimeo' => _x( 'Vimeo', 'Video provider', 'my-listing' ),
			'dailymotion' => _x( 'Dailymotion', 'Video provider', 'my-listing' ),
			'twitch' => _x( 'Twitch', 'Video provider', 'my-listing' ),
			'streamable' => _x( 'Streamable', 'Video provider', 'my-listing' ),
		];

		return apply_filters( 'mylisting/video/providers', $providers );
	}
}
