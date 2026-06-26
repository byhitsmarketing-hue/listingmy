<?php

namespace MyListing;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Assets {

	/**
	 * List of script handles to defer.
	 *
	 * @since 2.1
	 */
	protected $deferred_scripts = [
		'ml:dialog' => true,
		'ml:cts-carousel' => true,
		'ml:quick-view-modal' => true,
		'ml:testimonials' => true,
		'ml:clients-slider' => true,
		'mylisting-ajax-file-upload' => true,
		'mylisting-repeater-ajax-file-upload' => true,
		'mylisting-tabs' => true,
		'mylisting-accordions' => true,
		'mylisting-author-page' => true,
		'mylisting-listing-feed' => true,
		'mylisting-listing-feed-carousel' => true,
		'mylisting-single-listing' => true,
		'mylisting-listing-form' => true,
		'mylisting-product-page' => true,
		'mylisting-account-details' => true,
		'chartist' => true,
		'mylisting-visits-chart' => true,
		'mylisting-my-listings' => true,
		'mylisting-dashboard' => true,
		'mylisting-pricing-plans' => true,
		'mylisting-share-modal' => true,
		'mylisting-back-to-top' => true,
		'mylisting-elementor-column' => true,
		'mylisting-header' => true,
		'mylisting-quick-search-form' => true,
		'mylisting-photoswipe' => true,
		'mylisting-owl' => true,
		'mylisting-background-carousel' => true,
		'mylisting-gallery-carousel-preview' => true,
		'mylisting-gallery-carousel' => true,
		'mylisting-messages' => true,
		'mylisting-open-chat' => true,
		'google-platform-js' => true,
		// 'recaptcha' => true,
		// 'mylisting-recaptcha' => true,
		'comment-reply' => true,

		// password strength
		'zxcvbn-async' => true,
		'password-strength-meter' => true,
		'wc-password-strength-meter' => true,
	];

	protected $soft_loaded_scripts = [
		'ml:quick-view-modal' => true,
		'ml:input-limit' => true,
	];

	/**
	 * Defer non-critical CSS.
	 *
	 * @link  https://web.dev/defer-non-critical-css/
	 * @since 2.2.3
	 */
	protected $deferred_styles = [
		'wp-block-library' => true,
		'wc-block-style' => true,
		'mapbox-gl' => true,
		'ml:quick-view-modal' => true,
		'mylisting-icons' => true,
		'ml:wp-editor-limit' => true,
	];

	public static function boot() {
		new self;
	}

