<?php
/**
 * Template for rendering a `details` block in single listing page.
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
    <div class="element">
        <?php mylisting_locate_template( 'templates/single-listing/content-blocks/partials/block-heading.php', compact( 'block', 'listing' ) ); ?>
        <div class="pf-body">
            <ul class="outlined-list details-block-content no-list-style">

                <?php foreach ( $rows as $row ): ?>
                    <li>
                        <?php if ( $row['icon'] ): ?>
                            <i class="<?php echo esc_attr( $row['icon'] ) ?>"></i>
                        <?php endif ?>
                        <span class="wp-editor-content"><?php echo $row['content'] ?></span>
                    </li>
                <?php endforeach ?>

            </ul>
        </div>
    </div>
</div>
