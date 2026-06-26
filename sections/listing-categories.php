<?php
$data = c27()->merge_options([
	'terms' => [],
	'taxonomy' => 'job_listing_category',
	'template' => 'template_1',
	'overlay_type' => 'gradient',
	'overlay_gradient' => 'gradient1',
	'overlay_solid_color' => 'rgba(0, 0, 0, .5)',
	'columns' => ['lg' => 3, 'md' => 3, 'sm' => 2, 'xs' => 1],
	'container' => 'container-fluid',
	'gap' => 30,
	'hide_empty_terms' => false,
	'parent_child_hide_icons' => false,
], $data);

$term_ids = array_column((array) $data['terms'], 'category_id');
$items = [];
$explore_link = c27()->get_setting( 'general_explore_listings_page' );

$calculate_term_total_count = static function( $term_obj ) {
	if ( ! $term_obj instanceof \MyListing\Src\Term ) {
		return 0;
	}

	$counts = (array) json_decode( get_term_meta( $term_obj->get_id(), 'listings_full_count', true ), true );
	$counts = array_map( 'absint', $counts );
	$total = array_sum( $counts );

	if ( $total ) {
		return $total;
	}

	return absint( $term_obj->get_data( 'count' ) );
};

if ( $data['taxonomy'] === 'listing_types' ) {
	$types = get_posts( [
		'post_type' => 'case27_listing_type',
		'posts_per_page' => -1,
		'post_status=any',
		'post__in' => $term_ids,
		'orderby' => 'post__in',
	] );

	foreach ( $types as $type ) {
		if ( ! ( $type = \MyListing\Src\Listing_Type::get( $type ) ) ) {
			continue;
		}

		$items[] = [
			'name' => $type->get_plural_name(),
			'link' => add_query_arg( 'type', $type->get_slug(), $explore_link ),
			'image' => $type->get_default_cover(),
			'count' => '',
			'color' => '#f24286',
			'text_color' => '#fff',
			'icon_template_1' => $type->get_icon(),
			'icon_template_2' => $type->get_icon(),
			'count_number' => 0,
			'children' => [],
		];
	}

} elseif ( taxonomy_exists( $data['taxonomy'] ) ) {
	$terms = (array) get_terms( [
		'taxonomy' => $data['taxonomy'],
		'hide_empty' => false,
		'include' => array_filter( $term_ids ) ? : [-1],
		'orderby' => 'include',
	] );

	if ( is_wp_error( $terms ) ) {
		return;
	}

	$hide_empty = ! empty( $data['hide_empty_terms'] );

	foreach ( $terms as $term ) {
		$term = new MyListing\Src\Term( $term );
		$image = $term->get_image();
		$children = [];
		$term_count_number = $calculate_term_total_count( $term );
		$term_count_display = sprintf( '(%s)', number_format_i18n( absint( $term_count_number ) ) );

		if (
			$data['template'] === 'template_parent_child'
			&& is_taxonomy_hierarchical( $data['taxonomy'] )
			&& $term->get_id()
		) {
			$child_terms = get_terms( [
				'taxonomy' => $data['taxonomy'],
				'hide_empty' => false,
				'parent' => $term->get_id(),
				'orderby' => 'name',
				'order' => 'ASC',
			] );

			if ( ! is_wp_error( $child_terms ) ) {
				foreach ( $child_terms as $child_term ) {
					$child = new MyListing\Src\Term( $child_term );
					$child_count_number = $calculate_term_total_count( $child );
					$child_count_display = sprintf( '(%s)', number_format_i18n( absint( $child_count_number ) ) );

					if ( $hide_empty && $child_count_number === 0 ) {
						continue;
					}

					$children[] = [
						'name' => $child->get_name(),
						'link' => $child->get_link(),
						'count' => $child_count_display,
						'count_number' => $child_count_number,
						'icon' => $child->get_icon( [ 'background' => false, 'color' => false ] ),
					];
				}
			}
		}

		$items[] = [
			'name' => $term->get_name(),
			'link' => $term->get_link(),
			'image' => is_array( $image ) ? $image['sizes']['large'] : false,
			'count' => $term_count_display,
			'count_number' => $term_count_number,
			'color' => $term->get_color(),
			'text_color' => $term->get_text_color(),
			'icon_template_1' => $term->get_icon( [ 'background' => false, 'color' => false ] ),
			'icon_template_2' => $term->get_icon( [ 'background' => false ] ),
			'children' => $children,
		];
	}
}


