<?php

namespace MyListing\Elementor\Widgets;

use Elementor\Group_Control_Typography;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Listing_Categories extends \Elementor\Widget_Base {

	public function get_name() {
		return 'case27-listing-categories-widget';
	}

	public function get_title() {
		return __( '<strong>27</strong> > Listing Terms', 'my-listing' );
	}

	public function get_icon() {
		return 'eicon-carousel';
	}

	protected function register_controls() {
		$custom_taxonomies = mylisting_custom_taxonomies();

		$this->start_controls_section( 'the_listing_categories', [
			'label' => __( 'Listing Terms', 'my-listing' ),
		] );

		$this->add_control( 'taxonomy', [
			'label'   => __( 'Taxonomy', 'my-listing' ),
			'type'    => \Elementor\Controls_Manager::SELECT,
			'default' => 'job_listing_category',
			'options' => array_merge( [
				'job_listing_category' => __( 'Categories', 'my-listing' ),
				'region' => __( 'Regions', 'my-listing' ),
				'case27_job_listing_tags' => __( 'Tags', 'my-listing' ),
				'listing_types' => __( 'Listing Types', 'my-listing' ),
			], $custom_taxonomies ),
		] );

		$this->add_control( 'select_categories', [
			'label' => __( 'Select Terms', 'my-listing' ),
			'type' => \Elementor\Controls_Manager::REPEATER,
			'fields' => [ [
				'name' => 'category_id',
				'label' => __( 'Select Category', 'my-listing' ),
				'type' => \Elementor\Controls_Manager::SELECT2,
				'options' => is_admin()
					? c27()->get_terms_dropdown_array( [ 'taxonomy' => 'job_listing_category', 'hide_empty' => false ] )
					: [],
				'label_block' => true,
				'default' => '',
			] ],
			'title_field' => 'Item #{{{ category_id }}}',
			'condition' => [ 'taxonomy' => 'job_listing_category' ],
		] );

		$this->add_control( 'select_regions', [
			'label' => __( 'Select Regions', 'my-listing' ),
			'type' => \Elementor\Controls_Manager::REPEATER,
			'fields' => [
				[
					'name' => 'category_id',
					'label' => __( 'Select Region', 'my-listing' ),
					'type' => \Elementor\Controls_Manager::SELECT2,
					'options' => is_admin()
						? c27()->get_terms_dropdown_array( [ 'taxonomy' => 'region', 'hide_empty' => false ] )
						: [],
					'label_block' => true,
					'default' => '',
				]
			],
			'title_field' => 'Item #{{{ category_id }}}',
			'condition' => [ 'taxonomy' => 'region' ],
		] );

		$this->add_control( 'select_tags', [
			'label' => __( 'Select Tags', 'my-listing' ),
			'type' => \Elementor\Controls_Manager::REPEATER,
			'fields' => [ [
				'name' => 'category_id',
				'label' => __( 'Select Tag', 'my-listing' ),
				'type' => \Elementor\Controls_Manager::SELECT2,
				'options' => is_admin()
					? c27()->get_terms_dropdown_array( [ 'taxonomy' => 'case27_job_listing_tags', 'hide_empty' => false ] )
					: [],
				'label_block' => true,
				'default' => '',
			] ],
			'title_field' => 'Item #{{{ category_id }}}',
			'condition' => [ 'taxonomy' => 'case27_job_listing_tags' ],
		] );

		$listing_types = is_admin()
			? \MyListing\get_posts_dropdown( 'case27_listing_type' )
			: [];

		$this->add_control( 'select_listing_types', [
			'label' => __( 'Select Listing Types', 'my-listing' ),
			'type' => \Elementor\Controls_Manager::REPEATER,
			'fields' => [ [
				'name' => 'category_id',
				'label' => __( 'Select Listing Type', 'my-listing' ),
				'type' => is_array( $listing_types )
					? \Elementor\Controls_Manager::SELECT2
					: \Elementor\Controls_Manager::TEXT,
				'options' => $listing_types,
				'label_block' => true,
				'default' => '',
			] ],
			'condition' => [ 'taxonomy' => 'listing_types' ],
		] );

		// Add controls for custom taxonomies
		if ( $custom_taxonomies ) {
			foreach ( $custom_taxonomies as $slug => $label ) {
				$this->add_control( 'select_'.$slug, [
					'label' => sprintf( _x( 'Select %s', 'custom taxonomy', 'my-listing' ), $label ),
					'type' => \Elementor\Controls_Manager::REPEATER,
					'fields' => [
						[
							'name' => 'category_id',
							'label' => __( 'Select Item', 'my-listing' ),
							'type' => \Elementor\Controls_Manager::SELECT2,
							'options' => is_admin()
								? c27()->get_terms_dropdown_array( [ 'taxonomy' => $slug, 'hide_empty' => false ] )
								: [],
							'label_block' => true,
							'default' => '',
						]
					],
					'title_field' => 'Item #{{{ category_id }}}',
					'condition' => [ 'taxonomy' => $slug ],
				] );
			}
		}

		$this->add_control( 'display_template', [
			'label' => __( 'Template', 'my-listing' ),
			'type' => \Elementor\Controls_Manager::SELECT,
			'options' => [
				'template_1' => __( 'Default', 'my-listing' ),
				'template_4' => __( 'Alternate', 'my-listing' ),
				'template_2' => __( 'Cards', 'my-listing' ),
				'template_3' => __( 'Cards Alternate', 'my-listing' ),
				'template_parent_child' => __( 'Hierarchy', 'my-listing' ),
			],
			'condition' => [ 'taxonomy!' => 'listing_types' ],
		] );

		$this->add_control( 'display_template_listing_types', [
			'label' => __( 'Template', 'my-listing' ),
			'type' => \Elementor\Controls_Manager::SELECT,
			'options' => [
				'template_1' => __( 'Default', 'my-listing' ),
				'template_4' => __( 'Alternate', 'my-listing' ),
				'template_2' => __( 'Cards', 'my-listing' ),
				'template_3' => __( 'Cards Alternate', 'my-listing' ),
			],
			'default' => 'template_1',
			'condition' => [ 'taxonomy' => 'listing_types' ],
		] );

		$this->add_control( 'hide_empty_parent_child', [
			'label' => __( 'Hide empty terms', 'my-listing' ),
			'type' => \Elementor\Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'condition' => [
				'display_template' => 'template_parent_child',
				'taxonomy!' => 'listing_types',
			],
		] );

		$this->add_control( 'category_background_size', [
			'label' => __( 'Background Size', 'my-listing' ),
			'type' => \Elementor\Controls_Manager::SELECT,
			'default' => 'cover',
			'options' => [
				'cover' => 'Cover',
				'contain' => 'Contain',
				'auto' => 'Auto',
				'30%' => '30%',
				'40%' => '40%',
				'50%' => '50%',
				'60%' => '60%',
				'70%' => '70%',
				'80%' => '80%',
				'90%' => '90%',
				'100%' => '100%',
				'110%' => '110%',
				'120%' => '120%',
			],
			'condition' => ['display_template' => 'template_3'],
			'selectors' => [ '{{WRAPPER}} .car-item-img' => 'background-size: {{VALUE}}' ],
		] );

		\MyListing\Elementor\apply_column_count_controls(
			$this,
			'column_count',
			__( 'Column count', 'my-listing' ),
			[
				'general' => ['min' => 1, 'max' => 4],
				'lg' => ['default' => 3], 'md' => ['default' => 3],
				'sm' => ['default' => 2], 'xs' => ['default' => 1],
			]
		);

		$this->add_control( 'grid_gap', [
			'label' => __( 'Column gap', 'my-listing' ),
			'type' => \Elementor\Controls_Manager::SLIDER,
			'size_units' => ['px'],
			'range' => [
				'px' => [
					'min' => 0,
					'max' => 50,
					'step' => 1,
				],
			],
			'default' => [
				'unit' => 'px',
				'size' => 20,
			],
		] );

		\MyListing\Elementor\apply_overlay_controls(
			$this,
			'27_overlay',
			__( 'Set an overlay', 'my-listing' )
		);

		$this->end_controls_section();

		$this->start_controls_section( 'parent_child_styles', [
			'label' => __( 'Hierarchy', 'my-listing' ),
			'tab' => \Elementor\Controls_Manager::TAB_STYLE,
			'condition' => [
				'display_template' => 'template_parent_child',
				'taxonomy!' => 'listing_types',
			],
		] );

		$this->add_control( 'parent_child_parent_header_heading', [
			'label' => __( 'Parent Header', 'my-listing' ),
			'type' => \Elementor\Controls_Manager::HEADING,
		] );

		$this->add_control( 'parent_child_card_radius', [
			'label' => __( 'Card Border Radius', 'my-listing' ),
			'type' => \Elementor\Controls_Manager::SLIDER,
			'size_units' => ['px'],
			'range' => [
				'px' => [
					'min' => 0,
					'max' => 40,
					'step' => 1,
				],
			],
			'default' => [
				'unit' => 'px',
				'size' => 8,
			],
			'selectors' => [
				'{{WRAPPER}} .listing-cats-parent-child .parent-child-card' => 'border-radius: {{SIZE}}{{UNIT}};',
			],
		] );

		$this->add_control( 'parent_child_parent_header_padding', [
			'label' => __( 'Header Padding', 'my-listing' ),
			'type' => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => ['px'],
			'selectors' => [
				'{{WRAPPER}} .listing-cats-parent-child .parent-child-card .parent-header' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			],
			'default' => [
				'top' => 15,
				'right' => 15,
				'bottom' => 15,
				'left' => 15,
				'unit' => 'px',
			],
		] );

		$this->add_control( 'parent_child_parent_header_bg', [
			'label' => __( 'Background Color', 'my-listing' ),
			'type' => \Elementor\Controls_Manager::COLOR,
			'default' => '#f2f4fb',
			'selectors' => [
				'{{WRAPPER}} .listing-cats-parent-child .parent-child-card .parent-header' => 'background-color: {{VALUE}};',
			],
		] );

		$this->add_control( 'parent_child_parent_header_text', [
			'label' => __( 'Text Color', 'my-listing' ),
			'type' => \Elementor\Controls_Manager::COLOR,
			'default' => '#172041',
			'selectors' => [
				'{{WRAPPER}} .listing-cats-parent-child .parent-child-card .parent-header' => 'color: {{VALUE}};',
				'{{WRAPPER}} .listing-cats-parent-child .parent-child-card .parent-header svg' => 'fill: {{VALUE}};',
			],
		] );

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'parent_child_parent_header_typography',
				'label' => __( 'Header Typography', 'my-listing' ),
				'selector' => '{{WRAPPER}} .listing-cats-parent-child .parent-child-card .name, {{WRAPPER}} .listing-cats-parent-child .parent-child-card .count',
				'fields_options' => [
					'font_size' => [
						'default' => [
							'size' => 17,
							'unit' => 'px',
						],
					],
					'font_weight' => [
						'default' => '600',
					],
					'line_height' => [
						'default' => [
							'size' => 1.2,
						],
					],
				],
			]
		);

		$this->add_control( 'parent_child_parent_icon_size', [
			'label' => __( 'Parent Icon Size', 'my-listing' ),
			'type' => \Elementor\Controls_Manager::SLIDER,
			'size_units' => ['px'],
			'range' => [
				'px' => [
					'min' => 12,
					'max' => 80,
					'step' => 1,
				],
			],
			'default' => [
				'unit' => 'px',
				'size' => 22,
			],
			'selectors' => [
				'{{WRAPPER}} .listing-cats-parent-child .parent-child-card .parent-icon i' => 'font-size: {{SIZE}}{{UNIT}};',
				'{{WRAPPER}} .listing-cats-parent-child .parent-child-card .parent-icon img' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				'{{WRAPPER}} .listing-cats-parent-child .parent-child-card .parent-icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
			],
		] );

		$this->add_control( 'parent_child_child_grid_heading', [
			'label' => __( 'Child Grid', 'my-listing' ),
			'type' => \Elementor\Controls_Manager::HEADING,
			'separator' => 'before',
		] );

		$this->add_control( 'parent_child_child_grid_padding', [
			'label' => __( 'Grid Padding', 'my-listing' ),
			'type' => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => ['px'],
			'selectors' => [
				'{{WRAPPER}} .listing-cats-parent-child .parent-child-card .child-grid' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			],
			'default' => [
				'top' => 15,
				'right' => 15,
				'bottom' => 15,
				'left' => 15,
				'unit' => 'px',
			],
		] );

		$this->add_control( 'parent_child_child_grid_bg', [
			'label' => __( 'Background Color', 'my-listing' ),
			'type' => \Elementor\Controls_Manager::COLOR,
			'default' => '#f2f4fb',
			'selectors' => [
				'{{WRAPPER}} .listing-cats-parent-child .parent-child-card .child-grid' => 'background-color: {{VALUE}};',
			],
		] );

		$this->add_control( 'parent_child_child_chip_bg', [
			'label' => __( 'Child Chip Background', 'my-listing' ),
			'type' => \Elementor\Controls_Manager::COLOR,
			'default' => '#f6f7fb',
			'selectors' => [
				'{{WRAPPER}} .listing-cats-parent-child .parent-child-card .child-chip' => 'background-color: {{VALUE}};',
			],
		] );

		$this->add_control( 'parent_child_child_chip_hover_bg', [
			'label' => __( 'Child Chip Hover Background', 'my-listing' ),
			'type' => \Elementor\Controls_Manager::COLOR,
			'default' => '#eef1f8',
			'selectors' => [
				'{{WRAPPER}} .listing-cats-parent-child .parent-child-card .child-chip:hover' => 'background-color: {{VALUE}};',
			],
		] );

		$this->add_control( 'parent_child_child_chip_padding', [
			'label' => __( 'Child Chip Padding', 'my-listing' ),
			'type' => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => ['px'],
			'selectors' => [
				'{{WRAPPER}} .listing-cats-parent-child .parent-child-card .child-chip' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			],
			'default' => [
				'top' => 8,
				'right' => 14,
				'bottom' => 8,
				'left' => 14,
				'unit' => 'px',
			],
		] );

		$this->add_control( 'parent_child_child_chip_border', [
			'label' => __( 'Child Chip Border Color', 'my-listing' ),
			'type' => \Elementor\Controls_Manager::COLOR,
			'default' => 'rgba(23, 32, 65, 0.08)',
			'selectors' => [
				'{{WRAPPER}} .listing-cats-parent-child .parent-child-card .child-chip' => 'border-color: {{VALUE}};',
			],
		] );

		$this->add_control( 'parent_child_child_chip_radius', [
			'label' => __( 'Child Chip Border Radius', 'my-listing' ),
			'type' => \Elementor\Controls_Manager::SLIDER,
			'size_units' => ['px'],
			'range' => [
				'px' => [
					'min' => 0,
					'max' => 50,
					'step' => 1,
				],
			],
			'default' => [
				'unit' => 'px',
				'size' => 5,
			],
			'selectors' => [
				'{{WRAPPER}} .listing-cats-parent-child .parent-child-card .child-chip' => 'border-radius: {{SIZE}}{{UNIT}};',
			],
		] );

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'parent_child_child_chip_typography',
				'label' => __( 'Child Chip Typography', 'my-listing' ),
				'selector' => '{{WRAPPER}} .listing-cats-parent-child .parent-child-card .child-chip, {{WRAPPER}} .listing-cats-parent-child .parent-child-card .child-chip .child-count',
				'fields_options' => [
					'font_size' => [
						'default' => [
							'size' => 15,
							'unit' => 'px',
						],
					],
				],
			]
		);

		$this->add_control( 'parent_child_child_icon_size', [
			'label' => __( 'Child Icon Size', 'my-listing' ),
			'type' => \Elementor\Controls_Manager::SLIDER,
			'size_units' => ['px'],
			'range' => [
				'px' => [
					'min' => 10,
					'max' => 50,
					'step' => 1,
				],
			],
			'default' => [
				'unit' => 'px',
				'size' => 20,
			],
			'selectors' => [
				'{{WRAPPER}} .listing-cats-parent-child .parent-child-card .child-chip .child-icon i' => 'font-size: {{SIZE}}{{UNIT}};',
				'{{WRAPPER}} .listing-cats-parent-child .parent-child-card .child-chip .child-icon img' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				'{{WRAPPER}} .listing-cats-parent-child .parent-child-card .child-chip .child-icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
			],
		] );

		$this->add_control( 'parent_child_child_chip_stack', [
			'label' => __( 'Stack Child Chips', 'my-listing' ),
			'type' => \Elementor\Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'description' => __( 'Force each child chip to take a full row.', 'my-listing' ),
			'selectors' => [
				'{{WRAPPER}} .listing-cats-parent-child .parent-child-card .child-chip' => 'flex-basis: 100%; width: 100%;',
			],
		] );

		$this->add_control( 'parent_child_child_chip_text', [
			'label' => __( 'Child Chip Text Color', 'my-listing' ),
			'type' => \Elementor\Controls_Manager::COLOR,
			'default' => '#172041',
			'selectors' => [
				'{{WRAPPER}} .listing-cats-parent-child .parent-child-card .child-chip' => 'color: {{VALUE}};',
				'{{WRAPPER}} .listing-cats-parent-child .parent-child-card .child-chip .child-count' => 'color: {{VALUE}};',
				'{{WRAPPER}} .listing-cats-parent-child .parent-child-card .child-chip svg' => 'fill: {{VALUE}};',
			],
		] );

		$this->add_control( 'parent_child_child_chip_hover_text', [
			'label' => __( 'Child Chip Hover Text Color', 'my-listing' ),
			'type' => \Elementor\Controls_Manager::COLOR,
			'default' => '#172041',
			'selectors' => [
				'{{WRAPPER}} .listing-cats-parent-child .parent-child-card .child-chip:hover' => 'color: {{VALUE}};',
				'{{WRAPPER}} .listing-cats-parent-child .parent-child-card .child-chip:hover .child-count' => 'color: {{VALUE}};',
				'{{WRAPPER}} .listing-cats-parent-child .parent-child-card .child-chip:hover svg' => 'fill: {{VALUE}};',
			],
		] );

		$this->add_control( 'parent_child_hide_icons', [
			'label' => __( 'Hide Icons', 'my-listing' ),
			'type' => \Elementor\Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'separator' => 'before',
		] );

		$this->end_controls_section();
	}

	protected function render( $instance = [] ) {
		wp_print_styles( 'mylisting-listing-categories-widget' );

		$taxonomy = $this->get_settings( 'taxonomy' );

		switch ( $taxonomy ) {
			case 'region' :
				$terms = $this->get_settings('select_regions');
			break;

			case 'case27_job_listing_tags' :
				$terms = $this->get_settings('select_tags');
			break;

			case 'job_listing_category' :
				$terms = $this->get_settings('select_categories');
			break;

			case 'listing_types' :
				$terms = $this->get_settings('select_listing_types');
			break;

			default :
				$custom_taxonomies = mylisting_custom_taxonomies();

				foreach ( $custom_taxonomies as $slug => $label ) {
					if ( $taxonomy != $slug ) {
						continue;
					}

					$terms = $this->get_settings( "select_{$slug}" );
				}

			break;
		}

		$template = $this->get_settings( 'display_template' );

		if ( $taxonomy === 'listing_types' ) {
			$template_listing_types = $this->get_settings( 'display_template_listing_types' );

			if ( ! empty( $template_listing_types ) ) {
				$template = $template_listing_types;
			}

			if ( $template === 'template_parent_child' ) {
				$template = 'template_1';
			}
		}

		$gap = $this->get_settings( 'grid_gap' );
		$hide_icons_setting = $this->get_settings( 'parent_child_hide_icons' );
		$hide_icons = in_array( $hide_icons_setting, [ 'yes', 'none' ], true );

		c27()->get_section( 'listing-categories', [
			'taxonomy' => $this->get_settings('taxonomy'),
			'terms' => (array) $terms,
			'template' => $template,
			'overlay_type' => $this->get_settings('27_overlay'),
			'overlay_gradient' => $this->get_settings('27_overlay__gradient'),
			'overlay_solid_color' => $this->get_settings('27_overlay__solid_color'),
			'gap' => isset( $gap['size'] ) ? absint( $gap['size'] ) : 30,
			'hide_empty_terms' => $this->get_settings( 'hide_empty_parent_child' ) === 'yes',
			'columns' => [
				'lg' => $this->get_settings('column_count__lg'),
				'md' => $this->get_settings('column_count__md'),
				'sm' => $this->get_settings('column_count__sm'),
				'xs' => $this->get_settings('column_count__xs'),
			],
			'parent_child_hide_icons' => $hide_icons,
		] );
	}

	protected function content_template() {}
	public function render_plain_content() {}
}
