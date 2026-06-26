<?php
/**
 * Template for rendering a `social_networks` block in single listing page.
 *
 * @since 1.0
 */
if ( ! defined('ABSPATH') ) {
	exit;
}

$networks = $listing->get_social_networks();
if ( empty( $networks ) ) {
	return;
}

$links = array_map( function( $network ) {
	return [
		'name' => $network['name'],
		'icon' => !empty($network['icon']) 
		? sprintf('<i class="%s"></i>', esc_attr($network['icon'])) 
		: (!empty($network['svg']) ? sprintf('<span class="svg-icon %s">%s</span>', esc_attr($network['class']), $network['svg']) : ''),
		'link' => $network['link'],
		'color' => $network['color'],
		'text_color' => '#fff',
		'target' => '_blank',
	];
}, $networks );
?>

<div class="<?php echo esc_attr( $block->get_wrapper_classes() ) ?>" id="<?php echo esc_attr( $block->get_wrapper_id() ) ?>">
    <div class="element">
        <?php mylisting_locate_template( 'templates/single-listing/content-blocks/partials/block-heading.php', compact( 'block', 'listing' ) ); ?>
        <div class="pf-body">

			<?php if ( $block->get_prop('style') === 'colored-icons' ): ?>

				<?php mylisting_locate_template(
					'templates/single-listing/content-blocks/lists/colored-list.php', [
					'items' => $links
				] ) ?>

			<?php else: ?>

				<?php mylisting_locate_template(
					'templates/single-listing/content-blocks/lists/outlined-list.php', [
					'items' => $links
				] ) ?>

			<?php endif ?>

        </div>
    </div>
</div>