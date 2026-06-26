<?php
/**
 * Template for displaying regular Explore page with map.
 *
 * @since 1.0
 */
if ( ! defined('ABSPATH') ) {
	exit;
}

wp_print_styles('mylisting-blog-feed-widget');
if ( ! $data['disable_isotope'] ) {
	wp_enqueue_script('mylisting-isotope');
}
$data['listing-wrap'] = 'col-md-12 grid-item';
?>

<div<?php echo $data['ssr']['enabled'] ? '' : ' v-cloak'; ?>
	:class="['mobile-tab-'+state.mobileTab,mapExpanded?'map-expanded':'',loading?'loading-new-results':'']"
	class="cts-explore finder-container fc-type-1 <?php echo esc_attr( $data['finder_columns'] ) ?> <?php echo esc_attr( $data['explore_pagination'] . '-pagination' ) ?> <?php echo $data['types_template'] === 'dropdown' ? 'explore-types-dropdown' : 'explore-types-topbar' ?>"
	id="c27-explore-listings"
	:style="containerStyles"
>

	<?php if ($data['ssr']['enabled']): ?>
		<div data-template="<?php echo $data['template'] ?>" data-mobile-view="<?php echo $data['mobile_view'] ?>" <?php echo $data['ssr']['enabled'] ? ' v-cloak' : ''; ?> class="ssr-loading"></div>
	<?php endif ?>
	<?php if ( $data['types_template'] === 'topbar' ): ?>
		<?php require locate_template( 'templates/explore/partials/topbar.php' ) ?>
	<?php endif ?>

	<?php require locate_template( 'templates/explore/partials/primary-filters.php' ) ?>
	<div @scroll="infiniteScroll" class="<?php echo $data['template'] === 'explore-2' ? 'fc-one-column min-scroll' : 'fc-default' ?>">
		<div class="finder-search min-scroll" id="finderSearch" :class="( state.mobileTab === 'filters' ? '' : 'visible-lg' )">
			<div class="finder-tabs-wrapper">
				<?php require locate_template( 'templates/explore/partials/sidebar.php' ) ?>
			</div>
		</div>
		<div class="finder-listings min-scroll" @scroll="infiniteScroll" id="finderListings" :class="( state.mobileTab === 'results' ? '' : 'visible-lg' )">
			<div class="fl-head">
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

						<?php if ( $data['template'] === 'explore-1' ): ?>
							<a href="#" class="expand-map-btn" v-if="!$root.isMobile && !$root.mapExpanded"	@click.prevent="$root.toggleMap(true)">
								<i class="mi map"></i>
								<span><?php _ex( 'Map view', 'Explore page', 'my-listing' ) ?></span>
							</a>
						<?php endif ?>
					</div>
				<?= $data['explore_pagination'] === 'pages' ? '</results-header>' : '</load-more-results>'; ?>
			</div>
			<?php if ( $data['explore_pagination'] === 'load-more' ): ?>
				<div class="results-view"
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
				<div class="results-view" :class="isotope ? 'grid' : ''" v-show="!loading && found_posts !== 0"<?php echo $data['ssr']['attrs']['results']; ?>>
					<?php if ( $data['ssr']['enabled'] && $data['ssr']['results_html'] ) : ?>
						<?php echo $data['ssr']['results_html']; ?>
					<?php endif; ?>
				</div>
			<?php endif ?>

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
					<div class="load-more-btn col-md-12">
						<a v-show="!$root.loading && $root.found_posts !== 0 && canLoadMore" href="#" @click.prevent="loadMore" class="buttons full-width button-5"><?php _ex('Load more', 'Explore page', 'my-listing') ?></a>
					</div>
				</load-more-results>
			<?php else: ?>
				<div class="col-md-12 center-button pagination c27-explore-pagination" v-show="!loading"<?php echo $data['ssr']['attrs']['pagination']; ?>>
					<?php if ( $data['ssr']['enabled'] && $data['ssr']['pagination_html'] ) : ?>
						<?php echo $data['ssr']['pagination_html']; ?>
					<?php endif; ?>
				</div>
			<?php endif ?>
		</div>
	</div>

	<?php require locate_template( 'templates/explore/partials/compare-bar.php' ) ?>
	<div class="finder-map" id="finderMap" :class="{'map-mobile-visible':state.mobileTab==='map'}">
		<div
			class="map c27-map mylisting-map-loading"
			id="<?php echo esc_attr( 'map__' . uniqid() ) ?>"
			data-options="<?php echo c27()->encode_attr( [
				'skin' => $data['map']['skin'],
				'scrollwheel' => $data['map']['scrollwheel'],
				'mapTypeControl' => ! empty( $data['map']['map_type_control'] ) || ! empty( $data['map']['show_satellite_toggle'] ),
				'zoom' => $data['map']['default_zoom'],
				'minZoom' => $data['map']['min_zoom'],
				'maxZoom' => $data['map']['max_zoom'],
				'defaultLat' => $data['map']['default_lat'],
				'defaultLng' => $data['map']['default_lng'],
			] ) ?>"
		>
		</div>
		<?php require locate_template( 'templates/explore/partials/drag-search-toggle.php' ) ?>
		<?php if ( $data['template'] === 'explore-1' ): ?>
			<a href="#" class="collapse-map-btn" v-if="!isMobile && mapExpanded"
				@click.prevent="$root.toggleMap(false)">
				<i class="mi view_agenda"></i>
				<span><?php _ex( 'List view', 'Explore page', 'my-listing' ) ?></span>
			</a>
		<?php endif ?>
	</div>
	<div style="display: none;">
		<div id="explore-map-location-ctrl" title="<?php echo esc_attr( _x( 'Click to show your location', 'Explore page', 'my-listing' ) ) ?>">
			<i class="mi my_location"></i>
		</div>
	</div>

	<?php require locate_template( 'templates/explore/partials/mobile-nav.php' ) ?>
</div>
