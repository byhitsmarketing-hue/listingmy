<div class="container c27-listing-submitted-notice">
	<div class="row">
		<div class="col-md-10 col-md-push-1">

			<div class="element submit-l-message">
				<div class="pf-head">
					<div class="title-style-1">
						<h5>
							<i class="material-icons">check_circle_outline</i>
							<?php
							$is_switch = isset( $_REQUEST['action'] ) && $_REQUEST['action'] === 'switch' ? true : false;
							$is_relist = isset( $_REQUEST['action'] ) && $_REQUEST['action'] === 'relist' ? true : false;

							if ( $is_switch ) {
								switch( $listing->get_status() ):
									case 'publish':
										printf( __( 'The plan for %s has been updated.', 'my-listing' ), $listing->get_title() );
									break;
									case 'pending' :
										printf( __( 'The plan for %s has been updated. Your listing will be visible once approved.', 'my-listing' ), $listing->get_title() );
									break;
									default :
									break;
								endswitch;
							} elseif ( $is_relist ) {
								switch( $listing->get_status() ):
									case 'publish':
										printf( __( 'Listing relisted successfully. To view your listing <a href="%s">click here</a>.', 'my-listing' ), $listing->get_link() );
										break;
									case 'pending' :
										echo __( 'Listing relisted successfully. Your listing will be visible once approved.', 'my-listing' );
										break;
									case 'draft' :
										printf( __( 'Listing saved successfully', 'my-listing' ) );
										break;
									default :
										break;
								endswitch;
							} else {
								switch ( $listing->get_status() ) :
									case 'publish' :
										printf( __( 'Listing listed successfully. To view your listing <a href="%s">click here</a>.', 'my-listing' ), $listing->get_link() );
									break;
									case 'pending' :
										echo __( 'Listing submitted successfully. Your listing will be visible once approved.', 'my-listing' );
									break;
									case 'draft' :
										printf( __( 'Listing saved successfully', 'my-listing' ) );
									break;
									default :
										// do_action( 'job_manager_job_submitted_content_' . str_replace( '-', '_', sanitize_title( $job->post_status ) ), $job );
									break;
								endswitch;
							}
							?>
						</h5>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
	// prevent form resubmission
	if ( window.history.replaceState ) {
		window.history.replaceState( null, null, window.location.href );
	}
</script>