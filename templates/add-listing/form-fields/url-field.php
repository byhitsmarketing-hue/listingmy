<?php if ( ! empty( $field['allow_multiple'] ) ): ?>
	<?php
		wp_enqueue_script( 'ml:repeater-field' );
		wp_enqueue_script( 'mylisting-repeater-ajax-file-upload' );
	?>
	<?php
		$raw_value = $field['value'] ?? [];
		$urls = is_array( $raw_value ) ? $raw_value : ( ! empty( $raw_value ) ? [ $raw_value ] : [] );
		$urls = array_values( array_filter( $urls ) );
		$items = array_map( function( $url ) {
			return [ 'url' => $url ];
		}, $urls );
		if ( empty( $items ) ) {
			$items = [ [ 'url' => '' ] ];
		}
		$selection_limit = method_exists( $field, 'get_current_selection_limit' )
			? $field->get_current_selection_limit()
			: null;
	?>
	<div class="resturant-menu-repeater video-url" data-list="<?php echo htmlspecialchars( json_encode( $items ), ENT_QUOTES, 'UTF-8' ); ?>" data-selection-limit="<?php echo esc_attr( $selection_limit ?? '' ); ?>">
		<div data-repeater-list="<?php echo esc_attr( isset( $field['name'] ) ? $field['name'] : $key ); ?>">
			<div data-repeater-item>
				<input type="url" class="input-text" name="url"
					<?php if ( ! empty( $field['required'] ) ) echo 'required'; ?>
					placeholder="<?php echo esc_attr( $field['placeholder'] ); ?>">
				<i class="mi swap_vert repeater-drag-handle" title="<?php esc_attr_e( 'Drag to reorder', 'my-listing' ); ?>"></i>
				<button data-repeater-delete type="button" class="buttons button-5 icon-only small" aria-label="<?php echo esc_attr( _x( 'Delete Video', 'Video field -> delete item', 'my-listing' ) ); ?>"><i class="material-icons delete"></i></button>
			</div>
		</div>
		<input data-repeater-create type="button" value="<?php esc_attr_e( 'Add Video URL', 'my-listing' ) ?>">
	</div>
	<?php
	c27()->ml_display_field_limits(
		$field,
		_x( 'Maximum %d video can be added.', 'Add listing form', 'my-listing' ),
		_x( 'Maximum %d videos can be added.', 'Add listing form', 'my-listing' )
	);
	?>
<?php else: ?>
	<?php
	$value = $field['value'] ?? '';
	while ( is_array( $value ) ) {
		$value = isset( $value['url'] ) ? $value['url'] : reset( $value );
	}
	?>
	<input type="url" class="input-text" name="<?php echo esc_attr( isset( $field['name'] ) ? $field['name'] : $key ); ?>"
		id="<?php echo esc_attr( $key ); ?>" <?php if ( ! empty( $field['required'] ) ) echo 'required'; ?>
		placeholder="<?php echo esc_attr( $field['placeholder'] ); ?>" value="<?php echo esc_attr( $value ); ?>"
		>
<?php endif ?>
