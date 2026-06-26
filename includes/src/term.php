<?php

namespace MyListing\Src;

class Term {

	private $data, $taxonomy;


	public static $instances = [];

	/**
	 * Get a new term instance (Multiton pattern). When called the first time, the term
	 * will be fetched from database. Otherwise, it will return the previous instance.
	 *
	 * @since 2.1
	 * @param $term int or \WP_Term
	 */
	public static function get( $term ) {
		if ( is_numeric( $term ) ) {
			$term = get_term( $term );
		}

		if ( ! $term instanceof \WP_Term ) {
			return false;
		}

		if ( ! array_key_exists( $term->term_id, self::$instances ) ) {
			self::$instances[ $term->term_id ] = new self( $term );
		}

		return self::$instances[ $term->term_id ];
	}

	public static function get_by_slug( $slug, $taxonomy ) {
		return self::get( get_term_by( 'slug', $slug, $taxonomy ) );
	}

	public function __construct( \WP_Term $term ) {
		$this->data = $term;
	}

	public function get_id() {
		return $this->data->term_id;
	}

	public function get_name() {
		return wp_specialchars_decode( $this->data->name );
	}

	public function get_description() {
		return $this->data->description;
	}

	public function get_slug() {
		return $this->data->slug;
	}

	public function get_parent_id() {
		return $this->data->parent;
	}

	public function get_full_name( $object = null, $name = null ) {
		if ( ! $object ) {
			$object = $this->data;
		}

		if ( ! $name ) {
			$name = $object->name;
		}

		if ( $object->parent && ( $parent = get_term( $object->parent, $object->taxonomy ) ) ) {
			return $this->get_full_name( $parent, "{$parent->name} &#9656; {$name}" );
		}

		return $name;
	}

	public function get_data( $key = null ) {
		if ( $key ) {
			return isset( $this->data->$key ) ? $this->data->$key : false;
		}

		return $this->data;
	}

	/**
	 * Get the taxonomy object this term belongs to.
	 *
	 * @since 2.2.3
	 */
	public function get_taxonomy() {
		if ( ! is_null( $this->taxonomy ) ) {
			return $this->taxonomy;
		}

		$this->taxonomy = get_taxonomy( $this->data->taxonomy );
		return $this->taxonomy;
	}

	/**
	 * Get the taxonomy slug this term belongs to.
	 *
	 * @since 2.2.3
	 */
	public function get_taxonomy_slug() {
		return $this->data->taxonomy;
	}

	/**
	 * Determine whether the taxonomy this term belongs to
	 * is a listing post type taxonomy.
	 *
	 * @since 2.2.3
	 */
	public function is_listing_taxonomy() {
		$taxonomy = $this->get_taxonomy();
		return in_array( 'job_listing', (array) $taxonomy->object_type, true );
	}

	/**
	 * Get Icon
	 *
	 * @param array $args Options to modify the icon output.
	 * @return string HTML output.
	 */
	public function get_icon( $args = [] ) {
		$args = c27()->merge_options( [
			'color' => true,
			'background' => true,
			], $args );

		$styles = '';

		// filter: drop-shadow(red 0px -200px 0px)
		if ( $args['color'] ) {
			$styles .= 'color: ' . esc_attr( $this->get_text_color() ) . '; ';
		}

		if ( $args['background'] ) {
			$styles .= 'background: ' . esc_attr( $this->get_color() ) . '; ';
		}

		// Image Icon.
		if ( 'image' === $this->get_icon_type() && $image_id = $this->get_icon_image_id() ) {
			ob_start(); ?>
				<div class="term-icon image-icon" style="<?php echo esc_attr( $styles ) ?>">
					<?php echo wp_get_attachment_image( $image_id, 'full' ); ?>
				</div>
			<?php return ob_get_clean();
		}

		// Font Icon.
		ob_start(); ?>
			<i class="<?php echo esc_attr( $this->get_icon_font() ) ?>" style="<?php echo esc_attr( $styles ) ?>"></i>
		<?php return ob_get_clean();
	}

