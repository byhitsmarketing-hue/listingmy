<?php

namespace MyListing\Ext\Sharer;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Sharer {
	use \MyListing\Src\Traits\Instantiatable;

	public function __construct() {
		add_action( 'wp_head', [ $this, 'add_opengraph_tags' ], 1 );
	}

	public function add_opengraph_tags() {
    	global $post;

    	if ( is_singular( 'job_listing' ) && ( $listing = \MyListing\Src\Listing::get( $post ) ) ) {
    		$tags = [];

    		$tags['og:title'] = $listing->get_name();
    		$tags['og:url'] = $listing->get_link();
    		$tags['og:site_name'] = get_bloginfo();
    		$tags['og:type'] = 'profile';
    		$tags['og:description'] = $listing->get_share_description();

    		if ( $logo = $listing->get_share_image() ) {
    			$tags['og:image'] = esc_url( $logo );
    		}

    		$tags = apply_filters( 'mylisting\single\og:tags', $tags, $listing );

    		foreach ( $tags as $property => $content ) {
    			printf( "<meta property=\"%s\" content=\"%s\" />\n", esc_attr( $property ), esc_attr( $content ) );
    		}
		}

		if ( is_author() ) {
			$author = get_queried_object();

			$tags = [];

			$tags['og:title'] = $author->display_name;
			$tags['og:url'] = get_author_posts_url( $author->ID );
			$tags['og:site_name'] = get_bloginfo();
			$tags['og:type'] = 'profile';
			$tags['og:description'] = get_the_author_meta( 'description', $author->ID );

			$avatar_url = get_avatar_url( $author->ID );
			if ( $avatar_url ) {
				$tags['og:image'] = esc_url( $avatar_url );
			}

			$tags = apply_filters( 'mylisting\author\og:tags', $tags, $author );

			foreach ( $tags as $property => $content ) {
				printf( "<meta property=\"%s\" content=\"%s\" />\n", esc_attr( $property ), esc_attr( $content ) );
			}
		}
	}

	// not used since 2.10.5
	public function remove_yoast_duplicate_og_tags() {
		global $post;

		if ( ! is_singular( 'job_listing' ) ) {
			return false;
		}

		$listing = \MyListing\Src\Listing::get( $post );

		add_filter( 'wpseo_opengraph_title',    '__return_false', 50 );
    	add_filter( 'wpseo_opengraph_desc', 	'__return_false', 50 );
    	add_filter( 'wpseo_opengraph_url',      '__return_false', 50 );
    	add_filter( 'wpseo_opengraph_type',     '__return_false', 50 );
    	add_filter( 'wpseo_opengraph_site_name','__return_false', 50 );
    	add_filter( 'wpseo_opengraph_image', function( $image ) use ( $listing ) {
    		return $listing->get_share_image();
    	}, 99, 1 );
	}

	public function get_links( $options = [] ) {
		$options = c27()->merge_options([
			'title' => false,
			'image' => false,
			'permalink' => false,
			'description' => false,
			'icons' => false,
		], $options);

		$options['title'] = wp_kses( $options['title'], [] );
		$options['description'] = wp_kses( $options['description'], [] );
		$selected_links = c27()->get_setting( 'select_share_networks' ) ?? [];

		$all_links = [
			'facebook' 	=> $this->facebook($options),
			'twitter'  	=> $this->twitter($options),
			'whatsapp'	=> $this->whatsapp($options),
			'viber'		=> $this->viber($options),
			'telegram'	=> $this->telegram($options),
			'pinterest'	=> $this->pinterest($options),
			'linkedin'	=> $this->linkedin($options),
			'tumblr'	=> $this->tumblr($options),
			'reddit'	=> $this->reddit($options),
			'vkontakte'	=> $this->vkontakte($options),
			'mail'		=> $this->mail($options),
			'copy_link' => $this->copy_link($options),
			'native_share' => $this->native_share($options),
			'threads'   => $this->threads($options),
			'bluesky'   => $this->bluesky($options),
		];

		if (empty($selected_links)) {
			return apply_filters( 'mylisting\share\get-links', $all_links);
		}

		$filtered_links = [];
		foreach ($all_links as $key => $link) {
			if (in_array($key, $selected_links)) {
				$filtered_links[$key] = $link;
			}
		}

		return apply_filters( 'mylisting\share\get-links', $filtered_links);
	}

	public function facebook($options) {
		if ( empty( $options['permalink'] ) ) {
			return;
		}

		$url = 'https://www.facebook.com/sharer/sharer.php';
		$url .= '?u=' . urlencode($options['permalink']);
		if ( ! empty( $options['title'] ) ) {
			$url .= '&quote=' . urlencode($options['title']);
		}
		if ($options['description']) $url .= '&description=' . urlencode($options['description']);
		if ($options['image']) $url .= '&picture=' . urlencode($options['image']);

		return $this->get_link_template( [
			'title' => _x( 'Facebook', 'Share dialog', 'my-listing' ),
			'permalink' => $url,
			'icon' => 'fa fa-facebook',
			'color' => '#3b5998',
		] );
	}

