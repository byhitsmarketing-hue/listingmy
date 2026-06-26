<?php

namespace MyListing\Src\Listing_Types\Content_Blocks;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Location_Block extends Base_Block {

	public function props() {
		$this->props['type'] = 'location';
		$this->props['title'] = 'Location';
		$this->props['icon'] = 'mi map';
		$this->props['display_type'] = 'interactive';
		$this->props['scale_image'] = false;
		$this->props['map_skin'] = 'skin1';
		$this->props['map_zoom'] = 11;
		$this->props['show_field'] = 'job_location';
		$this->allowed_fields = [ 'location' ];
	}

	public function get_editor_options() {
		$this->getLabelField();
		$this->getSourceField();
		$this->getMapSettings();
	}

	protected function getMapSettings() { ?>
		<div class="form-group">
			<label>How would you like to display the map?</label>
			<div class="select-wrapper">
				<select v-model="block.display_type">
					<option value="interactive">Interactive (scrollable/zoomable)</option>
					<option value="static">Static Image (Works with Google Maps & Mapbox)</option>
				</select>
			</div>
		</div>

		<div class="form-group" v-if="block.display_type === 'static'">
			<div class="mb5"></div>
			<label>
				<input type="checkbox" class="form-checkbox" v-model="block.scale_image">
				Enable high-resolution (retina) map image?
			</label>
			<p class="mb0">Note: This increases API usage.</p>
		</div>

		<div class="form-group">
			<label>Map Skin</label>
			<div class="select-wrapper">
				<select v-model="block.map_skin" v-if="block.display_type === 'interactive'">
					<?php foreach ( \MyListing\Apis\Maps\get_skins() as $key => $label ): ?>
						<option value="<?php echo esc_attr( $key ) ?>"><?php echo esc_html( $label ) ?></option>
					<?php endforeach ?>
				</select>
				<select v-model="block.map_skin" v-if="block.display_type === 'static'">
					<option value="skin12">Standard</option>
					<option value="skin1">Light skin</option>
					<option value="skin2">Dark skin</option>
				</select>
			</div>
		</div>

		<div class="form-group">
			<label>Default map zoom level</label>
			<input type="number" min="0" max="22" v-model="block.map_zoom">
			<p class="mb0">Enter a value between 0 (no zoom) and 22 (maximum zoom). Default: 11.</p>
		</div>
	<?php }
}
