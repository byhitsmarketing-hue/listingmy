<?php
/**
 * Template for rendering a `tags` block in single listing page.
 *
 * @since 1.0
 */
if ( ! defined('ABSPATH') ) {
	exit;
}

// get list of tags
$terms = $listing->get_field( 'tags' );

// validate
if ( empty( $terms ) || is_wp_error( $terms ) ) {
	return;
}
?>

<div class="<?php echo esc_attr( $block->get_wrapper_classes() ) ?>" id="<?php echo esc_attr( $block->get_wrapper_id() ) ?>">
	<div class="element">
		<?php mylisting_locate_template( 'templates/single-listing/content-blocks/partials/block-heading.php', compact( 'block', 'listing' ) ); ?>
		<div class="pf-body">

			<?php mylisting_locate_template(
				'templates/single-listing/content-blocks/lists/outlined-list.php', [
				'items' => array_filter( array_map( function( $term ) {
					if ( ! $term = \MyListing\Src\Term::get( $term ) ) {
						return false;
					}

					return [
						'link' => $term->get_link(),
						'name' => $term->get_name(),
						'color' => $term->get_color(),
						'text_color' => $term->get_text_color(),
						'icon' => $term->get_icon( [ 'background' => false, 'color' => false ] ),
					];
				}, $terms ) )
			] ) ?>

		</div>
	</div>
</div>
