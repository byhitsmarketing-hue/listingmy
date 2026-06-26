<?php

namespace MyListing\Elementor\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Title_Bar extends \Elementor\Widget_Base {

	public function get_name() {
		return 'case27-title-bar-widget';
	}

	public function get_title() {
		return __( '<strong>27</strong> > Breadcrumbs', 'my-listing' );
	}

	public function get_icon() {
		return 'eicon-divider';
	}

	protected function register_controls() {
		$this->start_controls_section(
			'the_title_bar_controls',
			['label' => esc_html__( 'Page Heading', 'my-listing' ),]
		);

		$this->add_control(
			'the_title',
			[
				'label' => __( 'Title', 'my-listing' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'default' => '',
			]
		);

		$this->add_control(
			'text_color',
			[
				'label' => __( 'Text Color', 'my-listing' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ml-breadcrumbs h1, {{WRAPPER}} .ml-breadcrumbs li a, {{WRAPPER}} .ml-breadcrumbs li a span' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'show_breadcrumbs',
			[
				'label' => __( 'Show Breadcrumbs?', 'my-listing' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'default' => 'yes',
				'label_on' => __( 'Show', 'my-listing' ),
				'label_off' => __( 'Hide', 'my-listing' ),
				'return_value' => 'yes',
			]
		);

		$this->end_controls_section();
	}

	protected function render( $instance = [] ) {
		$post_id = get_the_ID();
		$widget_id = $this->get_id();

		c27()->get_section( 'title-bar', [
			'title' => c27()->ml_t(
				$this->get_settings('the_title'),
				'elementor.widget.text',
				[ 'post_id' => $post_id, 'widget_id' => $widget_id, 'setting_key' => 'title' ]
			),
			'show_breadcrumbs' => $this->get_settings('show_breadcrumbs'),
		] );
	}

	protected function content_template() {}
	public function render_plain_content() {}
}
