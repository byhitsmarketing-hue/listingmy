<?php

namespace MyListing;

if ( ! defined('ABSPATH') ) {
	exit;
}

return [
	// to do after 2.11.6
	'styles' => [
		'testimonials',
		'clients-slider',
		'countdown',
		'quick-view-modal',
		'jquery-ui',
	],

	'scripts' => [
		'dialog',
		'testimonials',
		'quick-view-modal',
		'input-limit',
		'wp-editor-limit',
		'cts-carousel',
		[
			'src' => 'basic-search-form',
			'deps' => [ 'c27-main' ],
		],
		[
			'src' => 'explore',
			'deps' => [ 'c27-main', 'ml:dialog' ],
		],
		[
			'src' => 'clients-slider',
			'deps' => [ 'mylisting-owl' ],
		],
		[
			'src' => 'checklist-limit',
			'deps' => [ 'c27-main' ],
		],
		[
			'src' => 'repeater-field',
			'deps' => [ 'jquery', 'jquery-ui-sortable' ],
		],
		// to do: add all script handles here
	],
];
