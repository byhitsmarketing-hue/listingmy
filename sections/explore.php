<?php

if ( ! defined('ABSPATH') ) {
	exit;
}

/**
 * Explore page options.
 */
$data = c27()->merge_options([
	'title'    		 => '',
	'subtitle'       => '',
	'template' 		 => 'explore-default',
	'mobile_view'       => 'results',
    'categories'     => [ 'count' => 10, ],
    'is_edit_mode'   => false,
    'scroll_to_results' => false,
    'disable_live_url_update' => false,
    'drag_search' => true,
    'default_values' => '',
	'listing-wrap'   => '',
    'listing_types'  => [],
    'types_template' => 'topbar',
	'finder_columns' => 'finder-one-columns',
	'first_load_method' => 'server',
	'categories_overlay' => [
		'type' => 'gradient',
		'gradient' => 'gradient1',
		'solid_color' => 'rgba(0, 0, 0, .1)',
	],
	'map' => [
		'default_lat' => 51.492,
		'default_lng' => -0.130,
		'default_zoom' => 11,
		'min_zoom' => 2,
		'max_zoom' => 18,
		'skin' => 'skin1',
    	'scrollwheel' => false,
		'map_type_control' => false,
	],
	'display_ad' 	=> false,
	'ad_pub_id'		=> '',
	'ad_slot_id'	=> '',
	'ad_interval'	=> '',
	'circle' => [
    	'circle_color' => '#B7BABE',
    	'circle_opacity' => 0.1,
    	'circle_border_color' => '#B7BABE',
    	'circle_border_opacity' => 0.3,
	]
], $data);

$GLOBALS['c27-explore'] = new MyListing\Src\Explore( $data );
$explore = &$GLOBALS['c27-explore'];

if ( ! in_array( $data['types_template'], ['topbar', 'dropdown'] ) ) {
	$data['types_template'] = 'topbar';
}

/*
 * The maximum number of columns for explore-2 template is "two". So, if the user sets
 * the option to "three" in Elementor settings, convert it to "two" columns.
 */
if ( $data['template'] == 'explore-2' && $data['finder_columns'] == 'finder-three-columns' ) {
	$data['finder_columns'] = 'finder-two-columns';
}

$data['first_load_method'] = in_array( $data['first_load_method'], [ 'server', 'client' ], true )
	? $data['first_load_method']
	: 'server';
$is_server_prefetch_enabled = $data['first_load_method'] === 'server';
$data['is_server_prefetch_enabled'] = $is_server_prefetch_enabled;

/**
 * If a query string for default filter values has been passed, use it.
 *
 * @since 2.2
 */
$default_type = null;
if ( ! empty( $data['default_values'] ) && ( $query_string = parse_url( $data['default_values'], PHP_URL_QUERY ) ) ) {
	$query_args = wp_parse_args( $query_string );
	if ( $query_args ) {
		foreach ( $query_args as $key => $value ) {
			if ( ! isset( $_GET[ $key ] ) ) {
				$_GET[ $key ] = $value;
			}
			if ( $key === 'type' ) {
				$default_type = $_GET['type'];
			}
		}
	}
}

switch ( $data['template'] ) {
	case 'explore-no-map':
		$data['listing-wrap'] = 'col-md-4 col-sm-6 grid-item';
		break;
	case 'explore-classic':
		$data['listing-wrap'] = 'col-md-6 col-sm-6 grid-item';
		break;
	default:
		$data['listing-wrap'] = 'col-md-12 grid-item';
		break;
}

$listing_types_config = $explore->get_types_config();
foreach ( $listing_types_config as $slug => &$type_config ) {
	$type_config['prefetched_results'] = false;
}
unset( $type_config );

$active_listing_type_slug = ! empty( $default_type )
	? $default_type
	: ( $explore->active_listing_type ? $explore->active_listing_type->get_slug() : null );

$data['initial_results'] = [];
if ( $is_server_prefetch_enabled && $active_listing_type_slug && isset( $listing_types_config[ $active_listing_type_slug ] ) ) {
	$request_data = $explore->get_initial_request_data( $listing_types_config[ $active_listing_type_slug ] );

	if ( $request_data ) {
		$pagination_link = add_query_arg(
			'pg',
			'__PAGE__',
			remove_query_arg( 'pg', \MyListing\get_current_url() )
		);
		$pagination_link = str_replace( '__PAGE__', '{page}', $pagination_link );

		$initial_request = [
			'listing_type' => $active_listing_type_slug,
			'form_data' => $request_data,
			'listing_wrap' => $data['listing-wrap'],
			'pagination_link' => $pagination_link,
		];

		if ( $data['display_ad'] && $data['ad_pub_id'] && $data['ad_slot_id'] && $data['ad_interval'] ) {
			$initial_request['display_ad'] = $data['display_ad'];
			$initial_request['pub_id'] = $data['ad_pub_id'];
			$initial_request['slot_id'] = $data['ad_slot_id'];
			$initial_request['ad_interval'] = $data['ad_interval'];
		}

		$initial_results = \MyListing\Src\Queries\Explore_Listings::instance()->run( $initial_request );

		if ( $initial_results && ! is_wp_error( $initial_results ) ) {
			$listing_types_config[ $active_listing_type_slug ]['prefetched_results'] = $initial_results;
			$data['initial_results'] = $initial_results;
		} elseif ( is_wp_error( $initial_results ) ) {
			// Fallback to client-side loading on SSR failure
			$data['first_load_method'] = 'client';
			$is_server_prefetch_enabled = false;
			$data['is_server_prefetch_enabled'] = false;
		}
	}
}