	public function __construct() {
		// register scripts and styles
		add_action( 'wp_enqueue_scripts', [ $this, 'register_scripts' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'register_scripts' ] );
		add_action( 'elementor/editor/before_enqueue_scripts', [ $this, 'register_scripts' ] );
		add_action( 'elementor/ajax/register_actions', [ $this, 'register_scripts' ] );

		add_action( 'wp_default_scripts', [ $this, 'remove_jquery_migrate' ] );
		// enqueue assets
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ], 30 );
		add_action( 'wp_head', [ $this, 'print_head_content' ] );
		add_action( 'admin_head', [ $this, 'print_head_content' ] );
		add_action( 'customize_controls_enqueue_scripts', [ $this, 'print_head_content' ] );
		add_action( 'elementor/editor/before_enqueue_scripts', [ $this, 'print_head_content' ] );

		// dynamic styles
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_dynamic_styles' ], 1000 );
		add_action( 'acf/save_post', [ $this, 'maybe_generate_dynamic_styles' ], 120 );
		add_action( 'after_switch_theme', '\MyListing\generate_dynamic_styles', 20 );

		// defer scripts
		add_filter( 'script_loader_tag', [ $this, 'defer_scripts' ], 10, 2 );
		add_filter( 'style_loader_tag', [ $this, 'defer_styles' ], 10, 4 );

		// dequeue unnecessary scripts
		add_action( 'wp_print_scripts', [ $this, 'dequeue_scripts' ], 1000 );

		if ( apply_filters( 'mylisting/disable-wp-emoji', true ) !== false ) {
			add_action( 'init', function() {
				remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
				remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
				remove_action( 'wp_print_styles', 'print_emoji_styles' );
				remove_action( 'admin_print_styles', 'print_emoji_styles' );
				remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
				remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
				remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
				add_filter( 'tiny_mce_plugins', function( $plugins ) {
					return is_array( $plugins ) ? array_diff( $plugins, [ 'wpemoji' ] ) : [];
				} );
				add_filter( 'wp_resource_hints', function( $urls, $relation_type ) {
					if ( $relation_type === 'dns-prefetch' ) {
						$emoji_svg_url = apply_filters( 'emoji_svg_url', 'https://s.w.org/images/core/emoji/12.0.0-1/svg/' );
						$urls = array_diff( $urls, [ $emoji_svg_url ] );
					}
					return $urls;
				}, 10, 2 );
			} );
		}
	}

	public function register_scripts() {
		$suffix = is_rtl() ? '-rtl' : '';
		$assets = trailingslashit( get_template_directory_uri() ).'assets/';
		$dist = trailingslashit( $assets ).'dist/';
		$vendor = trailingslashit( $assets ).'vendor/';
		$version = \MyListing\get_assets_version();

		// styles
		foreach ( \MyListing\config('assets.styles') as $style ) {
			wp_register_style( sprintf( 'ml:%s', $style ), $dist.$style.$suffix.'.css', [], $version );
		}

		// scripts
		foreach ( \MyListing\config('assets.scripts') as $script ) {
			if ( is_array( $script ) ) {
				wp_register_script( sprintf( 'ml:%s', $script['src'] ), $dist.$script['src'].'.js', $script['deps'] ?? [], $version, true );
			} else {
				wp_register_script( sprintf( 'ml:%s', $script ), $dist.$script.'.js', [], $version, true );
			}
		}

		// frontend styles
		wp_register_style( 'mylisting-vendor', c27()->template_uri( 'assets/dist/vendor'.$suffix.'.css' ), [], \MyListing\get_assets_version() );
		wp_register_style( 'mylisting-frontend', c27()->template_uri( 'assets/dist/frontend'.$suffix.'.css' ), [], \MyListing\get_assets_version() );
		wp_register_style( 'mylisting-default-fonts', c27()->template_uri( 'assets/dist/default-fonts'.$suffix.'.css' ), [], \MyListing\get_assets_version() );

		// responsive styles
		wp_register_style('ml-max-w-1200', c27()->template_uri( 'assets/dist/max-width-1200'.$suffix.'.css' ), ['mylisting-frontend'], \MyListing\get_assets_version(), '(max-width: 1200px)');
		wp_register_style('ml-max-w-992', c27()->template_uri( 'assets/dist/max-width-992'.$suffix.'.css' ), ['mylisting-frontend'], \MyListing\get_assets_version(), '(max-width: 992px)');
		wp_register_style('ml-max-w-768', c27()->template_uri( 'assets/dist/max-width-768'.$suffix.'.css' ), ['mylisting-frontend'], \MyListing\get_assets_version(), '(max-width: 768px)');
		wp_register_style('ml-max-w-600', c27()->template_uri( 'assets/dist/max-width-600'.$suffix.'.css' ), ['mylisting-frontend'], \MyListing\get_assets_version(), '(max-width: 600px)');
		wp_register_style('ml-max-w-480', c27()->template_uri( 'assets/dist/max-width-480'.$suffix.'.css' ), ['mylisting-frontend'], \MyListing\get_assets_version(), '(max-width: 480px)');
		wp_register_style('ml-max-w-320', c27()->template_uri( 'assets/dist/max-width-320'.$suffix.'.css' ), ['mylisting-frontend'], \MyListing\get_assets_version(), '(max-width: 320px)');
		wp_register_style('ml-min-w-993', c27()->template_uri( 'assets/dist/min-width-993'.$suffix.'.css' ), ['mylisting-frontend'], \MyListing\get_assets_version(), '(min-width: 993px)');
		wp_register_style('ml-min-w-1201', c27()->template_uri( 'assets/dist/min-width-1201'.$suffix.'.css' ), ['mylisting-frontend'], \MyListing\get_assets_version(), '(min-width: 1201px)');

		// frontend dashboard
        wp_register_style( 'mylisting-dashboard', c27()->template_uri( 'assets/dist/dashboard'.$suffix.'.css' ), [], \MyListing\get_assets_version() );
        wp_register_style( 'chartist', c27()->template_uri( 'assets/vendor/chartist/chartist.css' ), [], \MyListing\get_assets_version() );
        wp_register_script( 'chartist', c27()->template_uri( 'assets/vendor/chartist/chartist.js' ), [], \MyListing\get_assets_version(), true );
        wp_register_script( 'mylisting-visits-chart', c27()->template_uri( 'assets/dist/visits-chart.js' ), ['jquery'], \MyListing\get_assets_version(), true );
        wp_register_script( 'mylisting-my-listings', c27()->template_uri( 'assets/dist/my-listings.js' ), ['jquery'], \MyListing\get_assets_version(), true );
        wp_register_script( 'mylisting-account-details', c27()->template_uri( 'assets/dist/account-details.js' ), ['jquery'], \MyListing\get_assets_version(), true );
        wp_register_script( 'mylisting-dashboard', c27()->template_uri( 'assets/dist/dashboard.js' ), ['jquery'], \MyListing\get_assets_version(), true );

        // Woocommerce pages style
        // General style
		wp_register_style( 'wc-general-style', c27()->template_uri( 'assets/dist/wc-general-style'.$suffix.'.css' ), [], \MyListing\get_assets_version() );
		// Product page
		wp_register_style( 'mylisting-product-page', c27()->template_uri( 'assets/dist/wc-product-page'.$suffix.'.css' ), [], \MyListing\get_assets_version() );
        wp_register_script( 'mylisting-product-page', c27()->template_uri( 'assets/dist/wc-product-page.js' ), ['jquery'], \MyListing\get_assets_version(), true );
		// Cart page
		wp_register_style( 'wc-cart-page', c27()->template_uri( 'assets/dist/wc-cart-page'.$suffix.'.css' ), [], \MyListing\get_assets_version() );
		// Checkout page
		wp_register_style( 'wc-checkout-page', c27()->template_uri( 'assets/dist/wc-checkout-page'.$suffix.'.css' ), [], \MyListing\get_assets_version() );
		// Login page
		wp_register_style( 'wc-login-register-page', c27()->template_uri( 'assets/dist/wc-login-register-page'.$suffix.'.css' ), [], \MyListing\get_assets_version() );

        // admin styles
        wp_register_style( 'mylisting-admin-general', c27()->template_uri( 'assets/dist/admin/admin'.$suffix.'.css' ), [], \MyListing\get_assets_version() );

        // backend dashboard
        wp_register_style( 'mylisting-admin-dashboard', c27()->template_uri( 'assets/dist/admin/dashboard'.$suffix.'.css' ), [], \MyListing\get_assets_version() );

        // backend shortcodes page
        wp_register_style( 'mylisting-admin-shortcodes', c27()->template_uri( 'assets/dist/admin/shortcodes'.$suffix.'.css' ), [], \MyListing\get_assets_version() );
        wp_register_script( 'mylisting-admin-shortcodes', c27()->template_uri( 'assets/dist/admin/shortcodes.js' ), [], \MyListing\get_assets_version(), true );

        // frontend add listing
		wp_register_style( 'mylisting-add-listing', c27()->template_uri( 'assets/dist/add-listing'.$suffix.'.css' ), [], \MyListing\get_assets_version() );
		wp_register_script( 'mylisting-listing-form', c27()->template_uri( 'assets/dist/add-listing.js' ), ['c27-main'], \MyListing\get_assets_version(), true );
		wp_register_script( 'mylisting-pricing-plans', c27()->template_uri( 'assets/dist/pricing-plans.js' ), ['c27-main'], \MyListing\get_assets_version(), true );

        // 404 page
		wp_register_style( 'mylisting-404', c27()->template_uri( 'assets/dist/404'.$suffix.'.css' ), [], \MyListing\get_assets_version() );

        // MyListing header
		wp_register_style( 'mylisting-header', c27()->template_uri( 'assets/dist/header'.$suffix.'.css' ), [], \MyListing\get_assets_version() );
		wp_register_script( 'mylisting-header', c27()->template_uri( 'assets/dist/header.js' ), ['jquery'], \MyListing\get_assets_version(), true );

        // MyListing footer
		wp_register_style( 'mylisting-footer', c27()->template_uri( 'assets/dist/footer'.$suffix.'.css' ), [], \MyListing\get_assets_version() );

		// backend add listing
		wp_register_style( 'mylisting-admin-form', c27()->template_uri( 'assets/dist/add-listing-admin'.$suffix.'.css' ), [], \MyListing\get_assets_version() );
		wp_register_script( 'mylisting-admin-form', c27()->template_uri( 'assets/dist/add-listing-admin.js' ), ['jquery'], \MyListing\get_assets_version(), true );

		// frontend single listing
		wp_register_style( 'mylisting-single-listing', c27()->template_uri( 'assets/dist/single-listing'.$suffix.'.css' ), [], \MyListing\get_assets_version() );
		wp_register_script( 'mylisting-single-listing', c27()->template_uri( 'assets/dist/single-listing.js' ), ['c27-main'], \MyListing\get_assets_version(), true );

		// MyListing widgets
		wp_register_style( 'mylisting-video-block', c27()->template_uri( 'assets/dist/video-block'.$suffix.'.css' ), [], \MyListing\get_assets_version() );
		// tabs widget
		wp_register_script( 'mylisting-tabs', c27()->template_uri( 'assets/dist/tabs.js' ), ['c27-main'], \MyListing\get_assets_version(), true );
		wp_register_style( 'mylisting-tabs', c27()->template_uri( 'assets/dist/tabs'.$suffix.'.css' ), [], \MyListing\get_assets_version() );
		// accordions widget
		wp_register_script( 'mylisting-accordions', c27()->template_uri( 'assets/dist/accordions.js' ), ['c27-main'], \MyListing\get_assets_version(), true );
		wp_register_style( 'mylisting-accordions', c27()->template_uri( 'assets/dist/accordions'.$suffix.'.css' ), [], \MyListing\get_assets_version() );
		// Photoswipe scripts
		wp_register_script( 'mylisting-photoswipe', c27()->template_uri( 'assets/dist/photoswipe.js' ), ['c27-main'], \MyListing\get_assets_version(), true );
		wp_register_style( 'mylisting-photoswipe', c27()->template_uri( 'assets/dist/photoswipe'.$suffix.'.css' ), [], \MyListing\get_assets_version() );

		// Map 
		wp_register_style( 'mylisting-map-widget', c27()->template_uri( 'assets/dist/map-widget'.$suffix.'.css' ), [], \MyListing\get_assets_version() );
		// Explore listings
		wp_register_style( 'mylisting-explore-widget', c27()->template_uri( 'assets/dist/explore-widget'.$suffix.'.css' ), [], \MyListing\get_assets_version() );
		// Package selection
		wp_register_style( 'mylisting-package-selection-widget', c27()->template_uri( 'assets/dist/package-selection-widget'.$suffix.'.css' ), [], \MyListing\get_assets_version() );
		// Listing categories
		wp_register_style( 'mylisting-listing-categories-widget', c27()->template_uri( 'assets/dist/listing-categories-widget'.$suffix.'.css' ), [], \MyListing\get_assets_version() );
		// Basic search form
		wp_register_style( 'mylisting-basic-search-form', c27()->template_uri( 'assets/dist/basic-search-form'.$suffix.'.css' ), [], \MyListing\get_assets_version() );

		// Quick search form
		wp_register_style( 'mylisting-quick-search-form', c27()->template_uri( 'assets/dist/quick-search-form'.$suffix.'.css' ), [], \MyListing\get_assets_version() );
		wp_register_script( 'mylisting-quick-search-form', c27()->template_uri( 'assets/dist/quick-search-form.js' ), ['c27-main'], \MyListing\get_assets_version(), true );

		// Listing categories shortcode
		wp_register_style( 'mylisting-listing-categories-shortcode', c27()->template_uri( 'assets/dist/listing-categories-shortcode'.$suffix.'.css' ), [], \MyListing\get_assets_version() );
		// Page heading widgets
		wp_register_style( 'mylisting-breadcrumbs-widget', c27()->template_uri( 'assets/dist/breadcrumbs-widget'.$suffix.'.css' ), [], \MyListing\get_assets_version() );
		// Blog feed widget
		wp_register_style( 'mylisting-blog-feed-widget', c27()->template_uri( 'assets/dist/blog-feed-widget'.$suffix.'.css' ), [], \MyListing\get_assets_version() );
		// Single blog page
		wp_register_style( 'mylisting-single-blog', c27()->template_uri( 'assets/dist/single-blog'.$suffix.'.css' ), [], \MyListing\get_assets_version() );
		// Gallery carousel
		wp_register_script( 'mylisting-background-carousel', c27()->template_uri( 'assets/dist/background-carousel.js' ), [], \MyListing\get_assets_version(), true );
		wp_register_script( 'mylisting-gallery-carousel', c27()->template_uri( 'assets/dist/gallery-carousel.js' ), ['mylisting-owl'], \MyListing\get_assets_version(), true );
		wp_register_style( 'mylisting-gallery-carousel', c27()->template_uri( 'assets/dist/gallery-carousel'.$suffix.'.css' ), [], \MyListing\get_assets_version() );
		wp_register_script( 'mylisting-gallery-carousel-preview', c27()->template_uri( 'assets/dist/gallery-carousel-preview.js' ), ['mylisting-owl'], \MyListing\get_assets_version(), true );
		wp_register_style( 'mylisting-gallery-carousel-preview', c27()->template_uri( 'assets/dist/gallery-carousel-preview'.$suffix.'.css' ), [], \MyListing\get_assets_version() );
		wp_register_style( 'mylisting-gallery-grid', c27()->template_uri( 'assets/dist/gallery-grid'.$suffix.'.css' ), [], \MyListing\get_assets_version() );

		// repeater block
		wp_register_style( 'mylisting-repeater-block', c27()->template_uri( 'assets/dist/repeater-block'.$suffix.'.css' ), [], \MyListing\get_assets_version() );
		// share modal
		wp_register_style( 'mylisting-share-modal', c27()->template_uri( 'assets/dist/share-modal'.$suffix.'.css' ), [], \MyListing\get_assets_version() );
		// mylsting wp widgets
		wp_register_style( 'mylisting-wp-widgets', c27()->template_uri( 'assets/dist/ml-wp-widgets'.$suffix.'.css' ), [], \MyListing\get_assets_version() );


		// Back to top
		wp_register_script( 'mylisting-back-to-top', c27()->template_uri( 'assets/dist/back-to-top.js' ), ['c27-main'], \MyListing\get_assets_version(), true );
		// Share modal
		wp_register_script( 'mylisting-share-modal', c27()->template_uri( 'assets/dist/share-modal.js' ), ['c27-main'], \MyListing\get_assets_version(), true );
		// Listing feed
		wp_register_script( 'mylisting-listing-feed', c27()->template_uri( 'assets/dist/listing-feed.js' ), ['c27-main'], \MyListing\get_assets_version(), true );
		wp_register_script( 'mylisting-listing-feed-carousel', c27()->template_uri( 'assets/dist/listing-feed-carousel.js' ), ['c27-main'], \MyListing\get_assets_version(), true );
		// Author page
		wp_register_script( 'mylisting-author-page', c27()->template_uri( 'assets/dist/author-page.js' ), ['c27-main'], \MyListing\get_assets_version(), true );

		// authentication scripts (login, register, social-login)
		wp_register_script( 'mylisting-auth', c27()->template_uri( 'assets/dist/auth.js' ), ['c27-main'], \MyListing\get_assets_version(), true );

		// elementor editor scripts
		wp_register_script( 'mylisting-elementor', c27()->template_uri( 'assets/dist/elementor.js' ), ['jquery'], \MyListing\get_assets_version(), true );
		// elementor column widget
		wp_register_script( 'mylisting-elementor-column', c27()->template_uri( 'assets/dist/elementor-column.js' ), ['jquery'], \MyListing\get_assets_version(), true );

		// theme onboarding scripts
		wp_register_script( 'mylisting-admin-onboarding', c27()->template_uri( 'assets/dist/admin/onboarding.js' ), ['jquery', 'vuejs'], \MyListing\get_assets_version(), true );

		// admin user-roles scripts
		wp_register_script( 'mylisting-admin-user-roles', c27()->template_uri( 'assets/dist/admin/user-roles.js' ), ['jquery', 'vuejs'], \MyListing\get_assets_version(), true );

		// admin type editor
		wp_register_script( 'mylisting-admin-type-editor', c27()->template_uri( 'assets/dist/admin/type-editor.js' ), ['jsoneditor', 'theme-script-vendor', 'wp-color-picker-alpha', 'theme-script-main'], \MyListing\get_assets_version(), true );
		// admin theme options
        wp_register_script( 'mylisting-admin-theme-options', c27()->template_uri( 'assets/dist/admin/theme-options.js' ), [ 'jquery', 'wp-color-picker', 'theme-script-main' ], \MyListing\get_assets_version(), true );
		wp_register_style( 'mylisting-admin-theme-options', c27()->template_uri( 'assets/dist/admin/theme-options'.$suffix.'.css' ), [], \MyListing\get_assets_version() );
		wp_register_script('wp-color-picker-alpha', c27()->template_uri('assets/vendor/color-picker-alpha/wp-color-picker-alpha.js'), ['wp-color-picker','jquery'], \MyListing\get_assets_version(), true);

        // loaders
		wp_register_style( 'mylisting-paper-spinner', c27()->template_uri( 'assets/dist/paper-spinner'.$suffix.'.css' ), [], \MyListing\get_assets_version() );
		wp_register_style( 'mylisting-spin-box', c27()->template_uri( 'assets/dist/spin-box'.$suffix.'.css' ), [], \MyListing\get_assets_version() );
		wp_register_style( 'mylisting-four-dots', c27()->template_uri( 'assets/dist/four-dots'.$suffix.'.css' ), [], \MyListing\get_assets_version() );
		wp_register_style( 'mylisting-rotating-plane', c27()->template_uri( 'assets/dist/rotating-plane'.$suffix.'.css' ), [], \MyListing\get_assets_version() );
		wp_register_style( 'mylisting-double-bounce', c27()->template_uri( 'assets/dist/double-bounce'.$suffix.'.css' ), [], \MyListing\get_assets_version() );
		wp_register_style( 'mylisting-wave', c27()->template_uri( 'assets/dist/wave'.$suffix.'.css' ), [], \MyListing\get_assets_version() );

        // vuejs
        wp_register_script( 'vuejs', c27()->template_uri( 'assets/vendor/vuejs/'.( \MyListing\is_dev_mode() ? 'vue.js' : 'vue.min.js' ) ), [], '2.7.16', true );

		// jsoneditor
        wp_register_script( 'jsoneditor', c27()->template_uri( 'assets/vendor/jsoneditor/jsoneditor.js' ), [], '5.13.2', true );
        wp_register_style( 'jsoneditor', c27()->template_uri( 'assets/vendor/jsoneditor/jsoneditor.css' ), [], '5.13.2' );

        // sortablejs and vue-draggable wrapper
        wp_register_script( 'sortablejs', c27()->template_uri( 'assets/vendor/sortable/sortable.js' ), [], '1.9.0', true );
        wp_register_script( 'vue-draggable', c27()->template_uri( 'assets/vendor/sortable/vue-draggable.js' ), [], '1.1.0', true );

        // isotope
        wp_register_script( 'mylisting-isotope', c27()->template_uri( 'assets/dist/isotope.js' ), [], \MyListing\get_assets_version(), true );

        // datepicker
        wp_register_script( 'mylisting-datepicker', c27()->template_uri( 'assets/dist/datepicker.js' ), [], \MyListing\get_assets_version(), true );
		wp_register_style( 'mylisting-datepicker', c27()->template_uri( 'assets/dist/datepicker'.$suffix.'.css' ), [], \MyListing\get_assets_version() );

        // owl carousel
        wp_register_script( 'mylisting-owl', c27()->template_uri( 'assets/dist/owl.js' ), [], \MyListing\get_assets_version(), true );

        // script to open new chat from quick actions
        wp_register_script( 'mylisting-open-chat', c27()->template_uri( 'assets/dist/open-chat.js' ), [], \MyListing\get_assets_version(), true );


        // custom taxonomies
        wp_register_script( 'mylisting-admin-custom-taxonomies', c27()->template_uri( 'assets/dist/admin/custom-taxonomies.js' ), ['wp-util'], \MyListing\get_assets_version(), true );

        // icons
		wp_register_style( 'mylisting-material-icons', 'https://fonts.googleapis.com/icon?family=Material+Icons&display=swap' );
		wp_register_style( 'mylisting-icons', c27()->template_uri( 'assets/dist/icons'.$suffix.'.css' ), [], \MyListing\get_assets_version() );

		// recaptcha
		$recaptcha_type = mylisting_get_setting('mylisting_recaptcha_type');
		$recaptcha_site_key = mylisting_get_setting('recaptcha_site_key');

		if ($recaptcha_type === 'v2') {
			wp_register_script( 'recaptcha', 'https://www.google.com/recaptcha/api.js?onload=cts_render_captcha&render=explicit', [], null, true );
		} else {
			wp_register_script('recaptcha', 'https://www.google.com/recaptcha/api.js?render=' . $recaptcha_site_key, [], null, true);
		}
        wp_register_script( 'mylisting-recaptcha', c27()->template_uri( 'assets/dist/recaptcha.js' ), ['recaptcha'], \MyListing\get_assets_version(), true );

		/**
		 * Select2 - first use wp_deregister_script to unset select2 loaded
		 * by other plugins, then register it again to use the latest version.
		 */
		wp_deregister_script( 'select2' );
        wp_register_script( 'mylisting-select2', c27()->template_uri( 'assets/vendor/select2/select2.js' ), ['jquery'], '4.0.13', true );
        wp_register_style( 'mylisting-select2', c27()->template_uri( 'assets/vendor/select2/select2.css' ), [], '4.0.13' );

        // google maps
		wp_register_script( 'mylisting-google-maps', c27()->template_uri( 'assets/dist/maps/google-maps/google-maps.js' ), ['jquery'], \MyListing\get_assets_version(), true );
		wp_register_style( 'mylisting-google-maps', c27()->template_uri( 'assets/dist/maps/google-maps/google-maps'.$suffix.'.css' ), [], \MyListing\get_assets_version() );

		// mapbox
		wp_register_script(	'mapbox-gl', 'https://api.tiles.mapbox.com/mapbox-gl-js/v1.9.0/mapbox-gl.js', [], \MyListing\get_assets_version(), true	);
		wp_register_style( 'mapbox-gl',	'https://api.tiles.mapbox.com/mapbox-gl-js/v1.9.0/mapbox-gl.css', [], \MyListing\get_assets_version() );
		wp_register_script( 'mylisting-mapbox', c27()->template_uri( 'assets/dist/maps/mapbox/mapbox.js' ), ['jquery'], \MyListing\get_assets_version(), true );
		wp_register_style( 'mylisting-mapbox', c27()->template_uri( 'assets/dist/maps/mapbox/mapbox'.$suffix.'.css' ), [], \MyListing\get_assets_version() );

		// openstreetmap
		wp_register_script( 'mylisting-openstreetmap', c27()->template_uri( 'assets/dist/maps/openstreetmap/openstreetmap.js' ), ['jquery'], \MyListing\get_assets_version(), true );
		wp_register_style( 'mylisting-openstreetmap', c27()->template_uri( 'assets/dist/maps/openstreetmap/openstreetmap'.$suffix.'.css' ), [], \MyListing\get_assets_version() );

        // add listing image upload
    	wp_register_script( 'jquery-iframe-transport', c27()->template_uri( 'assets/vendor/jquery-fileupload/jquery.iframe-transport.js' ), [ 'jquery' ], '1.8.3', true );
		wp_register_script( 'jquery-fileupload', c27()->template_uri( 'assets/vendor/jquery-fileupload/jquery.fileupload.js' ), [ 'jquery', 'jquery-iframe-transport', 'jquery-ui-widget' ], '9.11.2', true );
		wp_register_script( 'mylisting-ajax-file-upload', c27()->template_uri( 'assets/vendor/jquery-fileupload/ajax-file-upload.js' ), [ 'jquery', 'jquery-fileupload' ], \MyListing\get_assets_version(), true );

		wp_register_script( 'mylisting-repeater-ajax-file-upload', c27()->template_uri( 'assets/vendor/jquery-fileupload/repeater-ajax-file-upload.js' ), [ 'jquery', 'jquery-fileupload' ], \MyListing\get_assets_version(), true );
		
        // editor styles
	    $this->add_editor_style( c27()->template_uri( sprintf( 'assets/dist/editor-styles.css?ver=%s', \MyListing\get_assets_version() ) ) );

	    // load helper js scripts on dev environemnt only
	    if ( \MyListing\is_dev_mode() ) {
        	wp_enqueue_script( 'mylisting-dev', c27()->template_uri( 'assets/dist/dev.js' ), [], \MyListing\get_assets_version(), true );
	    }
	}

	public function remove_jquery_migrate( $scripts ) {
		$scripts->registered['jquery']->deps = array_diff(
			$scripts->registered['jquery']->deps,
			[ 'jquery-migrate' ]
		);
	}

    /**
     * Load WP Editor custom styles.
     *
     * @since 1.0
     */
	public function add_editor_style( $stylesheet ) {
		// for backend editors
		if ( is_admin() ) {
			return add_editor_style( $stylesheet );
		}

	    global $editor_styles;
	    $stylesheet = (array) $stylesheet;
	    if ( is_rtl() ) {
	        $stylesheet[] = str_replace( '.css', '-rtl.css', $stylesheet[0] );
	    }

	    $editor_styles = array_merge( (array) $editor_styles, $stylesheet );
	}

	/**
     * Enqueue theme scripts.
     *
     * @since 1.0.0
	 */
	public function enqueue_scripts() {
		global $wp_query;

		// icons
		wp_enqueue_style( 'mylisting-icons' );
		wp_enqueue_style( 'mylisting-material-icons' );

		// sortable
		// since 2.10.9 we only enqueue it in submit form

		// moment
		wp_enqueue_script( 'moment' );
		$this->load_moment_locale();

		wp_enqueue_script( 'mylisting-select2' );
		wp_enqueue_style( 'mylisting-select2' );
		wp_enqueue_script( 'ml:quick-view-modal' );

		$dependencies = [
			'jquery',
			'moment',
			'mylisting-select2',
			'vuejs',
			'jquery-ui-slider',
		];

		// Frontend scripts.
		wp_enqueue_script( 'mylisting-vendor', c27()->template_uri( 'assets/dist/vendor.js' ), $dependencies, \MyListing\get_assets_version(), true );
		wp_enqueue_script( 'c27-main', c27()->template_uri( 'assets/dist/frontend.js' ), ['mylisting-vendor'], \MyListing\get_assets_version(), true );

		// Comment reply script
		if ( is_singular() && comments_open() && get_option('thread_comments') ) {
			wp_enqueue_script( 'comment-reply' );
		}

		if ( is_singular( 'job_listing' ) ) {
			wp_enqueue_script( 'mylisting-single-listing' );
		}


		// Custom JavaScript
		wp_add_inline_script( 'c27-main', c27()->get_setting('custom_js') );

		// frontend styles
		wp_enqueue_style( 'mylisting-vendor' );
		wp_enqueue_style( 'mylisting-frontend' );
		wp_enqueue_style( 'ml-max-w-1200' );
		wp_enqueue_style( 'ml-max-w-992' );
		wp_enqueue_style( 'ml-max-w-768' );
		wp_enqueue_style( 'ml-max-w-600' );
		wp_enqueue_style( 'ml-max-w-480' );
		wp_enqueue_style( 'ml-max-w-320' );
		wp_enqueue_style( 'ml-min-w-993' );
		wp_enqueue_style( 'ml-min-w-1201' );

		// Listing page style
		if ( is_singular( 'job_listing' ) || is_author() ) {
			wp_enqueue_style( 'mylisting-single-listing' );
			wp_enqueue_script('ml:cts-carousel');
		}

		if ( is_404() ) {
			wp_enqueue_style( 'mylisting-404' );
		}

		if ( is_author() ) {
			wp_enqueue_script('mylisting-author-page');
		}

		// enquque preview card carousel
		wp_enqueue_script('mylisting-background-carousel');

		// Woocommerce styles
		wp_enqueue_style( 'wc-general-style' );
		if ( class_exists( '\\WooCommerce' ) ) {
			if ( is_product() ) {
				wp_enqueue_script( 'mylisting-product-page' );
				wp_enqueue_style( 'mylisting-product-page' );
			}
			if ( is_cart() ) {
				wp_enqueue_style( 'wc-cart-page' );
			}
			if ( is_checkout() ) {
				wp_enqueue_style( 'wc-checkout-page' );
			}

			if ( is_account_page() && !is_user_logged_in() ) {
				wp_enqueue_style( 'wc-login-register-page' );
			}

			if ( is_account_page() ) {
				wp_enqueue_script('ml:cts-carousel');
			}
		}

		// theme style.css
		wp_enqueue_style( 'theme-styles-default', c27()->template_uri( 'style.css' ) );

		if ( apply_filters( 'mylisting/assets/load-default-font', true ) !== false ) {
			wp_enqueue_style( 'mylisting-default-fonts' );
			printf( '<link rel="preload" as="font" href="%s" crossorigin>', c27()->template_uri( 'assets/fonts/GlacialIndifference/Regular.otf' ) );
			printf( '<link rel="preload" as="font" href="%s" crossorigin>', c27()->template_uri( 'assets/fonts/GlacialIndifference/Bold.otf' ) );
			printf( '<link rel="preload" as="font" href="%s" crossorigin>', c27()->template_uri( 'assets/fonts/GlacialIndifference/Italic.otf' ) );
		}

	}

	/**
	 * Enqueue dynamic styles.
	 *
	 * @since 2.0
	 */
	public function enqueue_dynamic_styles() {
		$upload_dir = wp_get_upload_dir();
		if ( ! is_array( $upload_dir ) || empty( $upload_dir['basedir'] ) || empty( $upload_dir['baseurl'] ) ) {
			return;
		}

		// if file does not exist, generate it
		if ( ! file_exists( trailingslashit( $upload_dir['basedir'] ) . 'mylisting-dynamic-styles.css' ) ) {
			\MyListing\generate_dynamic_styles();
		}

		// Adjust the URL scheme to match the site's current scheme
		$style_url = set_url_scheme( trailingslashit( $upload_dir['baseurl'] ) . 'mylisting-dynamic-styles.css' );

		wp_enqueue_style(
			'mylisting-dynamic-styles',
			esc_url( $style_url ), [],
			filemtime( trailingslashit( $upload_dir['basedir'] ) . 'mylisting-dynamic-styles.css' )
		);
	}

	public function maybe_generate_dynamic_styles() {
		if ( is_admin() && ! empty( $_GET['page'] ) && $_GET['page'] === 'theme-general-settings' ) {
			\MyListing\generate_dynamic_styles();
		}
	}

	public function load_moment_locale() {
		$locales = [
			'af', 'ar-dz', 'ar-kw', 'ar-ly', 'ar-ma', 'ar-sa', 'ar-tn', 'ar', 'az', 'be', 'bg', 'bm', 'bn', 'bo', 'br', 'bs', 'ca', 'cs', 'cv', 'cy',
			'da', 'de-at', 'de-ch', 'de', 'dv', 'el', 'en-au', 'en-ca', 'en-gb', 'en-ie', 'en-il', 'en-nz', 'eo', 'es-do', 'es-us', 'es', 'et', 'eu',
			'fa', 'fi', 'fo', 'fr-ca', 'fr-ch', 'fr', 'fy', 'gd', 'gl', 'gom-latn', 'gu', 'he', 'hi', 'hr', 'hu', 'hy-am', 'id', 'is', 'it', 'ja', 'jv',
			'ka', 'kk', 'km', 'kn', 'ko', 'ky', 'lb', 'lo', 'lt', 'lv', 'me', 'mi', 'mk', 'ml', 'mr', 'ms-my', 'ms', 'mt', 'my', 'nb', 'ne', 'nl-be',
			'nl', 'nn', 'pa-in', 'pl', 'pt-br', 'pt', 'ro', 'ru', 'sd', 'se', 'si', 'sk', 'sl', 'sq', 'sr-cyrl', 'sr', 'ss', 'sv', 'sw', 'ta', 'te',
			'tet', 'tg', 'th', 'tl-ph', 'tlh', 'tr', 'tzl', 'tzm-latn', 'tzm', 'ug-cn', 'uk', 'ur', 'uz-latn', 'uz', 'vi', 'x-pseudo', 'yo', 'zh-cn', 'zh-hk', 'zh-tw'
		];

		$load_locale = false;
		$locale = str_replace( '_', '-', strtolower( get_locale() ) );

		if ( in_array( $locale, $locales ) ) {
			$load_locale = $locale;
		} elseif ( strpos( $locale, '-') !== false ) {
			$locale = explode( '-', $locale );
			if ( in_array( $locale[0], $locales ) ) {
				$load_locale = $locale[0];
			}
		}

		if ( $load_locale ) {
			wp_enqueue_script( 'moment-locale-' . $load_locale, sprintf( 'https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.27.0/locale/%s.min.js', $load_locale ), ['moment'], '1.0', true );
			wp_add_inline_script( 'moment-locale-' . $load_locale, sprintf( 'window.MyListing_Moment_Locale = \'%s\';', $load_locale ) );
		}
	}

	/**
	 * Print content within the site <head></head>.
	 *
	 * @since 1.7.2
	 */
	public function print_head_content() {
		// MyListing object.
		$data = apply_filters( 'mylisting/localize-data', [
			'Helpers' => new \stdClass,
			'Handlers' => new \stdClass,
		] );

		foreach ( (array) $data as $key => $value ) {
			if ( ! is_scalar( $value ) ) {
				continue;
			}
			$data[ $key ] = html_entity_decode( (string) $value, ENT_QUOTES, 'UTF-8' );
		}

		printf( '<script type="text/javascript">var MyListing = %s;</script>', wp_json_encode( (object) $data ) );

		$osm_tiles = [];

		if (mylisting()->get( 'maps.provider', 'google-maps' ) === 'free-map') {
			$osm_tiles = [
				'tile_url' => mylisting()->get( 'maps.osmaps_tile_url' ),
				'tile_options' => mylisting()->get( 'maps.osm_custom_options' ),
			];
		}

		// CASE27 object.
		$case27 = array_merge([
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'login_url' => \MyListing\get_login_url(),
			'register_url' => \MyListing\get_register_url(),
			'mylisting_ajax_url' => \MyListing\Ajax::get_endpoint(),
			'theme_folder' => get_template_directory_uri(),
			'theme_version' => \MyListing\get_assets_version(),
			'env' => \MyListing\is_dev_mode() ? 'dev' : 'production',
			'ajax_nonce' => wp_create_nonce('c27_ajax_nonce'),
			'l10n' => [
				'selectOption' => _x( 'Select an option', 'Dropdown placeholder', 'my-listing' ),
				'errorLoading' => _x( 'The results could not be loaded.', 'Dropdown could not load results', 'my-listing' ),
				'removeAllItems' => _x( 'Remove all items', 'Dropdown could not load results', 'my-listing' ),
				'loadingMore'  => _x( 'Loading more results…', 'Dropdown loading more results', 'my-listing' ),
				'noResults'    => _x( 'No results found', 'Dropdown no results found', 'my-listing' ),
				'searching'    => _x( 'Searching…', 'Dropdown searching', 'my-listing' ),
				'datepicker'   => [
					'format'           => apply_filters( 'mylisting/datepicker/date_format', 'DD MMMM, YY' ),
					'timeFormat'       => apply_filters( 'mylisting/datepicker/time_format', 'h:mm A' ),
					'dateTimeFormat'   => apply_filters( 'mylisting/datepicker/datetime_format', 'DD MMMM, YY, h:mm A' ),
					'timePicker24Hour' => apply_filters( 'mylisting/datepicker/enable_24h_format', false ),
			        'firstDay'         => apply_filters( 'mylisting/datepicker/first_day', 1 ),
			        'applyLabel'       => _x( 'Apply',        'Datepicker apply date', 'my-listing' ),
			        'cancelLabel'      => _x( 'Cancel',       'Datepicker cancel date', 'my-listing' ),
			        'customRangeLabel' => _x( 'Custom Range', 'Datepicker custom range', 'my-listing' ),
			        'daysOfWeek' => [
			        	_x( 'Su', 'Datepicker weekday names', 'my-listing' ),
			        	_x( 'Mo', 'Datepicker weekday names', 'my-listing' ),
			        	_x( 'Tu', 'Datepicker weekday names', 'my-listing' ),
			        	_x( 'We', 'Datepicker weekday names', 'my-listing' ),
			        	_x( 'Th', 'Datepicker weekday names', 'my-listing' ),
			        	_x( 'Fr', 'Datepicker weekday names', 'my-listing' ),
			        	_x( 'Sa', 'Datepicker weekday names', 'my-listing' ),
			        ],
			        'monthNames' => [
			        	_x( 'January',   'Datepicker month names', 'my-listing' ),
			        	_x( 'February',  'Datepicker month names', 'my-listing' ),
			        	_x( 'March',     'Datepicker month names', 'my-listing' ),
			        	_x( 'April',     'Datepicker month names', 'my-listing' ),
			        	_x( 'May',       'Datepicker month names', 'my-listing' ),
			        	_x( 'June',      'Datepicker month names', 'my-listing' ),
			        	_x( 'July',      'Datepicker month names', 'my-listing' ),
			        	_x( 'August',    'Datepicker month names', 'my-listing' ),
			        	_x( 'September', 'Datepicker month names', 'my-listing' ),
			        	_x( 'October',   'Datepicker month names', 'my-listing' ),
			        	_x( 'November',  'Datepicker month names', 'my-listing' ),
			        	_x( 'December',  'Datepicker month names', 'my-listing' ),
			        ],
				],
				'irreversible_action' => _x( 'This is an irreversible action. Proceed anyway?', 'Alerts: irreversible action', 'my-listing' ),
				'delete_listing_confirm' => _x( 'Are you sure you want to delete this listing?', 'Alerts: delete listing', 'my-listing' ),
				'copied_to_clipboard' => _x( 'Copied!', 'Alerts: Copied to clipboard', 'my-listing' ),
				'nearby_listings_location_required' => _x( 'Enter a location to find nearby listings.', 'Nearby listings dialog', 'my-listing' ),
				'nearby_listings_retrieving_location' => _x( 'Retrieving location...', 'Nearby listings dialog', 'my-listing' ),
				'nearby_listings_searching' => _x( 'Searching for nearby listings...', 'Nearby listings dialog', 'my-listing' ),
				'geolocation_failed' => _x( 'You must enable location to use this feature.', 'Explore map', 'my-listing' ),
				'something_went_wrong' => __( 'Something went wrong.', 'my-listing' ),
				'all_in_category' => _x( 'All in "%s"', 'Category dropdown', 'my-listing' ),
				'invalid_file_type' => _x( 'Invalid file type. Accepted types:', 'Add listing form', 'my-listing' ),
				'file_limit_exceeded' => _x( 'You have exceeded the file upload limit (%d).', 'Add listing form', 'my-listing' ),
				'file_size_limit' => _x( 'This attachement (%s) exceeds upload size limit (%d).', 'Add listing form', 'my-listing' ),
				'terms_limit' => _x( 'You can select a maximum of %s item(s) for this field.', 'Add listing form', 'my-listing' ),
				'input_too_short' => _x( 'Field value can\'t be shorter than %d characters.', 'Add listing form', 'my-listing' ),
				'input_too_long' => _x( 'Field value can\'t be longer than %d characters.', 'Add listing form', 'my-listing' ),
				'recaptcha_config_error' => _x( 'reCAPTCHA could not be loaded. Please check your internet connection or contact the site administrator if the problem persists.', 'reCAPTCHA error message', 'my-listing' ),
			],
			'woocommerce' => [],
			'map_provider' => mylisting()->get( 'maps.provider', 'google-maps' ),
			'google_btn_local'	=> get_locale(),
			'caption1' => c27()->get_setting( 'lightbox_title_caption' ),
			'caption2' => c27()->get_setting( 'lightbox_description_caption' ),
			'close_on_scroll' => c27()->get_setting( 'lightbox_close_on_scroll' ),
			'recaptcha_type' => mylisting_get_setting('mylisting_recaptcha_type'),
			'recaptcha_key' => mylisting_get_setting('recaptcha_site_key'),
			'select2_keep_open_multi' => apply_filters( 'mylisting/select2/keep_open_multiselect', true ),
		], $osm_tiles);

		ob_start();
		mylisting_locate_template( 'templates/add-listing/form-fields/uploaded-file-html.php', [ 'name' => '', 'value' => '', 'extension' => 'jpg' ] );
		$case27['js_field_html_img'] = esc_js( str_replace( "\n", '', ob_get_clean() ) );

		ob_start();
		mylisting_locate_template( 'templates/add-listing/form-fields/uploaded-file-html.php', [ 'name' => '', 'value' => '', 'extension' => 'zip' ] );
		$case27['js_field_html'] = esc_js( str_replace( "\n", '', ob_get_clean() ) );

		if ( is_admin() ) {
			$case27['map_skins'] = \MyListing\Apis\Maps\get_skins();
		}

		foreach ( (array) $case27 as $key => $value ) {
			if ( ! is_scalar( $value ) ) {
				continue;
			}
			$case27[ $key ] = html_entity_decode( (string) $value, ENT_QUOTES, 'UTF-8' );
		}

		printf( '<script type="text/javascript">var CASE27 = %s;</script>', wp_json_encode( (object) $case27 ) );
	}

	/**
	 * Defer some of the theme scripts.
	 *
	 * @since 2.1
	 */
	public function defer_scripts( $tag, $handle ) {
		if ( isset( $this->deferred_scripts[ $handle ] ) ) {
			$tag = str_replace( '<script ', '<script async defer ', $tag );
		}

		if ( isset( $this->soft_loaded_scripts[ $handle ] ) ) {
			$tag = str_replace( ' src=', ' data-src=', $tag );
		}

		return $tag;
	}

	/**
	 * Defer non-critical CSS.
	 *
	 * @link  https://web.dev/defer-non-critical-css/
	 * @since 2.2.3
	 */
	public function defer_styles( $tag, $handle, $href, $media ) {
		if ( isset( $this->deferred_styles[ $handle ] ) ) {
			return str_replace( "rel='stylesheet'", "rel='preload stylesheet' as='style' onload=\"this.onload=null;this.rel='stylesheet'\"", $tag );
		}

		return $tag;
	}

	public function dequeue_scripts() {
		// disable woocommerce pretty photo plugin
		wp_dequeue_style( 'woocommerce_prettyPhoto_css' );
		wp_dequeue_script( 'prettyPhoto' );
		wp_dequeue_script( 'prettyPhoto-init' );
		wp_dequeue_script( 'photoswipe' );
		wp_dequeue_script( 'photoswipe-ui-default' );

		// disable password strength scripts (only needed on /my-account page)
		if ( function_exists( 'is_account_page' ) ) {
			if ( ! ( is_account_page() && ! is_user_logged_in() ) ) {
				wp_dequeue_script( 'wc-password-strength-meter' );
			}
		} else {
			wp_dequeue_script( 'wc-password-strength-meter' );
		}
	}
}
