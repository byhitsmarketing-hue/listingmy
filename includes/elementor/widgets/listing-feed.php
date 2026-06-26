<?php

namespace MyListing\Elementor\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Listing_Feed extends \Elementor\Widget_Base {

	public function get_name() {
		return 'case27-listing-feed-widget';
	}

	public function get_title() {
		return __( '<strong>27</strong> > Listing Feed', 'my-listing' );
	}

	public function get_icon() {
		return 'eicon-posts-grid';
	}

	public function get_script_depends() {
		if (\MyListing\is_preview_mode()) {
			return ['mylisting-listing-feed', 'mylisting-background-carousel', 'mylisting-listing-feed-carousel'];
		}

		return [''];
	}

	protected function register_controls() {
		$this->start_controls_section( 'the_listing_feed', [
			'label' => __( 'Listing Feed', 'my-listing' ),
		] );

		$this->add_control( 'the_template', [
			'label' => __( 'Template', 'my-listing' ),
			'type' => \Elementor\Controls_Manager::SELECT,
			'default' => 'grid',
			'options' => [
				'grid' => __( 'Grid', 'my-listing' ),
				'carousel' => __( 'Carousel', 'my-listing' ),
			],
		] );

		$this->add_control( 'owl_customize', [
			'label' => __( 'Customize carousel?', 'my-listing' ),
			'description' => __( 'Customize carousel options', 'my-listing' ),
			'type' => \Elementor\Controls_Manager::SWITCHER,
			'default' => '',
			'label_on' => __( 'Yes', 'my-listing' ),
			'label_off' => __( 'No', 'my-listing' ),
			'return_value' => 'yes',
			'condition' => ['the_template' => 'carousel'],
		] );

		$this->add_control( 'owl_nav', [
			'label' => _x( 'Switch navigation to dots?', 'Elementor > Listing Feed > Widget Settings', 'my-listing' ),
			'type' => \Elementor\Controls_Manager::SWITCHER,
			'default' => '',
			'label_on' => __( 'Yes', 'my-listing' ),
			'label_off' => __( 'No', 'my-listing' ),
			'return_value' => 'dots',
			'condition' => [
				'owl_customize' => 'yes',
				'the_template' => 'carousel',
			],
		] );

		$this->add_control( 'invert_nav_color', [
			'label' => __( 'Invert nav color?', 'my-listing' ),
			'description' => __( 'Use this option on dark section backgrounds for better visibility.', 'my-listing' ),
			'type' => \Elementor\Controls_Manager::SWITCHER,
			'default' => '',
			'label_on' => __( 'Yes', 'my-listing' ),
			'label_off' => __( 'No', 'my-listing' ),
			'return_value' => 'yes',
			'condition' => [
				'owl_customize' => 'yes',
				'the_template' => 'carousel',
			],
		] );

		$this->add_control( 'owl_autoplay', [
			'label' => _x( 'Autoplay?', 'Elementor > Listing Feed > Widget Settings', 'my-listing' ),
			'type' => \Elementor\Controls_Manager::SWITCHER,
			'default' => '',
			'label_on' => __( 'Yes', 'my-listing' ),
			'label_off' => __( 'No', 'my-listing' ),
			'return_value' => 'yes',
			'condition' => [
				'owl_customize' => 'yes',
				'the_template' => 'carousel',
			],
		] );

		$this->add_control( 'owl_speed', [
			'label'   => __( 'Carousel speed (s)', 'my-listing' ),
			'type'    => \Elementor\Controls_Manager::NUMBER,
			'default' => 2.5,
			'min' => 0,
			'max' => 10,
			'step' => 0.1,
			'condition' => [
				'owl_customize' => 'yes',
				'the_template' => 'carousel',
			],
		] );

		$this->add_control( 'owl_loop', [
			'label' => _x( 'Loop items?', 'Elementor > Listing Feed > Widget Settings', 'my-listing' ),
			'type' => \Elementor\Controls_Manager::SWITCHER,
			'default' => 'yes',
			'label_on' => __( 'Yes', 'my-listing' ),
			'label_off' => __( 'No', 'my-listing' ),
			'return_value' => 'yes',
			'condition' => [
				'owl_customize' => 'yes',
				'the_template' => 'carousel',
			],
		] );

		$this->add_control( 'owl_items_desktop', [
			'label'   => __( 'Number of visible listings (Desktop)', 'my-listing' ),
			'type'    => \Elementor\Controls_Manager::NUMBER,
			'default' => 3,
			'condition' => [
				'owl_customize' => 'yes',
				'the_template' => 'carousel',
			],
		] );

		$this->add_control( 'owl_items_tablet', [
			'label'   => __( 'Number of visible listings (Tablet)', 'my-listing' ),
			'type'    => \Elementor\Controls_Manager::NUMBER,
			'default' => 2,
			'condition' => [
				'owl_customize' => 'yes',
				'the_template' => 'carousel',
			],
		] );

		$this->add_control( 'owl_items_mobile', [
			'label'   => __( 'Number of visible listings (Mobile)', 'my-listing' ),
			'type'    => \Elementor\Controls_Manager::NUMBER,
			'default' => 1,
			'condition' => [
				'owl_customize' => 'yes',
				'the_template' => 'carousel',
			],
		] );

		$this->add_control( 'show_blank_slides', [
			'label' => __( 'Show blank slides?', 'my-listing' ),
			'description' => __( 'Fill carousel with blank slides when there are 3 or fewer listings to maintain visual consistency.', 'my-listing' ),
			'type' => \Elementor\Controls_Manager::SWITCHER,
			'default' => 'yes',
			'label_on' => __( 'Yes', 'my-listing' ),
			'label_off' => __( 'No', 'my-listing' ),
			'return_value' => 'yes',
			'condition' => ['the_template' => 'carousel'],
		] );

		$this->add_control( 'posts_per_page', [
			'label'   => __( 'Listings per page', 'my-listing' ),
			'type'    => \Elementor\Controls_Manager::NUMBER,
			'default' => 6,
		] );

		$this->add_control( 'enable_pagination', [
			'label' => __( 'Enable pagination?', 'my-listing' ),
			'description' => __( 'Enable pagination for listing feed widget.', 'my-listing' ),
			'type' => \Elementor\Controls_Manager::SWITCHER,
			'default' => '',
			'label_on' => __( 'Yes', 'my-listing' ),
			'label_off' => __( 'No', 'my-listing' ),
			'return_value' => 'yes',
			'condition' => [ 'the_template' => 'grid' ]
		] );

		$this->add_control( 'lf_pagination', [
			'label' => __( 'Pagination', 'my-listing' ),
			'type' => \Elementor\Controls_Manager::SELECT2,
			'default' => 'prev-next',
			'options' => [
				'pages' => __( 'Pages', 'my-listing' ),
				'load-more' => __( 'Load more', 'my-listing' ),
				'prev-next' => __( 'Prev/Next', 'my-listing' ),
			],
			'condition' => [ 
				'the_template' => 'grid',
				'enable_pagination' => 'yes',
			]
		] );

		$this->add_control( '27_disable_isotope', [
			'label' => __( 'Disable isotope masonry?', 'my-listing' ),
			'description' => __( 'Disabling isotope will improve loading speed.', 'my-listing' ),
			'type' => \Elementor\Controls_Manager::SWITCHER,
			'default' => '',
			'label_on' => __( 'Yes', 'my-listing' ),
			'label_off' => __( 'No', 'my-listing' ),
			'return_value' => 'yes',
			'condition' => [ 'the_template' => 'grid' ]
		] );

		$this->add_control( 'query_method', [
			'label' => _x( 'Find listings using:', 'Elementor > Listing Feed > Widget Settings', 'my-listing' ),
			'type' => \Elementor\Controls_Manager::SELECT,
			'default' => 'filters',
			'options' => [
				'filters' => _x( 'Filters', 'Elementor > Listing Feed > Widget Settings', 'my-listing' ),
				'query_string' => _x( 'Explore page query URL', 'Elementor > Listing Feed > Widget Settings', 'my-listing' ),
			],
		] );

		$this->add_control( 'select_authors', [
			'label' => __( 'Filter by Authors', 'my-listing' ),
			'type' => \Elementor\Controls_Manager::TEXT,
			'description' => __( 'Enter user IDs separated by commas.', 'my-listing' ),
			'label_block' => true,
			'condition' => ['query_method' => 'filters'],
		]);

		$this->add_control( 'select_categories', [
			'label' => __( 'Filter by Categories', 'my-listing' ),
			'type' => \Elementor\Controls_Manager::SELECT2,
			'options' => is_admin()
				? c27()->get_terms_dropdown_array( [ 'taxonomy' => 'job_listing_category', 'hide_empty' => false ] )
				: [],
			'multiple' => true,
			'label_block' => true,
			'condition' => ['query_method' => 'filters'],
		] );

		$this->add_control( 'select_regions', [
			'label' => __( 'Filter by Regions', 'my-listing' ),
			'type' => \Elementor\Controls_Manager::SELECT2,
			'options' => is_admin()
				? c27()->get_terms_dropdown_array( [ 'taxonomy' => 'region', 'hide_empty' => false ] )
				: [],
			'multiple' => true,
			'label_block' => true,
			'condition' => ['query_method' => 'filters'],
		] );

		$this->add_control( 'select_tags', [
			'label' => __( 'Filter by Tags', 'my-listing' ),
			'type' => \Elementor\Controls_Manager::SELECT2,
			'options' => is_admin()
				? c27()->get_terms_dropdown_array( [ 'taxonomy' => 'case27_job_listing_tags', 'hide_empty' => false ] )
				: [],
			'multiple' => true,
			'label_block' => true,
			'condition' => ['query_method' => 'filters'],
		] );

		$taxonomy_list = mylisting_custom_taxonomies();

		foreach ( $taxonomy_list as $slug => $label ) {
			$this->add_control( 'select_'.$slug, [
				'label' => sprintf( '%s %s', __( 'Filter by', 'my-listing' ), $label ),
				'type' => \Elementor\Controls_Manager::SELECT2,
				'options' => is_admin()
					? c27()->get_terms_dropdown_array( [ 'taxonomy' => $slug, 'hide_empty' => false ] )
					: [],
				'multiple' => true,
				'label_block' => true,
				'condition' => ['query_method' => 'filters'],
			] );
		}

		$this->add_control( 'select_listing_types', [
			'label' => __( 'Filter by Listing Type(s).', 'my-listing' ),
			'type' => 'mylisting-posts-dropdown',
			'multiple' => true,
			'label_block' => true,
			'post_type' => 'case27_listing_type',
			'post_key' => 'slug',
			'condition' => ['query_method' => 'filters'],
		] );

		$this->add_control( 'priority_levels', [
			'label' => __( 'Filter by Priority', 'my-listing' ),
			'description' => __( 'Leave blank to include all priority levels', 'my-listing' ),
			'type' => \Elementor\Controls_Manager::SELECT2,
			'options' => [
				'normal' => 'Normal',
				'featured' => 'Featured',
				'promoted' => 'Promoted',
				'custom' => 'Custom',
			],
			'multiple' => true,
			'label_block' => true,
			'condition' => ['query_method' => 'filters'],
		] );

		$listings = is_admin()
			? \MyListing\get_posts_dropdown( 'job_listing' )
			: [];

		$repeater = new \Elementor\Repeater();

		$repeater->add_control(
			'listing_id',
			[
				'label' => is_array( $listings )
					? __( 'Select listing', 'my-listing' )
					: _x( 'Enter listing ID', 'Elementor/Listing Feed: Select a listing', 'my-listing' ),
				'type' => is_array( $listings )
					? \Elementor\Controls_Manager::SELECT2
					: \Elementor\Controls_Manager::TEXT,
				'options' => $listings,
				'default' => '',
				'label_block' => true,
			]
		);

		$this->add_control( 'select_listings', [
			'label' => __( 'Or select a list of listings.', 'my-listing' ),
			'type' => \Elementor\Controls_Manager::REPEATER,
			'fields' => $repeater->get_controls(),
			'title_field' => 'Listing ID: {{{ listing_id }}}',
			'condition' => ['query_method' => 'filters'],
		] );

		$this->add_control( 'order_by', [
			'label' => __( 'Order by', 'my-listing' ),
			'type' => \Elementor\Controls_Manager::SELECT,
			'default' => 'date',
			'options' => [
				'title' => __( 'A-Z', 'my-listing' ),
				'date' => __( 'Date', 'my-listing' ),
				'post__in' => __( 'Included order', 'my-listing' ),
				'_case27_average_rating' => __( 'Rating', 'my-listing' ),
				'rand' => __( 'Random', 'my-listing' ),
				'modified' => __( 'Last modified date', 'my-listing' ),
			],
			'condition' => ['query_method' => 'filters'],
		] );

		$this->add_control( 'order', [
			'label' => __( 'Order', 'my-listing' ),
			'type' => \Elementor\Controls_Manager::SELECT,
			'default' => 'DESC',
			'options' => [
				'ASC' => __( 'Ascending', 'my-listing' ),
				'DESC' => __( 'Descending', 'my-listing' ),
			],
			'condition' => ['query_method' => 'filters'],
		] );

		$this->add_control( 'behavior', [
			'label' => __( 'Order by priority first?', 'my-listing' ),
			'description' => __( 'If selected, listings will first be ordered based on their priority, then based on the "Order By" setting above.', 'my-listing' ),
			'type' => \Elementor\Controls_Manager::SWITCHER,
			'default' => 'yes',
			'label_on' => __( 'Yes', 'my-listing' ),
			'label_off' => __( 'No', 'my-listing' ),
			'return_value' => 'yes',
			'condition' => ['query_method' => 'filters'],
		] );

		$this->add_control( 'query_string', [
			'label' => __( 'Paste the URL here', 'my-listing' ),
			'type' => \Elementor\Controls_Manager::TEXT,
			'default' => '',
			'label_block' => true,
			'placeholder' => home_url( '/explore?type=events&sort=latest' ),
			'description' => 'In Explore page, you can filter results the way you want, grab the generated URL from the address bar, and paste it here, to get that exact list of listings.',
			'condition' => ['query_method' => 'query_string'],
		] );

		$this->add_control( 'show_promoted_badge', [
			'label' => __( 'Show badge for featured/promoted listings?', 'my-listing' ),
			'type' => \Elementor\Controls_Manager::SWITCHER,
			'default' => 'yes',
			'label_on' => __( 'Yes', 'my-listing' ),
			'label_off' => __( 'No', 'my-listing' ),
			'return_value' => 'yes',
			'condition' => ['query_method' => 'filters'],
		] );

		$this->add_control( 'cache_for', [
			'label' => __( 'Cache results for (in minutes)', 'my-listing' ),
			'description' => 'Set how long the listing feed results cache should be used before it is regenerated. Set to "0" to disable.',
			'type' => \Elementor\Controls_Manager::NUMBER,
			'min' => 0,
			'default' => 720, // 12 hours
		] );

		\MyListing\Elementor\apply_column_count_controls(
			$this,
			'column_count',
			__( 'Column count', 'my-listing' ),
			[
				'heading' => ['condition' => ['the_template' => ['grid']]],
				'general' => [
					'condition' => ['the_template' => ['grid']],
					'min' => 1,
					'max' => 4,
				],
				'lg' => ['default' => 3], 'md' => ['default' => 3],
				'sm' => ['default' => 2], 'xs' => ['default' => 1],
			]
		);

		$this->end_controls_section();
	}