	/**
	 * Get Icon Font
	 */
	public function get_icon_font() {
		if ( $icon = $this->get_recursive_field( 'icon' ) ) {
			return $icon;
		}

		if ( $this->data->taxonomy == 'region' ) {
			$default_icon = 'icon-location-pin-4';
		} else {
			$default_icon = 'mi bookmark_border';
		}

		return apply_filters( 'case27\classes\term\default_icon', $default_icon, $this );
	}

	/**
	 * Get Icon Image
	 */
	public function get_icon_image() {
		if ( $icon = $this->get_recursive_field( 'icon_image' ) ) {
			return $icon;
		}

		return apply_filters( 'case27\classes\term\default_icon_image', '' );
	}

	/**
	 * Get Icon Image URL.
	 */
	public function get_icon_image_url() {
		$image = $this->get_icon_image();
		$image_url = $image && is_array( $image ) && isset( $image['sizes']['large'] ) ? $image['sizes']['large'] : '';
		return esc_url( $image_url );
	}

	/**
	 * Get Icon Image URL.
	 */
	public function get_icon_image_id() {
		$image = $this->get_icon_image();
		$image_id = '';
		if ( $image && isset( $image['id'] ) ) {
			$image_id = $image['id'];
		}

		return $image_id;
	}

	/**
	 * Get Icon Type
	 */
	public function get_icon_type() {
		$type = $this->get_recursive_field( 'icon_type' );
		return 'image' === $type ? 'image' : 'icon';
	}

	public function get_image() {
		if ( $image = $this->get_recursive_field( 'image' ) ) {
			return $image;
		}

		return apply_filters( 'case27\classes\term\default_image', '', $this );
	}

	public function get_color() {
		if ( $color = $this->get_recursive_field( 'color' ) ) {
			return $color;
		}

		return apply_filters( 'case27\classes\term\default_color', c27()->get_setting( 'general_brand_color', '#f24286' ), $this );
	}

	public function get_text_color() {
		if ( $text_color = $this->get_recursive_field( 'text_color' ) ) {
			return $text_color;
		}

		return apply_filters( 'case27\classes\term\default_text_color', '#fff', $this );
	}

	public function get_link() {
		return get_term_link( $this->data );
	}

	/**
	 * Retrieve the number of listings this term belongs to, optionally
	 * filtered to a specific listing type.
	 *
	 * @since 1.0
	 */
	public function get_count( $listing_type = null ) {
		$counts = (array) json_decode( get_term_meta( $this->data->term_id, 'listings_full_count', true ), ARRAY_A );
		if ( ! empty( $listing_type ) ) {
			$count = isset( $counts[ $listing_type ] ) ? absint( $counts[ $listing_type ] ) : 0;
		} else {
			$count = array_sum( $counts );
		}

		if ( empty( $counts ) ) {
			$count = $this->data->count;
		}

		if ( $count ) {
			return sprintf(
				_n( '%s listing', '%s listings', $count, 'my-listing' ),
				number_format_i18n( $count )
			);
		}

		return __( 'No listings', 'my-listing' );
	}

	public function get_count_by_type( $listing_type = null ) {
		$counts = (array) json_decode( get_term_meta( $this->data->term_id, 'listings_full_count', true ), ARRAY_A );
		
		if ( ! empty( $listing_type ) ) {
			$count = isset( $counts[ $listing_type ] ) ? absint( $counts[ $listing_type ] ) : 0;
			if ( $count ) {
				return sprintf(
					_n( '%s listing', '%s listings', $count, 'my-listing' ),
					number_format_i18n( $count )
				);
			}
		} else {
			$formatted_counts = array();
			foreach ( $counts as $type => $count ) {
				if ( $count ) {
					$formatted_counts[ $type ] = sprintf(
						_n( '%s listing', '%s listings', $count, 'my-listing' ),
						number_format_i18n( $count )
					);
				}
			}
			return $formatted_counts;
		}
	}


	/*
	 * Check if field value exists for this term.
	 * If not, recursively check the term parent, until a value is found.
	 * Otherwise, return false.
	 */
	public function get_recursive_field( $field_name, $term = null ) {
		if ( ! $term ) {
			$term = $this->data;
		}

		if ( ! $term instanceof \WP_Term ) {
			return false;
		}

		$field = $this->get_term_field_value( $term, $field_name );
		if ( $this->field_value_exists( $field ) ) {
			return $field;
		}

		if ( $term->parent && ( $parent = get_term( $term->parent, $term->taxonomy ) ) ) {
			return $this->get_recursive_field( $field_name, $parent );
		}

		return false;
	}

