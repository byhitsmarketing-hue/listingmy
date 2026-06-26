<?php

namespace MyListing\Src\Forms\Fields;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Texteditor_Field extends Wp_Editor_Field {

	public function get_posted_value() {
		return isset( $_POST[ $this->key ] )
			? wp_kses_post( trim( stripslashes( $_POST[ $this->key ] ) ) )
			: '';
	}

	public function field_props() {
		parent::field_props();
		$this->props['type'] = 'texteditor';
		$this->props['editor-type'] = 'wp-editor';
		if ( empty( $this->props['package_limits'] ) ) {
			$this->props['package_limits'] = [];
		}
	}

	public function get_editor_options() {
		$this->getLabelField();
		$this->getKeyField();
		$this->getPlaceholderField();
		$this->getDescriptionField();
		$this->getEditorTypeField();
		$this->getEditorControlsField();
		$this->getAllowShortcodesField();

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
	}

	protected function getEditorControlsField() { ?>
		<template v-if="field['editor-type'] == 'wp-editor'">
			<?php parent::getEditorControlsField() ?>
		</template>
	<?php }

	protected function getAllowShortcodesField() { ?>
		<template v-if="field['editor-type'] == 'wp-editor'">
			<?php parent::getAllowShortcodesField() ?>
		</template>
	<?php }

	protected function getEditorTypeField() { ?>
		<div class="form-group">
			<label>Type</label>
			<div class="select-wrapper">
				<select v-model="field['editor-type']">
					<option value="textarea">Textarea</option>
					<option value="wp-editor">WP Editor</option>
				</select>
			</div>
		</div>
	<?php }
}