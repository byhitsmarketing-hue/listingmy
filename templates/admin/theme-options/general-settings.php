<?php
/**
 * Container for the Vue-powered Theme Options interface.
 *
 * @since 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap mylisting-theme-options-wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e( 'Theme Options', 'my-listing' ); ?></h1>
    <div id="mylisting-theme-options" v-cloak></div>
</div>