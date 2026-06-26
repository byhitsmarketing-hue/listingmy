<?php

namespace MyListing\Src\Endpoints;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Listing_Types_Endpoint {

	public function __construct() {
		add_action( 'mylisting_ajax_get_listing_types', [ $this, 'handle' ] );
	}

	/**
	 * Retrieve the listing types
	 *
	 * @since 2.9
	 */
	public function handle() {

		$listing_types = \MyListing\get_listing_types();

		if ( ! $listing_types ) {
			return;
		}

		$results = [];
		foreach ( $listing_types as $type ) {
			$results[] = [
				'id' => $type->get_slug(),
				'text' => $type->get_plural_name(),
			];
		}

		// Send response object.
		wp_send_json( [
			'success' => true,
			'results' => $results,
		] );
	}
}
