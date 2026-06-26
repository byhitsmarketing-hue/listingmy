<?php
/**
 * Template for rendering the `carousel` template for gallery block in single listing page.
 *
 * @since 1.0
 */
if ( ! defined('ABSPATH') ) {
	exit;
}
wp_enqueue_script( 'mylisting-owl' );
wp_enqueue_script( 'mylisting-photoswipe' );
wp_enqueue_script( 'mylisting-gallery-carousel' );
wp_print_styles('mylisting-photoswipe');
wp_print_styles('mylisting-gallery-carousel');

$items_per_row = min( 3, count( $gallery_items ) );

// if we're displaying a single image, use the full size image
if ( count( $gallery_items ) === 1 && ( $gallery_items[0]['type'] ?? 'image' ) === 'image' ) {
	$gallery_items[0]['url'] = $gallery_items[0]['full_size_url'];
}

$gallery_nav = '';
if ( count( $gallery_items ) > 3 ) {
	ob_start();
	?>
	<div class="gallery-nav">
		<ul class="no-list-style">
			<li><a aria-label="<?php echo esc_attr( _ex( 'Gallery navigation previous', 'Gallery block arrows - SR', 'my-listing' ) ) ?>" href="#" class="gallery-prev-btn"><i class="mi keyboard_arrow_left"></i></a></li>
			<li><a aria-label="<?php echo esc_attr( _ex( 'Gallery navigation next', 'Gallery block arrows - SR', 'my-listing' ) ) ?>" href="#" class="gallery-next-btn"><i class="mi keyboard_arrow_right"></i></a></li>
		</ul>
	</div>
	<?php
	$gallery_nav = ob_get_clean();
}
?>

<div class="<?php echo esc_attr( $block->get_wrapper_classes() ) ?>" id="<?php echo esc_attr( $block->get_wrapper_id() ) ?>">
	<div class="element gallery-carousel-block carousel-items-<?php echo count( $gallery_items ) ?>">
		<?php mylisting_locate_template( 'templates/single-listing/content-blocks/partials/block-heading.php', [
			'block' => $block,
			'listing' => $listing,
			'after_title_style_html' => $gallery_nav,
		] ); ?>

		<div class="pf-body">
			<div class="gallery-carousel owl-carousel photoswipe-gallery"
				data-items="<?php echo absint( $items_per_row ) ?>" data-items-mobile="<?php echo absint( $items_per_row ) ?>">
				<?php foreach ( $gallery_items as $item ): ?>
					<?php if ( ( $item['type'] ?? 'image' ) === 'video' ): ?>
						<a
						aria-label="<?php echo esc_attr( _ex( 'Gallery video', 'Gallery block videos - SR', 'my-listing' ) ) ?>"
						class="item photoswipe-item is-video"
						href="<?php echo esc_url( $item['video_url'] ) ?>"
						data-pswp-type="video"
						data-pswp-mime-type="<?php echo esc_attr( $item['mime_type'] ?? '' ) ?>"
						data-pswp-poster="<?php echo esc_url( $item['poster_url'] ?? '' ) ?>"
						<?php echo ! empty( $item['width'] ) ? sprintf( 'data-full-width="%d"', absint( $item['width'] ) ) : '' ?>
						<?php echo ! empty( $item['height'] ) ? sprintf( 'data-full-height="%d"', absint( $item['height'] ) ) : '' ?>
						<?php echo ! empty( $item['thumb_url'] ) ? sprintf( 'style="background-image: url(\'%s\')"', esc_url( $item['thumb_url'] ) ) : '' ?>
						description="<?php echo esc_attr( $item['description'] ?? '' ) ?>" caption="<?php echo esc_attr( $item['caption'] ?? '' ) ?>" title="<?php echo esc_attr( $item['title'] ?? '' ) ?>"
						>
						<video class="gallery-video-thumb" preload="metadata" muted playsinline aria-hidden="true" tabindex="-1">
							<source
							src="<?php echo esc_url( $item['video_url'] ) ?>"
							<?php echo ! empty( $item['mime_type'] ) ? sprintf( 'type="%s"', esc_attr( $item['mime_type'] ) ) : '' ?>
							>
						</video>
						<i class="mi play_arrow"></i>
					</a>
				<?php else: ?>
					<a
						aria-label="<?php echo esc_attr( _ex( 'Gallery image', 'Gallery block images - SR', 'my-listing' ) ) ?>"
						class="item photoswipe-item"
						href="<?php echo esc_url( $item['full_size_url'] ) ?>"
						style="background-image: url('<?php echo esc_url( $item['url'] ) ?>')"
						description="<?php echo esc_attr( $item['description'] ?? '' ) ?>" caption="<?php echo esc_attr( $item['caption'] ?? '' ) ?>" title="<?php echo esc_attr( $item['title'] ?? '' ) ?>" alt="<?php echo esc_attr( $item['alt'] ?? '' ) ?>"
					></a>
				<?php endif ?>
				<?php endforeach ?>
			</div>
		</div>
	</div>
</div>
