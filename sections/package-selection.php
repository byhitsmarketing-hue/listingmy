<?php
	if (!class_exists('WooCommerce')) return;

	$data = c27()->merge_options([
			'packages' => [],
			'submit_page' => '',
			'submit_url' => '',
		], $data);

	$default_submit_to = ! empty( $data['submit_url'] ) ? $data['submit_url'] : get_permalink( $data['submit_page'] );
?>
<section class="i-section">
	<div class="container-fluid">
		<div class="row section-body">
			<?php foreach ((array) $data['packages'] as $package):
				$product = wc_get_product( $package['package'] ); if (!$product) continue;
				$product_image = false;
				if ( function_exists( 'get_field' ) ) {
					$_product_image = get_field( 'pricing_plan_image', $product->get_id() );
					if ( is_array( $_product_image ) && ! empty( $_product_image['sizes'] ) && ! empty( $_product_image['sizes']['large'] ) ) {
						$product_image = $_product_image['sizes']['large'];
					}
				}

				// Fallback to native meta (attachment ID) when ACF is not available or returns no image.
				if ( ! $product_image ) {
					$att_id = absint( get_post_meta( $product->get_id(), 'pricing_plan_image', true ) );
					if ( $att_id ) {
						$src = wp_get_attachment_image_src( $att_id, 'large' );
						if ( $src && ! empty( $src[0] ) ) {
							$product_image = $src[0];
						}
					}
				}
				?>
				<div class="col-md-4 col-sm-6 col-xs-12">
					<div class="pricing-item <?php echo $package['featured'] ? 'featured' : '' ?>">
						<?php if ( $package['featured'] ): ?>
							<div class="featured-plan-badge">
								<span class="icon-flash"></span>
							</div>
						<?php endif ?>

						<h2 class="plan-name"><?php echo $product->get_title() ?></h2>
						<?php if ( $product_image ): ?>
							<img src="<?php echo esc_url( $product_image ) ?>" alt="Pricing plan image" class="plan-image">
						<?php endif ?>
						<h2 class="plan-price case27-primary-text"><?php echo $product->get_price_html() ?></h2>
						<p class="plan-desc"><?php echo $product->get_short_description() ?></p>
						<div class="plan-features"><?php echo $product->get_description() ?></div>
						<div class="select-package">
							<a class="select-plan buttons button-2" href="<?php echo esc_url( add_query_arg( 'selected_package', $product->get_id(), ! empty( $package['submit_url'] ) ? $package['submit_url'] : $default_submit_to ) ) ?>">
								<i class="material-icons sm-icon send"></i><?php _e( 'Select Plan', 'my-listing' ) ?>
							</a>
						</div>
					</div>
				</div>
			<?php endforeach ?>
		</div>
	</div>
</section>
