<?php
/**
 * Template for rendering a `gallery` block in single listing page.
 *
 * @since 1.0
 */
if ( ! defined('ABSPATH') ) {
	exit;
}

// get the field instance
if ( ! ( $field = $listing->get_field_object( $block->get_prop( 'show_field' ) ) ) ) {
	return;
}

// get the gallery template to display
$gallery_type = $block->get_prop('gallery_type');
$field_value = $listing->get_field( $block->get_prop('show_field') );

// validate all media and format values for use in templates
$gallery_items = [];
foreach ( (array) $field_value as $file ) {
	if ( is_array( $file ) ) {
		$file = array_shift( $file );
	}

	if ( ! is_string( $file ) || ! ( $file = trim( $file ) ) ) {
		continue;
	}

	$file_url = $file;
	$file_url_clean = current( explode( '?', $file_url ) );

	$attachment_id = attachment_url_to_postid( $file_url_clean );
	if ( ! $attachment_id ) {
		$attachment_id = c27()->get_attachment_by_guid( $file_url_clean );
	}

	$file_info = wp_check_filetype( $file_url_clean );
	$mime_type = $attachment_id ? get_post_mime_type( $attachment_id ) : '';
	if ( ! $mime_type ) {
		$mime_type = $file_info['type'] ?? '';
	}

	if ( ! empty( $file_info['type'] ) && (
		strpos( $file_info['type'], 'image/' ) === 0
		|| strpos( $file_info['type'], 'video/' ) === 0
	) ) {
		$mime_type = $file_info['type'];
	}

	if ( $mime_type && strpos( $mime_type, 'video/' ) === 0 ) {
		$item_meta = [];
		if ( $attachment_id && ( $attachment = get_post( $attachment_id ) ) ) {
			$item_meta = [
				'title' => get_the_title( $attachment_id ),
				'caption' => wp_get_attachment_caption( $attachment_id ),
				'description' => $attachment->post_content,
			];
		}

		$video_width = 0;
		$video_height = 0;
		if ( $attachment_id ) {
			$video_metadata = wp_get_attachment_metadata( $attachment_id );
			if ( is_array( $video_metadata ) ) {
				$video_width = absint( $video_metadata['width'] ?? 0 );
				$video_height = absint( $video_metadata['height'] ?? 0 );
			}
		}

		$poster_url = '';
		if ( $attachment_id ) {
			$poster = wp_get_attachment_image_src( $attachment_id, 'large', true );
			$poster_url = is_array( $poster ) && ! empty( $poster[0] ) ? $poster[0] : '';
		}

		if ( ! $poster_url && $attachment_id ) {
			$poster_url = wp_mime_type_icon( $attachment_id );
		}

		$gallery_items[] = array_merge( [
			'type' => 'video',
			'video_url' => $file_url,
			'mime_type' => $mime_type,
			'poster_url' => $poster_url,
			'thumb_url' => $poster_url,
			'width' => $video_width,
			'height' => $video_height,
		], $item_meta );

		continue;
	}

	if ( ! $mime_type || strpos( $mime_type, 'image/' ) !== 0 ) {
		continue;
	}

	$full_url = $attachment_id ? wp_get_attachment_url( $attachment_id ) : $file_url;
	$image_url = c27()->get_resized_image(
		$file_url,
		$gallery_type === 'carousel-with-preview' ? 'large' : 'medium'
	);

	if ( ! $image_url ) {
		continue;
	}

	$item_meta = [];
	if ( $attachment_id && ( $attachment = get_post( $attachment_id ) ) ) {
		$item_meta = [
			'alt' => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
			'title' => get_the_title( $attachment_id ),
			'caption' => wp_get_attachment_caption( $attachment_id ),
			'description' => $attachment->post_content,
		];
	}

	$gallery_items[] = array_merge( [
		'type' => 'image',
		'attachment_id' => absint( $attachment_id ),
		'url' => $image_url,
		'full_size_url' => $full_url ?: $image_url,
		'thumb_url' => $image_url,
	], $item_meta );
}

// if no valid gallery items are found, don't display the block
if ( empty( $gallery_items ) ) {
	return;
}

if ( $gallery_type === 'carousel-with-preview' ) {
	require locate_template( 'templates/single-listing/content-blocks/gallery-block/carousel-with-preview.php' );
} elseif ( $gallery_type === 'grid' ) {
	require locate_template( 'templates/single-listing/content-blocks/gallery-block/grid.php' );
} else {
	require locate_template( 'templates/single-listing/content-blocks/gallery-block/carousel.php' );
}
