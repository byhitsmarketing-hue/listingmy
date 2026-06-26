<?php
/**
 * Template for rendering a `raw` block in single listing page.
 *
 * @since 1.0
 */
if ( ! defined('ABSPATH') ) {
	exit;
}

$slot_id = $block->get_prop('slot_id');
$pub_id = $block->get_prop('pub_id');

if ( empty( $slot_id ) || empty( $pub_id ) ) {
	return null;
}

// check conditions
$conditions = new \MyListing\Src\Conditions( $block, $listing );
if ( ! $conditions->passes() ) {
	return null;
}

\MyListing\print_script_tag( $pub_id ); ?>

<div class="<?php echo esc_attr( $block->get_wrapper_classes() ) ?>" id="<?php echo esc_attr( $block->get_wrapper_id() ) ?>">
	<div class="element content-block">
		<?php mylisting_locate_template( 'templates/single-listing/content-blocks/partials/block-heading.php', compact( 'block', 'listing' ) ); ?>
		<div class="pf-body">
			<ins
				class="adsbygoogle"
				style="display:block;"
				data-ad-client="<?php echo esc_attr( $pub_id ) ?>"
				data-ad-slot="<?php echo esc_attr( $slot_id ) ?>"
				data-ad-format="auto"
			></ins>
		</div>
	</div>
</div>
