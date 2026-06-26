<?php
/**
 * Template for rendering the `carousel-with-preview` template for gallery block in single listing page.
 *
 * @since 1.0
 */
if ( ! defined('ABSPATH') ) {
	exit;
}

wp_enqueue_script( 'mylisting-owl' );
wp_enqueue_script( 'mylisting-photoswipe' );
wp_enqueue_script( 'mylisting-gallery-carousel-preview' );
wp_print_styles('mylisting-photoswipe');
wp_print_styles('mylisting-gallery-carousel-preview');

$items_per_row = min( 3, count( $gallery_items ) );
?>

<div class="<?php echo esc_attr( $block->get_wrapper_classes() ) ?>" id="<?php echo esc_attr( $block->get_wrapper_id() ) ?>">
	<div class="element slider-padding gallery-block">
		<?php mylisting_locate_template( 'templates/single-listing/content-blocks/partials/block-heading.php', compact( 'block', 'listing' ) ); ?>

		<div class="pf-body">
			<div class="gallerySlider">
				<div class="owl-carousel galleryPreview photoswipe-gallery">
					<?php foreach ( $gallery_items as $item ): ?>
						<?php if ( ( $item['type'] ?? 'image' ) === 'video' ): ?>
							<a
							aria-label="<?php echo esc_attr( _ex( 'Listing gallery video', 'Gallery block video - SR', 'my-listing' ) ) ?>"
							class="item photoswipe-item is-video"
							href="<?php echo esc_url( $item['video_url'] ) ?>"
							data-pswp-type="video"
							data-pswp-mime-type="<?php echo esc_attr( $item['mime_type'] ?? '' ) ?>"
							data-pswp-poster="<?php echo esc_url( $item['poster_url'] ?? '' ) ?>"
							<?php echo ! empty( $item['width'] ) ? sprintf( 'data-full-width="%d"', absint( $item['width'] ) ) : '' ?>
							<?php echo ! empty( $item['height'] ) ? sprintf( 'data-full-height="%d"', absint( $item['height'] ) ) : '' ?>
							<?php echo ( ! empty( $item['width'] ) && ! empty( $item['height'] ) ) ? sprintf( 'style="aspect-ratio: %d / %d;"', absint( $item['width'] ), absint( $item['height'] ) ) : '' ?>
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
						<a aria-label="<?php echo esc_attr( _ex( 'Listing gallery thumb', 'Gallery block thumb - SR', 'my-listing' ) ) ?>" class="item photoswipe-item" href="<?php echo esc_url( $item['full_size_url'] ) ?>">
							<?php echo apply_filters( 'post_thumbnail_html', '<img src="'. esc_url( $item['url'] ).'" alt="'. esc_attr( $item['alt'] ?? '' ).'" description="' . esc_attr( $item['description'] ?? '' ) . '" caption="' . esc_attr( $item['caption'] ?? '' ) . '" title="' . esc_attr( $item['title'] ?? '' ) . '" >', $listing->get_id(), $item['attachment_id'] ?? 0, 'large', [] ); ?>
						</a>
					<?php endif ?>
					<?php endforeach ?>
				</div>

				<?php if ( count( $gallery_items ) > 1 ): ?>
					<div class="gallery-thumb owl-carousel" data-items="<?php echo absint( $items_per_row ) ?>"
						data-items-mobile="<?php echo absint( $items_per_row ) ?>">
						<?php foreach ( $gallery_items as $key => $item ): ?>
							<a
								aria-label="<?php echo esc_attr( _ex( 'Listing gallery item', 'Gallery block items - SR', 'my-listing' ) ) ?>"
								class="item slide-thumb <?php echo ( $item['type'] ?? 'image' ) === 'video' ? 'is-video' : '' ?>"
								data-slide-no="<?php echo esc_attr( $key ) ?>"
								href="<?php echo esc_url( $item['thumb_url'] ?? $item['url'] ?? '#' ) ?>"
								<?php echo ! empty( $item['thumb_url'] ) ? sprintf( 'style="background-image: url(\'%s\')"', esc_url( $item['thumb_url'] ) ) : '' ?>
								>
									<?php if ( ( $item['type'] ?? 'image' ) === 'video' ): ?>
										<video class="gallery-video-thumb" preload="metadata" muted playsinline aria-hidden="true" tabindex="-1">
											<source
												src="<?php echo esc_url( $item['video_url'] ) ?>"
												<?php echo ! empty( $item['mime_type'] ) ? sprintf( 'type="%s"', esc_attr( $item['mime_type'] ) ) : '' ?>
											>
										</video>
									<?php endif ?>
									<?php if ( ( $item['type'] ?? 'image' ) === 'video' ): ?>
										<i class="mi play_arrow"></i>
									<?php endif ?>
								</a>
						<?php endforeach ?>
					</div>
				<?php endif ?>

				<?php if ( count( $gallery_items ) > 3 ): ?>
					<div class="gallery-nav">
						<ul class="no-list-style">
							<li><a aria-label="<?php echo esc_attr( _ex( 'Gallery navigation previous', 'Gallery block arrows - SR', 'my-listing' ) ) ?>" href="#" class="gallery-prev-btn"><i class="mi keyboard_arrow_left"></i></a></li>
							<li><a aria-label="<?php echo esc_attr( _ex( 'Gallery navigation next', 'Gallery block arrows - SR', 'my-listing' ) ) ?>" href="#" class="gallery-next-btn"><i class="mi keyboard_arrow_right"></i></a></li>
						</ul>
					</div>
				<?php endif ?>
			</div>
		</div>
	</div>
</div>
