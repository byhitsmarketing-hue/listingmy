<?php

namespace MyListing\Src\Geocoder;

if ( ! defined('ABSPATH') ) {
	exit;
}

class OpenStreet_Maps_Geocoder extends Geocoder {

	protected function client_geocode( $location ) {
		$gcoding_type = mylisting()->get( 'maps.osmaps_gcoding_type', 'nominatim' );

		if ( $gcoding_type == 'gmaps' ) {

			$language = mylisting()->get( 'maps.osmaps_lang', 'default' );
			
			// if set to default, try to retrieve the browser language via js and use that
			if ( $language === 'default' ) {
				$language = false;
			}

			$params = [
				'key' => mylisting()->get( 'maps.gmaps_api_key' ),
				'language' => $language !== 'default' ? $language : 'en',
			];

			if ( is_array( $location ) ) {
				$params['latlng'] = join( ',', array_map( 'floatval', $location ) );
			} else {
				$params['address'] = (string) $location;
			}

			$request = wp_remote_get( sprintf( 'https://maps.googleapis.com/maps/api/geocode/json?%s', http_build_query( $params ) ), [
				'httpversion' => '1.1',
				'sslverify' => false,
			] );

			if ( is_wp_error( $request ) ) {
				throw new \Exception( 'Could not perform geocoding request.' );
			}

			$response = json_decode( wp_remote_retrieve_body( $request ) );
			if ( ! is_object( $response ) || $response->status !== 'OK' || empty( $response->results ) ) {
				throw new \Exception( sprintf(
					'(%s) %s',
					$response->status ?? 'REQUEST_FAILED',
					$response->error_message ?? 'Geocoding request failed.'
				) );
			}

			return $response->results[0];

		} else if ( $gcoding_type == 'mapbox' ) {
			$url = 'https://api.mapbox.com/geocoding/v5/mapbox.places/%s.json?%s';
			$language = mylisting()->get( 'maps.osmaps_lang', 'default' );
			if ( empty( $language ) || $language === 'default' ) {
				$language = 'en';
			}

			$params = [
				'access_token' => mylisting()->get( 'maps.osmaps_mapbox_api_key' ),
				'language' => $language,
				'limit' => 1,
			];

			if ( is_array( $location ) ) {
				$location = join( ',', array_reverse( array_map( 'floatval', $location ) ) );
			} else {
				$location = urlencode( $location );
			}

			$request = wp_remote_get( sprintf( $url, $location, http_build_query( $params ) ), [
				'httpversion' => '1.1',
				'sslverify' => false,
			] );

			if ( is_wp_error( $request ) ) {
				throw new \Exception( 'Could not perform geocoding request.' );
			}

			$response = json_decode( wp_remote_retrieve_body( $request ) );
			if ( ! is_object( $response ) || empty( $response->features ) ) {
				throw new \Exception( $response->message ?? 'Geocoding request failed.' );
			}

			return $response->features[0];
		} else {
			$url = 'https://nominatim.openstreetmap.org/search?%s';
			$language = mylisting()->get('maps.osmaps_lang', 'default');
			if (empty($language) || $language === 'default') {
			    $language = 'en';
			}

			if ( is_array( $location ) ) {
				$url = 'https://nominatim.openstreetmap.org/reverse%s';

				$edit_url = add_query_arg( [
					'lat' => $location[0],
					'lon' => $location[1],
				], $url );

				$params = [
				    'format' => 'json',
				    'accept-language' => $language,
				];

			} else {
				$location = urlencode( $location );

				$params = [
				    'q' => $location,
				    'format' => 'json',
				    'limit' => 1
				];
			}

			$request = wp_remote_get(sprintf($url, http_build_query($params)), [
			    'httpversion' => '1.1',
			    'sslverify' => false,
			]);

			if (is_wp_error($request)) {
			    throw new \Exception('Could not perform geocoding request.');
			}

			$response = json_decode(wp_remote_retrieve_body($request));
			if (empty($response)) {
			    throw new \Exception('Geocoding request failed.');
			}

			if (count($response) === 0) {
			    throw new \Exception('No results found.');
			}

			return $response[0];
		}
	}

	protected function transform_response( $response ) {

		$gcoding_type = mylisting()->get( 'maps.osmaps_gcoding_type', 'nominatim' );

		if ( $gcoding_type == 'gmaps' ) {
			$feature = [
				'latitude'  => $response->geometry->location->lat,
				'longitude' => $response->geometry->location->lng,
				'address'   => $response->formatted_address,
				'provider'  => 'google-maps',
				'meta'      => [],
			];

			if ( ! empty( $response->address_components ) ) {
				foreach ( $response->address_components as $component ) {
					if ( empty( $component->types ) ) {
						continue;
					}

					foreach ( $component->types as $component_type ) {
						$feature['meta'][ $component_type ] = $component->long_name;
					}
				}
			}
		} else if ( $gcoding_type == 'mapbox' ) {
			$feature = [
				'latitude'  => $response->geometry->coordinates[0],
				'longitude' => $response->geometry->coordinates[1],
				'address'   => $response->place_name,
				'provider'  => 'mapbox',
				'meta'      => [],
			];
		} else {
			$feature = [
				'latitude'  => $response->lat,
				'longitude' => $response->lon,
				'address'   => $response->display_name,
				'provider'  => 'free-map',
				'meta'      => [],
			];
		}

		return $feature;
	}
}
