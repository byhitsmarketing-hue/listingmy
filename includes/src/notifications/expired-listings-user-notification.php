<?php

namespace MyListing\Src\Notifications;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Expired_Listings_User_Notification extends Base_Notification {

	public $user, $listings;

	public static function hook() {
		add_action( 'mylisting/submission/listing-expired', function( $listing_ids ) {
			if ( empty( $listing_ids ) || ! is_array( $listing_ids ) ) {
				return;
			}

			$expired_listings = get_posts( [
				'post_type'      => 'job_listing',
				'post_status'    => 'expired',
				'posts_per_page' => -1,
				'post__in'       => $listing_ids,
			] );

			$grouped = [];
			foreach ( $expired_listings as $listing ) {
				if ( ! isset( $grouped[ $listing->post_author ] ) ) {
					$grouped[ $listing->post_author ] = [];
				}
				$grouped[ $listing->post_author ][] = $listing;
			}

			foreach ( $grouped as $author_id => $listings ) {
				new self( [ 'user-id' => $author_id, 'listings' => $listings ] );
			}
		} );
	}

	public static function settings() {
		return [
			'name'        => _x( 'Notify users of expired listings', 'Notifications', 'my-listing' ),
			'description' => _x( 'Send an email to the user whenever one or more of their listings have expired.', 'Notifications', 'my-listing' ),
		];
	}

	/**
	 * Validate and prepare notification arguments.
	 *
	 * @since 2.1
	 */
	public function prepare( $args ) {
		if ( empty( $args['user-id'] ) || empty( $args['listings'] ) ) {
			throw new \Exception( 'Missing arguments.' );
		}

		$this->user = get_userdata( $args['user-id'] );
		if ( ! $this->user ) {
			throw new \Exception( 'Invalid user ID.' );
		}

		$this->listings = [];
		foreach ( $args['listings'] as $listing ) {
			if ( $listing = \MyListing\Src\Listing::get( $listing ) ) {
				$this->listings[] = $listing;
			}
		}
		if ( empty( $this->listings ) ) {
			throw new \Exception( 'Invalid listings.' );
		}
	}

	public function get_mailto() {
		return $this->user->user_email;
	}

	public function get_subject() {
		if ( count( $this->listings ) === 1 ) {
			return sprintf(
				_x( '"%s" has expired.', 'Notifications', 'my-listing' ),
				esc_html( $this->listings[0]->get_name() )
			);
		}

		return sprintf(
			_x( '%s listings have expired.', 'Notifications', 'my-listing' ),
			number_format_i18n( count( $this->listings ) )
		);
	}

	public function get_message() {
		$template = new Notification_Template;

		$template->add_paragraph( sprintf(
			_x( 'Hi %s,', 'Notifications', 'my-listing' ),
			esc_html( $this->user->first_name )
		) );

		if ( count( $this->listings ) > 1 ) {
			$template->add_paragraph( _x( 'The following listings have expired.', 'Notifications', 'my-listing' ) );
		}

		foreach ( $this->listings as $listing ) {
			$add_listing_page = c27()->get_setting( 'general_add_listing_page' );
			if ( $add_listing_page ) {
				$relist_url = add_query_arg( [
					'action'  => 'relist',
					'listing' => $listing->get_id(),
				], $add_listing_page );
			} else {
				$relist_url = esc_url( wc_get_account_endpoint_url( \MyListing\my_listings_endpoint_slug() ) );
			}

			$template
			->add_paragraph( sprintf(
				_x( '<strong>%s</strong> expired on <strong>%s</strong>', 'Notifications', 'my-listing' ),
				esc_html( $listing->get_name() ),
				date_i18n( get_option( 'date_format' ), strtotime( get_post_meta( $listing->get_id(), '_job_expires', true ) ) )
			) )
			->add_button( _x( 'Republish Listing', 'Notifications', 'my-listing' ), esc_url( $relist_url ) )
			->add_thematic_break();
		}

		$template->add_primary_button(
			_x( 'Manage your listings', 'Notifications', 'my-listing' ),
			esc_url( wc_get_account_endpoint_url( \MyListing\my_listings_endpoint_slug() ) )
		);

		return $template->get_body();
	}

}
