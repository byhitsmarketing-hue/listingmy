<?php

namespace MyListing\Src\Forms\Fields;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Text_Field extends Base_Field {

	public function init() {
		if ( $this->get_key() === 'job_phone' && $this->props['content_lock'] ) {
			$key = 'phone';
			add_filter( 'mylisting/compile-string/unescaped-fields', function( $fields ) use ( $key ) {
				$fields[] = $key;
				return $fields;
			} );

			add_filter( 'mylisting/compile-string-field/phone', function( $value, $field ) {
				return sprintf( '<div class="c27-display-button" data-listing-id="%1$s" data-field-id="%2$s">%3$s</div>', $field->listing->get_id(), $field->get_key(), esc_html__( 'Show Number', 'my-listing' ) );
			}, 50, 2 );
		}
	}

	public function get_posted_value() {
		return isset( $_POST[ $this->key ] )
			? sanitize_text_field( stripslashes( $_POST[ $this->key ] ) )
			: '';
	}

	public function validate() {
		$value = $this->get_posted_value();
		$this->validateMinLength();
		$this->validateMaxLength();
	}

	public function get_value( $all = false ) {
		$value = parent::get_value();

		if ( $all ) {
			return $value;
		}

		if ( isset( $_REQUEST['action'] ) && in_array( $_REQUEST['action'], [ 'switch', 'duplicate' ], true ) ) {
			return $value;
		}

		$max_length = $this->get_current_character_limit( 'max' );

		if ( $max_length !== null && is_string( $value ) && $value !== '' ) {
			$length_callback = function_exists( 'mb_strlen' ) ? 'mb_strlen' : 'strlen';

			if ( $length_callback( $value ) > $max_length ) {
				$substr_callback = function_exists( 'mb_substr' ) ? 'mb_substr' : 'substr';
				$value = $substr_callback( $value, 0, $max_length );
			}
		}

		return $value;
	}

	public function field_props() {
		$this->props['type'] = 'text';
		$this->props['minlength'] = '';
		$this->props['maxlength'] = '';
		$this->props['content_lock'] = false;
		$this->props['enable_package_limits'] = false;
		$this->props['package_limits'] = [];
	}

	public function get_editor_options() {
		$this->getLabelField();
		$this->getKeyField();
		$this->getPlaceholderField();
		$this->getDescriptionField();

		$this->getMinLengthField();
		$this->getMaxLengthField();
		$this->get_package_specific_limit_options(
			'Character',
			'field.enable_package_limits',
			'field.package_limits',
			[
				[ 'model_key' => 'minlength', 'placeholder' => 'Min characters', 'type' => 'number', 'step' => '1' ],
				[ 'model_key' => 'maxlength', 'placeholder' => 'Max characters', 'type' => 'number', 'step' => '1' ],
			],
			"{package: '', minlength: '', maxlength: ''}"
		);

		$this->getRequiredField();
		$this->getShowInSubmitFormField();
		$this->getShowInAdminField();
		$this->getShowInCompareField();
		$this->getContentLockField();
	}

	protected function getContentLockField() { ?>
		<div class="form-group" v-show="field.slug === 'job_phone'">
			<label>Content Lock</label>
			<label class="form-switch mb0">
				<input type="checkbox" v-model="field.content_lock">
				<span class="switch-slider"></span>
			</label>
		</div>
	<?php }
}
