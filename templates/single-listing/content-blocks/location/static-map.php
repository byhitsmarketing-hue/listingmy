<?php
/**
 * Template for rendering a static map in the single listing page with a custom skin.
 *
 * @since 2.11.8
 */
if ( ! defined('ABSPATH') ) {
    exit;
}

$map_provider = mylisting()->get('maps.provider', 'google-maps');

$selected_skin = $block->get_prop( 'map_skin' );

$style_array = c27()->get_map_skins()[$selected_skin] ?? c27()->get_map_skins()['skin12'];
$mapbox_style = 'mapbox/streets-v11';
if ( $selected_skin === 'skin1' ) {
    $mapbox_style = 'mapbox/light-v10';
} elseif ( $selected_skin === 'skin2' ) {
    $mapbox_style = 'mapbox/dark-v10';
}

$markers = [];
foreach ( (array) $locations as $location ) {
    if ( empty( $location['lat'] ) || empty( $location['lng'] ) ) {
        continue;
    }
    $markers[] = [
        'lat' => $location['lat'],
        'lng' => $location['lng'],
    ];
}

// Initialize the map URL.
$map_url = '';

if ( $map_provider === 'google-maps' ) {
    $google_markers = array_map(function($marker) {
        return $marker['lat'] . ',' . $marker['lng'];
    }, $markers);

    $map_url = add_query_arg([
        'size' => '600x300',
        'scale' => $block->get_prop('scale_image') ? 2 : 1,
        'zoom' => $block->get_prop('map_zoom'),
        'maptype' => 'roadmap',
        'markers' => implode('|', $google_markers),
        'key' => mylisting()->get('maps.gmaps_api_key'),
        'style' => implode('&style=', $style_array),
    ], 'https://maps.googleapis.com/maps/api/staticmap');

} elseif ( $map_provider === 'mapbox' ) {
    $mapbox_markers = array_map(function($marker) {
        return sprintf('pin-l(%s,%s)', $marker['lng'], $marker['lat']);
    }, $markers);

    $center_lat = $locations[0]['lat'] ?? 0;
    $center_lng = $locations[0]['lng'] ?? 0;
    $scale_suffix = $block->get_prop( 'scale_image' ) ? '@2x' : '';

    $map_url = sprintf(
        'https://api.mapbox.com/styles/v1/%s/static/%s/%s,%s,%d/%dx%d%s?access_token=%s',
        $mapbox_style,
        implode(',', $mapbox_markers),
        $center_lng,
        $center_lat,
        $block->get_prop('map_zoom') ?: 10,
        600,
        300,
        $scale_suffix,
        mylisting()->get('maps.mapbox_api_key')
    );
}
?>

<div class="static-map <?php echo esc_attr( $block->get_wrapper_classes() ); ?>" id="<?php echo esc_attr( $block->get_wrapper_id() ); ?>">
    <div class="element map-block">
        <?php mylisting_locate_template( 'templates/single-listing/content-blocks/partials/block-heading.php', compact( 'block', 'listing' ) ); ?>
        <div class="pf-body">
            <div class="contact-map">
                <?php
                    $map_link = \MyListing\get_directions_link( [
                        'lat' => $locations[0]['lat'] ?? '',
                        'lng' => $locations[0]['lng'] ?? '',
                        'address' => $locations[0]['address'] ?? '',
                    ] );
                ?>
                <a href="<?php echo esc_url( $map_link ); ?>" target="_blank">
                    <img loading="lazy" src="<?php echo esc_url( $map_url ); ?>" alt="<?php esc_attr_e( 'Static map', 'my-listing' ); ?>">
                </a>
            </div>
            <div class="map-block-address">
                <ul class="no-list-style">
                    <?php foreach ( $locations as $location ) :
                        $link = \MyListing\get_directions_link( [
                            'lat' => $location['lat'] ?? '',
                            'lng' => $location['lng'] ?? '',
                            'address' => $location['address'] ?? '',
                        ] );
                    ?>
                        <li>
                            <p><?php echo esc_html( $location['address'] ?? '' ); ?></p>
                            <?php if ( $link ) : ?>
                                <div class="location-address">
                                    <a href="<?php echo esc_url( $link ); ?>" target="_blank">
                                        <?php _e( 'Get Directions', 'my-listing' ); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>
