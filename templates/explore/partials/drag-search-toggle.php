<?php
/**
 * Map controls.
 *
 * @since 2.7.2
 */
if ( ! defined('ABSPATH') ) {
	exit;
}

$show_map_type_control = ! empty( $data['map']['map_type_control'] ) || ! empty( $data['map']['show_satellite_toggle'] );
?>
<?php if ( $data['drag_search'] || $show_map_type_control ): ?>
	<div class="map-controls" :class="{'mb-skin': mapProvider === 'mapbox'}">
		<?php if ( $data['drag_search'] ): ?>
			<div class="map-control mapdrag-switch">
				<div class="md-checkbox">
					<input type="checkbox" v-model="dragSearch" id="explore-drag-toggle">
					<label for="explore-drag-toggle"><?php _ex( 'Search as I move the map', 'Explore', 'my-listing' ) ?></label>
				</div>
			</div>
		<?php endif ?>
		<?php if ( $show_map_type_control ): ?>
			<div class="map-control map-satellite-switch" v-if="mapProvider === 'mapbox'">
				<div class="md-checkbox">
					<input type="checkbox" v-model="satelliteView" id="explore-satellite-toggle">
					<label for="explore-satellite-toggle"><?php _ex( 'Satellite view', 'Explore', 'my-listing' ) ?></label>
				</div>
			</div>
		<?php endif ?>
	</div>
<?php endif ?>
