<?php

namespace MyListing\Utils;
use MyListing\Src\Theme_Options\Settings_Repository;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Helpers {
	use \MyListing\Src\Traits\Instantiatable;

	private $media_cache = [];

    // Get theme template path, with the given $path appended to it.
	public function template_path( $path ) {
		return get_template_directory() . "/$path";
	}

    // Get theme template uri, with the given $uri appended to it.
	public function template_uri( $uri = '' ) {
		return get_template_directory_uri() . "/$uri";
	}

    // URI to asset folder.
	public function asset( $asset ) {
		return $this->template_uri( "assets/$asset" );
	}

    // URI to images folder.
	public function image( $image ) {
		if ( file_exists( trailingslashit( get_stylesheet_directory() ) . 'assets/images/' . $image ) ) {
			return trailingslashit( get_stylesheet_directory_uri() ) . 'assets/images/' . $image;
		}

		return $this->asset( 'images/'.$image );
	}

    // Retrieve the featured_image url for the given post, on the given size.
	public function featured_image( $postID, $size = 'large' ) {
		$image = wp_get_attachment_image_src( get_post_thumbnail_id( $postID ), $size );
		return $image ? array_shift( $image ) : false;
	}

    // Get post terms from the given taxonomy.
	public function get_terms( $postID, $taxonomy = 'category' ) {
		$raw_terms = (array) wp_get_post_terms( $postID, $taxonomy );

		$terms = [];
		if ( ! empty( $raw_terms['errors'] ) ) {
			return $terms;
		}

		foreach ( $raw_terms as $raw_term ) {
			$terms[] = [
				'name' => $raw_term->name,
				'link' => get_term_link( $raw_term )
			];
		}

		return $terms;
	}

    // Print the post excerpt, limiting it to a given number of characters.
	public function the_excerpt( $charlength, $after = "&hellip;" ) {
		$excerpt = get_the_excerpt();
		$charlength++;

		if ( mb_strlen( $excerpt ) > $charlength ) {
			$subex = mb_substr( $excerpt, 0, $charlength - 5 );
			$exwords = explode( ' ', $subex );
			$excut = - ( mb_strlen( $exwords[ count( $exwords ) - 1 ] ) ) - 1;
			if ( $excut < 0 ) {
				echo mb_substr( $subex, 0, $excut );
			} else {
				echo $subex;
			}
			echo $after;
		} else {
			echo $excerpt;
		}
	}

	public function the_text_excerpt( $text, $charlength, $after = "&hellip;", $echo = true ) {
		$charlength++;
		$output = '';

		if ( mb_strlen( $text ) > $charlength ) {
			$subex = mb_substr( $text, 0, $charlength - 5 );
			$exwords = explode( ' ', $subex );
			$excut = - ( mb_strlen( $exwords[ count( $exwords ) - 1 ] ) ) - 1;
			if ( $excut < 0 ) {
				$output .= mb_substr( $subex, 0, $excut );
			} else {
				$output .= $subex;
			}
			$output .= $after;
		} else {
			$output .= $text;
		}

		if ( $echo ) {
			echo $output;
			return;
		}

		return $output;
	}

	public function get_reading_time( $post = null ) {
		$post = get_post( $post );

		if ( ! ( $post instanceof \WP_Post ) ) {
			return '';
		}

		if ( ! $this->get_setting( 'blog_show_reading_time', true ) ) {
			return '';
		}

		$words_per_minute_setting = absint( $this->get_setting( 'blog_reading_time_wpm', 200 ) );
		$words_per_minute_setting = $words_per_minute_setting > 0 ? $words_per_minute_setting : 200;

		$content = wp_strip_all_tags( strip_shortcodes( $post->post_content ) );
		$content = trim( preg_replace( '/\s+/', ' ', $content ) );

		if ( $content === '' ) {
			return '';
		}

		$words_per_minute = absint( apply_filters( 'mylisting/blog/words-per-minute', $words_per_minute_setting, $post ) );
		$words_per_minute = $words_per_minute > 0 ? $words_per_minute : 200;

		$words = preg_split( '/\s+/u', $content );
		$word_count = is_array( $words ) ? count( array_filter( $words ) ) : 0;

		if ( $word_count < 1 ) {
			return '';
		}

		$minutes = max( 1, (int) ceil( $word_count / $words_per_minute ) );
		$label = sprintf( _n( '%s min read', '%s min read', $minutes, 'my-listing' ), number_format_i18n( $minutes ) );

		return apply_filters( 'mylisting/blog/reading-time', $label, $minutes, $post );
	}

	public function get_post_relations(int $postID) {
		global $wpdb;
		$ids = $wpdb->get_results(
			"SELECT child_listing_id FROM {$wpdb->prefix}mylisting_relations WHERE parent_listing_id = $postID
			UNION
			SELECT parent_listing_id FROM {$wpdb->prefix}mylisting_relations WHERE child_listing_id = $postID",
			ARRAY_A
		);

		return array_column($ids, 'child_listing_id');
	}


	public function merge_options( $defaults, $options ) {
		return array_replace_recursive( $defaults, $options );
	}

	public function get_partial( $template, $data = [] ) {
		if (!locate_template("partials/{$template}.php")) return;

		require locate_template("partials/{$template}.php");
	}

	public function get_section( $template, $data = [] ) {
		if (!locate_template("sections/{$template}.php")) return;

		require locate_template("sections/{$template}.php");
	}

	public function get_users_dropdown_array($args = [], $key = 'ID', $value = 'display_name') {
		$options = [];
		$users = get_users($args);

		if (is_wp_error($users)) {
			return [];
		}

		foreach ((array) $users as $user) {
			$options[$user->{$key}] = $user->{$value};
		}

		return $options;
	}

	public function get_terms_dropdown_array($args = [], $key = 'term_id', $value = 'name') {
		$options = [];
		$terms = get_terms($args);

		if (is_wp_error($terms)) {
			return [];
		}

		foreach ((array) $terms as $term) {
			$options[$term->{$key}] = $term->{$value};
		}

		return $options;
	}

	public function get_icon_markup( $icon_string ) {
		if (strpos($icon_string, '://') !== false) {
			$icon_arr = explode('://', $icon_string);

			return "<i class=\"{$icon_arr[0]}\">{$icon_arr[1]}</i>";
		}

		return "<i class=\"{$icon_string}\"></i>";
	}

	public function get_setting( $setting, $default = '' ) {
		$repository = Settings_Repository::instance();

		if ( $repository->has_field( $setting ) ) {
			$value = $repository->get( $setting, null );

			if ( $value !== null ) {
				return $value;
			}

			return $default;
		}

		if ( function_exists( 'get_field' ) ) {
			$value = get_field( $setting, 'option' );
			if ( $value !== null ) {
				return $value;
			}
		}

		$legacy_value = get_option( 'options_'.$setting, null );
		if ( null !== $legacy_value ) {
			return $legacy_value;
		}

		return $default;
	}

	public function get_site_logo_url() {
		if ( $logo_obj = c27()->get_setting( 'general_site_logo' ) ) {
			// Prefer a generated 'large' size when available; fall back to original URL.
			if ( is_array( $logo_obj ) ) {
				if ( isset( $logo_obj['sizes'] ) && isset( $logo_obj['sizes']['large'] ) && ! empty( $logo_obj['sizes']['large'] ) ) {
					return $logo_obj['sizes']['large'];
				}

				if ( ! empty( $logo_obj['url'] ) ) {
					return $logo_obj['url'];
				}
			}
		}

		return '';
	}

	public function get_site_logo($setting_key = 'general_site_logo', $size = 'medium', $classes = '', $attr = [] ) {
		$logo_obj = c27()->get_setting($setting_key);

		if ( ! empty( $logo_obj ) && isset( $logo_obj['ID'] ) ) {
			return wp_get_attachment_image(
				$logo_obj['ID'],
				$size,
				false,
				[
					'class' => $classes,
					'alt'   => esc_attr( $attr['alt'] ?? c27()->get_site_logo_alt_text() ),
					'aria-hidden' => 'true',
					'decoding' => 'async',
				]
			);
		}

		return '';
	}

	public function get_site_logo_alt_text() {
		if ($logo_obj = c27()->get_setting('general_site_logo')) {
			return $logo_obj['alt'];
		}

		return '';
	}

	/**
	 * Label translation helper for WPML and Polylang.
	 * Mirrors apply_filters usage across templates.
	 *
	 * @param mixed $text   Original label/text
	 * @param string $scope  Logical scope, e.g. 'single.cover-action'
	 * @param array  $args   Extra args passed to filter (e.g. ['listing' => $listing, 'action' => $action])
	 * @return string
	 */
	public function ml_t( $text, string $scope, array $args = [] ): string {
		// Ensure $text is always a string to prevent type errors.
		if ( ! is_string( $text ) || $text === '' ) {
			return '';
		}

		// Prevent callers from overriding the provided scope while keeping other context args intact.
		if ( isset( $args['scope'] ) ) {
			unset( $args['scope'] );
		}

		return apply_filters(
			'mylisting/labels/translate',
			$text,
			['scope' => $scope] + $args
		);
	}

	public function upload_file( $file, $allowed_mime_types = [] ) {
		include_once( ABSPATH . 'wp-admin/includes/file.php' );
		include_once( ABSPATH . 'wp-admin/includes/media.php' );
		include_once( ABSPATH . 'wp-admin/includes/image.php' );

		$uploaded_file = new \stdClass();

		if ( ! in_array( $file['type'], $allowed_mime_types ) ) {
			return new \WP_Error( 'upload', sprintf( __( 'Uploaded files need to be one of the following file types: %s', 'my-listing' ), implode( ', ', array_keys( $allowed_mime_types ) ) ) );
		}

		$upload = wp_handle_upload($file, ['test_form' => false]);

		if ( ! empty( $upload['error'] ) ) {
			return new \WP_Error( 'upload', $upload['error'] );
		}

		$wp_filetype = wp_check_filetype($upload['file']);
		$attach_id = wp_insert_attachment([
			'post_mime_type' => $wp_filetype['type'],
			'post_title' => sanitize_file_name($upload['file']),
			'post_content' => '',
			'post_status' => 'inherit'
			], $upload['file']);

		$attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
		wp_update_attachment_metadata( $attach_id, $attach_data );

		return $attach_id;
	}

	public function file_size_validation( $allowed_size, $file_size, $label, $name ) {
		if ( ! empty( $allowed_size ) && is_numeric( $allowed_size ) && absint( $allowed_size ) > 0 && is_numeric( $file_size ) && absint( $allowed_size ) < $file_size ) {
			throw new \Exception( sprintf(
				_x( 'File %1$s in field "%3$s" exceeds the size limit of %2$d KB.', 'File size validation error', 'my-listing' ),
				sprintf('"%s"', esc_html($name)),
				absint( $allowed_size ),
				esc_html( $label )
			) );
		}
	}

	public function get_gradients() {
		return [
    		'gradient1' => ['from' => '#7dd2c7', 'to' => '#f04786'],
			'gradient2' => ['from' => '#71d68b', 'to' => '#00af9c'],
			'gradient3' => ['from' => '#FF5F6D', 'to' => '#FFC371'],
			'gradient4' => ['from' => '#EECDA3', 'to' => '#EF629F'],
			'gradient5' => ['from' => '#114357', 'to' => '#F29492'],
			'gradient6' => ['from' => '#52EDC7', 'to' => '#F29492'],
			'gradient7' => ['from' => '#C644FC', 'to' => '#5856D6'],
    	];
	}

	public function get_map_skins() {
		return [
			'skin1' => [
				'feature:water|element:geometry|color:0xe9e9e9|lightness:17',
				'feature:landscape|element:geometry|color:0xf5f5f5|lightness:20',
				'feature:road.highway|element:geometry.fill|color:0xffffff|lightness:17',
				'feature:road.highway|element:geometry.stroke|color:0xffffff|lightness:29|weight:0.2',
				'feature:road.arterial|element:geometry|color:0xffffff|lightness:18',
				'feature:road.local|element:geometry|color:0xffffff|lightness:16',
				'feature:poi|element:geometry|color:0xf5f5f5|lightness:21',
				'feature:poi.park|element:geometry|color:0xdedede|lightness:21',
				'element:labels.text.stroke|visibility:on|color:0xffffff|lightness:16',
				'element:labels.text.fill|color:0x333333|lightness:40',
				'element:labels.icon|visibility:off',
				'feature:transit|element:geometry|color:0xf2f2f2|lightness:19',
				'feature:administrative|element:geometry.fill|color:0xfefefe|lightness:20',
				'feature:administrative|element:geometry.stroke|color:0xfefefe|lightness:17|weight:1.2',
			],
			'skin2' => [
				'feature:all|element:labels.text.fill|saturation:0|color:0xf3f3f3|lightness:-40|gamma:1',
				'feature:all|element:labels.text.stroke|visibility:on|color:0x000000|lightness:12',
				'feature:all|element:labels.icon|visibility:off',
				'feature:administrative|element:geometry.fill|color:0x2c2d37|lightness:4',
				'feature:administrative|element:geometry.stroke|color:0x2c2d37|lightness:17|weight:1.2',
				'feature:landscape|element:geometry|color:0x2c2d37|lightness:25|gamma:0.60',
				'feature:poi|element:geometry|color:0x2c2d37|lightness:26|gamma:0.49',
				'feature:road.highway|element:geometry.fill|color:0x2c2d37|lightness:17|gamma:0.60',
				'feature:road.highway|element:geometry.stroke|color:0x2c2d37|lightness:29|weight:0.2|gamma:0.60',
				'feature:road.arterial|element:geometry|color:0x2c2d37|lightness:18|gamma:0.60',
				'feature:road.local|element:geometry|color:0x2c2d37|lightness:16|gamma:0.60',
				'feature:transit|element:geometry|color:0x2c2d37|lightness:29|gamma:0.60',
				'feature:water|element:geometry|color:0x3c3d47|lightness:16|gamma:0.50',
			],
			// use this for standard map style
			'skin12' => [],
			// Add more skins as needed
		];
	}

	public function new_admin_page( $type = 'menu', $args = [] ) {
		if ( ! in_array( $type, [ 'menu', 'submenu', 'theme' ] ) ) return;

		call_user_func_array('add_' . $type . '_page', $args);
	}

	public function hexToRgb( $hex, $alpha = 1 ) {
		$rgb = [];

		if ( strpos( $hex, 'rgb' ) !== false ) {
			$hex = str_replace( ['rgba', 'rgb', '(', ')', ' '], '', $hex );
			$hexArr = explode( ',', $hex );

			$rgb['r'] = isset( $hexArr[0] ) ? absint( $hexArr[0] ) : 0;
			$rgb['g'] = isset( $hexArr[1] ) ? absint( $hexArr[1] ) : 0;
			$rgb['b'] = isset( $hexArr[2] ) ? absint( $hexArr[2] ) : 0;
			$rgb['a'] = isset( $hexArr[3] ) ? (float) $hexArr[3] : 1;

			return $rgb;
		}

		$hex      = str_replace( '#', '', $hex );
		$length   = strlen( $hex );
		$rgb['r'] = hexdec( $length == 6 ? substr( $hex, 0, 2 ) : ( $length == 3 ? str_repeat( substr( $hex, 0, 1 ), 2 ) : 0 ) );
		$rgb['g'] = hexdec( $length == 6 ? substr( $hex, 2, 2 ) : ( $length == 3 ? str_repeat( substr( $hex, 1, 1 ), 2 ) : 0 ) );
		$rgb['b'] = hexdec( $length == 6 ? substr( $hex, 4, 2 ) : ( $length == 3 ? str_repeat( substr( $hex, 2, 1 ), 2 ) : 0 ) );
		$rgb['a'] = $alpha;

		return $rgb;
	}

	public static function get_video_embed_rules( $context = [] ) {
		$parent_param = isset( $context['parent_param'] ) ? $context['parent_param'] : '';

		$rules = [
			[
				'service' => 'youtube',
				'pattern' => '%(youtu.*be.*)\/(watch\?v=|embed\/|v\/|shorts|)(.*?((?=[&#?])|$))%i',
				'callback' => function( $matches ) {
					if ( empty( $matches[3] ) ) {
						return null;
					}

					$video_id = trim( $matches[3] );
					if ( $video_id === '' ) {
						return null;
					}

					return [
						'url' => "https://www.youtube.com/embed/{$video_id}?origin=*",
						'type' => 'external',
						'service' => 'youtube',
						'video_id' => $video_id,
					];
				},
			],
			[
				'service' => 'vimeo',
				'pattern' => "/(https?:\\/\\/)?(www\\.)?(player\\.)?vimeo\\.com\\/([a-z]*\\/)*(?<id>[0-9]{6,11})[?]?.*/",
				'callback' => function( $matches ) {
					if ( empty( $matches['id'] ) ) {
						return null;
					}

					$video_id = trim( $matches['id'] );
					if ( $video_id === '' ) {
						return null;
					}

					return [
						'url' => "https://player.vimeo.com/video/{$video_id}?api=1&player_id={$video_id}",
						'type' => 'external',
						'service' => 'vimeo',
						'video_id' => $video_id,
					];
				},
			],
			[
				'service' => 'dailymotion',
				'pattern' => "/^.+dailymotion.com\/(video|hub)\/(?<id>[^_]+)[^#]*(#video=(?<id2>[^_&]+))?/",
				'callback' => function( $matches ) {
					if ( empty( $matches['id'] ) ) {
						return null;
					}

					$video_id = trim( $matches['id'] );
					if ( $video_id === '' ) {
						return null;
					}

					return [
						'url' => "https://www.dailymotion.com/embed/video/{$video_id}",
						'type' => 'external',
						'service' => 'dailymotion',
						'video_id' => $video_id,
					];
				},
			],
			[
				'service' => 'twitch',
				'pattern' => '/twitch\.tv\/videos\/(?<id>\d+)/i',
				'callback' => function( $matches ) use ( $parent_param ) {
					if ( empty( $matches['id'] ) ) {
						return null;
					}

					$video_id = trim( $matches['id'] );
					if ( $video_id === '' ) {
						return null;
					}

					return [
						'url' => "https://player.twitch.tv/?video={$video_id}{$parent_param}",
						'type' => 'external',
						'service' => 'twitch',
						'video_id' => $video_id,
					];
				},
			],
			[
				'service' => 'twitch',
				'pattern' => '/(clips\.twitch\.tv\/|twitch\.tv\/(?:[^\/]+\/clip\/|clip\/))(?<id>[A-Za-z0-9_-]+)/i',
				'callback' => function( $matches ) use ( $parent_param ) {
					if ( empty( $matches['id'] ) ) {
						return null;
					}

					$video_id = trim( $matches['id'] );
					if ( $video_id === '' ) {
						return null;
					}

					return [
						'url' => "https://clips.twitch.tv/embed?clip={$video_id}{$parent_param}",
						'type' => 'external',
						'service' => 'twitch',
						'video_id' => $video_id,
					];
				},
			],
			[
				'service' => 'twitch',
				'pattern' => '/twitch\.tv\/(?<channel>[A-Za-z0-9_]+)(?:\/)?$/i',
				'callback' => function( $matches ) use ( $parent_param ) {
					if ( empty( $matches['channel'] ) ) {
						return null;
					}

					$video_id = trim( $matches['channel'] );
					if ( $video_id === '' ) {
						return null;
					}

					return [
						'url' => "https://player.twitch.tv/?channel={$video_id}{$parent_param}",
						'type' => 'external',
						'service' => 'twitch',
						'video_id' => $video_id,
					];
				},
			],
			[
				'service' => 'streamable',
				'pattern' => '/streamable\.com\/(?:s\/|e\/)?(?<id>[A-Za-z0-9]+)/i',
				'callback' => function( $matches ) {
					if ( empty( $matches['id'] ) ) {
						return null;
					}

					$video_id = trim( $matches['id'] );
					if ( $video_id === '' ) {
						return null;
					}

					return [
						'url' => "https://streamable.com/e/{$video_id}",
						'type' => 'external',
						'service' => 'streamable',
						'video_id' => $video_id,
					];
				},
			],
		];

		return apply_filters( 'mylisting/video/embed_rules', $rules, $context );
	}

	public static function get_video_embed_details( $url, $allowed_providers = [] ) {
		$filter_enabled = is_array( $allowed_providers );
		$allowed_providers = $filter_enabled ? array_values( array_filter( $allowed_providers ) ) : [];

		$site_host = parse_url( home_url(), PHP_URL_HOST );
		if ( empty( $site_host ) && ! empty( $_SERVER['HTTP_HOST'] ) ) {
			$site_host = $_SERVER['HTTP_HOST'];
		}

		$parent_param = $site_host ? '&parent=' . rawurlencode( $site_host ) : '';
		$context = [
			'url' => $url,
			'site_host' => $site_host,
			'parent_param' => $parent_param,
		];

		$rules = self::get_video_embed_rules( $context );
		foreach ( (array) $rules as $rule ) {
			if ( empty( $rule['pattern'] ) || ! is_string( $rule['pattern'] ) ) {
				continue;
			}

			$matches = [];
			if ( preg_match( $rule['pattern'], $url, $matches ) !== 1 ) {
				continue;
			}

			$details = null;
			if ( isset( $rule['callback'] ) && is_callable( $rule['callback'] ) ) {
				$details = call_user_func( $rule['callback'], $matches, $context, $rule );
			} elseif ( ! empty( $rule['embed_url'] ) && ! empty( $rule['service'] ) ) {
				$id_key = ! empty( $rule['id_key'] ) ? $rule['id_key'] : 'id';
				if ( ! empty( $matches[ $id_key ] ) ) {
					$video_id = trim( $matches[ $id_key ] );
					if ( $video_id !== '' ) {
						$details = [
							'url' => sprintf( $rule['embed_url'], $video_id ),
							'type' => isset( $rule['type'] ) ? $rule['type'] : 'external',
							'service' => $rule['service'],
							'video_id' => $video_id,
						];
					}
				}
			}

			if ( empty( $details ) || ! is_array( $details ) || empty( $details['url'] ) ) {
				continue;
			}

			if ( empty( $details['service'] ) && ! empty( $rule['service'] ) ) {
				$details['service'] = $rule['service'];
			}

			if ( $filter_enabled && ( empty( $details['service'] ) || ! in_array( $details['service'], $allowed_providers, true ) ) ) {
				return false;
			}

			return $details;
		}

		return false;
	}

	/**
	 * Safely output encoded data as html attribute.
	 *
	 * @since 1.6.2
	 */
	public function encode_attr( $string ) {
		return htmlspecialchars( json_encode( $string ), ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * Escape WordPress shortcode brackets.
	 * Used mainly to sanitize user input.
	 *
	 * @since 1.5.1
	 * @param string $value String to escape.
	 * @return string
	 */
	public function esc_shortcodes( $value ) {
		return str_replace( [ "[" , "]" ] , [ "&#91;" , "&#93;" ] , $value );
	}

	public function get_timezone() {
		return new \DateTimeZone( $this->get_timezone_string() );
	}

	public function get_timezone_string() {
		$timezone_string = get_option( 'timezone_string' );

		if ( $timezone_string ) {
			return $timezone_string;
		}

		$offset  = (float) get_option( 'gmt_offset' );
		$hours   = (int) $offset;
		$minutes = ( $offset - $hours );

		$sign      = ( $offset < 0 ) ? '-' : '+';
		$abs_hour  = abs( $hours );
		$abs_mins  = abs( $minutes * 60 );
		$tz_offset = sprintf( '%s%02d:%02d', $sign, $abs_hour, $abs_mins );

		return $tz_offset;
	}

	/**
	 * Format date in cover details
	 *
	 */
	public function format_date_range($start, $end, $date_format, $time_format = null) {
		$start_timestamp = strtotime($start);
		$end_timestamp = strtotime($end);

		if ($time_format) {
			// Format for datetime
			if (date('Y-m-d', $start_timestamp) === date('Y-m-d', $end_timestamp)) {
				$formatted_date = date_i18n($date_format, $start_timestamp);
				$formatted_start_time = date_i18n($time_format, $start_timestamp);
				$formatted_end_time = date_i18n($time_format, $end_timestamp);
				return "$formatted_date $formatted_start_time - $formatted_end_time";
			} else {
				$formatted_start = date_i18n("$date_format $time_format", $start_timestamp);
				$formatted_end = date_i18n("$date_format $time_format", $end_timestamp);
				return "$formatted_start - $formatted_end";
			}
		} else {
			// Format for date
			$formatted_start = date_i18n($date_format, $start_timestamp);
			$formatted_end = date_i18n($date_format, $end_timestamp);
			return "$formatted_start - $formatted_end";
		}
	}


	/**
	 * Retrieve object class name.
	 *
	 * @since 1.7.2
	 * @param bool $namespaced Whether to include the namespace or only the basename.
	 */
	public function get_class_name( $object, $namespaced = false ) {
		if ( $namespaced ) {
			return get_class( $object );
		}

		$parts = explode( '\\', get_class( $object ) );
		return end( $parts );
	}

	/**
	 * Receive a date object with current UTC time.
	 *
	 * @since 2.0
	 */
	public function utc() {
		return new \DateTime( 'now', new \DateTimeZone( 'UTC' ) );
	}

	public function get_flag( $country_code ) {
		if ( ! wp_style_is( 'flag-sprites-css', 'enqueued' ) ) {
			wp_enqueue_style( 'flag-sprites-css', $this->asset( 'vendor/flag-sprites/flags.min.css' ), [], \MyListing\get_assets_version() );
		}

		if ( ! ( $country = \MyListing\get_country_name_by_code( strtoupper( $country_code ) ) ) ) {
			$country = '';
			$country_code = 'unknown';
		}

		return sprintf(
			'<div class="flag-wrapper">' .
				'<img src="%s" class="flag flag-%s" alt="%s">' .
			'</div>',
			esc_url( $this->asset( 'vendor/flag-sprites/blank.gif' ) ),
			esc_attr( strtolower( $country_code ) ),
			esc_attr( $country )
		);
	}

	/**
	 * Determine the current listing type in submit listing form.
	 *
	 * @since 2.0
	 * @todo move to wp-job-manager integration dir as a util.
	 */
	public function get_submission_listing_type() {
		if ( ! empty( $_GET['listing_type'] ) ) {
			return $_GET['listing_type'];
		} elseif ( ! empty( $_GET['type'] ) ) {
			return $_GET['type'];
		} elseif ( ! empty( $_REQUEST['listing_type'] ) ) {
			return $_REQUEST['listing_type'];
		}

		return '';
	}

	public function get_package_id_for_validation( $listing_pkg ) {
		if ( ! empty( $listing_pkg ) ) {
			$post = get_post( $listing_pkg );
			if ( ! $post ) {
				return false;
			}

			if ( 'product' === $post->post_type ) {
				return $post->ID;
			}

			return $this->get_package_product_id( $post->ID );
		}

		return false;
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

	public function get_registered_scripts($handle) {
		global $wp_scripts;
		if (!isset($wp_scripts->registered[$handle])) return false;
		return $wp_scripts->registered[$handle]->src;
	}

	public function class2file( $classname, $with_namespace = false ) {
		$parts = explode( '\\', $classname );
		return strtolower( str_replace( '_', '-', $with_namespace ? $classname : array_pop( $parts ) ) );
	}

	public function file2class( $filename ) {
		return str_replace( ' ', '_', ucwords( str_replace( '-', ' ', $filename ) ) );
	}

	/**
	 * Modified version of get_job_listing_pagination function in WP Job Manager.
	 *
	 * @since 2.1
	 */
	public function get_listing_pagination( $max_num_pages, $current_page = 1, $link_url = '#' ) {
		if ( $max_num_pages <= 1 ) {
			return;
		}

		$end_size = 3; $mid_size = 3;
		$start_pages = range( 1, $end_size );
		$end_pages = range( $max_num_pages - $end_size + 1, $max_num_pages );
		$mid_pages = range( $current_page - $mid_size, $current_page + $mid_size );
		$pages = array_intersect( range( 1, $max_num_pages ), array_merge( $start_pages, $end_pages, $mid_pages ) );
		$prev_page = 0;
		$links = [];

		// prev link
		if ( $current_page && $current_page > 1 ) {
			$links[] = sprintf(
				'<li><a href="%s" rel="prev" data-page="%s">&larr;</a></li>',
				esc_url( str_replace( '{page}', ( $current_page - 1 ), $link_url ) ),
				esc_attr( $current_page - 1 )
			);
		}

		// page numbers
		foreach ( $pages as $page ) {
			if ( $prev_page != $page - 1 ) {
				$links[] = '<li><span class="gap">...</span></li>';
			}

			$links[] = ( $current_page == $page )
				? sprintf( '<li><span class="current" data-page="%s">%s</span></li>', esc_attr( $page ), esc_html( $page ) )
				: sprintf(
					'<li><a href="%s" data-page="%s">%s</a></li>',
					esc_url( str_replace( '{page}', ( $page ), $link_url ) ),
					esc_attr( $page ),
					esc_html( $page )
				);

			$prev_page = $page;
		}

		// next link
		if ( $current_page && $current_page < $max_num_pages ) {
			$links[] = sprintf(
				'<li><a href="%s" rel="next" data-page="%s">&rarr;</a></li>',
				esc_url( str_replace( '{page}', ( $current_page + 1 ), $link_url ) ),
				esc_attr( $current_page + 1 )
			);
		}

		return sprintf(
			'<nav class="job-manager-pagination"><ul class="no-list-style">%s</ul></nav>',
			join( '', $links )
		);
	}

	// Padding to aspect ratio
	public function calculateAspectRatio( $paddingPercentage ) {
		$width = 100;
		$height = $paddingPercentage;

		$gcd = $this->gcd( $width, $height );
		$aspectRatio = ( $width / $gcd ) . '/' . ( $height / $gcd );

		return $aspectRatio;
	}

	// Function to calculate the Greatest Common Divisor (GCD)
	public function gcd( $a, $b ) {
		return ( $b == 0 ) ? $a : $this->gcd( $b, $a % $b );
	}

	// Listing cover image height parser
	public function parseCoverHeightSetting( $input ) {
		if ( strpos( $input, '/' ) !== false || strpos( $input, ':' ) !== false ) {
			$input = str_replace( ':', '/', $input );

			list( $width, $height ) = array_map( 'absint', explode( '/', $input ) );

			$padding = ( $height / $width ) * 100;

			return [
				'padding' => $padding,
				'aspect_ratio' => "{$width}/{$height}",
			];
		} elseif ( is_numeric( $input ) ) {
			$padding = absint( $input );

			$aspect_ratio = $this->calculateAspectRatio( $padding );

			return [
				'padding' => $padding,
				'aspect_ratio' => $aspect_ratio,
			];
		}

		return [
			'padding' => 35,
			'aspect_ratio' => $this->calculateAspectRatio( 35 ),
		];
	}

	public function get_attachment_by_guid( $guid ) {
		if ( is_array( $guid ) ) {
			$guid = array_shift( $guid );
		}

		if ( is_numeric( $guid ) ) {
			return absint( $guid );
		}

		if ( ! is_string( $guid ) || $guid === '' ) {
			return false;
		}

		if ( array_key_exists( $guid, $this->media_cache ) ) {
			return $this->media_cache[ $guid ];
		}

		global $wpdb;

		$attachment_id = false;

		if ( function_exists( 'attachment_url_to_postid' ) ) {
			$attachment_id = attachment_url_to_postid( $guid );
		}

		if ( ! $attachment_id ) {
			$uploads = wp_get_upload_dir();

			if ( is_array( $uploads ) && ! empty( $uploads['baseurl'] ) ) {
				$relative_candidates = [];

				if ( strpos( $guid, $uploads['baseurl'] ) === 0 ) {
					$relative_candidates[] = ltrim( substr( $guid, strlen( $uploads['baseurl'] ) ), '/' );
				}

				if ( ! empty( $uploads['basedir'] ) && strpos( $guid, $uploads['basedir'] ) === 0 ) {
					$relative_candidates[] = ltrim( substr( $guid, strlen( $uploads['basedir'] ) ), '/' );
				}

				$path = wp_parse_url( $guid, PHP_URL_PATH );
				if ( $path ) {
					$base_path = wp_parse_url( $uploads['baseurl'], PHP_URL_PATH );

					if ( $base_path && strpos( $path, $base_path ) === 0 ) {
						$relative_candidates[] = ltrim( substr( $path, strlen( $base_path ) ), '/' );
					} else {
						$relative_candidates[] = ltrim( $path, '/' );
					}
				}

				$relative_candidates = array_unique( array_filter( $relative_candidates ) );

				foreach ( $relative_candidates as $relative_path ) {
					$maybe_id = $wpdb->get_var( $wpdb->prepare(
						"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value = %s LIMIT 1",
						$relative_path
					) );

					if ( $maybe_id ) {
						$attachment_id = absint( $maybe_id );
						$this->media_cache[ $relative_path ] = $attachment_id;
						break;
					}
				}
			}
		}

		if ( ! $attachment_id ) {
			$maybe_id = $wpdb->get_var( $wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'attachment' AND guid = %s LIMIT 1",
				$guid
			) );

			if ( $maybe_id ) {
				$attachment_id = absint( $maybe_id );
			}
		}

		if ( ! $attachment_id ) {
			$file_basename = wp_basename( $guid );

			if ( $file_basename ) {
				$like = '%' . $wpdb->esc_like( $file_basename );
				$maybe_id = $wpdb->get_var( $wpdb->prepare(
					"SELECT p.ID FROM {$wpdb->posts} p
						LEFT JOIN {$wpdb->postmeta} pm ON ( p.ID = pm.post_id AND pm.meta_key = '_wp_attached_file' )
					WHERE p.post_type = 'attachment' AND ( p.guid LIKE %s OR pm.meta_value LIKE %s )
					ORDER BY p.ID DESC
					LIMIT 1",
					$like,
					$like
				) );

				if ( $maybe_id ) {
					$attachment_id = absint( $maybe_id );
				}
			}
		}

		$this->media_cache[ $guid ] = $attachment_id ? $attachment_id : false;

		return $this->media_cache[ $guid ];
	}

	public function get_resized_image( $logo, $size = 'full' ) {
		if ( is_array( $logo ) ) {
			$logo = array_shift( $logo );
		}

		if ( $attachment_id = $this->get_attachment_by_guid( $logo, $size ) ) {
			return wp_get_attachment_image_url( $attachment_id, $size );
		}

		// no attachment found, fallback to the old wpjm image locator
		// mlog()->note( 'Fallback to wpjm locator for attachment: '.basename( $logo ) );

		global $_wp_additional_image_sizes;

		if ( 'full' !== $size
			 && strstr( $logo, WP_CONTENT_URL )
			 && ( isset( $_wp_additional_image_sizes[ $size ] ) || in_array( $size, array( 'thumbnail', 'medium', 'large', 'medium_large' ), true ) )
		) {

			if ( in_array( $size, array( 'thumbnail', 'medium', 'large', 'medium_large' ), true ) ) {
				$img_width  = get_option( $size . '_size_w' );
				$img_height = get_option( $size . '_size_h' );
				$img_crop   = get_option( $size . '_size_crop' );
			} else {
				$img_width  = $_wp_additional_image_sizes[ $size ]['width'];
				$img_height = $_wp_additional_image_sizes[ $size ]['height'];
				$img_crop   = $_wp_additional_image_sizes[ $size ]['crop'];
			}

			$upload_dir        = wp_upload_dir();
			$logo_path         = str_replace( array( $upload_dir['baseurl'], $upload_dir['url'], WP_CONTENT_URL ), array( $upload_dir['basedir'], $upload_dir['path'], WP_CONTENT_DIR ), $logo );
			$path_parts        = pathinfo( $logo_path );
			$dims              = $img_width . 'x' . $img_height;
			$resized_logo_path = str_replace( '.' . $path_parts['extension'], '-' . $dims . '.' . $path_parts['extension'], $logo_path );

			if ( strstr( $resized_logo_path, 'http:' ) || strstr( $resized_logo_path, 'https:' ) ) {
				return $logo;
			}

			if ( ! file_exists( $resized_logo_path ) ) {
				ob_start();

				$image = wp_get_image_editor( $logo_path );
				if ( ! is_wp_error( $image ) ) {
					$resize = $image->resize( $img_width, $img_height, $img_crop );
					if ( ! is_wp_error( $resize ) ) {
						$save = $image->save( $resized_logo_path );
						if ( ! is_wp_error( $save ) ) {
							$logo = dirname( $logo ) . '/' . basename( $resized_logo_path );
						}
					}
				}

				ob_get_clean();
			} else {
				$logo = dirname( $logo ) . '/' . basename( $resized_logo_path );
			}
		}

		return $logo;
	}

	public function set_terms_order( $post_id, $terms, $taxonomy = '' ) {
		global $wpdb;

		$counter = 0;
		foreach ( (array) $terms as $term ) {
			$wpdb->query( sprintf(
				"UPDATE {$wpdb->term_relationships} SET term_order = '%d' WHERE object_id = '%d' AND term_taxonomy_id = '%d'",
				++$counter,
				(int) $post_id,
				(int) $term
			) );
		}
	}

	/**
	 * Similar to `get_edit_post_link`, but is not restricted
	 * to certain roles.
	 *
	 * @since 2.1
	 */
	public function get_edit_post_link( $id = 0 ) {
	    if ( ! $post = get_post( $id ) ) {
	        return;
	    }

	    $action = '&amp;action=edit';

	    $post_type_object = get_post_type_object( $post->post_type );
	    if ( ! $post_type_object ) {
	        return;
	    }

	    if ( $post_type_object->_edit_link ) {
	        $link = admin_url( sprintf( $post_type_object->_edit_link . $action, $post->ID ) );
	    } else {
	        $link = '';
	    }

	    return $link;
	}

	/**
	 * Similar to `get_edit_user_link`, but is not restricted
	 * to certain roles.
	 *
	 * @since 2.1
	 */
	public function get_edit_user_link( $user_id = null ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( empty( $user_id ) ) {
			return '';
		}

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return '';
		}

		$link = add_query_arg( 'user_id', $user->ID, self_admin_url( 'user-edit.php' ) );

		return $link;
	}

	public function get_user_by_id_or_email( $id_or_email ) {
		// Process the user identifier.
		if ( is_numeric( $id_or_email ) ) {
		    $user = get_user_by( 'id', absint( $id_or_email ) );
		} elseif ( $id_or_email instanceof \WP_User ) {
		    // User Object
		    $user = $id_or_email;
		} elseif ( $id_or_email instanceof \WP_Post ) {
		    // Post Object
		    $user = get_user_by( 'id', (int) $id_or_email->post_author );
		} elseif ( $id_or_email instanceof \WP_Comment && ! empty( $id_or_email->user_id ) ) {
	    	$user = get_user_by( 'id', (int) $id_or_email->user_id );
		} elseif ( is_string( $id_or_email ) && is_email( $id_or_email ) ) {
			$user = get_user_by( 'email', $id_or_email );
		} else {
			$user = false;
		}

		if ( ! ( $user instanceof \WP_User ) ) {
			return false;
		}

		return $user;
	}

	/**
	 * Get a user role nice name based on it's slug.
	 *
	 * @since 2.1.6
	 */
	public function get_role_name( $role ) {
		global $wp_roles;
		return isset( $wp_roles->role_names[ $role ] ) ? translate_user_role( $wp_roles->role_names[ $role ] ) : false;
	}

	public static function get_extension_icon( $extension, $default = 'fa fa-file-o' ) {
		$icons = [
			'pdf'  => 'fa fa-file-pdf-o',
			'jpg'  => 'fa fa-file-image-o',
			'jpeg' => 'fa fa-file-image-o',
			'png'  => 'fa fa-file-image-o',
			'gif'  => 'fa fa-file-image-o',
			'flv'  => 'fa fa-file-video-o',
			'mp4'  => 'fa fa-file-video-o',
			'm3u8' => 'fa fa-file-video-o',
			'ts'   => 'fa fa-file-video-o',
			'3gp'  => 'fa fa-file-video-o',
			'mov'  => 'fa fa-file-video-o',
			'avi'  => 'fa fa-file-video-o',
			'wmv'  => 'fa fa-file-video-o',
		];

		return ! empty( $icons[ $extension ] ) ? $icons[ $extension ] : $default;
	}

	public static function add_custom_style( $stylestring ) {
		if ( ! isset( $GLOBALS['case27_custom_styles'] ) ) {
			$GLOBALS['case27_custom_styles'] = '';
		}

		$GLOBALS['case27_custom_styles'] .= $stylestring;
	}

	/**
	 * Get the SQL query for getting listings within a given proximity.
	 *
	 * @link  https://wordpress.stackexchange.com/a/206560/123815
	 * @since 1.0
	 */
	public static function get_proximity_sql() {
		global $wpdb;

		return "
			SELECT $wpdb->posts.ID,
				( %s * IFNULL( acos(
					cos( radians(%s) ) *
					cos( radians( location.lat ) ) *
					cos( radians( location.lng ) - radians(%s) ) +
					sin( radians(%s) ) *
					sin( radians( location.lat ) )
				), 0 ) )
				AS distance, location.lat AS latitude, location.lng AS longitude
				FROM $wpdb->posts
				INNER JOIN {$wpdb->prefix}mylisting_locations
					AS location
					ON $wpdb->posts.ID = location.listing_id
				WHERE 1=1
					AND ($wpdb->posts.post_status = 'publish' )
				HAVING distance < %s
				ORDER BY distance ASC";
	}

	public static function get_open_ranges( $schedule ) {
		$ranges = [];
		if ( ! is_array( $schedule ) ) {
			return [];
		}

		$indexes = [
			'Monday' => 0,
			'Tuesday' => 1,
			'Wednesday' => 2,
			'Thursday' => 3,
			'Friday' => 4,
			'Saturday' => 5,
			'Sunday' => 6,
		];

		unset( $schedule['timezone'] );

		// day length in minutes
		$day_length = 1440;
		foreach ( $schedule as $day => $data ) {
			$index = $indexes[ $day ];
			$day_start = $day_length * $index;

 			if ( $data['status'] === 'open-all-day' ) {
				$data['status'] = 'enter-hours';
				$data[] = [
					'from' => '00:00',
					'to' => '00:00',
				];
			}

			if ( $data['status'] === 'enter-hours' ) {
				unset( $data['status'] );
				foreach ( $data as $slot ) {
					$from = \DateTime::createFromFormat( 'H:i', $slot['from'] ?? null );
					$to = \DateTime::createFromFormat( 'H:i', $slot['to'] ?? null );
					if ( $from && $to ) {
						$from_minute = $day_start + ( absint( $from->format('H') ) * 60 ) + absint( $from->format('i') );
						$to_minute = $day_start + ( absint( $to->format('H') ) * 60 ) + absint( $to->format('i') );

						// handle overnight schedules, e.g. 19:00 - 03:00
						if ( $to_minute <= $from_minute ) {
							$to_minute += $day_length;
						}

						// handle overnight schedules going from sunday to monday
						if ( $to_minute > 10080 ) {
							$monday_minutes = $to_minute - 10080;
							$ranges[] = [ 0, $monday_minutes ];

							$to_minute = 10080;
						}

						$ranges[] = [ $from_minute, $to_minute ];
					}
				}
			}
		}

		usort( $ranges, function( $a, $b ) {
			return $a[0] - $b[0];
		} );

		$n = 0;
		$len = count( $ranges );
		for ( $i = 1; $i < $len; ++$i ) {
			if ( $ranges[$i][0] > $ranges[$n][1] + 1 ) {
				$n = $i;
			} else {
				if ( $ranges[$n][1] < $ranges[$i][1] ) {
					$ranges[$n][1] = $ranges[$i][1];
				}

				unset( $ranges[$i] );
			}
		}

		return array_values( $ranges );
	}

	/**
	 * Displays the selection limit message for a given field if applicable.
	 *
	 * @since 2.12
	 * @param \MyListing\Src\Forms\Fields\Field $field The field object.
	 */
	public function ml_display_field_limits(
		object $field,
		string $singular_message = 'Maximum %1$d item can be selected.',
		string $plural_message = 'Maximum %1$d items can be selected.'
	): void {
		if ( ! $field || ! method_exists( $field, 'get_current_selection_limit' ) ) {
			return;
		}

		$current_selection_limit = $field->get_current_selection_limit();

		if ( $current_selection_limit !== null && $current_selection_limit > 0 ) {
			$default_messages = _n_noop(
				'Maximum %1$d item can be selected.',
				'Maximum %1$d items can be selected.',
				'my-listing'
			);

			echo '<small class="description">';
			$message = ( $singular_message === 'Maximum %1$d item can be selected.' && $plural_message === 'Maximum %1$d items can be selected.' )
				? translate_nooped_plural( $default_messages, $current_selection_limit, 'my-listing' )
				: _n( $singular_message, $plural_message, $current_selection_limit, 'my-listing' );

			printf( $message, $current_selection_limit );
			echo '</small>';
		}
	}

	/**
	 * Display the character counter element for supported field types.
	 *
	 * @since 2.12
	 */
	public function ml_display_field_char_counter( $field ) {
		// Allow developers to disable this feature.
		if ( ! apply_filters( 'mylisting/submission/show_char_counter', true, $field ) ) {
			return;
		}

		// Only show for field types that support min/max length.
		$supported_types = apply_filters( 'mylisting/submission/char_counter_supported_types', [ 'text', 'textarea', 'texteditor', 'wp-editor' ] );
		if ( ! in_array( $field->get_type(), $supported_types, true ) ) {
			return;
		}

		$has_character_limit_method = is_object( $field ) && method_exists( $field, 'get_current_character_limit' );
		$minlength = $has_character_limit_method ? $field->get_current_character_limit( 'min' ) : absint( $field['minlength'] );
		$maxlength = $has_character_limit_method ? $field->get_current_character_limit( 'max' ) : absint( $field['maxlength'] );

		if ( ! $has_character_limit_method ) {
			$minlength = $minlength > 0 ? $minlength : null;
			$maxlength = $maxlength > 0 ? $maxlength : null;
		}

		if ( $minlength === null && $maxlength === null ) {
			return;
		}

		if ( in_array( $field->get_type(), [ 'text', 'textarea' ], true ) ) {
			wp_enqueue_script( 'ml:input-limit' );
		}

		if ( $field->get_type() === 'wp-editor' || $field->get_type() === 'texteditor' ) {
			wp_enqueue_script( 'ml:wp-editor-limit' );
		}

		?>
		<div class="field-char-counter small">
			<span class="counter-label"></span>
			<span class="counter-numbers"><?php
				if ( $maxlength !== null && $maxlength > 0 ) {
					printf( '0 / %d', $maxlength );
				}
			?></span>
		</div>
		<?php
	}
}
