<?php
/**
 * Rating filter for the listing type editor.
 *
 * @since 2.8
 */

namespace MyListing\Src\Listing_Types\Filters;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Rating extends Base_Filter {

	/**
	 * Custom form key for clean URL parameters.
	 *
	 * @since 2.8
	 */
	protected $form_key = 'rating';

	public function filter_props() {
		$this->props['type'] = 'rating';
		$this->props['label'] = __( 'Rating', 'my-listing' );
		$this->props['show_field'] = '_case27_average_rating';
		$this->props['default_label'] = __( 'Any rating', 'my-listing' );
		$this->props['form'] = 'advanced';
	}

	public function apply_to_query( $args, $form_data ) {
		if ( empty( $form_data[ $this->get_form_key() ] ) ) {
			return $args;
		}

		$rating_value = absint( $form_data[ $this->get_form_key() ] );
		
		// Convert 5-star user selection to 10-scale internal storage
		// User selects 1-5 stars, we query for 2-10 internal values
		$min_rating = $rating_value * 2;

		if ( $min_rating >= 2 && $min_rating <= 10 ) {
			$args['meta_query'][] = [
				'relation' => 'AND',
				[
					'key'     => '_case27_average_rating',
					'compare' => 'EXISTS',
				],
				[
					'key'     => '_case27_average_rating',
					'value'   => '',
					'compare' => '!=',
				],
				[
					'key'     => '_case27_average_rating',
					'value'   => $min_rating,
					'compare' => '>=',
					'type'    => 'DECIMAL(10,2)',
				],
			];
		}

		return $args;
	}

	public function get_request_value() {
		$value = ! empty( $_GET[ $this->get_form_key() ] )
			? absint( $_GET[ $this->get_form_key() ] )
			: '';

		// Validate rating value (1-5 stars)
		if ( $value >= 1 && $value <= 5 ) {
			return $value;
		}

		return '';
	}

	public function parse_request_value( $value ) {
		$rating = absint( $value );
		
		if ( $rating >= 1 && $rating <= 5 ) {
			return [
				'value' => $rating,
				'label' => $this->get_rating_label( $rating ),
			];
		}

		return [
			'value' => '',
			'label' => $this->get_prop('default_label'),
		];
	}

	/**
	 * Get rating options for the filter.
	 *
	 * @return array
	 */
	public function get_rating_options() {
		return [
			'5' => __( '5 stars', 'my-listing' ),
			'4' => __( '4+ stars', 'my-listing' ),
			'3' => __( '3+ stars', 'my-listing' ),
			'2' => __( '2+ stars', 'my-listing' ), 
			'1' => __( '1+ stars', 'my-listing' ),
			'' => $this->get_prop('default_label'),
		];
	}

	/**
	 * Get label for a specific rating value.
	 *
	 * @param int $rating
	 * @return string
	 */
	public function get_rating_label( $rating ) {
		$options = $this->get_rating_options();
		return isset( $options[ $rating ] ) ? $options[ $rating ] : $this->get_prop('default_label');
	}

	public function get_required_scripts(): array {
		return [];
	}
}
