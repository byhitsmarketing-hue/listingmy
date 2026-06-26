<?php
/**
 * Template for rendering an `accordion` block in single listing page.
 *
 * @since 1.0
 */
if ( ! defined('ABSPATH') ) {
    exit;
}

$rows = $block->get_formatted_rows( $listing );
if ( empty( $rows ) ) {
	return;
}
wp_enqueue_script('mylisting-accordions');
wp_print_styles('mylisting-accordions');
?>

<div class="<?php echo esc_attr( $block->get_wrapper_classes() ) ?>" id="<?php echo esc_attr( $block->get_wrapper_id() ) ?>">
    <div class="element">
        <?php mylisting_locate_template( 'templates/single-listing/content-blocks/partials/block-heading.php', compact( 'block', 'listing' ) ); ?>
        <div class="pf-body">
            <ul class="no-list-style">
                <div class="panel-group block-accordion <?php echo esc_attr( $block->get_unique_id().'-accordion' ) ?>" role="tablist" aria-multiselectable="true">

                    <?php foreach ( $rows as $key => $row ): ?>
                        <div class="panel panel-default">
                            <div class="panel-heading" role="tab">
                                <h4 class="panel-title">
                                    <a
                                        role="button"
                                        data-component="mylisting-accordion"
                                        data-parent=".<?php echo esc_attr( $block->get_unique_id().'-accordion' ) ?>"
                                        href="#<?php echo esc_attr( $block->get_unique_id().$key ) ?>"
                                    ><?php echo esc_html( $row['title'] ) ?></a>
                                </h4>
                            </div>
                            <div id="<?php echo esc_attr( $block->get_unique_id().$key ) ?>" class="panel-collapse collapse <?php echo $key === 0 ? 'in' : '' ?>" role="tabpanel">
                                <div class="panel-body wp-editor-content">
                                    <p><?php echo $row['content'] ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach ?>

                </div>
            </ul>
        </div>
    </div>
</div>
