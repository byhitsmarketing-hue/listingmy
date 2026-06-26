<?php
/**
 * Display "Head Buttons" in the listing preview card template.
 *
 * @since 1.0
 */
if ( ! defined('ABSPATH') ) {
    exit;
} ?>
<div class="lf-head <?php echo esc_attr( isset( $priority_class ) ? $priority_class : '' ) ?>">
    <?php if ( $listing->get_priority() >= 1 ): ?>
        <span class="tooltip-element tooltip-bottom">
            <div class="lf-head-btn ad-badge">
                <span><i class="icon-flash"></i></span>
            </div>
            <span class="tooltip-container"><?php echo esc_attr( $promotion_tooltip ) ?></span>
        </span>
    <?php endif ?>
    <?php if ( ! empty( $options['buttons'] ) ) {
        foreach ( (array) $options['buttons'] as $button ) {
            $string = c27()->ml_t(
                $button['label'],
                'preview.head-button',
                [
                    'listing_type' => $listing->type ?? null,
                    'button' => $button,
                ]
            );
            $string = \MyListing\sanitize_preview_card_string( $string, $listing );
            $attributes = [];
            $classes = [ 'lf-head-btn', has_shortcode( $button['label'], '27-format' ) ? 'formatted' : '' ];

            if ( $is_caching ) {
                list( $string, $attributes, $cls ) = \MyListing\prepare_string_for_cache( $string, $listing );
                $classes += $cls;
            } elseif ( \MyListing\str_contains( $string, '[[work_hours]]' ) ) {
                $classes[] = 'open-status listing-status-'.$listing->schedule->get_status();
            }

            if ( \MyListing\str_contains( $string, '[[:reviews-stars]]' ) ) {
                $classes[] = 'listing-rating rating-preview-card';
            }

            $content = do_shortcode( $listing->compile_string( $string ) );
            if ( ! empty( $content ) ) { ?>
                <div class="<?php echo esc_attr( join( ' ', $classes ) ) ?>" <?php echo join( ' ', $attributes ) ?>>
                    <?php echo $content ?>
                </div>
            <?php }
        }
    } ?>
</div>
