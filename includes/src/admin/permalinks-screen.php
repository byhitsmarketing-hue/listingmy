<?php

namespace MyListing\Src\Admin;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Permalinks_Screen {
	use \MyListing\Src\Traits\Instantiatable;

	// permalink settings
	private $permalinks = [];

	public function __construct() {
		$this->permalinks = \MyListing\Src\Permalinks::get_permalink_structure();
		$this->setup_fields();
		$this->save_settings();
	}

	/**
	 * Add settings fields related to permalinks.
	 *
	 * @since 2.1
	 */
	public function setup_fields() {
		$this->add_settings_field( [
			'name' => 'wpjm_job_base_slug',
			'label' => __( 'Listing base', 'my-listing' ),
			'value' => $this->permalinks['job_base'],
			'placeholder' => 'listing',
			'after' => $this->listing_type_permalink_docs(),
		] );

		$this->add_settings_field( [
			'name' => 'wpjm_job_category_slug',
			'label' => __( 'Category base', 'my-listing' ),
			'value' => $this->permalinks['category_base'],
			'placeholder' => 'category',
		] );

		$this->add_settings_field( [
			'name' => 'ml_region_slug',
			'label' => __( 'Region base', 'my-listing' ),
			'value' => $this->permalinks['region_base'],
			'placeholder' => 'region',
		] );

		$this->add_settings_field( [
			'name' => 'ml_tag_slug',
			'label' => __( 'Tag base', 'my-listing' ),
			'value' => $this->permalinks['tag_base'],
			'placeholder' => 'tag',
		] );

		if ( ! empty( mylisting_custom_taxonomies() ) ) {
			foreach ( mylisting_custom_taxonomies() as $tax_key => $tax_label ) {
				if ( empty( $tax_key ) || empty( $tax_label ) ) {
					continue;
				}

				$option_key = sprintf( '%s_base', $tax_key );
				$field_name = sprintf( 'ml_custom_tax_%s_slug', $tax_key );
				$this->add_settings_field( [
					'name' => $field_name,
					'label' => sprintf( __( '%s base', 'my-listing' ), $tax_label ),
					'value' => isset( $this->permalinks[ $option_key ] ) ? $this->permalinks[ $option_key ] : $tax_key,
					'placeholder' => $tax_key,
				] );
			}
		}
	}

	/**
	 * Render a settings field based on the given args.
	 *
	 * @since 2.1
	 */
	public function add_settings_field( $args = [] ) {
		$args = wp_parse_args( (array) $args, [
			'name' => '',
			'value' => '',
			'label' => '',
			'placeholder' => '',
			'after' => '',
		] );

		if ( empty( $args['name'] ) ) {
			return;
		}

		add_settings_field(
			$args['name'],
			$args['label'],
			function() use ( $args ) {
				printf(
					'<input name="%s" type="text" class="regular-text code" value="%s" placeholder="%s">',
					esc_attr( $args['name'] ),
					esc_attr( $args['value'] ),
					esc_attr( $args['placeholder'] )
				);
				echo $args['after'];
			},
			'permalink',
			'optional'
		);
	}

	/**
	 * Display availabe tags and a link to docs
	 * on the listing base setting.
	 *
	 * @since 2.1
	 */
	public function listing_type_permalink_docs() {
		ob_start(); ?>
		<p>
			Available tags: <code>%listing_type%</code> <code>%listing_category%</code> <code>%listing_region%</code>
			<a href="#" class="cts-show-tip" data-tip="permalink-docs" title="Click to learn more">[Learn More]</a>
		</p>
		<?php return ob_get_clean();
	}

	/**
	 * Handles saving listing permalink settings.
	 *
	 * @since 2.1
	 */
	public function save_settings() {
		if ( ! is_admin() || ! isset( $_POST['permalink_structure'] ) ) {
			return;
		}

		$permalinks = (array) get_option( 'mylisting_permalinks', [] );
		$permalinks['job_base'] = sanitize_text_field( $_POST['wpjm_job_base_slug'] );
		$permalinks['category_base'] = sanitize_text_field( $_POST['wpjm_job_category_slug'] );
		$permalinks['region_base'] = sanitize_text_field( $_POST['ml_region_slug'] );
		$permalinks['tag_base'] = sanitize_text_field( $_POST['ml_tag_slug'] );

		// Save custom taxonomy permalink bases
		if ( ! empty( mylisting_custom_taxonomies() ) ) {
			foreach ( mylisting_custom_taxonomies() as $tax_key => $tax_label ) {
				if ( empty( $tax_key ) || empty( $tax_label ) ) {
					continue;
				}

				$option_key = sprintf( '%s_base', $tax_key );
				$field_name = sprintf( 'ml_custom_tax_%s_slug', $tax_key );
				if ( isset( $_POST[ $field_name ] ) ) {
					$permalinks[ $option_key ] = sanitize_text_field( $_POST[ $field_name ] );
				} else {
					$permalinks[ $option_key ] = isset( $permalinks[ $option_key ] ) ? $permalinks[ $option_key ] : '';
				}
			}
		}

		update_option( 'mylisting_permalinks', $permalinks );
	}
}
