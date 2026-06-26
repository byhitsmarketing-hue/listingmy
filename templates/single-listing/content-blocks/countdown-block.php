<?php
/**
 * Template for rendering a `countdown` block in single listing page.
 *
 * @since 1.0
 */
if ( ! defined('ABSPATH') ) {
	exit;
}

$diff = $block->get_date_diff();
if ( empty( $diff ) ) {
	return;
}
?>

<div class="<?php echo esc_attr( $block->get_wrapper_classes() ) ?>" id="<?php echo esc_attr( $block->get_wrapper_id() ) ?>">
	<?php 
	wp_print_styles( 'ml:countdown' );
	?>
	<div class="element countdown-box countdown-block">
		<?php mylisting_locate_template( 'templates/single-listing/content-blocks/partials/block-heading.php', compact( 'block', 'listing' ) ); ?>
		<div class="pf-body">
			<ul class="countdown-list no-list-style">
				<li>
					<p><?php echo $diff->invert ? sprintf('%02d', $diff->format('%a')) : '00' ?></p>
					<span><?php _e( 'Days', 'my-listing' ) ?></span>
				</li>
				<li>
					<p><?php echo $diff->invert ? $diff->format('%H') : '00' ?></p>
					<span><?php _e( 'Hours', 'my-listing' ) ?></span>
				</li>
				<li>
					<p><?php echo $diff->invert ? $diff->format('%I') : '00' ?></p>
					<span><?php _e( 'Minutes', 'my-listing' ) ?></span>
				</li>
			</ul>
		</div>
	</div>
</div>
