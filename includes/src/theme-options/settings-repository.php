<?php
/**
* Central storage for Theme Tools > Theme Options settings.
*
* @since 3.0.0
*/

namespace MyListing\Src\Theme_Options;

use MyListing\Src\Traits\Instantiatable;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Settings_Repository {
	use Instantiatable;

	/**
	* Name of the option used to persist the new settings structure.
	*/
	protected $option_name = 'mylisting_theme_settings';

	/**
	* Tab definitions and field schemas.
	*
	* @var array
	*/
	protected $tabs = [];

	/**
	* Flat list of fields keyed by field key for quick lookup.
	*
	* @var array
	*/
	protected $fields = [];

	public function __construct() {
		add_action( 'after_setup_theme', function() {
			$this->tabs   = $this->get_default_tabs();
			$this->index_fields();
		} );
	}

	/**
	* Return the available tabs and their schema definitions.
	*/
	protected function get_default_tabs() {
		// Build dynamic values used in field descriptions/notices.
		$site_name = get_bloginfo( 'name' );
		$account_url = '';
		$my_products_endpoint = _x( 'my-products', 'URL endpoint for the "My Products" page in user dashboard', 'my-listing' );
		if ( function_exists( 'wc_get_endpoint_url' ) && function_exists( 'wc_get_page_permalink' ) ) {
			$account_url = wc_get_endpoint_url( $my_products_endpoint, '', wc_get_page_permalink( 'myaccount' ) );
		} else {
			// Fallback when WooCommerce helpers are unavailable.
			$account_url = trailingslashit( home_url( '/my-account' ) ) . trailingslashit( $my_products_endpoint );
		}

		// Compose notice HTML for the Simple Products feature.
		$simple_products_notice = sprintf(
			/* translators: 1: Site name, 2: My Products URL, 3: My Products URL */
			__( 'Simple Products are managed in your account area on %1$s. Quick link: <a href="%2$s" target="_blank" rel="noopener">%3$s</a>. You can also add this link to your My Account navigation/menu.', 'my-listing' ),
			esc_html( $site_name ),
			esc_url( $account_url ),
			esc_html( $account_url )
		);

		return [
			'general' => [
				'label'  => _x( 'General', 'Theme Tools > Theme Options tab', 'my-listing' ),
				// Optional per-tab layout definition for flexible UI rendering (rows -> columns -> field keys)
				'layout' => [
					[
						'columns'  => [
							[ 'keys' => [ 'general_site_logo' ] ],
							[ 'keys' => [ 'general_auth_bg' ] ],
						],
						// CSS grid template; frontend may use this as inline grid-template-columns
						'template' => '1fr 1fr',
					],
					[
						'columns'  => [
							[ 'keys' => [ 'general_brand_color', 'general_background_color' ] ],
							[ 'keys' => [ 'general_loading_overlay', 'general_loading_overlay_color', 'general_loading_overlay_background_color' ] ],
							[ 'keys' => [ 'lightbox_message', 'lightbox_title_caption', 'lightbox_description_caption', 'lightbox_close_on_scroll' ] ],
						],
						'template' => '1fr 1fr 1fr',
					],
				],
				'fields' => [
					[
						'key'       => 'general_site_logo',
						'type'      => 'image',
						'label'     => __( 'Site Logo', 'my-listing' ),
						'field_key' => 'field_595b7eda34dc9',
					],
					[
						'key'       => 'general_brand_color',
						'type'      => 'color',
						'label'     => __( 'Accent Color', 'my-listing' ),
						'default'   => '#f24286',
						'field_key' => 'field_5998c6c12e783',
					],
					[
						'key'       => 'general_background_color',
						'type'      => 'color',
						'label'     => __( 'Background Color', 'my-listing' ),
						'default'   => '#f4f4f4',
						'field_key' => 'field_5cc24c4ebc50a',
					],
					[
						'key'       => 'general_loading_overlay',
						'type'      => 'select',
						'label'     => __( 'Loading Screen', 'my-listing' ),
						'choices'   => [
							'none'             => __( 'None', 'my-listing' ),
							'material-spinner' => __( 'Material Spinner', 'my-listing' ),
							'circle-spinner'   => __( 'Circle Spinner', 'my-listing' ),
							'rotating-dots'    => __( 'Rotating Dots', 'my-listing' ),
							'rotating-plane'   => __( 'Rotating Plane', 'my-listing' ),
							'double-bounce'    => __( 'Double Bounce', 'my-listing' ),
							'wave'             => __( 'Wave', 'my-listing' ),
							'site-logo'        => __( 'Site Logo', 'my-listing' ),
						],
						'default'   => 'none',
						'field_key' => 'field_598dd43d705fa',
					],
					[
						'key'        => 'general_loading_overlay_color',
						'type'       => 'color',
						'label'      => __( 'Loader Color', 'my-listing' ),
						'default'    => '#242429',
						'conditions' => [
							[
								[
									'field'    => 'general_loading_overlay',
									'operator' => '!=',
									'value'    => 'none',
								],
							],
						],
						'field_key'  => 'field_59ba134da1abd',
					],
					[
						'key'        => 'general_loading_overlay_background_color',
						'type'       => 'color',
						'label'      => __( 'Loader Background Color', 'my-listing' ),
						'default'    => '#ffffff',
						'conditions' => [
							[
								[
									'field'    => 'general_loading_overlay',
									'operator' => '!=',
									'value'    => 'none',
								],
							],
						],
						'field_key'  => 'field_59ba138ca1abe',
					],
					[
						'key'       => 'general_auth_bg',
						'type'      => 'image',
						'label'     => __( 'Login/register page background', 'my-listing' ),
						'field_key' => 'field_5f3b8fe01ad1f',
					],
					[
						'key'     => 'lightbox_message',
						'type'    => 'message',
						'content' => __( 'Lightbox options for images that use theme\'s lightbox', 'my-listing' ),
					],
					[
						'key'       => 'lightbox_title_caption',
						'type'      => 'select',
						'label'     => __( 'Title', 'my-listing' ),
						'choices'   => [
							'false'       => __( 'None', 'my-listing' ),
							'title'       => __( 'Title', 'my-listing' ),
							'description' => __( 'Description', 'my-listing' ),
							'caption'     => __( 'Caption', 'my-listing' ),
							'alt'         => __( 'Alt', 'my-listing' ),
						],
						'default'   => 'false',
						'field_key' => 'field_641dcf9a8ff95',
					],
					[
						'key'       => 'lightbox_description_caption',
						'type'      => 'select',
						'label'     => __( 'Description', 'my-listing' ),
						'choices'   => [
							'false'       => __( 'None', 'my-listing' ),
							'title'       => __( 'Title', 'my-listing' ),
							'description' => __( 'Description', 'my-listing' ),
							'caption'     => __( 'Caption', 'my-listing' ),
							'alt'         => __( 'Alt', 'my-listing' ),
						],
						'default'   => 'false',
						'field_key' => 'field_641dd0048ff96',
					],
					[
						'key'       => 'lightbox_close_on_scroll',
						'type'      => 'toggle',
						'label'     => __( 'Close on scroll', 'my-listing' ),
						'default'   => true,
						'field_key' => 'field_6733d35821d54',
					],
				],
			],
			'header' => [
				'label'  => _x( 'Header', 'Theme Tools > Theme Options tab', 'my-listing' ),
				'layout' => [
					[
						'columns'  => [
							[ 'keys' => [ 'header_style', 'header_skin' ] ],
							[ 'keys' => [ 'header_width', 'boxed_header_width' ] ],
						],
						'template' => '1fr 1fr',
					],
					[
						'columns'  => [
							[ 'keys' => [ 'header_background_color', 'header_border_color' ] ],
							[ 'keys' => [ 'header_fixed', 'header_menu_location', 'header_mobile_menu_position' ] ],
						],
						'template' => '1fr 1fr',
					],
					[
						'columns'  => [
							[ 'keys' => [ 'header_logo_height_message', 'header_logo_height', 'header_logo_height_tablet', 'header_logo_height_mobile' ] ],
						],
						'template' => '1fr',
					],
					[
						'columns'  => [
							[ 'keys' => [ 'header_show_search_form', 'header_search_form_placeholder', 'header_search_form_featured_categories', 'header_search_form_listing_types' ] ],
							[ 'keys' => [ 'header_show_call_to_action_button', 'header_call_to_action_links_to', 'header_call_to_action_label' ] ],
						],
						'template' => '1fr 1fr',
					],
					[
						'columns'  => [
							[ 'keys' => [ 'header_show_cart', 'header_show_title_bar' ] ],
						],
						'template' => '1fr',
					],
					[
						'columns'  => [
							[ 'keys' => [ 'header_scroll_message', 'header_scroll_logo' ] ],
							[ 'keys' => [ 'header_scroll_skin', 'header_scroll_background_color', 'header_scroll_border_color' ] ],
						],
						'template' => '1fr 1fr',
					],
				],
				'fields' => [
					[
						'key'       => 'header_style',
						'type'      => 'select',
						'label'     => __( 'Header Height', 'my-listing' ),
						'choices'   => [
							'default'   => __( 'Normal', 'my-listing' ),
							'alternate' => __( 'Extended', 'my-listing' ),
						],
						'default'   => 'default',
						'field_key' => 'field_595b7d8981914',
					],
					[
						'key'       => 'header_skin',
						'type'      => 'select',
						'label'     => __( 'Header Text Color', 'my-listing' ),
						'choices'   => [
							'dark'  => __( 'Light', 'my-listing' ),
							'light' => __( 'Dark', 'my-listing' ),
						],
						'default'   => 'dark',
						'field_key' => 'field_59a1982a24d8f',
					],
					[
						'key'       => 'header_width',
						'type'      => 'select',
						'label'     => __( 'Header Width', 'my-listing' ),
						'choices'   => [
							'full-width' => __( 'Full Width', 'my-listing' ),
							'boxed'      => __( 'Boxed', 'my-listing' ),
						],
						'default'   => 'full-width',
						'field_key' => 'field_62446f0887a28',
					],
					[
						'key'        => 'boxed_header_width',
						'type'       => 'number',
						'label'      => __( 'Boxed Header Width (px)', 'my-listing' ),
						'default'    => 1120,
						'min'        => 0,
						'max'        => 1200,
						'step'       => 1,
						'conditions' => [ [ [ 'field' => 'header_width', 'operator' => '==', 'value' => 'boxed' ] ] ],
						'field_key'  => 'field_624471586b779',
					],
					[
						'key'       => 'header_background_color',
						'type'      => 'color',
						'label'     => __( 'Header Background Color', 'my-listing' ),
						'default'   => '#7747FF',
						'alpha'     => true,
						'field_key' => 'field_595b7e899d6ac',
					],
					[
						'key'       => 'header_border_color',
						'type'      => 'color',
						'label'     => __( 'Header Border Color', 'my-listing' ),
						'default'   => 'rgba(25, 28, 31, 0.96)',
						'field_key' => 'field_59a3566469433',
					],
					[
						'key'       => 'header_fixed',
						'type'      => 'toggle',
						'label'     => __( 'Sticky header on scroll?', 'my-listing' ),
						'default'   => true,
						'field_key' => 'field_595b7dd181915',
					],
					[
						'key'       => 'header_menu_location',
						'type'      => 'select',
						'label'     => __( 'Main Menu Location', 'my-listing' ),
						'choices'   => [ 'left' => __( 'Left', 'my-listing' ), 'center' => __( 'Center', 'my-listing' ), 'right' => __( 'Right', 'my-listing' ) ],
						'default'   => 'left',
						'field_key' => 'field_595b80b1a931a',
					],
					[
						'key'     => 'header_mobile_menu_position',
						'type'    => 'select',
						'label'   => __( 'Mobile menu position', 'my-listing' ),
						'choices' => [
							'left'  => __( 'Left', 'my-listing' ),
							'right' => __( 'Right', 'my-listing' ),
						],
						'default' => 'left',
					],
					[
						'key'     => 'header_logo_height_message',
						'type'    => 'message',
						'content' => __( 'Logo Height', 'my-listing' ),
					],
					[
						'key'       => 'header_logo_height',
						'type'      => 'number',
						'label'     => __( 'Logo Height (Desktop)', 'my-listing' ),
						'default'   => 38,
						'min'       => 24,
						'max'       => 80,
						'step'      => 2,
						'field_key' => 'field_59eeaac62c1c5',
					],
					[
						'key'       => 'header_logo_height_tablet',
						'type'      => 'number',
						'label'     => __( 'Logo Height (Tablet)', 'my-listing' ),
						'default'   => 50,
						'min'       => 10,
						'max'       => 100,
						'step'      => 1,
						'field_key' => 'field_5fcba8551e60f',
					],
					[
						'key'       => 'header_logo_height_mobile',
						'type'      => 'number',
						'label'     => __( 'Logo Height (Mobile)', 'my-listing' ),
						'default'   => 40,
						'min'       => 10,
						'max'       => 100,
						'step'      => 1,
						'field_key' => 'field_5fcba8571e610',
					],
					[
						'key'       => 'header_show_search_form',
						'type'      => 'toggle',
						'label'     => __( 'Show Search Form?', 'my-listing' ),
						'default'   => true,
						'field_key' => 'field_595b8055a9318',
					],
					[
						'key'        => 'header_search_form_placeholder',
						'type'       => 'text',
						'label'      => __( 'Search Form Placeholder', 'my-listing' ),
						'default'    => 'Type your search...',
						'conditions' => [ [ [ 'field' => 'header_show_search_form', 'operator' => '==', 'value' => true ] ] ],
						'field_key'  => 'field_595b8071a9319',
					],
					[
						'key'		=> 'header_search_form_featured_categories',
						'type'		=> 'taxonomy_terms',
						'label'		=> __( 'Search Form Featured Terms', 'my-listing' ),
						'instructions' => __( 'Select the terms to appear on initial search form focus. You can mix categories with other listing taxonomies (e.g. Regions, Tags) and reorder them as needed.', 'my-listing' ),
						'multiple'	=> true,
						'taxonomies' => [],
						'taxonomies_callback' => 'listing_taxonomies',
						'default_taxonomy' => 'job_listing_category',
						'placeholder' => __( 'Select...', 'my-listing' ),
						'conditions' => [ [ [ 'field' => 'header_show_search_form', 'operator' => '==', 'value' => true ] ] ],
						'field_key'	=> 'field_5964e0d3bbed9',
					],
					[
						'key'		=> 'header_search_form_listing_types',
						'type'		=> 'ajax_select',
						'label'		=> __( 'Listing types', 'my-listing' ),
						'instructions' => __( 'Select listing types to show in quick search. Leave empty to show all.', 'my-listing' ),
						'multiple'	=> true,
						'ajax_url'  => 'mylisting_list_posts',
						'ajax_params' => [ 'post_type' => 'case27_listing_type' ],
						'placeholder' => __( 'Select...', 'my-listing' ),
						'conditions' => [ [ [ 'field' => 'header_show_search_form', 'operator' => '==', 'value' => true ] ] ],
						'field_key'	=> 'field_6464ef9a14610',
					],
					[
						'key'       => 'header_show_call_to_action_button',
						'type'      => 'toggle',
						'label'     => __( 'Show Call to Action Button', 'my-listing' ),
						'default'   => true,
						'field_key' => 'field_595b820157999',
					],
					[
						'key'		=> 'header_call_to_action_links_to',
						'type'		=> 'ajax_select',
						'label'		=> __( 'Call to action links to page:', 'my-listing' ),
						'multiple'	=> false,
						'ajax_url'  => 'mylisting_list_posts',
						'ajax_params' => [ 'post_type' => 'page' ],
						'placeholder' => __( 'Select a page...', 'my-listing' ),
						'conditions' => [ [ [ 'field' => 'header_show_call_to_action_button', 'operator' => '==', 'value' => true ] ] ],
						'field_key'	=> 'field_595b82555799a',
					],
					[
						'key'        => 'header_call_to_action_label',
						'type'       => 'text',
						'label'      => __( 'Call to action button label', 'my-listing' ),
						'default'    => 'Add Listing',
						'conditions' => [ [ [ 'field' => 'header_show_call_to_action_button', 'operator' => '==', 'value' => true ] ] ],
						'field_key'  => 'field_595b82b95799b',
					],
					[
						'key'       => 'header_show_cart',
						'type'      => 'toggle',
						'label'     => __( 'Show cart', 'my-listing' ),
						'default'   => true,
						'field_key' => 'field_5c0490b2397ec',
					],
					[
						'key'         => 'header_show_title_bar',
						'type'        => 'toggle',
						'label'       => __( 'Enable breadcrumbs?', 'my-listing' ),
						'instructions'=> __( 'Can be overwritten through Elementor for each page.', 'my-listing' ),
						'default'     => false,
						'field_key'   => 'field_59a3660f98ace',
					],
					[
						'key'     => 'header_scroll_message',
						'type'    => 'message',
						'content' => __( 'These options are applied when user has scrolled and the header is set to stick to the top of the page.', 'my-listing' ),
					],
					[
						'key'       => 'header_scroll_logo',
						'type'      => 'image',
						'label'     => __( 'On scroll logo', 'my-listing' ),
						'field_key' => 'field_59ac724a6000a',
					],
					[
						'key'       => 'header_scroll_skin',
						'type'      => 'select',
						'label'     => __( 'On scroll header text color', 'my-listing' ),
						'choices'   => [ 'dark' => __( 'Light', 'my-listing' ), 'light' => __( 'Dark', 'my-listing' ) ],
						'default'   => 'dark',
						'field_key' => 'field_59a350150bddf',
					],
					[
						'key'       => 'header_scroll_background_color',
						'type'      => 'color',
						'label'     => __( 'On scroll header background color', 'my-listing' ),
						'default'   => '#7747FF',
						'alpha'     => true,
						'field_key' => 'field_59a34ff80bdde',
					],
					[
						'key'       => 'header_scroll_border_color',
						'type'      => 'color',
						'label'     => __( 'On scroll header border color', 'my-listing' ),
						'default'   => 'rgba(25, 28, 31, 0.96)',
						'field_key' => 'field_59ac71706c392',
					],
				],
			],
			'footer' => [
				'label'  => _x( 'Footer', 'Theme Tools > Theme Options tab', 'my-listing' ),
				'layout' => [
					[
						'columns'  => [
							[ 'keys' => [ 'footer_show', 'footer_background_color' ] ],
							[ 'keys' => [ 'footer_show_widgets', 'footer_show_menu', 'footer_show_back_to_top_button' ] ],
						],
						'template' => '1fr 1fr',
					],
					[
						'columns'  => [
							[ 'keys' => [ 'footer_widgets_per_row_d' ] ],
							[ 'keys' => [ 'footer_widgets_per_row_t' ] ],
							[ 'keys' => [ 'footer_widgets_per_row_m' ] ],
						],
						'template' => '1fr 1fr 1fr',
					],
					[
						'columns'  => [
							[ 'keys' => [ 'footer_text' ] ],
						],
						'template' => '1fr',
					],
				],
				'fields' => [
					[
						'key'       => 'footer_show',
						'type'      => 'toggle',
						'label'     => __( 'Show Footer?', 'my-listing' ),
						'default'   => true,
						'field_key' => 'field_5c0b1d9b0092e',
					],
					[
						'key'        => 'footer_background_color',
						'type'       => 'color',
						'label'      => __( 'Footer Background Color', 'my-listing' ),
						'default'    => '#ffffff',
						'conditions' => [ [ [ 'field' => 'footer_show', 'operator' => '==', 'value' => true ] ] ],
						'field_key'  => 'field_62445b08bbb5b',
					],
					[
						'key'       => 'footer_show_widgets',
						'type'      => 'toggle',
						'label'     => __( 'Show Widgets?', 'my-listing' ),
						'default'   => true,
						'field_key' => 'field_595b85b15dbec',
					],
					[
						'key'        => 'footer_widgets_per_row_d',
						'type'       => 'select',
						'label'      => __( 'Widgets Per Row (Desktop)', 'my-listing' ),
						'choices'    => [
							'col-lg-12' => '1',
							'col-lg-6'  => '2',
							'col-lg-4'  => '3',
							'col-lg-3'  => '4',
							'col-lg-20' => '5',
						],
						'default'    => 'col-lg-4',
						'conditions' => [ [ [ 'field' => 'footer_show_widgets', 'operator' => '==', 'value' => true ] ] ],
						'field_key'  => 'field_62430567679c5',
					],
					[
						'key'        => 'footer_widgets_per_row_t',
						'type'       => 'select',
						'label'      => __( 'Widgets Per Row (Tablet)', 'my-listing' ),
						'choices'    => [
							'col-sm-12' => '1',
							'col-sm-6'  => '2',
							'col-sm-4'  => '3',
							'col-sm-3'  => '4',
							'col-sm-20' => '5',
						],
						'default'    => 'col-sm-6',
						'conditions' => [ [ [ 'field' => 'footer_show_widgets', 'operator' => '==', 'value' => true ] ] ],
						'field_key'  => 'field_624449edf781c',
					],
					[
						'key'        => 'footer_widgets_per_row_m',
						'type'       => 'select',
						'label'      => __( 'Widgets Per Row (Mobile)', 'my-listing' ),
						'choices'    => [
							'col-xs-12' => '1',
							'col-xs-6'  => '2',
							'col-xs-4'  => '3',
						],
						'default'    => 'col-xs-12',
						'conditions' => [ [ [ 'field' => 'footer_show_widgets', 'operator' => '==', 'value' => true ] ] ],
						'field_key'  => 'field_62444a49f781d',
					],
					[
						'key'       => 'footer_show_menu',
						'type'      => 'toggle',
						'label'     => __( 'Show Footer Menu?', 'my-listing' ),
						'default'   => true,
						'field_key' => 'field_595b85cc5dbed',
					],
					[
						'key'       => 'footer_text',
						'type'      => 'text',
						'label'     => __( 'Footer Text', 'my-listing' ),
						'default'   => '',
						'allow_html' => true,
						'field_key' => 'field_595b85e35dbee',
					],
					[
						'key'       => 'footer_show_back_to_top_button',
						'type'      => 'toggle',
						'label'     => __( 'Show "Back to top" button?', 'my-listing' ),
						'default'   => false,
						'field_key' => 'field_598719cf8d4c3',
					],
				],
			],
			'explore' => [
				'label'  => _x( 'Explore', 'Theme Tools > Theme Options tab', 'my-listing' ),
				'layout' => [
					[
						'columns'  => [
							[ 'keys' => [ 'general_explore_listings_page' ] ],
							[ 'keys' => [ 'general_add_listing_page' ] ],
						],
						'template' => '1fr 1fr',
					],
					[
						'columns'  => [
							[ 'keys' => [ 'general_explore_listings_per_page' ] ],
						],
						'template' => '1fr',
					],
				],
				'fields' => [
					[
						'key'               => 'general_explore_listings_page',
						'type'              => 'ajax_select',
						'label'             => __( 'Default Explore Listings page', 'my-listing' ),
						'multiple'          => false,
						'ajax_url'          => 'mylisting_list_posts',
						'ajax_params'       => [ 'post_type' => 'page' ],
						'placeholder'       => __( 'Select a page...', 'my-listing' ),
						'field_key'         => 'field_595bd2fffffff',
					],
					[
						'key'               => 'general_add_listing_page',
						'type'              => 'ajax_select',
						'label'             => __( "Default 'Add a Listing' page", 'my-listing' ),
						'multiple'          => false,
						'ajax_url'          => 'mylisting_list_posts',
						'ajax_params'       => [ 'post_type' => 'page' ],
						'placeholder'       => __( 'Select a page...', 'my-listing' ),
						'field_key'         => 'field_59a455e61eccc',
					],
					[
						'key'       => 'general_explore_listings_per_page',
						'type'      => 'number',
						'label'     => __( 'Listings per page', 'my-listing' ),
						'instructions' => __( 'The amount of listings to show at once per search.', 'my-listing' ),
						'default'   => 9,
						'min'       => -1,
						'max'       => null,
						'step'      => 1,
						'field_key' => 'field_59770a24cb27d',
					],
				],
			],
			'single_listing' => [
				'label'  => _x( 'Single Listing', 'Theme Tools > Theme Options tab', 'my-listing' ),
				'layout' => [
					[
						'columns' => [
							[ 'keys' => [ 'single_listing_header_preset', 'single_listing_blend_header' ] ],
							[ 'keys' => [ 'single_listing_header_style', 'single_listing_header_width', 'single_listing_boxed_header_width' ] ],
						],
						'template' => '1fr 1fr',
					],
					[
						'columns' => [
							[ 'keys' => [ 'single_listing_header_background_color', 'single_listing_header_border_color' ] ],
							[ 'keys' => [ 'single_listing_header_skin' ] ],
						],
						'template' => '1fr 1fr',
					],
					[
						'columns' => [
							[ 'keys' => [ 'single_listing_header_fixed', 'single_listing_header_show_title_bar' ] ],
							[ 'keys' => [ 'single_listing_cover_height', 'single_listing_cover_picture_quality' ] ],
						],
						'template' => '1fr 1fr',
					],
					[
						'columns' => [
							[ 'keys' => [ 'single_listing_cover_overlay_color', 'single_listing_cover_overlay_opacity' ] ],
							[ 'keys' => [ 'listing_preview_overlay_color', 'listing_preview_overlay_opacity' ] ],
						],
						'template' => '1fr 1fr',
					],
				],
				'fields' => [
					[
						'key'       => 'single_listing_header_preset',
						'type'      => 'select',
						'label'     => __( 'Header Preset', 'my-listing' ),
						'choices'   => [
							'default'  => __( 'Default', 'my-listing' ),
							'header2'  => __( 'Transparent', 'my-listing' ),
							'header3'  => __( 'Dark Skin', 'my-listing' ),
							'header4'  => __( 'Light Skin', 'my-listing' ),
						],
						'default'   => 'header2',
						'field_key' => 'field_5963dbc3f9cbe',
					],
					[
						'key'       => 'single_listing_blend_header',
						'type'      => 'toggle',
						'label'     => __( 'Blend header with the cover section?', 'my-listing' ),
						'default'   => true,
						'field_key' => 'field_5e7b36643fc52',
					],
					[
						'key'       => 'single_listing_header_style',
						'type'      => 'select',
						'label'     => __( 'Header Height', 'my-listing' ),
						'choices'   => [ 'default' => __( 'Normal', 'my-listing' ), 'alternate' => __( 'Extended', 'my-listing' ) ],
						'default'   => 'default',
						'field_key' => 'field_595b7d8981914',
					],
					[
						'key'       => 'single_listing_header_skin',
						'type'      => 'select',
						'label'     => __( 'Header Text Color', 'my-listing' ),
						'choices'   => [ 'dark' => __( 'Light', 'my-listing' ), 'light' => __( 'Dark', 'my-listing' ) ],
						'default'   => 'dark',
						'field_key' => 'field_59a1982a24d8f',
					],
					[
						'key'       => 'single_listing_header_background_color',
						'type'      => 'color',
						'label'     => __( 'Header Background Color', 'my-listing' ),
						'default'   => '#7747FF',
						'alpha'     => true,
						'field_key' => 'field_595b7e899d6ac',
					],
					[
						'key'       => 'single_listing_header_border_color',
						'type'      => 'color',
						'label'     => __( 'Header Border Color', 'my-listing' ),
						'default'   => 'rgba(25, 28, 31, 0.96)',
						'field_key' => 'field_59a3566469433',
					],
					[
						'key'       => 'single_listing_header_width',
						'type'      => 'select',
						'label'     => __( 'Header Width', 'my-listing' ),
						'choices'   => [ 'full-width' => __( 'Full Width', 'my-listing' ), 'boxed' => __( 'Boxed', 'my-listing' ) ],
						'default'   => 'full-width',
						'field_key' => 'field_62458f06a4db2',
					],
					[
						'key'        => 'single_listing_boxed_header_width',
						'type'       => 'number',
						'label'      => __( 'Boxed header width (px)', 'my-listing' ),
						'default'    => 1120,
						'min'        => 0,
						'max'        => 1200,
						'step'       => 1,
						'conditions' => [ [ [ 'field' => 'single_listing_header_width', 'operator' => '==', 'value' => 'boxed' ] ] ],
						'field_key'  => 'field_62458f18a4db3',
					],
					[
						'key'       => 'single_listing_header_fixed',
						'type'      => 'toggle',
						'label'     => __( 'Sticky header on scroll?', 'my-listing' ),
						'default'   => true,
						'field_key' => 'field_62865020d24be',
					],
					[
						'key'       => 'single_listing_header_show_title_bar',
						'type'      => 'toggle',
						'label'     => __( 'Enable breadcrumbs?', 'my-listing' ),
						'instructions' => __( 'Can be overwritten through Elementor for each page.', 'my-listing' ),
						'default'   => false,
						'field_key' => 'field_66e37550cc17c',
					],
					[
						'key'       => 'single_listing_cover_height',
						'type'      => 'text',
						'label'     => __( 'Cover image height', 'my-listing' ),
						'instructions' => __( 'Set the aspect ratio of the cover image. Common values: 16:9, 4:3, 1:1. Default: 20:7', 'my-listing' ),
						'default'   => '',
						'field_key' => 'field_5e858a4202b77',
					],
					[
						'key'       => 'single_listing_cover_picture_quality',
						'type'      => 'select',
						'label'     => __( 'Cover image picture quality', 'my-listing' ),
						'choices'   => [
							'medium'       => __( 'Medium', 'my-listing' ),
							'medium_large' => __( 'Medium Large', 'my-listing' ),
							'large'        => __( 'Large', 'my-listing' ),
							'full'         => __( 'Full', 'my-listing' ),
						],
						'default'   => 'large',
						'field_key' => 'field_6740b09a96562',
					],
					[
						'key'       => 'single_listing_cover_overlay_color',
						'type'      => 'color',
						'label'     => __( 'Cover Overlay Color', 'my-listing' ),
						'default'   => '#242429',
						'field_key' => 'field_59a056ca65404',
					],
					[
						'key'       => 'single_listing_cover_overlay_opacity',
						'type'      => 'number',
						'label'     => __( 'Cover Overlay Opacity', 'my-listing' ),
						'default'   => 0.5,
						'min'       => 0,
						'max'       => 1,
						'step'      => 0.025,
						'field_key' => 'field_59a056ef65405',
					],
					[
						'key'       => 'listing_preview_overlay_color',
						'type'      => 'color',
						'label'     => __( 'Listing Preview Overlay Color', 'my-listing' ),
						'default'   => '#242429',
						'field_key' => 'field_59a169755eeef',
					],
					[
						'key'         => 'listing_preview_overlay_opacity',
						'type'        => 'number',
						'label'       => __( 'Listing Preview Overlay Opacity', 'my-listing' ),
						'default'     => 0.5,
						'min'         => 0,
						'max'         => 1,
						'step'        => 0.025,
						'allow_float' => true,
						'field_key'   => 'field_59a1697b5eef0',
					],
				],
			],
			'blog' => [
				'label'  => _x( 'Blog', 'Theme Tools > Theme Options tab', 'my-listing' ),
				'layout' => [
					[
						'columns'  => [
							[ 'keys' => [ 'blog_default_post_image' ] ],
							[ 'keys' => [ 'blog_show_reading_time', 'blog_reading_time_wpm' ] ],
						],
						'template' => '1fr 1fr',
					],
				],
				'fields' => [
					[
						'key'        => 'blog_show_reading_time',
						'type'       => 'toggle',
						'label'      => __( 'Show reading time', 'my-listing' ),
						'instructions' => __( 'Display estimated reading time on blog previews and single posts.', 'my-listing' ),
						'default'    => true,
					],
					[
						'key'         => 'blog_reading_time_wpm',
						'type'        => 'number',
						'label'       => __( 'Words per minute', 'my-listing' ),
						'instructions' => __( 'Words read per minute used to estimate reading time.', 'my-listing' ),
						'default'     => 200,
						'min'         => 50,
						'max'         => 1000,
						'step'        => 10,
						'conditions'  => [ [ [ 'field' => 'blog_show_reading_time', 'operator' => '==', 'value' => true ] ] ],
					],
					[
						'key'        => 'blog_default_post_image',
						'type'       => 'image',
						'label'      => __( 'Default post image', 'my-listing' ),
						'instructions' => __( "When posts don't have a featured image set, this will be used instead in the blog feed.", 'my-listing' ),
						'field_key'  => 'field_5971331211e6c',
					],
				],
			],
			'custom_code' => [
				'label'  => _x( 'Custom Code', 'Theme Tools > Theme Options tab', 'my-listing' ),
				'layout' => [
					[
						'columns'  => [ [ 'keys' => [ 'custom_code_switcher' ] ] ],
						'template' => '1fr',
					],
					[
						// Place all editors in the same row; conditions will hide the inactive ones,
						// avoiding extra empty grid rows.
						'columns'  => [ [ 'keys' => [ 'custom_css', 'custom_js', 'custom_code' ] ] ],
						'template' => '1fr',
					],
				],
				'fields' => [
					[
						'key'        => 'custom_code_switcher',
						'type'       => 'segmented',
						'label'      => '',
						'choices'    => [
							'custom_css'  => __( 'CSS', 'my-listing' ),
							'custom_js'   => __( 'JavaScript', 'my-listing' ),
							'custom_code' => __( 'Raw Code', 'my-listing' ),
						],
						'default'    => 'custom_css',
					],
					[
						'key'        => 'custom_css',
						'type'       => 'code',
						'label'      => __( 'Custom CSS', 'my-listing' ),
						'instructions' => __( 'Add custom styling to your site.', 'my-listing' ),
						'mode'       => 'css',
						'rows'       => 18,
						'conditions' => [ [ [ 'field' => 'custom_code_switcher', 'operator' => '==', 'value' => 'custom_css' ] ] ],
						'field_key'  => 'field_598dd4ba53c28',
					],
					[
						'key'        => 'custom_js',
						'type'       => 'code',
						'label'      => __( 'Custom JavaScript', 'my-listing' ),
						'instructions' => __( 'Add custom JavaScript code.', 'my-listing' ),
						'mode'       => 'javascript',
						'rows'       => 18,
						'conditions' => [ [ [ 'field' => 'custom_code_switcher', 'operator' => '==', 'value' => 'custom_js' ] ] ],
						'field_key'  => 'field_598dd4eb53c29',
					],
					[
						'key'        => 'custom_code',
						'type'       => 'code',
						'label'      => __( 'Custom Code', 'my-listing' ),
						'instructions' => __( 'Use this field for raw HTML/CSS/JS code. It will be outputted in the site footer.', 'my-listing' ),
						'mode'       => 'htmlmixed',
						'rows'       => 18,
						'conditions' => [ [ [ 'field' => 'custom_code_switcher', 'operator' => '==', 'value' => 'custom_code' ] ] ],
						'field_key'  => 'field_5a0d7bfc4e799',
					],
				],
			],
			'shop' => [
				'label'  => _x( 'Shop page', 'Theme Tools > Theme Options tab', 'my-listing' ),
				'layout' => [
					[
						'columns'  => [
							[ 'keys' => [ 'shop_page_product_columns' ] ],
							[ 'keys' => [ 'shop_page_sidebar' ] ],
						],
						'template' => '1fr 1fr',
					],
					[
						'columns'  => [
							[ 'keys' => [ 'product_vendors_enable' ] ],
						],
						'template' => '1fr',
					],
					[
						'columns'  => [
							[ 'keys' => [ 'product_vendors_notice' ] ],
						],
						'template' => '1fr',
					],
				],
				'fields' => [
					[
						'key'       => 'shop_page_product_columns',
						'type'      => 'number',
						'label'     => __( 'Number of Products Columns', 'my-listing' ),
						'default'   => 3,
						'min'       => 1,
						'max'       => 4,
						'step'      => 1,
						'field_key' => 'field_5af19f2bd8eed',
					],
					[
						'key'               => 'shop_page_sidebar',
						'type'              => 'select',
						'label'             => __( 'Select Shop Page Sidebar', 'my-listing' ),
						'multiple'          => false,
						'choices'           => [],
						'choices_callback'  => 'sidebars',
						'field_key'         => 'field_5af1a04387483',
					],
					[
						'key'       => 'product_vendors_enable',
						'type'      => 'toggle',
						'label'     => __( 'Enable Simple Products', 'my-listing' ),
						'instructions' => __( 'Enable this feature to allow site members to add and manage simple products.', 'my-listing' ),
						'default'   => false,
					],
					[
						'key'        => 'product_vendors_notice',
						'type'       => 'message',
						'content'    => $simple_products_notice,
						'conditions' => [ [ [ 'field' => 'product_vendors_enable', 'operator' => '==', 'value' => true ] ] ],
					],
				],
			],
			'share_modal' => [
				'label'  => _x( 'Share modal', 'Theme Tools > Theme Options tab', 'my-listing' ),
				'layout' => [
					[
						'columns'  => [
							[ 'keys' => [ 'share_network_facebook' ] ],
							[ 'keys' => [ 'share_network_twitter' ] ],
							[ 'keys' => [ 'share_network_whatsapp' ] ],
						],
						'template' => '1fr 1fr 1fr',
					],
					[
						'columns'  => [
							[ 'keys' => [ 'share_network_viber' ] ],
							[ 'keys' => [ 'share_network_telegram' ] ],
							[ 'keys' => [ 'share_network_pinterest' ] ],
						],
						'template' => '1fr 1fr 1fr',
					],
					[
						'columns'  => [
							[ 'keys' => [ 'share_network_linkedin' ] ],
							[ 'keys' => [ 'share_network_reddit' ] ],
							[ 'keys' => [ 'share_network_tumblr' ] ],
						],
						'template' => '1fr 1fr 1fr',
					],
					[
						'columns'  => [
							[ 'keys' => [ 'share_network_threads' ] ],
							[ 'keys' => [ 'share_network_bluesky' ] ],
							[ 'keys' => [ 'share_network_vkontakte' ] ],
						],
						'template' => '1fr 1fr 1fr',
					],
					[
						'columns'  => [
							[ 'keys' => [ 'share_network_mail' ] ],
							[ 'keys' => [ 'share_network_copy_link' ] ],
							[ 'keys' => [ 'share_network_native_share' ] ],
						],
						'template' => '1fr 1fr 1fr',
					],
				],
				'fields' => [
					[ 'key' => 'share_network_facebook',  'type' => 'toggle', 'label' => __( 'Facebook', 'my-listing' ),     'default' => false, 'legacy_select_field' => 'select_share_networks', 'legacy_select_token' => 'facebook' ],
					[ 'key' => 'share_network_twitter',   'type' => 'toggle', 'label' => __( 'X (Twitter)', 'my-listing' ),    'default' => false, 'legacy_select_field' => 'select_share_networks', 'legacy_select_token' => 'twitter' ],
					[ 'key' => 'share_network_whatsapp',  'type' => 'toggle', 'label' => __( 'WhatsApp', 'my-listing' ),       'default' => false, 'legacy_select_field' => 'select_share_networks', 'legacy_select_token' => 'whatsapp' ],
					[ 'key' => 'share_network_viber',     'type' => 'toggle', 'label' => __( 'Viber', 'my-listing' ),          'default' => false, 'legacy_select_field' => 'select_share_networks', 'legacy_select_token' => 'viber' ],
					[ 'key' => 'share_network_telegram',  'type' => 'toggle', 'label' => __( 'Telegram', 'my-listing' ),       'default' => false, 'legacy_select_field' => 'select_share_networks', 'legacy_select_token' => 'telegram' ],
					[ 'key' => 'share_network_pinterest', 'type' => 'toggle', 'label' => __( 'Pinterest', 'my-listing' ),      'default' => false, 'legacy_select_field' => 'select_share_networks', 'legacy_select_token' => 'pinterest' ],
					[ 'key' => 'share_network_linkedin',  'type' => 'toggle', 'label' => __( 'LinkedIn', 'my-listing' ),       'default' => false, 'legacy_select_field' => 'select_share_networks', 'legacy_select_token' => 'linkedin' ],
					[ 'key' => 'share_network_reddit',    'type' => 'toggle', 'label' => __( 'Reddit', 'my-listing' ),         'default' => false, 'legacy_select_field' => 'select_share_networks', 'legacy_select_token' => 'reddit' ],
					[ 'key' => 'share_network_tumblr',    'type' => 'toggle', 'label' => __( 'Tumblr', 'my-listing' ),         'default' => false, 'legacy_select_field' => 'select_share_networks', 'legacy_select_token' => 'tumblr' ],
					[ 'key' => 'share_network_threads',   'type' => 'toggle', 'label' => __( 'Threads', 'my-listing' ),        'default' => false, 'legacy_select_field' => 'select_share_networks', 'legacy_select_token' => 'threads' ],
					[ 'key' => 'share_network_bluesky',   'type' => 'toggle', 'label' => __( 'Bluesky', 'my-listing' ),        'default' => false, 'legacy_select_field' => 'select_share_networks', 'legacy_select_token' => 'bluesky' ],
					[ 'key' => 'share_network_vkontakte', 'type' => 'toggle', 'label' => __( 'VKontakte', 'my-listing' ),      'default' => false, 'legacy_select_field' => 'select_share_networks', 'legacy_select_token' => 'vkontakte' ],
					[ 'key' => 'share_network_mail',      'type' => 'toggle', 'label' => __( 'Mail', 'my-listing' ),           'default' => false, 'legacy_select_field' => 'select_share_networks', 'legacy_select_token' => 'mail' ],
					[ 'key' => 'share_network_copy_link', 'type' => 'toggle', 'label' => __( 'Copy link', 'my-listing' ),      'default' => false, 'legacy_select_field' => 'select_share_networks', 'legacy_select_token' => 'copy_link' ],
					[ 'key' => 'share_network_native_share', 'type' => 'toggle', 'label' => __( 'Native share', 'my-listing' ), 'default' => false, 'legacy_select_field' => 'select_share_networks', 'legacy_select_token' => 'native_share' ],
				],
			],
		];
	}

	/**
	* Index fields for faster lookup and ensure defaults are in place.
	*/
	protected function index_fields() {
		foreach ( $this->tabs as $tab_key => $tab ) {
			if ( empty( $tab['fields'] ) || ! is_array( $tab['fields'] ) ) {
				continue;
			}

			foreach ( $tab['fields'] as $index => $field ) {
				$field['group'] = $tab_key;

				$this->tabs[ $tab_key ]['fields'][ $index ] = $field;

				if ( empty( $field['key'] ) || $field['type'] === 'message' ) {
					continue;
				}

				$this->fields[ $field['key'] ] = $field;
			}
		}
	}

	/**
	* Retrieve a flat list of tabs with minimal metadata.
	*/
	public function get_tabs() {
		$tabs = [];
		foreach ( $this->tabs as $key => $tab ) {
			$tabs[] = [
				'key'   => $key,
				'label' => $tab['label'],
			];
		}

		return $tabs;
	}

	/**
	* Determine if the provided group exists.
	*/
	public function has_tab( $group ) {
		return isset( $this->tabs[ $group ] );
	}

	/**
	* Determine if the repository manages the provided field.
	*/
	public function has_field( $key ) {
		return isset( $this->fields[ $key ] );
	}

	/**
	* Return field schema definitions for the requested group.
	* Also resolves dynamic choices for fields that specify a `choices_callback`.
	*/
	public function get_tab_fields( $group ) {
		$fields = $this->tabs[ $group ]['fields'] ?? [];

		foreach ( $fields as $index => $field ) {
			if ( empty( $field['key'] ) || ( $field['type'] ?? '' ) === 'message' ) {
				continue;
			}

			// Populate dynamic choices if required.
			if ( empty( $field['choices'] ) && ! empty( $field['choices_callback'] ) && is_string( $field['choices_callback'] ) ) {
				$callback = $field['choices_callback'];
				switch ( $callback ) {
					case 'listing_categories':
						$field['choices'] = $this->get_listing_category_choices();
						break;
					case 'listing_types':
						$field['choices'] = $this->get_listing_type_choices();
						break;
					case 'pages':
						$field['choices'] = $this->get_page_choices();
						break;
					case 'sidebars':
						$field['choices'] = $this->get_sidebar_choices();
						break;
				}
				$fields[ $index ] = $field;
			}

			// Populate taxonomy choices for taxonomy_terms fields if required.
			if ( empty( $field['taxonomies'] ) && ! empty( $field['taxonomies_callback'] ) && is_string( $field['taxonomies_callback'] ) ) {
				$callback = $field['taxonomies_callback'];
				switch ( $callback ) {
					case 'listing_taxonomies':
						$exclude = isset( $field['taxonomies_exclude'] ) && is_array( $field['taxonomies_exclude'] ) ? $field['taxonomies_exclude'] : [];
						$field['taxonomies'] = $this->get_listing_taxonomy_choices( $exclude );
						break;
				}
				$fields[ $index ] = $field;
			}
		}

		return $fields;
	}

	/**
	* Build choices for job listing categories taxonomy.
	*/
	protected function get_listing_category_choices() {
		$choices = [];
		$terms = \get_terms( [
			'taxonomy'   => 'job_listing_category',
			'hide_empty' => false,
		] );

		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$choices[ (string) $term->term_id ] = $term->name;
			}
		}

		return $choices;
	}

	/**
	* Build choices for listing types (case27_listing_type post type).
	*/
	protected function get_listing_type_choices() {
		$choices = [];
		$posts = \get_posts( [
			'post_type'      => 'case27_listing_type',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'suppress_filters' => false,
		] );

		foreach ( $posts as $p ) {
			$choices[ (string) $p->ID ] = $p->post_title;
		}

		return $choices;
	}

	/**
	* Build choices for listing-related taxonomies.
	*/
	protected function get_listing_taxonomy_choices( array $exclude = [] ) {
		$choices = [];
		$taxonomies = get_object_taxonomies( 'job_listing', 'objects' );

		foreach ( (array) $taxonomies as $taxonomy ) {
			if ( empty( $taxonomy->name ) ) {
				continue;
			}

			if ( in_array( $taxonomy->name, $exclude, true ) ) {
				continue;
			}

			if ( empty( $taxonomy->publicly_queryable ) ) {
				continue;
			}

			$label = isset( $taxonomy->labels->name ) ? $taxonomy->labels->name : $taxonomy->name;
			$choices[ $taxonomy->name ] = $label;
		}

		if ( count( $choices ) > 1 ) {
			asort( $choices, SORT_NATURAL | SORT_FLAG_CASE );
		}

		return $choices;
	}

	/**
	* Build choices for all pages.
	*/
	protected function get_page_choices() {
		$choices = [];
		$pages = \get_pages( [
			'sort_column' => 'post_title',
			'sort_order'  => 'ASC',
		] );

		foreach ( $pages as $p ) {
			$choices[ (string) $p->ID ] = $p->post_title;
		}

		return $choices;
	}

	/**
	* Build choices for registered sidebars (by ID => name).
	*/
	protected function get_sidebar_choices() {
		$choices = [];
		global $wp_registered_sidebars;
		if ( is_array( $wp_registered_sidebars ) ) {
			foreach ( $wp_registered_sidebars as $sidebar ) {
				if ( ! empty( $sidebar['id'] ) ) {
					$choices[ (string) $sidebar['id'] ] = isset( $sidebar['name'] ) ? $sidebar['name'] : (string) $sidebar['id'];
				}
			}
		}
		return $choices;
	}

	/**
	* Return layout schema for the requested group, if provided.
	*/
	public function get_tab_layout( $group ) {
		return $this->tabs[ $group ]['layout'] ?? [];
	}

	/**
	* Retrieve formatted values for the editor UI for the provided group.
	*/
	public function get_values_for_editor( $group ) {
		$values   = [];
		$settings = $this->load_settings();
		$stored   = isset( $settings[ $group ] ) && is_array( $settings[ $group ] ) ? $settings[ $group ] : [];

		foreach ( $this->get_tab_fields( $group ) as $field ) {
			if ( empty( $field['key'] ) || $field['type'] === 'message' ) {
				continue;
			}

			if ( array_key_exists( $field['key'], $stored ) ) {
				$raw = $stored[ $field['key'] ];
			} else {
				$raw = $this->get_legacy_value( $field );

				if ( null === $raw ) {
					$raw = $this->get_default_value( $field );
				}
			}

			// For the editor UI, return values in the exact format the controls expect
			// (e.g., IDs for select fields), not the frontend/theme-consumption format.
			$values[ $field['key'] ] = $this->format_value_for_editor( $field, $raw );
		}

		return $values;
	}

	/**
	* Retrieve a single setting value formatted for theme usage.
	*/
	public function get( $key, $default = null ) {
		// Back-compat alias: compute the legacy multiselect value from per-network toggles.
		if ( $key === 'select_share_networks' ) {
			$map = [
				'facebook'     => 'share_network_facebook',
				'twitter'      => 'share_network_twitter',
				'whatsapp'     => 'share_network_whatsapp',
				'viber'        => 'share_network_viber',
				'telegram'     => 'share_network_telegram',
				'pinterest'    => 'share_network_pinterest',
				'linkedin'     => 'share_network_linkedin',
				'reddit'       => 'share_network_reddit',
				'tumblr'       => 'share_network_tumblr',
				'threads'      => 'share_network_threads',
				'bluesky'      => 'share_network_bluesky',
				'vkontakte'    => 'share_network_vkontakte',
				'mail'         => 'share_network_mail',
				'copy_link'    => 'share_network_copy_link',
				'native_share' => 'share_network_native_share',
			];
			$selected = [];
			foreach ( $map as $token => $toggle_key ) {
				// If toggle exists and is enabled, include token.
				if ( isset( $this->fields[ $toggle_key ] ) && $this->get( $toggle_key, false ) ) {
					$selected[] = $token;
				}
			}
			return $selected ?: $default;
		}

		if ( ! isset( $this->fields[ $key ] ) ) {
			return $default;
		}

		$field    = $this->fields[ $key ];
		$settings = $this->load_settings();
		$stored   = $settings[ $field['group'] ][ $key ] ?? null;

		if ( null === $stored ) {
			$stored = $this->get_legacy_value( $field );

			if ( null === $stored ) {
				$stored = $this->get_default_value( $field );
			}
		}

		$formatted = $this->format_value_for_output( $field, $stored );

		if ( null === $formatted ) {
			return $default;
		}

		return $formatted;
	}

	/**
	* Update settings for a specific group.
	*/
	public function update_group( $group, array $values ) {
		if ( ! $this->has_tab( $group ) ) {
			return new WP_Error( 'invalid_group', __( 'Invalid settings group.', 'my-listing' ) );
		}

		$settings           = $this->load_settings();
		$existing_group     = isset( $settings[ $group ] ) && is_array( $settings[ $group ] ) ? $settings[ $group ] : [];
		$updated_group_meta = [];

		foreach ( $this->get_tab_fields( $group ) as $field ) {
			if ( empty( $field['key'] ) || $field['type'] === 'message' ) {
				continue;
			}

			$incoming  = array_key_exists( $field['key'], $values ) ? $values[ $field['key'] ] : $this->get_default_value( $field );
			$sanitized = $this->sanitize_value( $field, $incoming );

			$existing_group[ $field['key'] ] = $sanitized;
			$updated_group_meta[ $field['key'] ] = $sanitized;

			$this->update_legacy_option( $field, $sanitized );
		}

		$settings[ $group ] = $existing_group;
		$this->persist_settings( $settings );

		// Keep legacy multiselect 'select_share_networks' in sync based on per-network toggles.
		if ( $group === 'share_modal' ) {
			$map = [
				'facebook'     => 'share_network_facebook',
				'twitter'      => 'share_network_twitter',
				'whatsapp'     => 'share_network_whatsapp',
				'viber'        => 'share_network_viber',
				'telegram'     => 'share_network_telegram',
				'pinterest'    => 'share_network_pinterest',
				'linkedin'     => 'share_network_linkedin',
				'reddit'       => 'share_network_reddit',
				'tumblr'       => 'share_network_tumblr',
				'threads'      => 'share_network_threads',
				'bluesky'      => 'share_network_bluesky',
				'vkontakte'    => 'share_network_vkontakte',
				'mail'         => 'share_network_mail',
				'copy_link'    => 'share_network_copy_link',
				'native_share' => 'share_network_native_share',
			];
			$selected = [];
			foreach ( $map as $token => $toggle_key ) {
				if ( ! empty( $existing_group[ $toggle_key ] ) ) {
					$selected[] = $token;
				}
			}
			update_option( 'options_select_share_networks', $selected );
			// Legacy ACF field key of the old multiselect.
			update_option( '_options_select_share_networks', 'field_64874ef3af05e' );
		}

		return $updated_group_meta;
	}

	/**
	* Load settings from the database.
	*/
	protected function load_settings() {
		$settings = get_option( $this->option_name, [] );

		if ( ! is_array( $settings ) ) {
			$settings = [];
		}

		return $settings;
	}

	/**
	* Persist settings to the database.
	*/
	protected function persist_settings( array $settings ) {
		update_option( $this->option_name, $settings );
	}

	/**
	* Fetch a legacy value saved by ACF so existing installations keep working.
	*/
	protected function get_legacy_value( array $field ) {
		$option_key = 'options_' . $field['key'];
		$value      = get_option( $option_key, null );

		if ( null === $value ) {
			// Special back-compat: Toggle fields mapped from the old multiselect 'select_share_networks'.
			if ( ( $field['type'] ?? '' ) === 'toggle' && ! empty( $field['legacy_select_field'] ) && ! empty( $field['legacy_select_token'] ) ) {
				$legacy = get_option( 'options_' . $field['legacy_select_field'], null );
				if ( is_array( $legacy ) ) {
					return in_array( $field['legacy_select_token'], $legacy, true );
				}
			}
			return null;
		}

		// Normalize legacy values for special select types (e.g., page links stored as URLs)
		if ( ( $field['type'] ?? '' ) === 'select' ) {
			$callback = isset( $field['choices_callback'] ) ? $field['choices_callback'] : '';
			if ( $callback === 'pages' ) {
				// Legacy ACF 'page_link' often stores a URL. Convert URL to a page ID for internal use.
				if ( is_numeric( $value ) ) {
					$value = (int) $value;
				} elseif ( is_string( $value ) && $value !== '' ) {
					$id = url_to_postid( $value );
					if ( ! $id ) {
						$path = wp_parse_url( $value, PHP_URL_PATH );
						$path = is_string( $path ) ? trim( $path, '/' ) : '';
						if ( $path ) {
							$page = get_page_by_path( $path );
							$id = $page ? (int) $page->ID : 0;
						}
					}
					$value = $id ?: null;
				}
			}
		}

		// Normalize legacy values for ajax_select page fields (ACF page_link stores a URL).
		if ( ( $field['type'] ?? '' ) === 'ajax_select' ) {
			$ajax_url    = isset( $field['ajax_url'] ) ? $field['ajax_url'] : '';
			$ajax_params = isset( $field['ajax_params'] ) ? $field['ajax_params'] : [];

			if ( $ajax_url === 'mylisting_list_posts' && isset( $ajax_params['post_type'] ) && $ajax_params['post_type'] === 'page' ) {
				if ( is_numeric( $value ) ) {
					$value = (int) $value;
				} elseif ( is_string( $value ) && $value !== '' ) {
					$id = url_to_postid( $value );
					if ( ! $id ) {
						$path = wp_parse_url( $value, PHP_URL_PATH );
						$path = is_string( $path ) ? trim( $path, '/' ) : '';
						if ( $path ) {
							$page = get_page_by_path( $path );
							$id = $page ? (int) $page->ID : 0;
						}
					}
					$value = $id ?: null;
				}
			}
		}

		switch ( $field['type'] ) {
			case 'image':
				$attachment_id = 0;
				if ( is_array( $value ) ) {
					$attachment_id = isset( $value['ID'] ) ? absint( $value['ID'] ) : ( isset( $value['id'] ) ? absint( $value['id'] ) : 0 );
				} else {
					$attachment_id = absint( $value );
				}
				return $attachment_id > 0 ? $attachment_id : null;
			case 'toggle':
				return (bool) $value;
			case 'number':
				if ( ! is_numeric( $value ) ) {
					return null;
				}
				return $this->number_allows_float( $field ) ? (float) $value : (int) $value;
			case 'text':
				return is_string( $value ) ? $value : ( $value === null ? null : strval( $value ) );
			default:
				return $value;
		}
	}

	/**
	 * Format values for the editor UI (Vue components) to ensure correct selected state
	 * and control behavior. This differs from format_value_for_output(), which formats
	 * for theme/frontend consumption.
	 */
	protected function format_value_for_editor( array $field, $value ) {
		switch ( $field['type'] ) {
			case 'image':
				// Editor expects a full attachment object similar to wp.media JSON.
				return $this->format_value_for_output( $field, $value );
			case 'color':
				return is_string( $value ) ? $value : '';
			case 'text':
				return is_string( $value ) ? $value : ( $value === null ? '' : strval( $value ) );
			case 'code':
				// Preserve raw code for editor textarea.
				return is_string( $value ) ? $value : ( $value === null ? '' : strval( $value ) );
			case 'number':
				if ( ! is_numeric( $value ) ) { return null; }
				return $this->number_allows_float( $field ) ? (float) $value : (int) $value;
			case 'toggle':
				return (bool) $value;
			case 'select':
				$is_multiple = ! empty( $field['multiple'] );
				$callback    = isset( $field['choices_callback'] ) ? $field['choices_callback'] : '';

				// For editor, selects should use IDs (as strings) that match option keys.
				if ( $callback === 'listing_categories' ) {
					if ( $is_multiple ) {
						$vals = is_array( $value ) ? $value : ( ( $value === null || $value === '' ) ? [] : [ $value ] );
						$vals = array_filter( array_map( function( $v ) { return is_scalar( $v ) ? (string) $v : null; }, $vals ) );
						return array_values( $vals );
					}
					return is_scalar( $value ) ? (string) $value : '';
				}

				if ( $callback === 'pages' ) {
					// Expect a single page ID.
					if ( is_numeric( $value ) ) {
						return (string) (int) $value;
					}
					// If a URL sneaks in, try to resolve to ID for editor selected state.
					if ( is_string( $value ) && $value !== '' ) {
						$id = url_to_postid( $value );
						if ( ! $id ) {
							$path = wp_parse_url( $value, PHP_URL_PATH );
							$path = is_string( $path ) ? trim( $path, '/' ) : '';
							if ( $path ) {
								$page = get_page_by_path( $path );
								$id = $page ? (int) $page->ID : 0;
							}
						}
						return $id ? (string) $id : '';
					}
					return '';
				}

				// Default: return raw value for editor.
				return $value;
			case 'ajax_select':
				// AJAX selects: return IDs as strings for editor
				$is_multiple = ! empty( $field['multiple'] );
				if ( $is_multiple ) {
					$vals = is_array( $value ) ? $value : ( ( $value === null || $value === '' ) ? [] : [ $value ] );
					$vals = array_filter( array_map( function( $v ) { return is_scalar( $v ) ? (string) $v : null; }, $vals ) );
					return array_values( $vals );
				}
				return is_scalar( $value ) && $value !== '' ? (string) $value : '';
			case 'taxonomy_terms':
				// Taxonomy term tokens stored as strings: `taxonomy:term_id`.
				$vals = is_array( $value ) ? $value : ( ( $value === null || $value === '' ) ? [] : [ $value ] );
				$vals = array_values( array_filter( array_map( 'strval', array_filter( $vals, 'is_scalar' ) ) ) );

				// Back-compat: accept raw IDs/slugs and map them to the default taxonomy.
				$allowed_taxonomies = isset( $field['taxonomies'] ) && is_array( $field['taxonomies'] ) ? array_keys( $field['taxonomies'] ) : [];
				$default_taxonomy = isset( $field['default_taxonomy'] ) ? sanitize_key( $field['default_taxonomy'] ) : '';
				if ( ! $default_taxonomy && ! empty( $allowed_taxonomies ) ) {
					$default_taxonomy = (string) reset( $allowed_taxonomies );
				}

				$tokens = [];
				foreach ( $vals as $raw ) {
					$raw = trim( (string) $raw );
					if ( $raw === '' ) {
						continue;
					}

					if ( strpos( $raw, ':' ) !== false ) {
						$tokens[] = $raw;
						continue;
					}

					if ( ! $default_taxonomy ) {
						continue;
					}

					if ( is_numeric( $raw ) ) {
						$id = absint( $raw );
						if ( $id ) {
							$tokens[] = "{$default_taxonomy}:{$id}";
						}
						continue;
					}

					$slug = sanitize_title( $raw );
					if ( $slug ) {
						$term = get_term_by( 'slug', $slug, $default_taxonomy );
						if ( $term && ! is_wp_error( $term ) ) {
							$tokens[] = "{$default_taxonomy}:" . absint( $term->term_id );
						}
					}
				}

				return array_values( array_unique( $tokens ) );
			default:
				return $value;
		}
	}

	/**
	* Format values for UI/theme consumption.
	*/
	protected function format_value_for_output( array $field, $value ) {
		switch ( $field['type'] ) {
			case 'image':
				$attachment_id = 0;

				if ( is_array( $value ) ) {
					if ( isset( $value['ID'] ) ) {
						$attachment_id = absint( $value['ID'] );
					} elseif ( isset( $value['id'] ) ) {
						$attachment_id = absint( $value['id'] );
					}
				} else {
					$attachment_id = absint( $value );
				}

				if ( ! $attachment_id ) {
					return null;
				}

				$attachment = wp_prepare_attachment_for_js( $attachment_id );

				if ( ! $attachment ) {
					return null;
				}

				$attachment['ID'] = $attachment['id'];
				// Convert sizes to ACF-like flat URL map for compatibility with legacy code
				if ( isset( $attachment['sizes'] ) && is_array( $attachment['sizes'] ) ) {
					$flat_sizes = [];
					foreach ( $attachment['sizes'] as $size_key => $size_item ) {
						if ( is_array( $size_item ) && isset( $size_item['url'] ) ) {
							$flat_sizes[ $size_key ] = $size_item['url'];
						}
					}
					$attachment['sizes'] = $flat_sizes;
				}

				return $attachment;
			case 'color':
				// Preserve color strings as provided (hex/rgb/rgba). If empty for specific fields, map to 'transparent'.
				$val = is_string( $value ) ? $value : '';
				if ( $val === '' && isset( $field['key'] ) && $field['key'] === 'header_background_color' ) {
					return 'transparent';
				}
				return $val;
			case 'select':
				// Convert stored IDs to objects/URLs where needed for frontend compatibility.
				$is_multiple = ! empty( $field['multiple'] );
				$callback = isset( $field['choices_callback'] ) ? $field['choices_callback'] : '';

				if ( $callback === 'listing_categories' ) {
					$ids = $is_multiple ? (array) $value : ( $value === null || $value === '' ? [] : [ $value ] );
					$ids = array_filter( array_map( 'absint', $ids ) );
					if ( empty( $ids ) ) {
						return [];
					}
					$terms = get_terms( [ 'taxonomy' => 'job_listing_category', 'hide_empty' => false, 'include' => $ids ] );
					return is_wp_error( $terms ) ? [] : array_values( $terms );
				}

				if ( $callback === 'listing_types' ) {
					$ids = $is_multiple ? (array) $value : ( $value === null || $value === '' ? [] : [ $value ] );
					$ids = array_filter( array_map( 'absint', $ids ) );

					if ( empty( $ids ) ) {
						return $is_multiple ? [] : null;
					}

					$posts = \get_posts( [
						'post_type'      => 'case27_listing_type',
						'post__in'       => $ids,
						'orderby'        => 'post__in',
						'posts_per_page' => count( $ids ),
						'suppress_filters' => false,
					] );

					if ( empty( $posts ) ) {
						return $is_multiple ? [] : null;
					}

					$indexed = [];
					foreach ( $posts as $post ) {
						$indexed[ (int) $post->ID ] = $post;
					}

					$ordered = [];
					foreach ( $ids as $id ) {
						if ( isset( $indexed[ $id ] ) ) {
							$ordered[] = $indexed[ $id ];
						}
					}

					return $is_multiple ? $ordered : ( $ordered ? $ordered[0] : null );
				}
				if ( $callback === 'pages' ) {
					$id = is_scalar( $value ) ? absint( $value ) : 0;
					return $id ? get_permalink( $id ) : '';
				}

				// Default: return value as-is.
				return $value;
			case 'ajax_select':
				// AJAX selects output format depends on the ajax_url type
				$is_multiple = ! empty( $field['multiple'] );
				$ajax_url = isset( $field['ajax_url'] ) ? $field['ajax_url'] : '';
				$ajax_params = isset( $field['ajax_params'] ) ? $field['ajax_params'] : [];

				// For page selects, return permalink
				if ( $ajax_url === 'mylisting_list_posts' && isset( $ajax_params['post_type'] ) && $ajax_params['post_type'] === 'page' ) {
					$id = is_scalar( $value ) ? absint( $value ) : 0;
					return $id ? get_permalink( $id ) : '';
				}

				// For term selects, return term objects
				if ( $ajax_url === 'mylisting_list_terms' && isset( $ajax_params['taxonomy'] ) ) {
					$ids = $is_multiple ? (array) $value : ( $value === null || $value === '' ? [] : [ $value ] );
					$ids = array_filter( array_map( 'absint', $ids ) );
					if ( empty( $ids ) ) {
						return [];
					}
					$terms = get_terms( [ 'taxonomy' => $ajax_params['taxonomy'], 'hide_empty' => false, 'include' => $ids ] );
					return is_wp_error( $terms ) ? [] : array_values( $terms );
				}

				// For listing type selects, return post objects
				if ( $ajax_url === 'mylisting_list_posts' && isset( $ajax_params['post_type'] ) && $ajax_params['post_type'] === 'case27_listing_type' ) {
					$ids = $is_multiple ? (array) $value : ( $value === null || $value === '' ? [] : [ $value ] );
					$ids = array_filter( array_map( 'absint', $ids ) );
					if ( empty( $ids ) ) {
						return $is_multiple ? [] : null;
					}
					$posts = \get_posts( [
						'post_type'      => 'case27_listing_type',
						'post__in'       => $ids,
						'orderby'        => 'post__in',
						'posts_per_page' => count( $ids ),
						'suppress_filters' => false,
					] );
					if ( empty( $posts ) ) {
						return $is_multiple ? [] : null;
					}
					$indexed = [];
					foreach ( $posts as $post ) {
						$indexed[ (int) $post->ID ] = $post;
					}
					$ordered = [];
					foreach ( $ids as $id ) {
						if ( isset( $indexed[ $id ] ) ) {
							$ordered[] = $indexed[ $id ];
						}
					}
					return $is_multiple ? $ordered : ( $ordered ? $ordered[0] : null );
				}

				// Default: return raw value
				return $value;
			case 'taxonomy_terms':
				// Multi-taxonomy term tokens: `taxonomy:term_id` -> return WP_Term objects.
				$tokens = is_array( $value ) ? $value : ( ( $value === null || $value === '' ) ? [] : [ $value ] );
				$tokens = array_values( array_filter( array_map( 'strval', array_filter( $tokens, 'is_scalar' ) ) ) );
				if ( empty( $tokens ) ) {
					return [];
				}

				$allowed_taxonomies = isset( $field['taxonomies'] ) && is_array( $field['taxonomies'] ) ? array_keys( $field['taxonomies'] ) : [];
				$default_taxonomy = isset( $field['default_taxonomy'] ) ? sanitize_key( $field['default_taxonomy'] ) : '';
				if ( ! $default_taxonomy && ! empty( $allowed_taxonomies ) ) {
					$default_taxonomy = (string) reset( $allowed_taxonomies );
				}

				$by_tax = [];
				$order  = [];
				$seen   = [];

				foreach ( $tokens as $token ) {
					$token = trim( $token );
					if ( $token === '' ) {
						continue;
					}

					$tax = '';
					$id  = 0;

					if ( strpos( $token, ':' ) !== false ) {
						$parts = explode( ':', $token, 2 );
						$tax = sanitize_key( $parts[0] );
						$id  = absint( $parts[1] );
					} else {
						// Back-compat: accept raw IDs/slugs under default taxonomy.
						$tax = $default_taxonomy;
						if ( is_numeric( $token ) ) {
							$id = absint( $token );
						} else {
							$slug = sanitize_title( $token );
							if ( $tax && $slug ) {
								$term = get_term_by( 'slug', $slug, $tax );
								$id = $term && ! is_wp_error( $term ) ? absint( $term->term_id ) : 0;
							}
						}
					}

					if ( ! $tax || ! $id ) {
						continue;
					}

					if ( ! taxonomy_exists( $tax ) ) {
						continue;
					}

					if ( ! empty( $allowed_taxonomies ) && ! in_array( $tax, $allowed_taxonomies, true ) ) {
						continue;
					}

					$key = "{$tax}:{$id}";
					if ( isset( $seen[ $key ] ) ) {
						continue;
					}
					$seen[ $key ] = true;

					$by_tax[ $tax ][] = $id;
					$order[] = $key;
				}

				if ( empty( $order ) ) {
					return [];
				}

				$indexed = [];
				foreach ( $by_tax as $taxonomy => $ids ) {
					$terms = get_terms( [
						'taxonomy'   => $taxonomy,
						'hide_empty' => false,
						'include'    => $ids,
						'orderby'    => 'include',
						'number'     => count( $ids ),
					] );

					if ( empty( $terms ) || is_wp_error( $terms ) ) {
						continue;
					}

					foreach ( $terms as $term ) {
						$indexed[ "{$taxonomy}:{$term->term_id}" ] = $term;
					}
				}

				$terms = [];
				foreach ( $order as $key ) {
					if ( isset( $indexed[ $key ] ) ) {
						$terms[] = $indexed[ $key ];
					}
				}

				return $terms;
			case 'number':
				if ( ! is_numeric( $value ) ) { return null; }
				return $this->number_allows_float( $field ) ? (float) $value : (int) $value;
			case 'text':
				return is_string( $value ) ? $value : ( $value === null ? null : strval( $value ) );
			case 'code':
				// Raw output for CSS/JS/HTML code.
				return is_string( $value ) ? $value : ( $value === null ? '' : strval( $value ) );
			case 'toggle':
				return (bool) $value;
			default:
				return $value;
		}
	}

	/**
	* Sanitize and normalize stored values.
	*/
	protected function sanitize_value( array $field, $value ) {
		switch ( $field['type'] ) {
			case 'image':
				if ( is_array( $value ) ) {
					if ( isset( $value['ID'] ) ) {
						$value = $value['ID'];
					} elseif ( isset( $value['id'] ) ) {
						$value = $value['id'];
					}
				}

				$attachment_id = absint( $value );

				return $attachment_id > 0 ? $attachment_id : 0;
			case 'color':
				$val = is_string( $value ) ? trim( $value ) : '';
				// Allow clearing the color to an empty string (treated as transparent/none by UI).
				if ( $val === '' ) {
					return '';
				}
				// Accept rgba()/rgb() values as-is if they appear valid.
				if ( preg_match( '/^rgba?\([\d\s.,%]+\)$/i', $val ) ) {
					return $val;
				}
				$hex = sanitize_hex_color( $val );
				if ( $hex ) {
					return $hex;
				}
				$default = $this->get_default_value( $field );
				return is_string( $default ) ? $default : '';
			case 'select':
				$choices = isset( $field['choices'] ) && is_array( $field['choices'] ) ? $field['choices'] : [];
				$is_multiple = ! empty( $field['multiple'] );

				if ( $is_multiple ) {
					$vals = is_array( $value ) ? $value : ( ( $value === null || $value === '' ) ? [] : [ $value ] );
					$vals = array_map( 'strval', array_filter( $vals, 'is_scalar' ) );
					$valid = [];
					foreach ( $vals as $v ) {
						if ( isset( $choices[ $v ] ) ) {
							$valid[] = $v;
						}
					}
					return $valid;
				} else {
					$val = is_scalar( $value ) ? (string) $value : '';
					if ( isset( $choices[ $val ] ) ) {
						return $val;
					}
					$default = $this->get_default_value( $field );
					if ( isset( $choices[ $default ] ) ) {
						return $default;
					}
					return count( $choices ) ? (string) array_key_first( $choices ) : '';
				}
			case 'ajax_select':
				// AJAX selects store IDs directly (no choices validation needed)
				$is_multiple = ! empty( $field['multiple'] );
				if ( $is_multiple ) {
					$vals = is_array( $value ) ? $value : ( ( $value === null || $value === '' ) ? [] : [ $value ] );
					return array_values( array_filter( array_map( 'strval', array_filter( $vals, 'is_scalar' ) ) ) );
				} else {
					return is_scalar( $value ) && $value !== '' ? (string) $value : '';
				}
			case 'taxonomy_terms':
				$vals = is_array( $value ) ? $value : ( ( $value === null || $value === '' ) ? [] : [ $value ] );
				$vals = array_filter( $vals, 'is_scalar' );

				$allowed_taxonomies = isset( $field['taxonomies'] ) && is_array( $field['taxonomies'] ) ? array_keys( $field['taxonomies'] ) : [];
				$default_taxonomy = isset( $field['default_taxonomy'] ) ? sanitize_key( $field['default_taxonomy'] ) : '';
				if ( ! $default_taxonomy && ! empty( $allowed_taxonomies ) ) {
					$default_taxonomy = (string) reset( $allowed_taxonomies );
				}

				$valid = [];
				foreach ( $vals as $raw_token ) {
					$raw_token = trim( (string) $raw_token );
					if ( $raw_token === '' ) {
						continue;
					}

					$taxonomy = '';
					$id = 0;

					// Preferred format: taxonomy:term_id.
					if ( strpos( $raw_token, ':' ) !== false ) {
						$parts = explode( ':', $raw_token, 2 );
						$taxonomy = sanitize_key( $parts[0] );
						$id = absint( $parts[1] );
					} else {
						// Legacy/back-compat: accept raw IDs or slugs under the default taxonomy.
						$taxonomy = $default_taxonomy;
						if ( is_numeric( $raw_token ) ) {
							$id = absint( $raw_token );
						} else {
							$slug = sanitize_title( $raw_token );
							if ( $taxonomy && $slug ) {
								$term = get_term_by( 'slug', $slug, $taxonomy );
								$id = $term && ! is_wp_error( $term ) ? absint( $term->term_id ) : 0;
							}
						}
					}

					if ( ! $taxonomy || ! $id ) {
						continue;
					}

					if ( ! taxonomy_exists( $taxonomy ) ) {
						continue;
					}

					if ( ! empty( $allowed_taxonomies ) && ! in_array( $taxonomy, $allowed_taxonomies, true ) ) {
						continue;
					}

					$valid[] = "{$taxonomy}:{$id}";
				}

				return array_values( array_unique( $valid ) );
			case 'toggle':
				return (bool) $value;
			case 'number':
				if ( ! is_numeric( $value ) ) { return 0; }
				$allows_float = $this->number_allows_float( $field );
				$numeric = $allows_float ? (float) $value : (int) $value;
				if ( isset( $field['min'] ) && is_numeric( $field['min'] ) ) {
					$min = $allows_float ? (float) $field['min'] : (int) $field['min'];
					$numeric = max( $numeric, $min );
				}
				if ( isset( $field['max'] ) && is_numeric( $field['max'] ) ) {
					$max = $allows_float ? (float) $field['max'] : (int) $field['max'];
					$numeric = min( $numeric, $max );
				}
				return $numeric;
			case 'text':
				if ( ! empty( $field['allow_html'] ) ) {
					return wp_kses_post( is_string( $value ) ? $value : strval( $value ) );
				}
				return is_string( $value ) ? sanitize_text_field( $value ) : sanitize_text_field( strval( $value ) );
			case 'code':
				// Do not sanitize to preserve raw CSS/JS/HTML. Responsibility is on the admin.
				return is_string( $value ) ? $value : strval( $value );
			default:
				return $value;
		}
	}

	/**
	* Retrieve a field default value.
	*/
	protected function get_default_value( array $field ) {
		if ( array_key_exists( 'default', $field ) ) {
			return $field['default'];
		}

		switch ( $field['type'] ) {
			case 'select':
				$choices = isset( $field['choices'] ) && is_array( $field['choices'] ) ? $field['choices'] : [];
				return count( $choices ) ? (string) array_key_first( $choices ) : null;
			case 'taxonomy_terms':
				return [];
			case 'toggle':
				return false;
			case 'number':
				return null;
			case 'text':
				return '';
			case 'code':
				return '';
			default:
				return null;
		}
	}

	/**
	 * Determine whether a number field should allow floats.
	 */
	protected function number_allows_float( array $field ) {
		// Explicit override
		if ( isset( $field['allow_float'] ) ) {
			return (bool) $field['allow_float'];
		}
		// Heuristics based on step/min/max
		if ( isset( $field['step'] ) ) {
			$step = $field['step'];
			if ( is_string( $step ) && strpos( $step, '.' ) !== false ) { return true; }
			if ( is_numeric( $step ) && ( (float) $step !== (float) intval( $step ) ) ) { return true; }
		}
		foreach ( [ 'min', 'max' ] as $k ) {
			if ( isset( $field[ $k ] ) ) {
				$val = $field[ $k ];
				if ( is_string( $val ) && strpos( $val, '.' ) !== false ) { return true; }
				if ( is_numeric( $val ) && ( (float) $val !== (float) intval( $val ) ) ) { return true; }
			}
		}
		return false;
	}

	/**
	* Keep legacy ACF option keys in sync for backwards compatibility.
	*/
	protected function update_legacy_option( array $field, $value ) {
		if ( empty( $field['key'] ) ) {
			return;
		}

		$option_key = 'options_' . $field['key'];

		switch ( $field['type'] ) {
			case 'image':
				$stored_value = absint( $value );
				update_option( $option_key, $stored_value > 0 ? $stored_value : '' );
				break;
			case 'toggle':
				update_option( $option_key, $value ? 1 : 0 );
				break;
			case 'select':
				// Keep legacy ACF option compatible. For 'pages' selects, store the page ID.
				$callback = isset( $field['choices_callback'] ) ? $field['choices_callback'] : '';
				if ( $callback === 'pages' ) {
					$pid = is_scalar( $value ) ? absint( $value ) : 0;
					update_option( $option_key, $pid > 0 ? $pid : '' );
					break;
				}
				update_option( $option_key, $value );
				break;
			case 'ajax_select':
				// AJAX selects: store IDs directly for legacy compatibility
				$ajax_url = isset( $field['ajax_url'] ) ? $field['ajax_url'] : '';
				$ajax_params = isset( $field['ajax_params'] ) ? $field['ajax_params'] : [];

				// For page selects, store the page ID.
				if ( $ajax_url === 'mylisting_list_posts' && isset( $ajax_params['post_type'] ) && $ajax_params['post_type'] === 'page' ) {
					$pid = is_scalar( $value ) ? absint( $value ) : 0;
					update_option( $option_key, $pid > 0 ? $pid : '' );
					break;
				}
				update_option( $option_key, $value );
				break;
			case 'taxonomy_terms':
				// Keep legacy option compatible by storing only IDs of the default taxonomy.
				$default_taxonomy = isset( $field['default_taxonomy'] ) ? sanitize_key( $field['default_taxonomy'] ) : '';
				$tokens = is_array( $value ) ? $value : ( ( $value === null || $value === '' ) ? [] : [ $value ] );
				$tokens = array_values( array_filter( array_map( 'strval', array_filter( $tokens, 'is_scalar' ) ) ) );

				$ids = [];
				foreach ( $tokens as $token ) {
					$token = trim( $token );
					if ( $token === '' ) {
						continue;
					}

					$taxonomy = $default_taxonomy;
					$id = 0;

					if ( strpos( $token, ':' ) !== false ) {
						$parts = explode( ':', $token, 2 );
						$taxonomy = sanitize_key( $parts[0] );
						$id = absint( $parts[1] );
					} elseif ( is_numeric( $token ) ) {
						$id = absint( $token );
					} else {
						$slug = sanitize_title( $token );
						if ( $taxonomy && $slug ) {
							$term = get_term_by( 'slug', $slug, $taxonomy );
							$id = $term && ! is_wp_error( $term ) ? absint( $term->term_id ) : 0;
						}
					}

					if ( ! $taxonomy || ! $id ) {
						continue;
					}

					if ( $default_taxonomy && $taxonomy !== $default_taxonomy ) {
						continue;
					}

					$ids[] = $id;
				}

				update_option( $option_key, array_values( array_unique( $ids ) ) );
				break;
			default:
				update_option( $option_key, $value );
				break;
		}

		if ( ! empty( $field['field_key'] ) ) {
			update_option( '_' . $option_key, $field['field_key'] );
		}
	}
}
