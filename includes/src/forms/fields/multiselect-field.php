<?php

namespace MyListing\Src\Forms\Fields;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Multiselect_Field extends Base_Field {

	public $modifiers = [
		'labels' => '%s Label(s)',
	];

	public function get_posted_value() {
		return isset( $_POST[ $this->key ] )
			? array_map( 'sanitize_text_field', $_POST[ $this->key ] )
			: [];
	}

	public function validate() {
		$value = $this->get_posted_value();
		$this->validateSelectedOption();

		$limit = $this->get_current_selection_limit();
		$submitted_values = is_array( $value ) ? $value : [];
		$this->validate_selection_count(
			$submitted_values,
			$limit,
			$this->props['label']
		);
	}

	public function field_props() {
		$this->props['type'] = 'multiselect';
		$this->props['options'] = new \stdClass; // when encoded to json, it needs to be {} instead of [].
		$this->props['selection_limit'] = '';
		$this->props['enable_package_limits'] = false;
		$this->props['package_limits'] = [];
	}

	public function get_current_selection_limit() {
		$package_id = $this->_get_package_id_from_context( $this->listing ?? null );
		return $this->_calculate_current_selection_limit( $this->props, $package_id );
	}

	public function get_value( $all = false ) {
		$value = parent::get_value();
		$normalized_value = is_array( $value ) ? $value : (array) $value;

		if ( $all ) {
			return $value;
		}

		if ( isset( $_REQUEST['action'] ) && in_array( $_REQUEST['action'], [ 'switch', 'duplicate' ], true ) ) {
			return $normalized_value;
		}

		$limit = $this->get_current_selection_limit();

		if ( $limit !== null && $limit > 0 && count( $normalized_value ) > $limit ) {
			return array_slice( $normalized_value, 0, $limit );
		}

		return $normalized_value;
	}

	public function get_editor_options() {
		$this->getLabelField();
		$this->getKeyField();
		$this->getPlaceholderField();
		$this->getDescriptionField();
		$this->get_selection_limit_editor_options();
		$this->getRequiredField();
		$this->getShowInSubmitFormField();
		$this->getShowInAdminField();
		$this->getShowInCompareField();
		$this->getOptionsField();
	}

	public function string_value( $modifier = null ) {
		$selected = (array) $this->get_value();
		$options = $this->get_prop('options');
		$validated = [];

		// validate selected options and retrieve their labels
		foreach ( $selected as $value ) {
			if ( isset( $options[ $value ] ) ) {
				$validated[ $value ] = $options[ $value ];
			}
		}

		if ( $modifier === 'labels' ) {
			return join( ', ', $validated );
		}

		return join( ', ', array_keys( $validated ) );
	}
}
