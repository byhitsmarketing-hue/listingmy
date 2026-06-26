<?php
/**
 * Template for rendering the `grid` template for gallery block in single listing page.
 *
 * @since 1.0
 */
if ( ! defined('ABSPATH') ) {
	exit;
}
wp_enqueue_script( 'mylisting-photoswipe' );
wp_print_styles('mylisting-photoswipe'); 
wp_print_styles('mylisting-gallery-grid'); 
?>
<div class="<?php echo esc_attr( $block->get_wrapper_classes() ) ?>" id="<?php echo esc_attr( $block->get_wrapper_id() ) ?>">
	<div class="element gallery-grid-block carousel-items-<?php echo count( $gallery_items ) ?>">
		<?php mylisting_locate_template( 'templates/single-listing/content-blocks/partials/block-heading.php', compact( 'block', 'listing' ) ); ?>

		<div class="pf-body">
			<div class="gallery-grid photoswipe-gallery">

				<?php foreach ( $gallery_items as $item ): ?>
					<?php if ( ( $item['type'] ?? 'image' ) === 'video' ): ?>
						<a
						aria-label="<?php echo esc_attr( _ex( 'Listing gallery video', 'Gallery block video - SR', 'my-listing' ) ) ?>"
						class="gallery-item photoswipe-item is-video"
						href="<?php echo esc_url( $item['video_url'] ) ?>"
						data-pswp-type="video"
						data-pswp-mime-type="<?php echo esc_attr( $item['mime_type'] ?? '' ) ?>"
						data-pswp-poster="<?php echo esc_url( $item['poster_url'] ?? '' ) ?>"
						<?php echo ! empty( $item['width'] ) ? sprintf( 'data-full-width="%d"', absint( $item['width'] ) ) : '' ?>
						<?php echo ! empty( $item['height'] ) ? sprintf( 'data-full-height="%d"', absint( $item['height'] ) ) : '' ?>
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
					<a aria-label="<?php echo esc_attr( _ex( 'Listing gallery item', 'Gallery block items - SR', 'my-listing' ) ) ?>" class="gallery-item photoswipe-item" href="<?php echo esc_url( $item['full_size_url'] ) ?>">
						<?php echo apply_filters( 'post_thumbnail_html', '<img src="'. esc_url( $item['url'] ).'" alt="'. esc_attr( $item['alt'] ?? '' ).'" description="' . esc_attr( $item['description'] ?? '' ) . '" caption="' . esc_attr( $item['caption'] ?? '' ) . '" title="' . esc_attr( $item['title'] ?? '' ) . '" >', $listing->get_id(), $item['attachment_id'] ?? 0, 'medium', [] ); ?>
						<i class="mi search"></i>
					</a>
				<?php endif ?>
				<?php endforeach ?>

			</div>
		</div>
	</div>
</div>
