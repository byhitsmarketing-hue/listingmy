<?php
/**
 * Template for rendering a `video` block in single listing page.
 *
 * @since 1.0
 */
if ( ! defined('ABSPATH') ) {
	exit;
}

// get field value
$field = $listing->get_field( $block->get_prop( 'show_field' ), true );
$video_value = $listing->get_field( $block->get_prop( 'show_field' ) );
$video_urls = is_array( $video_value ) ? $video_value : ( $video_value ? [ $video_value ] : [] );
$video_urls = array_values( array_filter( $video_urls ) );

// If the field is not configured as multiple anymore, show only the first saved video.
if ( $field && method_exists( $field, 'get_prop' ) && ! $field->get_prop( 'allow_multiple' ) ) {
	$video_urls = array_slice( $video_urls, 0, 1 );
}

$video_urls = array_map( function( $video_url ) {
	while ( is_array( $video_url ) ) {
		$video_url = isset( $video_url['url'] ) ? $video_url['url'] : reset( $video_url );
	}

	return $video_url;
}, $video_urls );

$video_urls = array_values( array_filter( $video_urls ) );
$allowed_video_providers = null;
if ( $field && method_exists( $field, 'get_allowed_video_providers' ) ) {
	$allowed_video_providers = $field->get_allowed_video_providers();
}

$videos = [];
foreach ( $video_urls as $video_url ) {
	$video = \MyListing\Helpers::get_video_embed_details( $video_url, $allowed_video_providers );
	if ( $video ) {
		$videos[] = $video;
	}
}

// validate
if ( empty( $videos ) ) {
	return;
}

$has_gallery = count( $videos ) > 1;
if ( $has_gallery ) {
	wp_enqueue_script( 'mylisting-owl' );
	wp_enqueue_script( 'mylisting-gallery-carousel' );
}
wp_print_styles('mylisting-video-block');

$video_count_badge = '';
if ( $has_gallery ) {
	$videos_count = count( $videos );
	ob_start();
	?>
	<span
		class="video-count-badge"
		aria-label="<?php echo esc_attr( sprintf(
			_n( '%s video', '%s videos', $videos_count, 'my-listing' ),
			number_format_i18n( $videos_count )
		) ) ?>"
	>
		<?php echo esc_html( sprintf(
			_n( '%s video', '%s videos', $videos_count, 'my-listing' ),
			number_format_i18n( $videos_count )
		) ) ?>
	</span>
	<?php
	$video_count_badge = ob_get_clean();
}
?>

<div class="<?php echo esc_attr( $block->get_wrapper_classes() ) ?>" id="<?php echo esc_attr( $block->get_wrapper_id() ) ?>">
	<div class="element video-block">
		<?php mylisting_locate_template( 'templates/single-listing/content-blocks/partials/block-heading.php', [
			'block' => $block,
			'listing' => $listing,
			'after_title_style_html' => $video_count_badge,
		] ); ?>
		<div class="pf-body video-block-body<?php echo $has_gallery ? ' has-carousel' : ''; ?>">
			<?php if ( $has_gallery ): ?>
				<div class="gallery-carousel owl-carousel video-gallery-carousel" data-items="1" data-items-mobile="1">
					<?php foreach ( $videos as $video ): ?>
						<div class="item">
							<iframe src="<?php echo esc_attr( $video['url'] ) ?>" frameborder="0" allowfullscreen></iframe>
						</div>
					<?php endforeach ?>
				</div>

				<div class="prev-next-pagination">
					<a
					aria-label="<?php echo esc_attr( _ex( 'Video navigation previous', 'Video block arrows - SR', 'my-listing' ) ) ?>"
					aria-disabled="true"
					href="#"
					class="prev disabled"
					><?php echo esc_html_x( 'Previous video', 'Video block navigation', 'my-listing' ) ?></a>
					<a
					aria-label="<?php echo esc_attr( _ex( 'Video navigation next', 'Video block arrows - SR', 'my-listing' ) ) ?>"
					aria-disabled="false"
					href="#"
					class="next"
					><?php echo esc_html_x( 'Next video', 'Video block navigation', 'my-listing' ) ?></a>
				</div>
			<?php else: ?>
				<iframe src="<?php echo esc_attr( $videos[0]['url'] ) ?>" frameborder="0" allowfullscreen height="315"></iframe>
			<?php endif ?>
		</div>
	</div>
</div>