	public function twitter( $options ) {
		if ( empty( $options['title'] ) || empty( $options['permalink'] ) ) {
			return;
		}

		$url = sprintf(
			'https://x.com/share?text=%s&url=%s',
			urlencode( $options['title'] ),
			urlencode( $options['permalink'] )
		);

		return $this->get_link_template( [
			'title' => _x( 'X', 'Share dialog', 'my-listing' ),
			'permalink' => $url,
			'icon' => '',
			'color' => '#000',
			'svg' => \MyListing\get_svg('twitter.svg'),
		] );
	}

	public function threads($options) {
		if ( empty( $options['title'] ) || empty( $options['permalink'] ) ) {
			return;
		}

		$text = urlencode($options['title'] . ' ' . $options['permalink']);
		$desktop_url = 'https://www.threads.net/intent/post?text=' . $text;
		$mobile_url = 'barcelona://create?text=' . $text;

		return $this->get_link_template( [
			'title' => _x( 'Threads', 'Share dialog', 'my-listing' ),
			'permalink' => $desktop_url,
			'icon' => '',
			'color' => '#000000',
			'svg' => \MyListing\get_svg('threads.svg'),
			'class' => 'threads-share-button',
			'extra_attr' => 'data-mobile-url="' . esc_attr($mobile_url) . '"',
		] );
	}

	public function bluesky($options) {
		if (empty($options['title']) || empty($options['permalink'])) {
			return;
		}

		$text = urlencode($options['title'] . ' ' . $options['permalink']);
		$desktop_url = "https://bsky.app/intent/compose?text={$text}";
		$mobile_url = "bluesky://intent/compose?text={$text}";

		return $this->get_link_template([
			'title' => _x('Bluesky', 'Share dialog', 'my-listing'),
			'permalink' => $desktop_url,
			'icon' => '',
			'color' => '#0061F2',
			'svg' => \MyListing\get_svg('bluesky.svg'),
			'class' => 'bluesky-share-button',
			'extra_attr' => 'data-mobile-url="' . esc_attr($mobile_url) . '" target="_blank"',
		]);
	}

	public function pinterest( $options ) {
		if ( empty( $options['title'] ) || empty( $options['permalink'] ) || empty( $options['image'] ) ) {
			return;
		}

		$url = 'https://pinterest.com/pin/create/button/';
		$url .= '?url=' . urlencode($options['permalink']);
		$url .= '&media=' . urlencode($options['image']);
		$url .= '&description=' . urlencode($options['title']);

		return $this->get_link_template( [
			'title' => _x( 'Pinterest', 'Share dialog', 'my-listing' ),
			'permalink' => $url,
			'icon' => 'fa fa-pinterest',
			'color' => '#C92228',
		] );
	}

	public function linkedin( $options ) {
		if ( empty( $options['title'] ) || empty( $options['permalink'] ) ) {
			return;
		}

		$url = 'https://www.linkedin.com/shareArticle?mini=true';
		$url .= '&url=' . urlencode($options['permalink']);
		$url .= '&title=' . urlencode($options['title']);

		return $this->get_link_template( [
			'title' => _x( 'LinkedIn', 'Share dialog', 'my-listing' ),
			'permalink' => $url,
			'icon' => 'fa fa-linkedin',
			'color' => '#0077B5',
		] );
	}

	public function tumblr( $options ) {
		if ( empty( $options['title'] ) || empty( $options['permalink'] ) ) {
			return;
		}

		$url = 'https://www.tumblr.com/share?v=3';
		$url .= '&u=' . urlencode($options['permalink']);
		$url .= '&t=' . urlencode($options['title']);

		return $this->get_link_template( [
			'title' => _x( 'Tumblr', 'Share dialog', 'my-listing' ),
			'permalink' => $url,
			'icon' => 'fa fa-tumblr',
			'color' => '#35465c',
		] );
	}

	public function reddit( $options ) {
		if ( empty( $options['title'] ) || empty( $options['permalink'] ) ) {
			return;
		}

		$url = 'https://www.reddit.com/submit';
		$url .= '?url=' . urlencode($options['permalink']);
		$url .= '&title=' . urlencode($options['title']);

		return $this->get_link_template( [
			'title' => _x( 'Reddit', 'Share dialog', 'my-listing' ),
			'permalink' => $url,
			'icon' => 'fab fa-reddit',
			'color' => '#35465c',
		] );
	}

	public function whatsapp( $options ) {
		if ( empty( $options['title'] ) || empty( $options['permalink'] ) ) {
			return;
		}

		$url = sprintf( 'https://api.whatsapp.com/send?text=%s+%s', urlencode( $options['title'] ), urlencode( $options['permalink'] ) );

		return $this->get_link_template( [
			'title' => _x( 'WhatsApp', 'Share dialog', 'my-listing' ),
			'permalink' => $url,
			'icon' => 'fa fa-whatsapp',
			'color' => '#128c7e',
		] );
	}