$columns = [
	'lg' => max( 1, absint( $data['columns']['lg'] ) ),
	'md' => max( 1, absint( $data['columns']['md'] ) ),
	'sm' => max( 1, absint( $data['columns']['sm'] ) ),
	'xs' => max( 1, absint( $data['columns']['xs'] ) ),
];

$gap = isset( $data['gap'] ) ? absint( $data['gap'] ) : 30;

$grid_style = sprintf(
	'--ml-col-lg:%1$d;--ml-col-md:%2$d;--ml-col-sm:%3$d;--ml-col-xs:%4$d;--ml-grid-gap:%5$dpx;',
	$columns['lg'],
	$columns['md'],
	$columns['sm'],
	$columns['xs'],
	$gap
);
?>

<?php if ( ! $data['template'] || $data['template'] == 'template_1' ): ?>

	<section class="i-section">
		<div class="<?php echo esc_attr( $data['container'] ) ?>">
			<div class="section-body ml-listing-categories-grid" style="<?php echo esc_attr( $grid_style ); ?>">

				<?php foreach ( $items as $item ): ?>

					<div class="ml-grid-item">
						<div class="listing-cat" >
							<a href="<?php echo esc_url( $item['link'] ) ?>">
								<div class="overlay <?php echo $data['overlay_type'] == 'gradient' ? esc_attr( $data['overlay_gradient'] ) : '' ?>"
                         			 style="<?php echo $data['overlay_type'] == 'solid_color' ? 'background-color: ' . esc_attr( $data['overlay_solid_color'] ) . '; ' : '' ?>"></div>
								<div class="lc-background" style="<?php echo $item['image'] ? "background-image: url('" . esc_url( $item['image'] ) . "');" : ''; ?>">
								</div>
								<div class="lc-info">
									<h4 class="case27-secondary-text"><?php echo esc_html( $item['name'] ) ?></h4>
									<h6><?php echo esc_html( $item['count'] ) ?></h6>
								</div>
								<div class="lc-icon">
									<?php echo $item['icon_template_1'] ?>
								</div>
							</a>
						</div>
					</div>

				<?php endforeach ?>

			</div>
		</div>
	</section>

<?php endif ?>

<?php if ($data['template'] == 'template_2'): ?>

	<section class="i-section">
		<div class="<?php echo esc_attr( $data['container'] ) ?>">
			<div class="section-body ml-listing-categories-grid" style="<?php echo esc_attr( $grid_style ); ?>">

				<?php foreach ( $items as $item ): ?>

					<div class="ml-grid-item ac-category">
						<div class="cat-card" >
							<a href="<?php echo esc_url( $item['link'] ) ?>">
								<div class="ac-front-side face">
									<div class="hovering-c">
										<span class="cat-icon" style="background-color: <?php echo esc_attr( $item['color'] ) ?>;">
											<?php echo $item['icon_template_2']; ?>
										</span>
										<span class="category-name"><?php echo esc_html( $item['name'] ) ?></span>
									</div>
								</div>
								<div class="ac-back-side face" style="background-color: <?php echo esc_attr( $item['color'] ) ?>;">
									<div class="hovering-c">
										<p style="color: <?php echo esc_attr( $item['text_color'] ) ?>;">
											<?php echo esc_html( $item['count'] ) ?>
										</p>
									</div>
								</div>
							</a>
						</div>
					</div>

				<?php endforeach ?>

			</div>
		</div>
	</section>

<?php endif ?>

