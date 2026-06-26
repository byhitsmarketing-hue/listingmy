<div class="lf-item <?php echo esc_attr( 'lf-item-'.$options['template'] ) ?>" data-template="alternate">
    <a href="<?php echo esc_url( $listing->get_link() ) ?>">

        <?php
        /**
         * Include section overlay template.
         *
         * @since 1.0
         */
        require locate_template( 'templates/single-listing/previews/partials/overlay.php' ) ?>

        <?php if ($options['background']['type'] == 'gallery' && ( $gallery = $listing->get_field( 'gallery' ) ) ): ?>
            <div class="pc-slider">
                <div class="pc-slides">
                    <?php foreach ( array_slice( $gallery, 0, $gallery_count ) as $gallery_image ): ?>
                        <img alt="Gallery image" src="<?php echo esc_url( c27()->get_resized_image( $gallery_image, $bg_size ) ) ?>" class="single-slide">
                    <?php endforeach ?>
                </div>
                <div class="gallery-nav">
                    <ul>
                        <li><span aria-label="Prev" href="#" class="pc-slide-prev"><i class="mi keyboard_arrow_left"></i></span></li>
                        <li><span aria-label="Next" href="#" class="pc-slide-next"><i class="mi keyboard_arrow_right"></i></span></li>
                    </ul>
                </div>
            </div>
        <?php else: $options['background']['type'] = 'image'; endif; // Fallback to cover image if no gallery images are present ?>

        <?php if ($options['background']['type'] == 'image' && ( $cover = $listing->get_cover_image( $bg_size ) ) ): ?>
            <div class="lf-background" style="background-image: url('<?php echo esc_url( $cover ) ?>');"></div>
        <?php endif ?>

        <div class="lf-item-info-2">
            <?php if ( $logo = $listing->get_logo() ): ?>
                <div class="lf-avatar" style="background-image: url('<?php echo esc_url( $logo ) ?>')"></div>
            <?php endif ?>

            <h4 class="case27-primary-text listing-preview-title">
                <?php echo $listing->get_name() ?>
                <?php if ( $listing->is_verified() ): ?>
                    <img height="18" width="18" alt="<?php echo esc_attr( _ex( 'Verified listing', 'Alt text for verified icon', 'my-listing' ) ) ?>" class="verified-listing" src="<?php echo esc_url( c27()->image('tick.svg') ) ?>">
                <?php endif ?>
            </h4>

            <?php if ( $tagline ): ?>
                <h6><?php echo esc_html( $tagline ) ?></h6>
            <?php endif ?>

            <?php
            /**
             * Include info fields template.
             *
             * @since 1.0
             */
            require locate_template( 'templates/single-listing/previews/partials/info-fields.php' ) ?>
        </div>

    </a>
        <?php
        /**
         * Include head buttons template.
         *
         * @since 1.0
         */
        require locate_template( 'templates/single-listing/previews/partials/head-buttons.php' ) ?>
</div>

<?php
/**
 * Include footer sections template.
 *
 * @since 1.0
 */
require locate_template( 'templates/single-listing/previews/partials/footer-sections.php' ) ?>