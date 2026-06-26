<?php
/**
 * The template for displaying reviews.
 *
 * If the current post is protected by a password and
 * the visitor has not yet entered the password we will
 * return early without loading the comments.
 */

if ( post_password_required() ) {
	return;
}

$comments_wrapper = comments_open() ? 'col-md-7' : 'col-md-12';
$comment_form_wrapper = 'col-md-5';

if ( is_singular('post') ) {
	$comments_wrapper = 'col-md-8 col-md-offset-2';
	$comment_form_wrapper = 'col-md-8 col-md-offset-2';
}


?>

<div class="container">
	<div class="row">
		<div class="<?php echo esc_attr( $comments_wrapper ) ?> comments-list-wrapper" data-current-page="<?php echo esc_attr( get_option( 'default_comments_page' ) === 'newest' ? get_comment_pages_count() : 1 ) ?>" data-page-count="<?php echo esc_attr( get_comment_pages_count() ) ?>">

			<?php if (!comments_open()): ?>
				<div class="no-results-wrapper">
					<i class="no-results-icon material-icons mood_bad"></i>
					<li class="no_job_listings_found"><?php _e( 'Comments are closed.', 'my-listing' ) ?></li>
				</div>
			<?php else: ?>
				<?php if (!have_comments()): ?>
					<div class="no-results-wrapper">
						<i class="no-results-icon material-icons mood_bad"></i>
						<li class="no_job_listings_found"><?php _e( 'No comments yet.', 'my-listing' ) ?></li>
					</div>
				<?php else: ?>
					<?php
					wp_list_comments( [
						'walker' => new MyListing\Ext\Reviews\Walker,
						'type' => 'all',
					] );
					?>
				<?php endif ?>
			<?php endif ?>

			<?php if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ): ?>
				<nav id="comment-nav-below" class="navigation comment-navigation" role="navigation">
					<h2 class="screen-reader-text"><?php esc_html_e( 'Comment navigation', 'my-listing' ); ?></h2>
					<div class="nav-links">
					<?php if ( is_singular('job_listing') ): ?>
						<div class="nav-next load-more"><a href="#" class="buttons button-5 full-width"><?php echo esc_html__( 'Load more', 'my-listing' ) ?></a></div>
					<?php else: ?>
						<div class="nav-previous"><?php previous_comments_link( esc_html__( 'Older Comments', 'my-listing' ) ); ?></div>
						<div class="nav-next"><?php next_comments_link( esc_html__( 'Newer Comments', 'my-listing' ) ); ?></div>
					<?php endif ?>
					</div>
				</nav>
			<?php endif; ?>
		</div>

		<?php if ( comments_open() ): ?>
			<div class="<?php echo esc_attr( $comment_form_wrapper ) ?>">
				<div>
					<?php 
					include(locate_template('partials/review-form.php'))
					?>
				</div>
			</div>
		<?php endif ?>

	</div>
</div>