$initial_results = [];
$initial_results_count = 0;
$has_initial_results = false;
$initial_results_html = '';
$initial_pagination = '';

if ( $is_server_prefetch_enabled && ! empty( $data['initial_results'] ) && is_array( $data['initial_results'] ) ) {
	$initial_results = $data['initial_results'];
	$initial_results_count = isset( $initial_results['found_posts'] ) ? absint( $initial_results['found_posts'] ) : 0;
	$has_initial_results = $initial_results_count > 0;
	$initial_results_html = ! empty( $initial_results['html'] ) ? $initial_results['html'] : '';
	$initial_pagination = ! empty( $initial_results['pagination'] ) ? $initial_results['pagination'] : '';
}

$data['ssr'] = [
	'enabled' => $is_server_prefetch_enabled,
	'has_results' => $has_initial_results,
	'results_html' => $is_server_prefetch_enabled ? $initial_results_html : '',
	'pagination_html' => $is_server_prefetch_enabled ? $initial_pagination : '',
	'attrs' => [
		'results' => $is_server_prefetch_enabled ? ( $has_initial_results ? '' : ' style="display:none;"' ) : '',
		'no_results' => ( $is_server_prefetch_enabled && $has_initial_results ) ? ' style="display:none;"' : '',
		'loader' => $is_server_prefetch_enabled ? ' style="display:none;"' : '',
		'pagination' => $is_server_prefetch_enabled ? ( empty( $initial_pagination ) ? ' style="display:none;"' : '' ) : '',
	],
];

$explore_settings = [
	'ListingWrap' => $data['listing-wrap'],
	'ActiveMobileTab' => $data['mobile_view'],
	'ScrollToResults' => $data['scroll_to_results'],
	'Map' => $data['map'],
	'CircleColor' => $data['circle'],
	'IsFirstLoad' => true,
	'DisableIsotope' => isset($data['disable_isotope']) ? $data['disable_isotope'] : false,
	'DisableLiveUrlUpdate' => $data['disable_live_url_update'],
	'DragSearchEnabled' => $data['drag_search'],
	'DragSearchLabel' => _x( 'Visible map area', 'map drag search label', 'my-listing' ),
	'TermSettings' => $data['categories'],
	'ListingTypes' => $listing_types_config,
	'ExplorePage' => $explore::$explore_page && is_page( $explore::$explore_page->ID ) ? get_permalink( $explore::$explore_page ) : null,
	'ActiveListingType' => $active_listing_type_slug,
	'TermCache' => (object) [],
	'Cache' => (object) [],
	'ScrollPosition' => ! empty( $_GET['sp'] ) ? absint( $_GET['sp'] ) : 0,
	'Template' => $data['template'],
	'Pagination' => isset($data['explore_pagination']) ? $data['explore_pagination'] : 'pages',
	'InfiniteScroll' => isset($data['infinite_scroll']) ? $data['infinite_scroll'] : false,
	'FirstLoadMethod' => $data['first_load_method'],
	'DisplayAd' => $data['display_ad'],
	'AdPublisherID' => $data['ad_pub_id'],
	'AdSlotID' => $data['ad_slot_id'],
	'AdInterval' => $data['ad_interval']
];

$explore_settings_json = wp_json_encode(
	$explore_settings,
	JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
);

if ( false === $explore_settings_json ) {
	$explore_settings_json = '{}';
}

wp_enqueue_script( 'ml:explore' );
wp_add_inline_script(
	'ml:explore',
	'window.CASE27_Explore_Settings = window.CASE27_Explore_Settings || '.$explore_settings_json.';',
	'before'
);
?>

<script type="application/json" id="case27-explore-configuration"><?php echo $explore_settings_json ?></script>
<?php if (!$data['template'] || $data['template'] == 'explore-1' || $data['template'] == 'explore-2'): ?>
	<?php require locate_template( 'templates/explore/regular.php' ) ?>
<?php endif ?>

<?php if ($data['template'] == 'explore-no-map'): ?>
	<?php require locate_template( 'templates/explore/alternate.php' ) ?>
<?php endif ?>
<?php if ($data['template'] === 'explore-classic'): ?>
	<?php require locate_template( 'templates/explore/classic.php' ) ?>
<?php endif ?>

<?php if ( $data['display_ad'] ): ?>
	<?php \MyListing\print_script_tag( $data['ad_pub_id'] ) ?>
<?php endif ?>

<?php if ($data['is_edit_mode']): ?>
    <script type="text/javascript">case27_ready_script(jQuery); MyListing.Explore_Init(); MyListing.Maps.init();</script>
<?php endif ?>
