<?php

namespace MyListing\Src\Queries;

class Listing_Feed extends Query {
	use \MyListing\Src\Traits\Instantiatable;

	public function __construct() {
		add_action( 'mylisting_ajax_get_listing_feed_listings', [ $this, 'handle' ] );
		add_action( 'mylisting_ajax_nopriv_get_listing_feed_listings', [ $this, 'handle' ] );

		// @todo: use the custom ajax handler instead of wp_ajax
		add_action( 'wp_ajax_get_listing_feed_listings', [ $this, 'handle' ] );
		add_action( 'wp_ajax_nopriv_get_listing_feed_listings', [ $this, 'handle' ] );
	}

	/**
	 * Handle AJAX listing queries.
	 *
	 * @since 1.0.0
	 */
	public function handle() {
		if ( apply_filters( 'mylisting/ajax-get-request-security-check', false ) === true ) {
			check_ajax_referer( 'c27_ajax_nonce', 'security' );
		}

		$result = $this->run( $_GET );

		wp_send_json( $result );
	}

	/**
	 * Handle Listing feed requests, typically $_GET or $_POST.
	 * Request can be manually constructed, which allows using
	 * this function outside Ajax/POST context.
	 *
	 * @since 1.7.0
	 */
	public function run( $request ) {

		if ($request['req_type'] === 'server_req' && !$request['pagination']['enabled']) {
			$transient_key = 'listing_feed_' . md5( serialize( $request ) );
			$cached_results = get_transient( $transient_key );

			if ( $cached_results !== false ) {
				return $cached_results;
			}	
		}


		// handle find listings using explore page query url
		$filters = $request['filters'];
		$per_page = absint( ! empty($filters['posts_per_page']) ? $filters['posts_per_page'] : c27()->get_setting('general_explore_listings_per_page', 9) );
		$page = absint( ! empty($filters['page']) ? $filters['page'] : 0 );
		$result = [];

		if ( $request['query']['method'] === 'query_string' ) {
			if ( ! ( $query_string = parse_url( $request['query']['string'], PHP_URL_QUERY ) ) ) {
				return false;
			}

			if ( ! ( $query_args = wp_parse_args( $query_string ) ) ) {
				return false;
			}

			// 'pg' param must be converted to 'page'
			$query_args['page'] = is_null($filters['page']) && isset($query_args['pg']) && $query_args['pg'] !== '' ? max( 0, absint( $query_args['pg'] ) - 1 ) : $page;

			
			$listings = \MyListing\Src\Queries\Explore_Listings::instance()->run( [
				'listing_type' => ! empty( $query_args['type'] ) ? $query_args['type'] : false,
				'form_data' => c27()->merge_options( [
					'per_page' => $per_page,
				], (array) $query_args ),
				'return_query' => true,
			] );

			$result = $this->generate_html($listings, $request, $args = '', $result, $query_args['page']);

		} else {
		
			$starttime = microtime(true);
			// handle regular filter query
			$args = [
				'post_type' => 'job_listing',
				'post_status' => 'publish',
				'posts_per_page' => $per_page,
				'offset' => $page * $per_page,
				'ignore_sticky_posts' => false,
				'meta_query' => [],
				'tax_query' => [],
				'fields' => 'ids',
			];


			// filter by selected authors
			if ( ! empty($filters['authors']) ) {
				$author_ids = array_filter(array_map('intval', explode(',', $filters['authors'])));
				if (!empty($author_ids)) {
					$args['author__in'] = $author_ids;
				}
			}

			// filter by selected categories
			if ( ! empty($filters['categories']) ) {
				$args['tax_query'][] = [
					'taxonomy' => 'job_listing_category',
					'terms' => $filters['categories'],
					'field' => 'term_id',
				];
			}

			// filter by selected regions
			if ( ! empty($filters['regions']) ) {
				$args['tax_query'][] = [
					'taxonomy' => 'region',
					'terms' => $filters['regions'],
					'field' => 'term_id',
				];
			}

			// filter by selected tags
			if ( ! empty($filters['tags']) ) {
				$args['tax_query'][] = [
					'taxonomy' => 'case27_job_listing_tags',
					'terms' => $filters['tags'],
					'field' => 'term_id',
				];
			}

			// filter by selected custom taxonomies
			$taxonomy_list = $filters['custom_taxonomies'];
			foreach ( $taxonomy_list as $slug => $label ) {
				if ( ! empty( $filters[$slug] ) ) {
					$args['tax_query'][] = [
						'taxonomy' => $slug,
						'terms' => $filters[$slug],
						'field' => 'term_id',
					];
				}
			}

			if ( ! empty($filters['listings']) ) {
			// handle "select a list of listings" setting
				$include_ids = array_filter( array_map( 'absint', array_column(
					(array) $filters['listings'],
					'listing_id'
				) ) );

				if ( ! empty( $include_ids ) ) {
					$args['post__in'] = $include_ids;
				}
			}


			// filter by the listing type
			if ( isset($request['listing_types']) && $listing_types = $request['listing_types'] ) {
				$args['meta_query']['c27_listing_type_clause'] = [
					'key' => '_case27_listing_type',
					'value' => $listing_types,
					'compare' => 'IN',
				];
				if (count($listing_types) === 1) {
					$type = implode( ', ', $listing_types );
					if ( $listing_types_obj = ( get_page_by_path( $type, OBJECT, 'case27_listing_type' ) ) ) {
						$type = new \MyListing\Src\Listing_Type( $listing_types_obj );

						if ( $type->is_global() ) {

							$args['meta_query'] = [];

							if ( $type->is_global_custom() ) {
								if ( in_array('c27_posts', $type->get_global_types()) && count($type->get_global_types()) === 1 ) {
									$args['post_type'] = [ 'post' ];
								} elseif( in_array('c27_posts', $type->get_global_types()) && count($type->get_global_types()) > 1 ) {
									$args['post_type'] = ['job_listing', 'post'];
									$args['meta_query']['listing_type_query'] = [
										'relation' => 'OR',
										[
											'key' => '_case27_listing_type',
											'value' => $type->get_global_types(),
											'compare' => 'IN',
										],
										[
											'key' => '_case27_listing_type',
											'compare' => 'NOT EXISTS',
										],
									];
								} else {
									$args['meta_query']['listing_type_query'] = [
										'key'     => '_case27_listing_type',
										'value'   =>  $type->get_global_types(),
										'compare' => 'IN'
									];
								}
							} elseif ( !$type->is_global_custom() ) {
								$args['meta_query'] = [];
							}
						}
					}
				}
			}

			$orderby = $filters['order_by'];

			if ( $orderby ) {
				if ( $orderby === '_case27_average_rating' ) {
					add_filter( 'posts_join', [ $this, 'rating_field_join' ], 35, 2 );
					add_filter( 'posts_orderby', [ $this, 'rating_field_orderby' ], 35, 2 );
					$args['orderby'] = [];
				} elseif ( $orderby === 'rand' ) {
					// Randomize every 3 hours.
					$seed = apply_filters( 'mylisting/listingfeed/rand/seed', floor( time() / 10800 ) );
					$args['orderby'] = 'RAND(' . $seed . ')';
				} else {
					$args['orderby'] = $orderby;
				}
			}

			$args['order'] = $filters['order'] === 'ASC' ? 'ASC' : 'DESC';

			$order_by_priority = (bool) $filters['behavior'];

			// prevent duplicates
			add_filter( 'posts_distinct', [ $this, 'prevent_duplicates' ], 30, 2 );

			// join priority meta
			add_filter( 'posts_join', [ $this, 'priority_field_join' ], 30, 2 );

			// set which priority levels to include
			$priority_levels = isset($filters['priority']) ? array_filter((array) $filters['priority']) : [];
			$priority_field_where = function( $where, $query ) use ( $priority_levels ) {
				global $wpdb;

				$levels = [];
				if ( in_array( 'normal', $priority_levels ) ) { $levels[] = 0; }
				if ( in_array( 'featured', $priority_levels ) ) { $levels[] = 1; }
				if ( in_array( 'promoted', $priority_levels ) ) { $levels[] = 2; }

				// all priority levels are included
				if ( count( $priority_levels ) === 0 || count( $priority_levels ) === 4 ) {
					return $where;
				}

				if ( ! empty( $levels ) ) {

					// handle levels 0,1,2
					$where .= sprintf( ' AND ( priority_meta.meta_value IN (%s)', join( ',', $levels ) );
					// handle levels 3+
					if ( in_array( 'custom', $priority_levels ) ) {
						$where .= ' OR priority_meta.meta_value > 2 ';
					}

					$where .= ' ) ';
				} else if ( in_array( 'custom', $priority_levels ) ) {
					$where .= ' AND priority_meta.meta_value > 2 ';
				}

				return $where;
			};

			add_filter( 'posts_where', $priority_field_where, 30, 2 );

			// order by priority
			if ( $order_by_priority === true ) {
				$args['suppress_filters'] = false;
				add_filter( 'posts_orderby', [ $this, 'priority_field_orderby' ], 30, 2 );
			}

			$listings = new \WP_Query( $args );

			$result = $this->generate_html($listings, $request, $args, $result, $page);

			if ( \MyListing\is_dev_mode() ) {
				$result['timing'] = sprintf( '%sms', round( ( microtime(true) - $starttime ) * 1000 ) );
			}

			remove_filter( 'posts_join', [ $this, 'priority_field_join' ], 30 );
			remove_filter( 'posts_orderby', [ $this, 'priority_field_orderby' ], 30 );
			remove_filter( 'posts_where', $priority_field_where, 30 );
			remove_filter( 'posts_join', [ $this, 'rating_field_join' ], 35 );
			remove_filter( 'posts_orderby', [ $this, 'rating_field_orderby' ], 35 );
			remove_filter( 'posts_distinct', [ $this, 'prevent_duplicates' ], 30 );

		}

		if ($request['req_type'] === 'server_req' && !$request['pagination']['enabled']) {
			$cache_duration = isset($request['query']['cache_for']) ? intval($request['query']['cache_for']) * 60 : 7200;
			if ($cache_duration > 0) {
				set_transient($transient_key, $result, $cache_duration);
			} else {
				delete_transient($transient_key);
			}
		}

		return $result;

	}

