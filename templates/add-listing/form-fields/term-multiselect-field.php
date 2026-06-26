<?php
/**
 * Term multiselect field frontend template.
 *
 * @since 1.0.0
 */

// To maintain backward compatibility, transform every terms field to a 'terms-select'.
if ( $field['type'] !== 'term-select' ) {
	$field['type'] = 'term-select';
	return require locate_template( 'templates/add-listing/form-fields/term-select-field.php' );
}
?>
<div class="cts-term-multiselect">
	<select
		name="<?php echo esc_attr( isset( $field['name'] ) ? $field['name'] : $key ) ?>[]"
		id="<?php echo esc_attr( isset( $field['name'] ) ? $field['name'] : $key ) ?>"
		multiple="multiple"
		<?php if ( ! empty( $field['create_tag'] ) ) echo 'data-create-tags="true"'; ?>
		<?php if ( ! empty( $field['required'] ) ) echo 'required="required"'; ?>
		<?php if ( ! empty( $field['placeholder'] ) ) echo 'placeholder="' . esc_attr( $field['placeholder'] ) . '"'; ?>
		data-mylisting-ajax="true"
		data-mylisting-ajax-url="mylisting_list_terms"
		data-mylisting-ajax-params="<?php echo c27()->encode_attr( [ 'taxonomy' => $field['taxonomy'], 'listing-type-id' => $type_id ] ) ?>"
		data-placeholder="<?php echo esc_attr( ! empty( $field['placeholder'] ) ? $field['placeholder'] : null ) ?>"
		data-allow-clear="true"
		data-selection-limit="<?php echo esc_attr( $field->get_current_selection_limit() ); ?>"
	>
		<?php foreach ( (array) $selected as $term ):
			if ( ! $term instanceof \WP_Term ) continue; ?>
			<option value="<?php echo esc_attr( $term->term_id ) ?>" selected="selected">
				<?php echo esc_attr( $term->name ) ?>
			</option>
		<?php endforeach ?>
	</select>
</div>

<?php
c27()->ml_display_field_limits(
	$field,
	_x( 'Maximum %d item can be added.', 'Add listing form', 'my-listing' ),
	_x( 'Maximum %d items can be added.', 'Add listing form', 'my-listing' )
);
?>

