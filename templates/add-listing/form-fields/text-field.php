<?php
/**
 * Shows `text` form field on listing forms.
 *
 * @since 1.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$max_length = method_exists( $field, 'get_current_character_limit' )
	? $field->get_current_character_limit( 'max' )
	: ( is_numeric( $field['maxlength'] ) && absint( $field['maxlength'] ) > 0 ? absint( $field['maxlength'] ) : null );

$min_length = method_exists( $field, 'get_current_character_limit' )
	? $field->get_current_character_limit( 'min' )
	: ( is_numeric( $field['minlength'] ) && absint( $field['minlength'] ) > 0 ? absint( $field['minlength'] ) : null );
?>
<input
	type="text"
	class="input-text"
	name="<?php echo esc_attr( isset( $field['name'] ) ? $field['name'] : $key ); ?>"
	<?php if ( isset( $field['autocomplete'] ) && false === $field['autocomplete'] ) { echo ' autocomplete="off"'; } ?>
	id="<?php echo esc_attr( $key ); ?>"
	placeholder="<?php echo empty( $field['placeholder'] ) ? '' : esc_attr( $field['placeholder'] ); ?>"
	value="<?php echo isset( $field['value'] ) ? esc_attr( $field['value'] ) : ''; ?>"
	maxlength="<?php echo esc_attr( $max_length ?? '' ); ?>"
	minlength="<?php echo esc_attr( $min_length ?? '' ); ?>"
	<?php if ( ! empty( $field['required'] ) ) echo 'required'; ?>
>
