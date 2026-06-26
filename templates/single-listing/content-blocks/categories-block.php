<?php
/**
 * Template for rendering a `categories` block in single listing page.
 *
 * @since 1.0
 */
if ( ! defined('ABSPATH') ) {
	exit;
}

// get list of categories
$terms = $listing->get_field( 'category' );

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
				'templates/single-listing/content-blocks/lists/colored-list.php', [
				'items' => array_filter( array_map( function( $term ) {
					if ( ! $term = \MyListing\Src\Term::get( $term ) ) {
						return false;
					}

					return [
						'link' => $term->get_link(),
						'name' => $term->get_name(),
						'color' => $term->get_color(),
						'icon' => $term->get_icon( [ 'background' => false ] ),
					];
				}, $terms ) )
			] ) ?>

		</div>
	</div>
</div>