<?php if ($data['template'] == 'template_3'): ?>

	<section class="i-section">
		<div class="<?php echo esc_attr( $data['container'] ) ?>">
			<div class="ml-listing-categories-grid" style="<?php echo esc_attr( $grid_style ); ?>">

				<?php foreach ( $items as $item ): ?>

					<div class="ml-grid-item car-item">
						<a href="<?php echo esc_url( $item['link'] ) ?>">
							<div class="car-item-container">
								<div class="car-item-img" style="<?php echo $item['image'] ? "background-image: url('" . esc_url( $item['image'] ) . "');" : ''; ?>">
								</div>
								<div class="car-item-details">
									<h3><?php echo esc_html( $item['name'] ) ?></h3>
									<p><?php echo esc_html( $item['count'] ) ?></p>
								</div>
							</div>
						</a>
					</div>

				<?php endforeach ?>

			</div>
		</div>
	</section>

<?php endif ?>

<?php if ($data['template'] == 'template_4'): ?>

	<section class="i-section">
		<div class="<?php echo esc_attr( $data['container'] ) ?>">
			<div class="regions-featured ml-listing-categories-grid" style="<?php echo esc_attr( $grid_style ); ?>">

				<?php foreach ( $items as $item ): ?>

					<div class="ml-grid-item one-region">
						<a href="<?php echo esc_url( $item['link'] ) ?>">
							<div class="region-details">
								<h2 class="case27-secondary-text"><?php echo esc_html( $item['name'] ) ?></h2>
								<h3><?php echo esc_html( $item['count'] ) ?></h3>
							</div>
							<div class="region-image-holder">
								<div class="region-image" style="<?php echo $item['image'] ? "background-image: url('" . esc_url( $item['image'] ) . "');" : ''; ?>">
									<div class="overlay"></div>
								</div>
							</div>
						</a>
					</div>

				<?php endforeach ?>

			</div>
		</div>
	</section>

<?php endif ?>

<?php if ( $data['template'] === 'template_parent_child' ): ?>

	<section class="i-section listing-cats-parent-child">
		<div class="<?php echo esc_attr( $data['container'] ) ?>">
			<div class="section-body ml-listing-categories-grid" style="<?php echo esc_attr( $grid_style ); ?>">

				<?php foreach ( $items as $item ):
					if ( empty( $item['name'] ) ) {
						continue;
					}

					$show_empty = (bool) apply_filters( 'mylisting/categories-widget/show-empty', false, $item, $data );

					// Skip items that have no children to avoid empty columns unless explicitly allowed.
					if ( ! $show_empty && empty( $item['children'] ) ) {
						continue;
					}
				?>
					<div class="ml-grid-item">
						<div class="parent-child-card">
							<a class="parent-header" href="<?php echo esc_url( $item['link'] ) ?>">
								<?php if ( ! $data['parent_child_hide_icons'] && ! empty( $item['icon_template_1'] ) ): ?>
									<span class="parent-icon" aria-hidden="true"><?php echo $item['icon_template_1']; ?></span>
								<?php endif ?>
								<span class="name"><?php echo esc_html( $item['name'] ) ?></span>
								<?php
								$parent_count_display = '';
								if ( isset( $item['count_number'] ) ) {
									$parent_count_display = sprintf( '(%s)', number_format_i18n( absint( $item['count_number'] ) ) );
								}
								if ( '' !== $parent_count_display ):
								?>
									<span class="count"><?php echo esc_html( $parent_count_display ); ?></span>
								<?php endif ?>
							</a>

							<div class="child-grid">
								<?php foreach ( $item['children'] as $child ): ?>
									<a class="child-chip" href="<?php echo esc_url( $child['link'] ) ?>">
										<?php if ( ! $data['parent_child_hide_icons'] && ! empty( $child['icon'] ) ): ?>
											<span class="child-icon" aria-hidden="true"><?php echo $child['icon']; ?></span>
										<?php endif ?>
										<span class="child-name"><?php echo esc_html( $child['name'] ) ?></span>
										<?php
										if ( isset( $child['count_number'] ) ):
										?>
											<span class="child-count"><?php echo esc_html( sprintf( '(%s)', number_format_i18n( absint( $child['count_number'] ) ) ) ); ?></span>
										<?php endif ?>
									</a>
								<?php endforeach ?>
							</div>
						</div>
					</div>
				<?php endforeach ?>

			</div>
		</div>
	</section>

<?php endif ?>
