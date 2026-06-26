<?php
/**
 * Template for displaying alternate Explore page with grid results (no-map).
 *
 * @var   $data
 * @var   $explore
 * @since 2.0
 */

wp_print_styles('mylisting-blog-feed-widget');
if ( ! $data['disable_isotope'] ) {
	wp_enqueue_script('mylisting-isotope');
}
$data['listing-wrap'] = 'col-md-6 col-sm-6 grid-item';
?>
<div<?php echo $data['ssr']['enabled'] ? '' : ' v-cloak'; ?> id="c27-explore-listings" :class="['mobile-tab-'+state.mobileTab]" class="cts-explore explore-classic <?php echo $data['types_template'] === 'dropdown' ? 'explore-types-dropdown' : 'explore-types-topbar' ?> <?php echo esc_attr( $data['finder_columns'] ) ?>">
	<?php if ($data['ssr']['enabled']): ?>
		<div data-template="<?php echo $data['template'] ?>" data-mobile-view="<?php echo $data['mobile_view'] ?>" <?php echo $data['ssr']['enabled'] ? ' v-cloak' : ''; ?> class="ssr-loading"></div>
	<?php endif ?>
	<?php if ( $data['types_template'] === 'topbar' ): ?>
		<?php require locate_template( 'templates/explore/partials/topbar.php' ) ?>
	<?php endif ?>

	<?php require locate_template( 'templates/explore/partials/primary-filters.php' ) ?>

	<div class="finder-container fc-type-2">

		<div class="finder-search collapse" id="finderSearch" :class="( state.mobileTab === 'filters' ? '' : 'visible-lg' )">

			<div class="finder-tabs-wrapper">
				<?php require locate_template( 'templates/explore/partials/sidebar.php' ) ?>
			</div>
		</div>
		<div class="finder-overlay"></div>
	</div>

	<section class="i-section explore-type-4" :class="( state.mobileTab === 'results' ? '' : 'visible-lg' )">
		<div class="container">
			<div class="explore-classic-sidebar col-md-4">
				<div class="element">
					<?php require locate_template( 'templates/explore/partials/sidebar.php' ) ?>
				</div>
			</div>
			<div class="explore-classic-content col-md-8">
				<div class="fl-head row">
					<?= $data['explore_pagination'] === 'pages' ? '<results-header inline-template>' : '<load-more-results ref="loadMoreComponent" inline-template>'; ?>
					<div class="explore-desktop-head" v-if="foundPosts !== 0">
						<?php if ( $data['explore_pagination'] === 'pages' ): ?>
							<div class="load-previews-batch load-batch-icon" :class="! hasPrevPage ? 'batch-unavailable' : ''">
								<a aria-label="<?php echo esc_attr( _ex( 'Load previous results', 'Explore page > Load previous - SR', 'my-listing' ) ) ?>" href="#" @click.prevent="getPrevPage">
									<i class="material-icons arrow_back"></i>
								</a>
							</div>

							<span href="#" class="fl-results-no text-left" v-cloak>
								<span class="rslt-nr" v-html="resultCountText"></span>
							</span>

							<div class="load-next-batch load-batch-icon" :class="{ 'batch-unavailable': ! hasNextPage }">
								<a aria-label="<?php echo esc_attr( _ex( 'Load next results', 'Explore page > Load next - SR', 'my-listing' ) ) ?>" href="#" @click.prevent="getNextPage">
									<i class="material-icons arrow_forward"></i>
								</a>
							</div>
						<?php endif ?>
						<?php if ( $data['explore_pagination'] === 'load-more' ): ?>
							<span v-if="showing" href="#" class="fl-results-no text-left" v-cloak>
								<span class="rslt-nr" v-if="showing === 1"><?php _e( 'Showing <b>1</b> result', 'my-listing' ) ?></span>
								<span class="rslt-nr" v-else-if="showing <= foundPosts">
									<?php echo sprintf( _x( 'Showing <b>%s</b> out of <b>%s</b> results', 'Result count', 'my-listing' ), '{{ showing }}', '{{ foundPosts }}' ); ?>
								</span>
							</span>
						<?php endif ?>
					</div>
					<?= $data['explore_pagination'] === 'pages' ? '</results-header>' : '</load-more-results>'; ?>
				</div>


				<?php if ( $data['explore_pagination'] === 'load-more' ): ?>
					<div class="row results-view fc-type-2-results"
						:class="{
							loading: loading,
							grid: isotope
						}"
						v-show="found_posts !== 0"<?php echo $data['ssr']['attrs']['results']; ?>>
						<?php if ( $data['ssr']['enabled'] && $data['ssr']['results_html'] ) : ?>
							<?php echo $data['ssr']['results_html']; ?>
						<?php endif; ?>
					</div>
				<?php else: ?>
					<div class="row results-view fc-type-2-results" :class="isotope ? 'grid' : ''" v-show="!loading && found_posts !== 0"<?php echo $data['ssr']['attrs']['results']; ?>>
						<?php if ( $data['ssr']['enabled'] && $data['ssr']['results_html'] ) : ?>
							<?php echo $data['ssr']['results_html']; ?>
						<?php endif; ?>
					</div>
				<?php endif ?>

				<?php require locate_template( 'templates/explore/partials/compare-bar.php' ) ?>

				<div class="no-results-wrapper" v-show="!loading && found_posts === 0"<?php echo $data['ssr']['attrs']['no_results']; ?>>
					<i class="no-results-icon mi mood_bad"></i>
					<li role="presentation" class="no_job_listings_found">
						<?php _e( 'There are no listings matching your search.', 'my-listing' ) ?>
						<a href="#" class="reset-results-27 full-width" @click.prevent="resetFilters($event); getListings('reset', true);">
							<i class="mi refresh"></i>
							<?php _e( 'Reset Filters', 'my-listing' ) ?>
						</a>
					</li>
				</div>

				<div class="loader-bg" v-show="loading"<?php echo $data['ssr']['attrs']['loader']; ?>>
					<?php c27()->get_partial( 'spinner', [
						'color' => '#777',
						'classes' => 'center-vh',
						'size' => 28,
						'width' => 3,
					] ) ?>
				</div>

				<?php if ( $data['explore_pagination'] === 'load-more' ): ?>
					<load-more-results inline-template>
						<div class="load-more-btn">
							<a v-show="!$root.loading && $root.found_posts !== 0 && canLoadMore" href="#" @click.prevent="loadMore" class="buttons full-width button-5"><?php _ex('Load more', 'Explore page', 'my-listing') ?></a>
						</div>
					</load-more-results>
				<?php else: ?>
					<div class="row center-button pagination c27-explore-pagination" v-show="!loading"<?php echo $data['ssr']['attrs']['pagination']; ?>>
						<?php if ( $data['ssr']['enabled'] && $data['ssr']['pagination_html'] ) : ?>
							<?php echo $data['ssr']['pagination_html']; ?>
						<?php endif; ?>
					</div>
				<?php endif ?>
			</div>
		</div>
	</section>

	<?php require locate_template( 'templates/explore/partials/mobile-nav.php' ) ?>
</div>
