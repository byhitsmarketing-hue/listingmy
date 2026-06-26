<?php

namespace MyListing\Src\Listing_Types\Content_Blocks;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Reviews_Block extends Base_Block {

	public function props() {
		$this->props['type'] = 'reviews';
		$this->props['title'] = 'Reviews';
		$this->props['icon'] = 'mi rate_review';
		$this->props['display_review_form'] = true;
	}

	public function get_editor_options() {
		$this->getLabelField();
		?>
		<div class="form-group">
			<div class="mb5"></div>
			<label>
				<input type="checkbox" class="form-checkbox" v-model="block.display_review_form">
				Display "Add review" form?
			</label>
		</div>
		<?php
	}
}