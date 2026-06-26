<?php
/**
 * Template for rendering a `work_hours` block in single listing page.
 *
 * @since 1.0
 */
if ( ! defined('ABSPATH') ) {
	exit;
}

// get work hours
$work_hours = $listing->get_field( 'work_hours' ) ;
$schedule = new MyListing\Src\Work_Hours( $work_hours );
$block->add_wrapper_classes( 'open-now sl-zindex' );

// validate
if ( ! $work_hours || $schedule->is_empty() ) {
	return;
}
wp_enqueue_script('mylisting-accordions');

$work_hours_heading = sprintf(
	'<span class="%s work-hours-status">%s</span>',
	esc_attr( $schedule->get_status() ),
	esc_html( $schedule->get_message() )
);

ob_start();
?>
<div class="timing-today">
	<?php echo $schedule->get_todays_schedule() ?>
	<span class="tooltip-element center-flex">
		<span class="mi expand_more"></span>
		<span class="tooltip-container"><?php esc_attr_e( 'Toggle weekly schedule', 'my-listing' ) ?></span>
	</span>
</div>
<?php
$work_hours_after_heading = ob_get_clean();
?>

<div class="<?php echo esc_attr( $block->get_wrapper_classes() ) ?>" id="<?php echo esc_attr( $block->get_wrapper_id() ) ?>">
	<div class="element work-hours-block">
		<?php mylisting_locate_template( 'templates/single-listing/content-blocks/partials/block-heading.php', [
			'block' => $block,
			'listing' => $listing,
			'heading' => $work_hours_heading,
			'heading_is_html' => true,
			'head_attrs' => [
				'data-component' => 'mylisting-accordion',
				'data-target' => '#'.$block->get_unique_id().'-toggle',
			],
			'after_heading_html' => $work_hours_after_heading,
		] ); ?>
		<div class="open-hours-wrapper pf-body collapse <?php echo $block->get_prop('collapse') ? 'in' : '' ?>" id="<?php echo esc_attr( $block->get_unique_id().'-toggle' ) ?>">
			<div id="open-hours">
				<ul class="extra-details no-list-style">

					<?php foreach ( $schedule->get_schedule() as $weekday ): ?>
						<li>
							<p class="item-attr"><?php echo esc_html( $weekday['day_l10n'] ) ?></p>
							<p class="item-property"><?php echo $schedule->get_day_schedule( $weekday['day'] ) ?></p>
						</li>
					<?php endforeach ?>

					<?php if ( ! empty( $work_hours['timezone'] ) ):
						$localTime = new DateTime( 'now', new DateTimeZone( $work_hours['timezone'] ) );
						?>
						<p class="work-hours-timezone">
							<em><?php printf(
								__( '%s local time', 'my-listing' ),
								date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
								strtotime( $localTime->format('Y-m-d H:i:s') ) )
							) ?></em>
						</p>
					<?php endif ?>

				</ul>
			</div>
		</div>
	</div>
</div>
