<?php

if ( ! defined('ABSPATH') ) {
	exit;
}
if ( empty( $options['info_fields'] ) ) {
	return;
} ?>

<ul class="lf-contact no-list-style">
    <?php foreach ( (array) $options['info_fields'] as $info ) {
        $string = c27()->ml_t(
            $info['label'],
            'preview.info-field',
            [
                'listing_type' => $listing->type ?? null,
                'info' => $info,
            ]
        );
        $string = \MyListing\sanitize_preview_card_string( $string, $listing );
        $attributes = [];

        if ( $is_caching ) {
            list( $string, $attributes, $cls ) = \MyListing\prepare_string_for_cache( $string, $listing );
        }

        $content = do_shortcode( $listing->compile_string( $string ) );
        if ( ! empty( $content ) ) { ?>
            <li <?php echo join( ' ', $attributes ) ?>>
            	<?php if ( ! empty( $info['icon'] ) ): ?>
                	<i class="<?php echo esc_attr( $info['icon'] ) ?> sm-icon"></i>
            	<?php endif ?>
                <?php echo $content ?>
            </li>
        <?php }
	} ?>
</ul>
