<?php

namespace MyListing\Src\Forms\Fields;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Video_Url_Field extends Url_Field {

	public function field_props() {
		parent::field_props();
		$this->props['type'] = 'video-url';
	}
}

