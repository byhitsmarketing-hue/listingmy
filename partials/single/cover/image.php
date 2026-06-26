<?php
/**
 * Parallax cover image template for single listing page.
 *
 * @since 1.6.0
 */

// Use the empty template if listing cover image isn't available.
if ( ! ( $image = $listing->get_cover_image( 'full' ) ) ) {
    return require locate_template( 'partials/single/cover/none.php' );
}

// Overlay options.
$overlay_opacity = c27()->get_setting( 'single_listing_cover_overlay_opacity', '0.5' );
$overlay_color   = c27()->get_setting( 'single_listing_cover_overlay_color', '#242429' );

// Get the cover height setting
$cover_setting = c27()->get_setting( 'single_listing_cover_height', '35' );

// Parse the setting to get padding and aspect ratio
$cover_options = c27()->parseCoverHeightSetting( $cover_setting );

// Extract padding and aspect ratio
$padding = $cover_options['padding'];
$aspect_ratio = $cover_options['aspect_ratio'];

// Get the image ID
$image_id  = c27()->get_attachment_by_guid( $image );
$image_size  = c27()->get_setting( 'single_listing_cover_picture_quality', 'large' );
?>
<section class="featured-section profile-cover profile-cover-image" style="padding-bottom: <?php echo esc_attr( $padding ); ?>%;">
    <?php
    if ( $image_id ) {
        echo wp_get_attachment_image( $image_id, $image_size, false, [
            'fetchpriority' => 'high',
            'style' => 'width: 100%; height: auto; aspect-ratio:' . esc_attr( $aspect_ratio ) . '; object-fit: cover; position: absolute;',
            'alt' => get_post_meta( $image_id, '_wp_attachment_image_alt', true ) ?: esc_attr__( 'Listing cover image', 'my-listing' ),
        ] );
    } else {
        // Fallback to the image URL if the attachment ID is not found
        echo sprintf(
            '<img src="%s" alt="%s" fetchpriority="high" style="%s">',
            esc_url( $image ),
            esc_attr__( 'Listing cover image', 'my-listing' ),
            esc_attr( 'width:100%; height:auto; aspect-ratio:' . $aspect_ratio . '; object-fit:cover; position:absolute;' )
        );
    }
    ?>
    <div class="overlay"
         style="background-color: <?php echo esc_attr( $overlay_color ); ?>;
                opacity: <?php echo esc_attr( $overlay_opacity ); ?>;"
        >
    </div>
<!-- Omit the closing </section> tag -->