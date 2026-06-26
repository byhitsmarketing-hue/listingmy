<?php

if ( ! empty( $field['editor-controls'] ) && in_array( $field['editor-controls'], [ 'basic', 'advanced', 'all' ] ) ) {
	$controls = $field['editor-controls'];
} else {
	$controls = 'basic';
}

$editor = [
	'textarea_name' => $key,
	'textarea_rows' => 10,
];

if ( $controls == 'basic' ) {
	$editor['media_buttons'] = false;
	$editor['quicktags'] = false;
	$editor['tinymce'] = [
		'plugins'                       => 'lists,paste,tabfocus,wplink,wordpress',
		'paste_as_text'                 => true,
		'paste_auto_cleanup_on_paste'   => true,
		'paste_remove_spans'            => true,
		'paste_remove_styles'           => true,
		'paste_remove_styles_if_webkit' => true,
		'paste_strip_class_attributes'  => true,
		'toolbar1'                      => 'bold,italic,|,bullist,numlist,|,link,unlink,|,undo,redo',
		'toolbar2'                      => '',
		'toolbar3'                      => '',
		'toolbar4'                      => ''
	];
}

if ( $controls == 'advanced' ) {
	$editor['media_buttons'] = false;
	$editor['quicktags'] = false;
}

$data_maxlength = method_exists( $field, 'get_current_character_limit' )
	? $field->get_current_character_limit( 'max' )
	: ( is_numeric( $field['maxlength'] ) && absint( $field['maxlength'] ) > 0 ? absint( $field['maxlength'] ) : null );

$data_minlength = method_exists( $field, 'get_current_character_limit' )
	? $field->get_current_character_limit( 'min' )
	: ( is_numeric( $field['minlength'] ) && absint( $field['minlength'] ) > 0 ? absint( $field['minlength'] ) : null );

?>
<div class="wp-editor-container"
	data-maxlength="<?php echo esc_attr( $data_maxlength ?? '' ); ?>"
	data-minlength="<?php echo esc_attr( $data_minlength ?? '' ); ?>"
>
	<?php wp_editor( ( isset( $field['value'] ) ? wp_kses_post( $field['value'] ) : '' ), $key, $editor ); ?>
</div>
<?php