	protected function generate_html($listings, $request, $args, $result, $page) {
		ob_start();

		if (empty($args)) {
			$request['args'] = '';
		}

		if ( \MyListing\is_dev_mode() ) {
			$result['args'] = $args;
			$result['sql'] = $listings->request;
		}

		if ($request['template'] === 'carousel') {
			$request['listing_wrap'] = '';
		}

		foreach ( (array) $listings->posts as $listing_id ) {
			if ( get_post_type($listing_id) === 'job_listing' ) {
				printf(
					'<div class="%s">%s</div>',
					$request['listing_wrap'],
					\MyListing\get_preview_card( $listing_id )
				);
			} elseif(get_post_type($listing_id) === 'post') {
				c27()->get_partial( 'post-preview', [
					'wrap_in' => $request['listing_wrap'],
					'post_id' => $listing_id,
				] );
			}
		}

		$result['html'] = ob_get_clean();
		wp_reset_postdata();

		if ($request['template'] === 'carousel') {
			ob_start();

			$show_blank_slides = isset( $request['show_blank_slides'] ) ? $request['show_blank_slides'] : true;

			if ( $show_blank_slides && count( $listings->posts ) <= 3 ) {
				foreach (range( 0, absint( count( $listings->posts ) - 4 ) ) as $i) {
					printf(
						'<div class="%s"></div>',
						'item c27-blank-slide'
					);
				}
			}

			$result['blank-slide'] = ob_get_clean();
			wp_reset_postdata();
		}

		$result['pagination'] = c27()->get_listing_pagination( $listings->max_num_pages, ($page + 1) );
		$result['found_posts'] = absint( $listings->found_posts );
		$result['max_num_pages'] = absint( $listings->max_num_pages );

		return $result;
	}

}