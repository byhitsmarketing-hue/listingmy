<?php
/**
 * Template for displaying grid listing feed template
 *
 * 
 */

if ( ! defined('ABSPATH') ) {
	exit;
}

if( $data['disable_isotope'] !== 'yes') { wp_enqueue_script( 'mylisting-isotope' ); }
if( $data['template'] === 'grid' ) { wp_enqueue_script( 'mylisting-listing-feed' ); }

?>

<section class="i-section listing-feed <?php echo $data['hide_priority'] ? 'hide-priority' : '' ?>"
	data-lf-config="<?php 
	echo esc_attr( wp_json_encode( [
		'widget_id' => $data['widget_id'],
		'template' => $data['template'],
		'listing_types' => $data['listing_types'],
		'pagination' => $data['pagination'],
		'disable_isotope' => $data['disable_isotope'],
		'filters' => $data['filters'],
		'listing_wrap' => $data['listing_wrap'],
		'query' => $data['query'],
	] ) );
?>"
>
<div class="container-fluid">
	<div class="row section-body">
		<?php if (!empty($listings['html'])): ?>
			<div class="results-wrapper" :class="{ loading: loading, grid: isotope }">
				<?php echo $listings['html']; ?>
			</div>
		<?php endif ?>

		<?php if (empty($listings['found_posts'])): ?>
			<div class="no-results-wrapper">
				<i class="no-results-icon mi mood_bad"></i>
				<li role="presentation" class="no_job_listings_found">
					<?php _e('There are no listings matching your search.', 'my-listing'); ?>
				</li>
			</div>
		<?php endif; ?>
		<?php 
		if ( $data['pagination']['enabled'] && ! empty( $listings['found_posts'] ) && $listings['max_num_pages'] > 1 ):
			if ($data['pagination']['type'] === 'pages'):
				?>
				<div class="col-md-12 center-button pagination c27-explore-pagination">
					<?php echo $listings['pagination'] ?>
				</div>
				<?php 
			elseif($data['pagination']['type'] === 'load-more'): 
				?>
				<a href="#" @click.prevent="loadMore" class="load-more-listings buttons button-5"><?php echo esc_html_x('Load More', 'Listing feed pagination', 'my-listing') ?></a>
				<?php 
			else:
				?>
				<div class="prev-next-pagination">

					<a href="#" @click.prevent="getPrevPage" :class="this.filters.page === null || this.filters.page < 1 ? 'disabled' : ''" class="prev"><i class="material-icons arrow_back"></i><?php echo esc_html_x('Previous', 'Listing feed pagination', 'my-listing') ?></a>
					<a href="#" @click.prevent="getNextPage" class="next"><?php echo esc_html_x('Next', 'Listing feed pagination', 'my-listing') ?><i class="material-icons arrow_forward"></i></a>
				</div>
				<?php
			endif;
		endif; 
		?>
	</div>

</div>
</section>