<?php
/**
 * Helper functions for common validation rules for listings fields.
 *
 * @since 1.0
 */

namespace MyListing\Src\Forms\Fields\Traits;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait Validation_Helpers {

	/**
	 * Common validation rule among field with an option list,
	 * e.g. select, multiselect, checkbox, and radio fields.
	 *
	 * @since 2.1
	 */
	public function validateSelectedOption() {
		$value = $this->get_posted_value();
		$has_options = is_array( $this->props['options'] ) && ! empty( $this->props['options'] );

		foreach ( (array) $value as $option ) {
			if ( $has_options && ! in_array( $option, array_keys( $this->props['options'] ) ) ) {
				// translators: %s is the field label.
				throw new \Exception( sprintf( _x( 'Invalid value supplied for %s.', 'Add listing form', 'my-listing' ), $this->props['label'] ) );
			}
		}
	}

	public function validateMinLength( $strip_tags = false ) {
		$value = $this->get_posted_value();
		if ( $strip_tags ) {
			$value = wp_strip_all_tags( $value );
		}

		$min_length = $this->get_current_character_limit( 'min' );

		if ( $min_length !== null && mb_strlen( $value ) < $min_length ) {
			// translators: %1$s is the field label; %2%s is the minimum characters allowed.
			throw new \Exception( sprintf(
				_x( '%1$s can\'t be shorter than %2$s characters.', 'Add listing form', 'my-listing' ),
				$this->props['label'],
				absint( $min_length )
			) );
		}
	}

	public function validateMaxLength( $strip_tags = false ) {
		$value = $this->get_posted_value();
		if ( $strip_tags ) {
			$value = wp_strip_all_tags( $value );
		}

		$max_length = $this->get_current_character_limit( 'max' );

		if ( $max_length !== null && mb_strlen( $value ) > $max_length ) {
			// translators: %1$s is the field label; %2%s is the maximum characters allowed.
			throw new \Exception( sprintf(
				_x( '%1$s can\'t be longer than %2$s characters.', 'Add listing form', 'my-listing' ),
				$this->props['label'],
				absint( $max_length )
			) );
		}
	}

	// Validate work hours
	public function validateWorkHours($validateEachDay = false) {
		if (!$this->props['required']) {
			return;
		}
		$value = $this->get_posted_value();
		$invalidDays = [];
		$validDayExists = false;

		foreach ($value as $day => $data) {
			if (isset($data['status']) && $data['status'] === 'enter-hours') {
				if (!$validateEachDay && count($data) > 1) {
					$from = isset($data[0]['from']) ? $data[0]['from'] : '';
					$to = isset($data[0]['to']) ? $data[0]['to'] : '';

					// translators: %s is the field label.
					if (empty($from) || empty($to)) {
						throw new \Exception(sprintf(_x('%s is a required field.', 'Add listing form', 'my-listing'), $this->props['label']));
					} else {
						$validDayExists = true;
					}
				} else if (count($data) === 1) {
					$invalidDays[] = $day;
				} else if (count($data) > 1) {
					$from = isset($data[0]['from']) ? $data[0]['from'] : '';
					$to = isset($data[0]['to']) ? $data[0]['to'] : '';

					if (empty($from) || empty($to)) {
						if ($validateEachDay) {
							$invalidDays[] = $day;
						} else {
							$validDayExists = true;
						}
					} else {
						$validDayExists = true;
					}
				}
			} elseif (!$validateEachDay && isset($data['status']) && $data['status'] !== 'enter-hours') {
				return;
			}
		}

		// translators: %s is the field label.
		if (!$validateEachDay && !$validDayExists && count($value) > 0) {
			throw new \Exception(sprintf(_x('%s is a required field.', 'Add listing form', 'my-listing'), $this->props['label']));
		}

		if ($validateEachDay && !empty($invalidDays)) {
			$translatedInvalidDays = array_map(function($day) {
				return translate($day, 'my-listing');
			}, $invalidDays);
			$invalidFields = implode(', ', $translatedInvalidDays);
			// translators: %1$s is the field label; %2$s are the days that need to be filled
			throw new \Exception(sprintf(
				_x('%1$s: Fill the schedule for these days %2$s.', 'Work hours validation', 'my-listing'),
				$this->props['label'],
				$invalidFields
			) );
		}

	}

	/**
	 * Determines the relevant package ID from the current context.
	 *
	 * @since 2.12
	 * @param \MyListing\Src\Listing|null $listing Optional. The current listing instance.
	 * @return string|null The WooCommerce Product ID of the package, or null if not found.
	 */
	protected function _get_package_id_from_context( ?\MyListing\Src\Listing $listing = null ): ?string {

		if ( is_singular('job_listing') && $listing instanceof \MyListing\Src\Listing &&
			!in_array( $listing->get_status(), [ 'preview', 'pending_payment', 'draft' ], true ) ) {

			$direct_id = $listing->get_product_id();
			if ( $direct_id ) {
				return (string) $direct_id;
			}
		}

		if ( $listing instanceof \MyListing\Src\Listing && ! empty( $_REQUEST['action'] ) && $_REQUEST['action'] === 'edit' ) {
			return $listing->get_product_id();
		}

		// This is often the primary source during form submissions or AJAX calls.
		if ( ! empty( $_REQUEST['listing_package'] ) ) {
			$package_input = $_REQUEST['listing_package'];
			$resolved_id = \c27()->get_package_id_for_validation( $package_input );
			if ( $resolved_id ) {
				return (string) $resolved_id;
			}
		}

		return null;
	}

