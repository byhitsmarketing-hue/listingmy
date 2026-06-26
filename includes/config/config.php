<?php

namespace MyListing;

if ( ! defined('ABSPATH') ) {
	exit;
}

return [
	'assets'        => require_once locate_template( 'includes/config/assets.config.php' ),
];
