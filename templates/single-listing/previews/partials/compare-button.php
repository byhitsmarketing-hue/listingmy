<?php
/**
 * Compare button for the preview card template.
 *
 * @since 2.7
 */
if ( ! defined('ABSPATH') ) {
	exit;
}
?>
<li
   class="compare-button-li tooltip-element tooltip-right"
>
    <a aria-label="<?php echo esc_attr( _ex( 'Compare button', 'Preview card compare button - SR', 'my-listing' ) ) ?>" href="#" class="c27-compare-button" onclick="MyListing.Handlers.Compare_Button(event, this)">
       <i class="mi add"></i>
    </a>
    <span class="tooltip-container"><?php echo esc_attr( _x( 'Add to comparison', 'Preview card compare button', 'my-listing' ) ) ?></span>
</li>