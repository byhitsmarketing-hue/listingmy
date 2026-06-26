<?php

namespace MyListing\Src\Endpoints;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Orders_List_Endpoint {

	public function __construct() {
		add_action( 'mylisting_ajax_mylisting_list_orders', [ $this, 'handle' ] );
	}
	public function handle() {
		mylisting_check_ajax_referrer();

		try {
			if ( ! current_user_can( 'edit_pages' ) ) {
				throw new \Exception( _x( 'Invalid request', 'Orders dropdown list', 'my-listing' ) );
			}

			$page = ! empty( $_REQUEST['page'] ) ? ( absint( $_REQUEST['page'] ) - 1 ) : 0;
			$search = ! empty( $_REQUEST['search'] ) ? sanitize_text_field( $_REQUEST['search'] ) : '';
			$post_type = ! empty( $_REQUEST['post_type'] )
				? array_map( 'sanitize_text_field', (array) $_REQUEST['post_type'] )
				: [ 'shop_order' ];
			$per_page = 25;

			$allowed_post_types = [ 'shop_order' ];
			if ( post_type_exists( 'shop_subscription' ) ) {
				$allowed_post_types[] = 'shop_subscription';
			}
			$post_type = array_values( array_intersect( $post_type, $allowed_post_types ) );
			if ( empty( $post_type ) ) {
				$post_type = [ 'shop_order' ];
			}

			$post_statuses = [];
			if ( in_array( 'shop_order', $post_type, true ) && function_exists( 'wc_get_order_statuses' ) ) {
				$post_statuses = array_merge( $post_statuses, array_keys( wc_get_order_statuses() ) );
			}
			if ( in_array( 'shop_subscription', $post_type, true ) && function_exists( 'wcs_get_subscription_statuses' ) ) {
				$post_statuses = array_merge( $post_statuses, array_keys( wcs_get_subscription_statuses() ) );
			}
			$post_statuses = array_unique( $post_statuses );

			$args = [
				'post_type'      => $post_type,
				'post_status'    => ! empty( $post_statuses ) ? $post_statuses : 'any',
				'posts_per_page' => $per_page,
				'offset'         => $page * $per_page,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'fields'         => 'ids',
			];

			if ( ! empty( trim( $search ) ) ) {
				$search_id = absint( ltrim( trim( $search ), '#' ) );
				if ( $search_id ) {
					$args['post__in'] = [ $search_id ];
				} else {
					$args['s'] = trim( $search );
				}
			}

			$orders = [];
			if ( function_exists( 'wc_get_orders' ) ) {
				$query_args = [
					'type'   => $post_type,
					'status' => ! empty( $post_statuses ) ? $post_statuses : 'any',
					'limit'  => $per_page,
					'offset' => $page * $per_page,
					'orderby' => 'date',
					'order'   => 'DESC',
					'return'  => 'ids',
				];

				if ( ! empty( $args['post__in'] ) ) {
					$query_args['include'] = $args['post__in'];
				}
				if ( ! empty( $args['s'] ) ) {
					$query_args['search'] = $args['s'];
				}

				$orders = wc_get_orders( $query_args );
			} else {
				$orders = get_posts( $args );
			}
			if ( empty( $orders ) || is_wp_error( $orders ) ) {
				throw new \Exception( _x( 'No orders found.', 'Orders dropdown list', 'my-listing' ) );
			}

			$results = [];
			foreach ( $orders as $order_id ) {
				$order = function_exists( 'wc_get_order' ) ? wc_get_order( $order_id ) : null;
				$label = $order
					? sprintf( '#%s - %s', $order->get_order_number(), $order->get_date_created() ? $order->get_date_created()->date_i18n( 'F j, Y g:i a' ) : '' )
					: ( '#' . $order_id );

				$results[] = [
					'id'   => $order_id,
					'text' => $label,
				];
			}

			wp_send_json( [
				'success' => true,
				'results' => $results,
				'more'    => count( $results ) === $per_page,
				'args'    => \MyListing\is_dev_mode() ? $args : [],
			] );
		} catch ( \Exception $e ) {
			wp_send_json( [
				'success' => false,
				'message' => $e->getMessage(),
			] );
		}
	}
}
