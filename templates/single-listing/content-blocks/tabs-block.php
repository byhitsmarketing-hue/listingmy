<?php
/**
 * Template for rendering a `tabs` block in single listing page.
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
wp_enqueue_script( 'mylisting-tabs' );
wp_print_styles('mylisting-tabs');
?>

<div class="<?php echo esc_attr( $block->get_wrapper_classes() ) ?>" id="<?php echo esc_attr( $block->get_wrapper_id() ) ?>">
    <div class="element">
        <?php mylisting_locate_template( 'templates/single-listing/content-blocks/partials/block-heading.php', compact( 'block', 'listing' ) ); ?>
        <div class="pf-body">
            <div class="tab-element bl-tabs">
                <div class="bl-tabs-menu">
                    <ul class="nav nav-tabs" role="tablist">

                        <?php foreach ( $rows as $key => $row ): ?>
                            <li role="presentation" class="<?php echo $key === 0 ? 'active' : '' ?>">
                                <a
                                    href="#<?php echo esc_attr( $block->get_unique_id().$key ) ?>"
                                    aria-controls="<?php echo esc_attr( $block->get_unique_id().$key ) ?>"
                                    role="tab"
                                    class="tab-switch"
                                ><?php echo esc_html( $row['title'] ) ?></a>
                            </li>
                        <?php endforeach ?>

                    </ul>
                </div>
                <div class="tab-content">

                    <?php foreach ( $rows as $key => $row ): ?>
                        <div role="tabpanel" class="tab-pane fade <?php echo $key === 0 ? 'in active' : '' ?>" id="<?php echo esc_attr( $block->get_unique_id().$key ) ?>">
                            <div class="wp-editor-content">
                                 <?php echo $row['content'] ?>
                            </div>
                        </div>
                    <?php endforeach ?>

                </div>
            </div>
        </div>
    </div>
</div>