	/**
	 * Calculates the effective selection limit for a field.
	 *
	 * Considers the field's default limit and any package-specific overrides.
	 * A limit of 0 in package rules means 'no limit for this package'.
	 *
	 * @since 2.12
	 * @param array $field_props The field's properties array (must contain 'selection_limit', 'enable_package_limits', 'package_limits').
	 * @param string|null $package_id The resolved WooCommerce Product ID of the current package.
	 * @return int|null The selection limit number, or null if no limit is effectively set (0 or not defined).
	 */
	protected function _calculate_current_selection_limit( array $field_props, ?string $package_id ): ?int {
		$limit_to_use = null;
		// for file field we have file_limit instead of selection_limit
		if ( ! empty( $field_props['file_limit'] ) ) {
			$field_props['selection_limit'] = $field_props['file_limit'];
		}

		// Default limit from field settings
		if ( isset( $field_props['selection_limit'] ) && is_numeric( $field_props['selection_limit'] ) ) {
			$default_limit = absint( $field_props['selection_limit'] );
			if ( $default_limit > 0 ) {
				$limit_to_use = $default_limit;
			}
		}

		// Check for package-specific limits if enabled
		$enable_package_limits = $field_props['enable_package_limits'] ?? false;
		if ( $enable_package_limits === true && $package_id !== null ) {
			$package_limits_rules = $field_props['package_limits'] ?? [];
			if ( is_array( $package_limits_rules ) ) {
				foreach ( $package_limits_rules as $rule ) {
					if ( isset( $rule['package'] ) && (string) $rule['package'] === $package_id ) {
						if ( isset( $rule['limit'] ) && is_numeric( $rule['limit'] ) ) {
							$package_rule_limit = absint( $rule['limit'] );
							// A limit of 0 in package rule means 'no limit for this package for this rule',
							// effectively overriding any default field limit for this specific package.
							$limit_to_use = ( $package_rule_limit === 0 ) ? null : $package_rule_limit;
						}
						return $limit_to_use;
					}
				}
			}
		}

		return $limit_to_use;
	}

	/**
	 * Calculates the effective min or max character length considering package overrides.
	 *
	 * @since 2.13
	 * @param array $field_props Field properties array.
	 * @param string|null $package_id Active package product ID.
	 * @param string $limit_type Either 'min' or 'max'.
	 * @return int|null
	 */
	private function _calculate_current_character_limit( array $field_props, ?string $package_id, string $limit_type ): ?int {
		$limit_type = strtolower( $limit_type );
		$prop_key = $limit_type === 'min' ? 'minlength' : 'maxlength';
		$rule_key = $prop_key;

		$limit_to_use = null;

		if ( isset( $field_props[ $prop_key ] ) && is_numeric( $field_props[ $prop_key ] ) ) {
			$default = absint( $field_props[ $prop_key ] );
			if ( $default > 0 ) {
				$limit_to_use = $default;
			}
		}

		if ( ! empty( $field_props['enable_package_limits'] ) && $field_props['enable_package_limits'] === true && $package_id !== null ) {
			$package_limits_rules = $field_props['package_limits'] ?? [];
			if ( is_array( $package_limits_rules ) ) {
				foreach ( $package_limits_rules as $rule ) {
					if ( isset( $rule['package'] ) && (string) $rule['package'] === $package_id ) {
						if ( isset( $rule[ $rule_key ] ) && $rule[ $rule_key ] !== '' && is_numeric( $rule[ $rule_key ] ) ) {
							$package_limit = absint( $rule[ $rule_key ] );
							return $package_limit > 0 ? $package_limit : null;
						}
					}
				}
			}
		}

		return $limit_to_use;
	}

	/**
	 * Retrieves the effective character limit for the field.
	 *
	 * @since 2.13
	 * @param string $limit_type Either 'min' or 'max'.
	 * @return int|null
	 */
	public function get_current_character_limit( string $limit_type = 'max' ): ?int {
		$package_id = $this->_get_package_id_from_context( $this->listing ?? null );
		return $this->_calculate_current_character_limit( $this->props, $package_id, $limit_type );
	}

	/**
	 * Validates the number of selected items against a limit.
	 *
	 * @since 2.12
	 * @param array $submitted_values Array of submitted/selected values.
	 * @param int|null $limit The maximum number of items allowed. Null or 0 means no limit.
	 * @param string $field_label The human-readable label of the field for error messages.
	 * @param string $singular_error_format Singular form error message (use %1$d for limit, %2$s for label).
	 * @param string $plural_error_format Plural form error message (use %1$d for limit, %2$s for label).
	 * @throws \Exception If the number of items exceeds the limit.
	 */
	public function validate_selection_count(
		array $submitted_values,
		?int $limit,
		string $field_label,
		string $singular_error_format = 'You can only select <b>%1$d</b> item in the <b>%2$s</b> field.',
		string $plural_error_format = 'You can only select <b>%1$d</b> items in the <b>%2$s</b> field.'
	): void {
		// If limit is null or 0, it means unlimited selections are allowed.
		if ( $limit === null || $limit === 0 ) {
			return;
		}

		if ( count( $submitted_values ) > $limit ) {
			$message = sprintf(
				_n(
					$singular_error_format,
					$plural_error_format,
					$limit,
					'my-listing'
				),
				$limit,
				esc_html( $field_label )
			);
			throw new \Exception( $message );
		}
	}

}