	public function telegram( $options ) {
		if ( empty( $options['title'] ) || empty( $options['permalink'] ) ) {
			return;
		}

		$url = sprintf( 'https://telegram.me/share/url?url=%s&text=%s', urlencode($options['permalink']), urlencode($options['title']) );

		return $this->get_link_template( [
			'title' => _x( 'Telegram', 'Share dialog', 'my-listing' ),
			'permalink' => esc_url( $url ),
			'icon' => 'fa fa-telegram',
			'color' => '#0088cc',
		] );
	}

	public function vkontakte( $options ) {
		if ( empty( $options['title'] ) || empty( $options['permalink'] ) ) {
			return;
		}

		$url = 'https://vk.com/share.php?url=' . urlencode( $options['permalink'] );
		$url .= '&title=' . urlencode( $options['title'] );

		return $this->get_link_template( [
			'title' => _x( 'VKontakte', 'Share dialog', 'my-listing' ),
			'permalink' => $url,
			'icon' => 'fa fa-vk',
			'color' => '#5082b9',
		] );
	}

	public function mail( $options ) {
		if ( empty( $options['title'] ) || empty( $options['permalink'] ) ) {
			return;
		}

		$url = sprintf(
			'mailto:?subject=%s&body=%s',
			rawurlencode( '['.get_bloginfo('name').'] ' . $options['title'] ),
			rawurlencode( $options['permalink'] )
		);

		return $this->get_link_template( [
			'title' => _x( 'Mail', 'Share dialog', 'my-listing' ),
			'permalink' => $url,
			'icon' => 'fa fa-envelope-o',
			'color' => '#e74c3c',
			'popup' => false,
		] );
	}

	public function print_link( $link ) {
		if ( ! is_string( $link ) || empty( trim( $link ) ) ) {
			return;
		}

		echo $link;
	}

	public function get_link_template( $data ) {
		$has_popup = isset( $data['popup'] ) && $data['popup'] === false ? false : true;
		$classes = [];
		if ( $has_popup ) {
			$classes[] = 'cts-open-popup';
		}
		if ( ! empty( $data['class'] ) ) {
			$classes[] = esc_attr( $data['class'] );
		}

		$extra_attr = isset($data['extra_attr']) ? $data['extra_attr'] : '';

		ob_start(); ?>
		<a href="<?php echo esc_url( $data['permalink'] ) ?>" class="<?php echo esc_attr( implode(' ', $classes) ) ?>" <?php echo $extra_attr; ?>>
			<span style="background-color: <?php echo esc_attr( $data['color'] ) ?>;">
				<?php if ($data['icon']): ?>
					<i class="<?php echo esc_attr( $data['icon'] ) ?>"></i>
				<?php elseif ( $data['svg'] ): ?>
					<?php echo $data['svg']; ?>
				<?php endif ?>
			</span>
			<?php echo esc_html( $data['title'] ) ?>
		</a>
		<?php return trim( ob_get_clean() );
	}

	public function viber( $options ) {
		if ( empty( $options['title'] ) || empty( $options['permalink'] ) ) {
			return;
		}

		// Build a single encoded text payload for reliability across platforms
		$text = rawurlencode( $options['title'] . ' ' . $options['permalink'] );
		$url  = 'viber://forward?text=' . $text;

		return $this->get_link_template( [
			'title' => _x( 'Viber', 'Share dialog', 'my-listing' ),
			'permalink' => $url,
			'icon' => 'fab fa-viber',
			'color' => '#665CAC',
			'popup' => false,
		] );
	}

	public function copy_link( $options ) {
		if ( empty( $options['permalink'] ) ) {
			return;
		}

		$title = _x( 'Copy link', 'Share dialog', 'my-listing' );
		return sprintf(
			'<a class="c27-copy-link" href="%s" title="%s">'.
			'<span style="background-color:#95a5a6;">'.
				'<i class="fa fa-clone"></i>'.
				'</span>'.
				'<div>%s</div>'.
			'</a>',
			esc_url( $options['permalink'] ), $title, $title
		);
	}

	public function native_share( $options ) {
		if ( empty( $options['title'] ) || empty( $options['permalink'] ) ) {
			return;
		}

		$title = _x( 'Share via...', 'Share dialog', 'my-listing' );
		return sprintf(
			'<a class="c27-native-share" href="#" data-title="%s" data-link="%s">'.
			'<span style="background-color:#95a5a6;">'.
				'<i class="fa fa-share-square"></i>'.
				'</span>'.
				'%s'.
			'</a>',
			esc_attr($options['title']), esc_url($options['permalink']), $title
		);
	}
}
