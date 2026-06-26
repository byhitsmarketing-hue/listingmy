<?php
/**
 * Template for rendering a `code` block in single listing page.
 *
 * @since 1.0
 */
if ( ! defined('ABSPATH') ) {
    exit;
}

$comments = get_comments( array(
    'post_id' => $listing->get_id(),
    'order' => get_option( 'comment_order' ) === 'DESC' ? 'DESC' : 'ASC',
    'status' => 'approve',
) );

$commenter = wp_get_current_commenter();

if ( ! comments_open() || ( ! $block->get_prop('display_review_form') && empty( $comments ) ) ) {
    return;
}
?>

<div class="<?php echo esc_attr( $block->get_wrapper_classes() ) ?>" id="<?php echo esc_attr( $block->get_wrapper_id() ) ?>">
    <div class="element content-block reviews-list">
        <?php mylisting_locate_template( 'templates/single-listing/content-blocks/partials/block-heading.php', compact( 'block', 'listing' ) ); ?>
        <div class="pf-body">
            <div class="comments-list-wrapper" data-current-page="<?php echo esc_attr( get_option( 'default_comments_page' ) === 'newest' ? get_comment_pages_count($comments) : 1 ) ?>" data-page-count="<?php echo esc_attr( get_comment_pages_count($comments) ) ?>">
                <?php if ( empty( $comments ) ): ?>
                    <div class="no-results-wrapper">
                        <i class="no-results-icon material-icons mood_bad"></i>
                        <li class="no_job_listings_found"><?php _e( 'No reviews added yet.', 'my-listing' ); ?></li>
                    </div>
                <?php else: ?>
                    <?php
                    wp_list_comments( array(
                        'walker' => new MyListing\Ext\Reviews\Walker,
                        'type' => 'all',
                        'page' => get_option( 'default_comments_page' ) === 'newest' ? get_comment_pages_count($comments) : 1,
                    ), $comments );
                    ?>
                <?php endif; ?>

                <?php
                if ( get_comment_pages_count( $comments ) > 1 && get_option( 'page_comments' ) ):
                    ?>
                <nav id="comment-nav-below" class="navigation comment-navigation" role="navigation">
                    <h2 class="screen-reader-text"><?php esc_html_e( 'Comment navigation', 'my-listing' ); ?></h2>
                    <div class="nav-links">
                        <div class="nav-next load-more"><a href="#" class="buttons button-5 full-width"><?php echo esc_html__( 'Load more', 'my-listing' ); ?></a></div>
                    </div>
                </nav>
            <?php endif; ?>
            </div>
        </div>
    </div>
    <?php if ($block->get_prop('display_review_form')): ?>
    <?php include(locate_template('partials/review-form.php')); ?>
    <?php endif ?>
</div>
