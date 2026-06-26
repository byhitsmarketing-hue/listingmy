<?php $form_uid = uniqid( 'review_form_' ); ?>
<div class="element">
						<div class="pf-head">
							<div class="title-style-1">
								<i class="mi chat_bubble_outline"></i>
								<?php if ( is_singular('job_listing') && \MyListing\is_rating_enabled( get_the_ID() ) ): ?>
									<h4 class="title-style-1__heading"><?php _e( 'Add a review', 'my-listing' ) ?></h4>
								<?php else: ?>
									<h4 class="title-style-1__heading"><?php _e( 'Add a comment', 'my-listing' ) ?></h4>
								<?php endif ?>
							</div>
						</div>
						<div class="pf-body">

							<!-- Rating Field -->
							<?php $rating_field = MyListing\Ext\Reviews\Reviews::get_ratings_field( false, get_the_ID(), $form_uid . '_submit' ); ?>

							<!-- Gallery Field -->
							<?php $gallery_field = MyListing\Ext\Reviews\Reviews::get_gallery_field( false, get_the_ID(), $form_uid . '_submit' ); ?>

							<!-- Author Field -->
							<?php ob_start(); ?>
							<div class="form-group">
								<label><?php _e( 'Name', 'my-listing' ) ?></label>
								<input name="author" type="text" value="<?php echo esc_attr( $commenter['comment_author'] ) ?>" required="required" placeholder="<?php echo esc_html__('Your Name', 'my-listing') ?>">
							</div>
							<?php $author_field = ob_get_clean(); ?>

							<!-- Email Field -->
							<?php ob_start(); ?>
							<div class="form-group">
								<label><?php _e( 'Email', 'my-listing' ) ?></label>
								<input name="email" type="email" value="<?php echo esc_attr( $commenter['comment_author_email'] ) ?>" required="required" placeholder="<?php echo esc_html__('Your Email', 'my-listing') ?>">
							</div>
							<?php $email_field = ob_get_clean(); ?>

							<!-- Message Field -->
							<?php ob_start(); ?>
							<div class="form-group">
								<label><?php _e( 'Your Message', 'my-listing' ) ?></label>
								<textarea rows="5" name="comment" required="required" placeholder="<?php echo esc_html__('Enter message...', 'my-listing') ?>"></textarea>
							</div>
							<?php $message_field = ob_get_clean(); ?>

							<!-- Cookies Field -->
							<?php ob_start(); ?>
							<div class="comment-form-cookies-consent md-checkbox">
								<input id="wp-comment-cookies-consent" name="wp-comment-cookies-consent" type="checkbox" value="yes" <?php echo empty( $commenter['comment_author_email'] ) ? '' : ' checked="checked"' ?>>
                                <label for="wp-comment-cookies-consent"><?php _e( 'Save my name, email, and website in this browser for the next time I comment.', 'my-listing' ) ?></label>
                            </div>
							<?php $cookies_field = ob_get_clean(); ?>

							<!-- Submit Field -->
							<?php ob_start(); ?>
							<button name="submit" type="submit" class="buttons button-2 full-width">
								<?php if ( is_singular('job_listing') && \MyListing\is_rating_enabled( get_the_ID() ) ): ?>
									<?php echo esc_html__('Submit review', 'my-listing') ?>
								<?php else: ?>
									<?php echo esc_html__('Submit comment', 'my-listing') ?>
								<?php endif ?>
							</button>
							<?php $submit_field = ob_get_clean(); ?>

							<?php
							$args = array(
								'comment_field'       => $rating_field . $gallery_field . $message_field . $submit_field,
								'class_submit'        => 'hide',
								'cancel_reply_before' => ' &middot; <span>',
								'cancel_reply_after'  => '</span>',
							);

							$user_review = MyListing\Ext\Reviews\Reviews::has_user_reviewed( get_current_user_id(), get_the_ID() );

							if ( ! is_user_logged_in() ) {
								$args['comment_field'] = '';
								$args['fields'] = array(
									'rating_field'  => $rating_field,
									'gallery_field' => $gallery_field,
									'author'        => $author_field,
									'email'         => $email_field,
									'comment_field' => $message_field,
									'cookies' 		=> $cookies_field,
									'submit'        => $submit_field,
								);
							} elseif ( $user_review ) {
								$args['comment_field'] = $message_field . $submit_field;
							}
							?>

							<div class="sidebar-comment-form">
								<?php
								$hide_comment_form = false;
								if ( $user_review && is_singular('job_listing') ):
									$listing = \MyListing\Src\Listing::get( get_the_ID() );

									if ( $listing && $listing->type && ! $listing->type->multiple_comments_allowed() ):
										$hide_comment_form = true; ?>
										<form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post" enctype="multipart/form-data">
											<?php echo MyListing\Ext\Reviews\Reviews::get_ratings_field( $user_review, get_the_ID(), $form_uid . '_update' ); ?>
											<?php echo MyListing\Ext\Reviews\Reviews::get_gallery_field( $user_review, get_the_ID(), $form_uid . '_update' ); ?>
											<div class="form-group">
												<label><?php _e( 'Your Message', 'my-listing' ) ?></label>
												<textarea rows="5" name="comment" required="required" placeholder="<?php echo esc_html__('Enter message...', 'my-listing') ?>"><?php echo get_comment_text( $user_review ) ?></textarea>
											</div>
											<input type="hidden" name="action" value="update_review">
											<input type="hidden" name="listing_id" value="<?php echo esc_attr( get_the_ID() ) ?>">
											<?php wp_nonce_field('update_review', '_update_review_nonce') ?>
											<?php if ( \MyListing\is_rating_enabled( get_the_ID() ) ): ?>
												<button type="submit" class="buttons button-2 full-width"><?php echo esc_html__('Update review', 'my-listing') ?></button>
											<?php else: ?>
												<button type="submit" class="buttons button-2 full-width"><?php echo esc_html__('Update comment', 'my-listing') ?></button>
											<?php endif ?>
										</form>
									<?php endif ?>
								<?php endif ?>

								<div class="<?php echo $hide_comment_form ? 'hide' : '' ?>">
									<?php comment_form( $args ) ?>
								</div>
							</div>
						</div>
					</div>
