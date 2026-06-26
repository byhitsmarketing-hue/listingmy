<?php
/**
 * Template for rendering a `table` block in single listing page.
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
?>

<div class="<?php echo esc_attr( $block->get_wrapper_classes() ) ?>" id="<?php echo esc_attr( $block->get_wrapper_id() ) ?>">
    <div class="element table-block">
        <?php mylisting_locate_template( 'templates/single-listing/content-blocks/partials/block-heading.php', compact( 'block', 'listing' ) ); ?>
        <div class="pf-body">
            <ul class="extra-details no-list-style">

                <?php foreach ( $rows as $row ): ?>
                    <li>
                        <div class="item-attr"><?php echo $row['title'] ?></div>
                        <div class="item-property"><?php echo $row['content'] ?></div>
                    </li>
                <?php endforeach ?>

            </ul>
        </div>
    </div>
</div>
