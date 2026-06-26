<?php
/**
 * Social network importer form field.
 *
 * @since 2.6
 */
if ( ! defined('ABSPATH') ) {
	exit;
} ?>

<label><?php echo $field->get_label() ?></label>

<div class="pick-method">
	<label>
		<input type="radio" name="<?php echo $field_name ?>[method]" value="default" <?php checked( $method, 'default' ) ?>>
		Method 1
	</label>
	<label>
		<input type="radio" name="<?php echo $field_name ?>[method]" value="serialized" <?php checked( $method, 'serialized' ) ?>>
		Method 2
	</label>
</div>

<div class="import-method method-default mt15">
	<?php foreach ( \MyListing\Src\Forms\Fields\Links_Field::allowed_networks() as $network ): ?>
		<div class="dibvt mb10" style="width:100%;max-width:320px;">
			<label class="dib" style="width:30%";>
				<?php 
				echo !empty($network['icon']) 
				? sprintf('<i class="%s" style="color: %s; width:18px;font-size:16px;"></i>', esc_attr($network['icon']), esc_attr($network['color']))
				: (!empty($network['svg']) ? sprintf('<span style="display: inline-block; fill: %s; width:18px;"  class="svg-icon">%s</span>', esc_attr($network['color']), $network['svg']) : '');
				?>
				<strong><?php echo $network['name'] ?></strong>
			</label>
			<input type="text" name="<?php echo $field_name ?>[links][<?php echo esc_attr( $network['key'] ) ?>]" value="<?php echo $links[ $network['key'] ] ?? '' ?>" class="dib" style="width:60%;">
		</div>
	<?php endforeach ?>
</div>

<div class="import-method method-serialized mt15">
	<p class="mt5 mb5">
		Enter serialized data. If you have exported social networks from another
		MyListing site, you can enter the serialized export field here.
	</p>
	<input type="text" name="<?php echo $field_name ?>[serialized]" value="<?php echo $field_value['serialized'] ?? '' ?>" class="block">
</div>