	protected function get_term_field_value( \WP_Term $term, $field_name ) {
		$value = get_term_meta( $term->term_id, $field_name, true );

		if ( in_array( $field_name, [ 'icon_image', 'image' ], true ) ) {
			if ( is_array( $value ) && isset( $value['sizes'] ) ) {
				return $value;
			}

			if ( is_numeric( $value ) && (int) $value > 0 ) {
				if ( $formatted = $this->prepare_attachment_value( (int) $value ) ) {
					return $formatted;
				}
			}

			if ( is_string( $value ) && $value !== '' ) {
				$attachment_id = function_exists( 'attachment_url_to_postid' ) ? attachment_url_to_postid( $value ) : 0;
				if ( $attachment_id && ( $formatted = $this->prepare_attachment_value( $attachment_id ) ) ) {
					return $formatted;
				}

				$url = esc_url_raw( $value );
				if ( $url ) {
					return [
						'id'    => 0,
						'ID'    => 0,
						'url'   => $url,
						'sizes' => [
							'full'  => $url,
							'large' => $url,
						],
					];
				}
			}
		} elseif ( $value !== '' && $value !== null ) {
			return $value;
		}

		if ( function_exists( 'get_field' ) ) {
			$acf_value = get_field( $field_name, $term->taxonomy . '_' . $term->term_id );
			if ( $acf_value ) {
				return $acf_value;
			}
		}

		return null;
	}

	protected function prepare_attachment_value( $attachment_id ) {
		if ( ! $attachment_id ) {
			return null;
		}

		$attachment = wp_prepare_attachment_for_js( $attachment_id );
		if ( ! $attachment || empty( $attachment['url'] ) ) {
			return null;
		}

		$sizes = [];
		if ( ! empty( $attachment['sizes'] ) && is_array( $attachment['sizes'] ) ) {
			foreach ( $attachment['sizes'] as $size => $info ) {
				if ( is_array( $info ) && isset( $info['url'] ) ) {
					$sizes[ $size ] = $info['url'];
				}
			}
		}

		if ( ! isset( $sizes['large'] ) ) {
			$sizes['large'] = $attachment['url'];
		}

		return [
			'id'          => $attachment_id,
			'ID'          => $attachment_id,
			'url'         => $attachment['url'],
			'link'        => isset( $attachment['link'] ) ? $attachment['link'] : '',
			'alt'         => isset( $attachment['alt'] ) ? $attachment['alt'] : '',
			'title'       => isset( $attachment['title'] ) ? $attachment['title'] : '',
			'caption'     => isset( $attachment['caption'] ) ? $attachment['caption'] : '',
			'description' => isset( $attachment['description'] ) ? $attachment['description'] : '',
			'mime_type'   => isset( $attachment['mime'] ) ? $attachment['mime'] : '',
			'type'        => isset( $attachment['type'] ) ? $attachment['type'] : '',
			'subtype'     => isset( $attachment['subtype'] ) ? $attachment['subtype'] : '',
			'width'       => isset( $attachment['width'] ) ? (int) $attachment['width'] : '',
			'height'      => isset( $attachment['height'] ) ? (int) $attachment['height'] : '',
			'sizes'       => $sizes,
		];
	}

	protected function field_value_exists( $value ) {
		if ( is_array( $value ) ) {
			return ! empty( $value );
		}

		return $value !== null && $value !== '';
	}
	/**
	 * Get the list of listing types this term belongs to.
	 *
	 * @since  2.1
	 * @return array of listing type ids
	 */
	public function get_listing_types() {
		return array_filter( array_map( 'absint',
			(array) get_term_meta( $this->get_id(), 'listing_type', true )
		) );
	}

	/**
	 * Count and cache the number of listings this term belongs to,
	 * grouped by listing type.
	 *
	 * @since 2.2.3
	 */
	public function update_counts() {
		\MyListing\update_term_counts( $this->get_id(), $this->get_taxonomy_slug() );

		if ( $this->get_parent_id() && ( $parent = \MyListing\Src\Term::get( $this->get_parent_id() ) ) ) {
			$parent->update_counts();
		}
	}
}