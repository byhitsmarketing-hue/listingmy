<?php
/**
 * Single listing cover details template.
 *
 * @since 2.0
 */

$details = [];
$actions = [];

foreach ( (array) $layout['cover_details'] as $detail ) {
    if ( empty( $detail['id'] ) ) {
        $detail['id'] = sprintf( 'cd-%s', substr( md5( json_encode( $detail ) ), 0, 6 ) );
    }

    $detail['content'] = '';
    if ( ! empty( $detail['prefix'] ) ) {
        $detail['content'] .= sprintf( '<span class="prefix">%s</span>', $detail['prefix'] );
    }

    $field = $listing->get_field_object( $detail['field'] );
    $field_value = $field ? $field->get_string_value() : '';
    if ( $field && $field->get_type() == 'location' ) {
	    $field_value = $field->get_string_value('address');
	}
    $date_format = get_option( 'date_format' );
    $time_format = get_option( 'time_format' );
    if ( $field && $field->get_type() == 'recurring-date' ) {
        $recurring_dates = $field->get_value();
        $upcoming_date = \MyListing\Src\Recurring_Dates\get_upcoming_instances( $recurring_dates, 1 );
        $previous_date = \MyListing\Src\Recurring_Dates\get_previous_instances( $recurring_dates, 1 );
        $upcoming_date = $upcoming_date ?: $previous_date;
        if ( empty($upcoming_date )) {
            continue;
        }
    }
    if ( ! empty( $field_value ) || in_array( $field_value, [ 0, '0', 0.0 ], true ) ) {
        switch ( $detail['format'] ) {
            case 'number':
                $field_value = $field_value;
                if (is_numeric($field_value)) {
                    $field_value = number_format_i18n( $field_value, $detail['decimals'] ?? 0 );
                }
                break;
            case 'date':
                if ( $field && $field->get_type() == 'recurring-date' ) {
                    $start = $upcoming_date[0]['start'];
                    $end = $upcoming_date[0]['end'];
                    $field_value = c27()->format_date_range($start, $end, $date_format);
                } else {
                    $field_value = date_i18n( $date_format, strtotime( $field_value ) );
                }
                break;
            case 'datetime':
                if ( $field && $field->get_type() == 'recurring-date' ) {
                    $start = $upcoming_date[0]['start'];
                    $end = $upcoming_date[0]['end'];
                    $field_value = c27()->format_date_range($start, $end, $date_format, $time_format);
                } else {
                	$field_value = date_i18n( $date_format . ' ' . $time_format, strtotime( $field_value ) );
                }
                break;
            case 'time':
                if ( $field && $field->get_type() == 'recurring-date' ) {
	            	$date1 = date_i18n( $time_format, strtotime( $upcoming_date[0]['start'] ) );
	            	$date2 = date_i18n( $time_format, strtotime( $upcoming_date[0]['end'] ) );
	            	$field_value = $date1 . ' - ' . $date2;
	            } else {
                	$field_value = date_i18n( $time_format, strtotime( $field_value ) );
	            }
                break;
        }
        $detail['content'] .= $field_value;
    }

    if ( empty( trim( $field_value ) ) && ! in_array( $field_value, [ 0, '0', 0.0 ], true ) ) {
        continue;
    }

    if ( ! empty( $detail['suffix'] ) ) {
        $detail['content'] .= sprintf( '<span class="suffix">%s</span>', $detail['suffix'] );
    }

    if ( ! empty( $detail['content'] ) || in_array( $detail['content'], [ 0, '0', 0.0 ], true ) ) {
        $details[] = $detail;
    }
}

?>
<div class="col-md-6">
    <div class="listing-main-buttons <?php printf( 'detail-count-%d', count( (array) $layout['cover_actions'] ) + count( $details ) ) ?>">
        <ul class="no-list-style">
            <?php foreach ( $details as $detail ): ?>
                <li class="price-or-date">
                    <div class="lmb-label"><?php echo c27()->ml_t(
                        $detail['label'],
                        'single.cover-detail',
                        [
                            'listing' => $listing,
                            'detail' => $detail,
                        ]
                    ) ?></div>
                    <div class="value"><?php echo $detail['content'] ?></div>
                </li>
            <?php endforeach ?>

            <?php foreach ( $layout['cover_actions'] as $action ):
                if ( empty( $action['id'] ) ) {
                    $action['id'] = sprintf( 'cta-%s', substr( md5( json_encode( $action ) ), 0, 6 ) );
                }

                $action['class'] .= 'lmb-calltoaction';
                $action['original_label'] = $action['label'];

                // Allow translations for cover action labels before compiling string.
                $action['label'] = c27()->ml_t(
                    $action['label'],
                    'single.cover-action',
                    [
                        'listing' => $listing,
                        'action' => $action,
                    ]
                );
                if ( isset( $action['active_label'] ) && $action['active_label'] !== '' ) {
                    $action['original_active_label'] = $action['active_label'];
                    $action['active_label'] = c27()->ml_t(
                        $action['original_active_label'],
                        'single.cover-action-active',
                        [
                            'listing' => $listing,
                            'action' => $action,
                        ]
                    );
                    $action['active_label'] = do_shortcode( $listing->compile_string( $action['active_label'] ) );
                }

                $action['label'] = do_shortcode( $listing->compile_string( $action['label'] ) );

                if ( ! empty( $action['track_custom_btn'] ) ) {
                    $action['class'] .= ' ml-track-btn';
                }
                
                $template = sprintf( 'templates/single-listing/quick-actions/%s.php', $action['action'] ); ?>
                <?php if ( locate_template( $template ) ): ?>
                    <?php require locate_template( $template ) ?>
                <?php elseif ( has_action( sprintf( 'mylisting/single/quick-actions/%s', $action['action'] ) ) ): ?>
                    <?php do_action( sprintf( 'mylisting/single/quick-actions/%s', $action['action'] ), $action, $listing ) ?>
                <?php else: ?>
                    <?php // dump($action) ?>
                <?php endif ?>
            <?php endforeach ?>
        </ul>
    </div>
</div>
