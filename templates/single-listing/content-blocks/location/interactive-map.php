<?php
/**
 * Template for rendering a `location` block in single listing page.
 *
 * @since 1.0
 */
if ( ! defined('ABSPATH') ) {
	exit;
}

// use the listing logo for the marker image, with fallback to default marker, if none available show marker jpg
$marker_image = $listing->get_marker( 'thumbnail' ) ?: c27()->image( 'marker.jpg' );

$location_arr = [];
foreach ( (array) $locations as $key => $value ) {
	if ( ! $value || ! is_array( $value ) || empty( $value['address'] ) ) {
		continue;
	}

	$location_arr[] = [
        'marker_lat' => $value['lat'],
        'marker_lng' => $value['lng'],
    	'address' 	=> $value['address'],
        'marker_image' => [ 'url' => $marker_image ],
    ];
}

$mapargs = [
	'items_type' => 'custom-locations',
	'marker_type' => 'basic',
	'locations' => $location_arr,
	'skin' => $block->get_prop('map_skin'),
	'zoom' => absint( $block->get_prop('map_zoom') ) ?: 11,
	'draggable' => true,
];
?>

<div class="<?php echo esc_attr( $block->get_wrapper_classes() ) ?>" id="<?php echo esc_attr( $block->get_wrapper_id() ) ?>">
	<div class="element map-block">
		<?php mylisting_locate_template( 'templates/single-listing/content-blocks/partials/block-heading.php', compact( 'block', 'listing' ) ); ?>
		<div class="pf-body">
			<div class="contact-map">
				<div class="c27-map map" data-options="<?php echo c27()->encode_attr( $mapargs ) ?>"></div>
				<div class="c27-map-listings hide"></div>
			</div>
			<div class="map-block-address">
				<ul class="no-list-style">
					<?php foreach ( $location_arr as $location ) :
						$link = \MyListing\get_directions_link( [
							'lat' => $location['marker_lat'] ?? '',
							'lng' => $location['marker_lng'] ?? '',
							'address' => $location['address'] ?? '',
						] );
					?>
						<li>
							<p><?php echo esc_html( $location['address'] ) ?></p>
							<?php if ( $link ) : ?>
								<div class="location-address">
									<a href="<?php echo esc_url( $link ) ?>" target="_blank">
										<?php _ex( 'Get Directions', 'Map Block', 'my-listing' ) ?>
									</a>
								</div>
							<?php endif ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
	</div>
</div>