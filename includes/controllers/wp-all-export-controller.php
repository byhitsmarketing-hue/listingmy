<?php

namespace MyListing\Controllers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Wp_All_Export_Controller extends Base_Controller {

	protected function is_active() {
		$all_export_active = defined('PMXE_VERSION');
		return $all_export_active;
	}

	protected function hooks() {
		$this->filter( 'wp_all_export_csv_rows', '@export_parse_function', 99, 3 );
	}

	protected function export_parse_function( $post, $export_options, $export_id ) {

		if ( empty( $post ) ) {
			return $post;
		}
		
		foreach ($post as $index => $post_data ) {
			$post_id = '';
			
			if ( isset( $post_data['ID'] ) ) {
				$post_id = $post_data['ID'];
			} else if ( isset( $post_data['id'] ) ) {
				$post_id = $post_data['id'];
			}
			if ( empty( $post_id ) ) {
				continue;
			}
			
			if ( get_post_type( $post_id ) !== 'job_listing' ) {
				continue;
			}

			$listing = \MyListing\Src\Listing::get( $post_id );
			if ( isset( $post_id ) && isset( $post_data['_location'] ) ) {
				$locations = $listing->get_field( 'location' );
				if ( ! $locations ) {
					continue;
				}

				// Update the location field with the comma-separated list of locations.
				$location_values = [];
				foreach ($locations as $key => $location ) {
					if ( $location['address'] && $location['lat'] && $location['lng'] ) {
						$location_values[] = sprintf(
							'%s,%s,%s',
							esc_sql( $location['address'] ),
							(float) $location['lat'],
							(float) $location['lng'],
						);
					}
				}
				$post[$index]['_location'] = maybe_serialize( $location_values );
			}

			if ( isset( $post_id ) && isset( $post_data['_work_hours'] ) ) {
				$work_hours_field = $listing->get_field( 'work_hours' );
				if ( is_array($work_hours_field) ) {
					$days_of_week = [ 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday' ];
					$work_hours_formatted = [];

					foreach ( $days_of_week as $index => $day ) {
						if ( isset( $work_hours_field[$index] ) ) {
							$status_time = explode( ',', $work_hours_field[$index] );
							$status_parts = explode( ':', $status_time[0] );

							$work_hours_formatted[$day] = [
								'status' => $status_parts[0],
							];

							if ( $status_parts[0] === 'enter-hours' ) {
								$work_hours_formatted[$day][0] = json_decode( $status_parts[1], true );
							}
						}
					}

					$work_hours_formatted['timezone'] = $work_hours_field['timezone'];
					$post[$index]['_work_hours'] = maybe_serialize( $work_hours_formatted );
				}
			}

			// export recurring dates
			$rec_prefix = '_27_recurring_';
			$rec_prefix_length = strlen($rec_prefix);

			foreach ($post_data as $key => $value) {
				if (strpos($key, $rec_prefix) === 0) {
					$suffix = substr($key, $rec_prefix_length);

					if ( isset( $post_id ) && isset( $post_data[$key] ) ) {
						$events = $listing->get_field( $suffix );
						$structured_events = [];

						foreach ($events as $eventKey => $event) {
							$frequency_unit = isset($event['frequency']) && isset($event['unit']) 
							? $event['frequency'] . ' ' . $event['unit'] 
							: '';

							$structured_events[$eventKey] = [
								'start' => $event['start'],
								'end' => $event['end'],
								'repeat' => $event['repeat'],
								'frequency' => $frequency_unit,
								'until' => $event['until'],
							];
						}

						$post[$index][$key] = maybe_serialize($structured_events);
					}
				}
			}

		}

		return $post;
	}
}