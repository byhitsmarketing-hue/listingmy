<?php
/**
 * Rating filter options template for the listing type editor.
 *
 * @since 2.8
 */

if ( ! defined('ABSPATH') ) {
	exit;
}
?>

<div class="form-group">
	<label>Filter Label</label>
	<input type="text" v-model="filter.label">
</div>

<div class="form-group">
	<label>Default Label</label>
	<input type="text" v-model="filter.default_label" placeholder="Any rating">
	<p class="form-description">Label shown when no rating is selected.</p>
</div>