	protected function render( $instance = [] ) {

		$taxonomy_list = mylisting_custom_taxonomies();

		$selected_terms_for_taxonomies = [];

		foreach ( $taxonomy_list as $slug => $label ) {
			$selected_terms = $this->get_settings('select_' . $slug);

			if ( !empty($selected_terms) ) {
				$selected_terms_for_taxonomies[$slug] = $selected_terms;
			}
		}


		c27()->get_section( 'listing-feed', [
			'widget_id' => $this->get_id(),
			'req_type' => 'server_req',
			'template' => $this->get_settings('the_template'),
			'query' => [
				'method' => $this->get_settings('query_method'),
				'string' => $this->get_settings('query_string'),
				'cache_for' => $this->get_settings('cache_for'),
			],
			'pagination' => [
				'enabled' => $this->get_settings('enable_pagination') === 'yes',
				'type' => $this->get_settings('lf_pagination'),
			],
			'disable_isotope' => $this->get_settings('27_disable_isotope') === 'yes',
			'listing_types' => $this->get_settings('select_listing_types'),
			'nav_mode' => $this->get_settings('owl_nav'),
			'owl_autoplay' => $this->get_settings('owl_autoplay'),
			'owl_loop' => $this->get_settings('owl_loop'),
			'owl_speed' => $this->get_settings('owl_speed'),
			'owl_d' => $this->get_settings('owl_items_desktop'),
			'owl_t' => $this->get_settings('owl_items_tablet'),
			'owl_m' => $this->get_settings('owl_items_mobile'),
			'show_blank_slides' => $this->get_settings('show_blank_slides'),
			'invert_nav' => $this->get_settings('invert_nav_color') === 'yes',
			'is_edit_mode' => \Elementor\Plugin::$instance->editor->is_edit_mode(),
			'hide_priority' => $this->get_settings('show_promoted_badge') !== 'yes',
			'listing_wrap' => sprintf(
				'col-lg-%1$d col-md-%2$d col-sm-%3$d col-xs-%4$d grid-item',
				12 / absint( $this->get_settings('column_count__lg') ),
				12 / absint( $this->get_settings('column_count__md') ),
				12 / absint( $this->get_settings('column_count__sm') ),
				12 / $this->get_settings('column_count__xs')
			),
			// query parameters
			'filters' => array_merge([
				'page' => null,
				'posts_per_page' => $this->get_settings('posts_per_page'),
				'order_by' => $this->get_settings('order_by'),
				'order' => $this->get_settings('order'),
				'priority' => $this->get_settings('priority_levels'),
				'behavior' => $this->get_settings('behavior'),
				'authors' => $this->get_settings('select_authors') ?: [],
				'categories' => $this->get_settings('select_categories'),
				'regions' => $this->get_settings('select_regions'),
				'tags' => $this->get_settings('select_tags'),
				'custom_taxonomies' => mylisting_custom_taxonomies(),
				'listings' => $this->get_settings('select_listings'),
			], $selected_terms_for_taxonomies),
		] );

		// enqueue owl carousel if needed
		if ( empty($listing_types) ) {
			wp_enqueue_script('mylisting-owl');
			wp_enqueue_script('mylisting-background-carousel');
		} else if (count($listing_types) === 1) {
			if ( $listing_types_obj = ( get_page_by_path( $listing_types[0], OBJECT, 'case27_listing_type' ) ) ) {
				$type = new \MyListing\Src\Listing_Type( $listing_types_obj );
				if ( $type->get_preview_type() === 'gallery' ) {
					wp_enqueue_script('mylisting-owl');
					wp_enqueue_script('mylisting-background-carousel');
				}
			}
		} else {
			foreach ($listing_types as $type) {
				if ( $listing_types_obj = ( get_page_by_path( $type, OBJECT, 'case27_listing_type' ) ) ) {
					$type = new \MyListing\Src\Listing_Type( $listing_types_obj );
					if ( $type->get_preview_type() === 'gallery' ) {
						wp_enqueue_script('mylisting-owl');
						wp_enqueue_script('mylisting-background-carousel');
					}
				}
			}
		}
	}
}
