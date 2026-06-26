<?php
/**
 * Template for rendering a `raw` block in single listing page.
 *
 * @since 1.0
 */
if ( ! defined('ABSPATH') ) {
	exit;
}

// get block content
$content = $block->get_prop('content');
if ( empty( $content ) ) {
	return;
}

// check conditions
$conditions = new \MyListing\Src\Conditions( $block, $listing );
if ( ! $conditions->passes() ) {
	return;
}

// run shortcodes
if ( ! empty( $GLOBALS['wp_embed'] ) ) {
	$content = $GLOBALS['wp_embed']->run_shortcode( $content );
}

$content = do_shortcode( $content );
?>

<div class="<?php echo esc_attr( $block->get_wrapper_classes() ) ?>" id="<?php echo esc_attr( $block->get_wrapper_id() ) ?>">
	<div class="element content-block">
		<?php mylisting_locate_template( 'templates/single-listing/content-blocks/partials/block-heading.php', compact( 'block', 'listing' ) ); ?>
		<div class="pf-body">
			<?php echo $content ?>
		</div>
	</div>
</div>