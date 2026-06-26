<?php
/**
 * Template dispatcher for the `location` block.
 *
 * @since 2.11.8
 */
if ( ! defined('ABSPATH') ) {
	exit;
}

// get the field instance
$field = $listing->get_field_object( $block->get_prop( 'show_field' ) );
if ( ! $field || ! $field->get_value() ) {
	return;
}

$locations = $field->get_value();
if ( empty( $locations ) ) {
    return;
}

$display_type = $block->get_prop('display_type');

$template_dir = 'templates/single-listing/content-blocks/location/';
$template_map = [
    'static' => $template_dir . 'static-map.php',
    'interactive' => $template_dir . 'interactive-map.php',
];

$template_file = $template_map[$display_type] ?? $template_map['interactive'];

$template_data = [
	'listing' => $listing,
	'block' => $block,
	'field' => $field,
	'locations' => $locations,
];

mylisting_locate_template( $template_file, $template_data );
