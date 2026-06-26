<?php
/**
 * Template for displaying carousel listing feed template
 *
 * 
 */

if ( ! defined('ABSPATH') ) {
	exit;
}

wp_enqueue_script( 'mylisting-owl' ); 
wp_enqueue_script( 'mylisting-background-carousel' ); 
wp_enqueue_script( 'mylisting-listing-feed-carousel' ); 

?>

<section class="i-section listing-feed-2 <?php echo $data['hide_priority'] ? 'hide-priority' : '' ?>">
	<div class="container">
		<div class="row section-body">
			<div class="owl-carousel listing-feed-carousel c27-owl-nav" owl-mobile="<?php echo $data['owl_m'] ?: 1 ?>" owl-tablet="<?php echo $data['owl_t'] ?: 2 ?>" owl-desktop="<?php echo $data['owl_d'] ?: 3 ?>" owl-speed="<?php echo $data['owl_speed'] ?: 2.5 ?>" owl-loop="<?php echo $data['owl_loop'] ? true : false ?>" owl-autoplay="<?php echo $data['owl_autoplay'] ? true : false ?>" nav-style="<?php echo $data['invert_nav'] ? 'light':'' ?>" nav-mode="<?php echo $data['nav_mode'] ?: 'nav' ?>">
				<?php echo $listings['html'] ?? '' ?>

				<?php echo $listings['blank-slide'] ?? '' ?>

			</div>
		</div>
	</div>
</section>