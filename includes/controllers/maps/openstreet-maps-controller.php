<?php

namespace MyListing\Controllers\Maps;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OpenStreet_Maps_Controller extends \MyListing\Controllers\Base_Controller {

	protected function is_active() {
		return mylisting()->get( 'maps.provider', 'google-maps' ) === 'free-map';
	}

	protected function hooks() {
		$this->on( 'wp_enqueue_scripts', '@enqueue_scripts', 25 );
		$this->on( 'admin_enqueue_scripts', '@enqueue_scripts', 25 );
		$this->filter( 'mylisting/localize-data', '@localize_data', 25 );
	}

	protected function enqueue_scripts() {
		$gcoding_type = mylisting()->get( 'maps.osmaps_gcoding_type', 'nominatim' );
		
		if ( $gcoding_type == 'gmaps' ) {
			$args = [
				'key' => mylisting()->get( 'maps.gmaps_api_key' ),
				'libraries' => 'places',
				'v' => 'weekly',
				'callback' => 'Function.prototype',
				'loading' => 'async',
			];
			$language = mylisting()->get( 'maps.osmaps_lang', 'default' );
			if ( $language && $language !== 'default' ) {
				$args['language'] = $language;
			}
			wp_enqueue_script( 'google-maps', sprintf( 'https://maps.googleapis.com/maps/api/js?%s', http_build_query( $args ) ), [], null, true );
			wp_enqueue_style( 'mylisting-google-maps' );
		}
		wp_enqueue_style( 'mylisting-mapbox' );
		wp_enqueue_script( 'mylisting-openstreetmap' );
		wp_enqueue_style( 'mylisting-openstreetmap' );
	}


	protected function localize_data( $data ) {
		$gcoding_type = mylisting()->get( 'maps.osmaps_gcoding_type', 'nominatim' );
		$accesstoken = '';
		$TypeRestrictions = mylisting()->get( 'maps.osmaps_types', '' );
		$language = mylisting()->get( 'maps.osmaps_lang', 'default' );
		// if set to default, try to retrieve the browser language via js and use that
		if ( $language === 'default' ) {
			$language = false;
		}

		if ( $gcoding_type == 'gmaps' ) {
			$accesstoken = mylisting()->get( 'maps.gmaps_api_key' );
			$TypeRestrictions = mylisting()->get( 'maps.osmaps_gmaps_types', 'geocode' );
		} elseif ( $gcoding_type == 'mapbox' ) {
			$accesstoken = mylisting()->get( 'maps.mapbox_api_key' );
			$TypeRestrictions = mylisting()->get( 'maps.osmaps_mapbox_types', ['country'] );
		}

		$data['MapConfig']['GeocodingType'] = $gcoding_type;
		$data['MapConfig']['AccessToken'] = $accesstoken;
		$data['MapConfig']['Language'] = $language;
		$data['MapConfig']['TypeRestrictions'] = $TypeRestrictions;
		$data['MapConfig']['CountryRestrictions'] = mylisting()->get( 'maps.osmaps_locations', [] );
		return $data;
	}
}
