<?php
/**
 * Template for rendering a single listing content block heading.
 *
 * @since 2.16
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $block ) ) {
	return;
}

$heading = $heading ?? $block->get_title();
$heading_tag = $heading_tag ?? apply_filters(
	'mylisting/single-listing/content-block-heading-tag',
	'h3',
	$block,
	$listing ?? null
);

if ( ! in_array( $heading_tag, [ 'h2', 'h3', 'h4', 'h5', 'h6' ], true ) ) {
	$heading_tag = 'h3';
}

$icon = $icon ?? $block->get_icon();
$heading_is_html = ! empty( $heading_is_html );
$head_attrs = ! empty( $head_attrs ) && is_array( $head_attrs ) ? $head_attrs : [];
$after_heading_html = $after_heading_html ?? '';
$after_title_style_html = $after_title_style_html ?? '';
?>

<div class="pf-head"<?php
foreach ( $head_attrs as $attr => $value ):
	if ( ! preg_match( '/^[a-zA-Z_:][a-zA-Z0-9_:\.-]*$/', $attr ) ) {
		continue;
	}
	?> <?php echo esc_attr( $attr ) ?>="<?php echo esc_attr( $value ) ?>"<?php
endforeach;
?>>
	<div class="title-style-1">
		<?php if ( ! empty( $icon ) ): ?>
			<i class="<?php echo esc_attr( $icon ) ?>"></i>
		<?php endif ?>
		<<?php echo tag_escape( $heading_tag ) ?> class="title-style-1__heading">
			<?php echo $heading_is_html ? wp_kses_post( $heading ) : esc_html( $heading ) ?>
		</<?php echo tag_escape( $heading_tag ) ?>>
		<?php echo $after_heading_html ?>
	</div>
	<?php echo $after_title_style_html ?>
</div>
