<?php
/**
 * Main info (listing title, logo, rating, tagline, cover details/actions).
 * Rendered once and moved between desktop/mobile containers in JS.
 */
?>
<div class="container listing-main-info">
    <div class="col-md-6">
        <div class="profile-name <?php echo esc_attr( $tagline ? 'has-tagline' : 'no-tagline' ) ?> <?php echo esc_attr( $listing->get_rating() ? 'has-rating' : 'no-rating' ) ?>">
            <?php if ( $listing_logo ): ?>
                <?php 
                    wp_enqueue_script( 'mylisting-photoswipe' ); 
                    wp_print_styles('mylisting-photoswipe');
                ?>
                <?php  
                $logo_id = c27()->get_attachment_by_guid( $listing->get_field('logo')[0] ?? null );
                if ( $logo_id ) {
                    $logo_alt = get_post_meta($logo_id, '_wp_attachment_image_alt', true);
                    $logo_title = get_the_title($logo_id);
                    $logo_caption = wp_get_attachment_caption($logo_id);
                    $logo_description = get_post($logo_id)->post_content;   
                }
                ?>
                <a
                	aria-label="<?php echo esc_attr( _ex( 'Listing logo', 'Listing logo - SR', 'my-listing' ) ) ?>"
                    class="profile-avatar open-photo-swipe"
                    href="<?php echo esc_url( $listing->get_logo( 'full' ) ) ?>"
                    style="background-image: url('<?php echo esc_url( $listing_logo ) ?>')"
                    alt="<?php echo $logo_alt ?? '' ?>"
                    title="<?php echo $logo_title ?? '' ?>"
                    caption="<?php echo $logo_caption ?? '' ?>"
                    description="<?php echo $logo_description ?? '' ?>"
                ></a>
            <?php endif ?>

            <h1 class="case27-primary-text">
                <?php echo $listing->get_name() ?>
                <?php if ( $listing->is_verified() ): ?>
                    <span class="verified-badge tooltip-element">
                        <img height="21" width="21" alt="<?php echo esc_attr( _ex( 'Verified listing', 'Alt text for verified icon', 'my-listing' ) ) ?>" class="verified-listing" src="<?php echo esc_url( c27()->image('tick.svg') ) ?>">
                        <span class="tooltip-container"><?php echo esc_attr( _x( 'Verified listing', 'Single listing', 'my-listing' ) ) ?></span>
                    </span>
                <?php endif ?>
                <?php if ( $listing->editable_by_current_user() && function_exists( 'wc_get_account_endpoint_url' ) ):
                    $edit_link = add_query_arg( [
                        'action' => 'edit',
                        'job_id' => $listing->get_id(),
                    ], wc_get_account_endpoint_url( \MyListing\my_listings_endpoint_slug() ) );
                    ?>
                    <span class="tooltip-element">
                        <a
                        href="<?php echo esc_url( $edit_link ) ?>"
                        class="edit-listing"
                        ><i class="mi edit"></i></a>
                        <span class="tooltip-container"><?php echo esc_attr( _x( 'Edit listing', 'Single listing edit link title', 'my-listing' ) ) ?></span>
                    </span>
                <?php endif ?>
            </h1>
            <div class="pa-below-title">
                <?php mylisting_locate_template( 'partials/star-ratings.php', [
                    'rating' => $listing->get_rating(),
                    'max-rating' => MyListing\Ext\Reviews\Reviews::max_rating( $listing->get_id() ),
                    'class' => 'listing-rating',
                ] ) ?>

                <?php if ( $tagline ): ?>
                    <h2 class="profile-tagline listing-tagline-field"><?php echo esc_html( $tagline ) ?></h2>
                <?php endif ?>
            </div>
        </div>
    </div>

    <?php
    /**
     * Quick actions / details list.
     */
    require locate_template( 'templates/single-listing/cover-details.php' );
    ?>
</div>
