<?php

namespace MyListing\Src\Claims;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Claims {
	use \MyListing\Src\Traits\Instantiatable;

	public function __construct() {

		require_once locate_template('includes/src/claims/claim-fields.php');

		// register post type
		add_action( 'init', [ $this, 'register_claim_post_type' ] );

		// Add title.
		add_filter( 'the_title', [ $this, 'claim_title' ], 10, 2 );

		// Cover button output.
		add_action( 'mylisting/single/quick-actions/claim-listing', [ $this, 'display_claim_quick_action' ], 30, 2 );

		// Claim shortcode.
		add_action( 'init', function() {
			add_shortcode( 'claim_listing', [ $this, 'claim_listing_shortcode' ] );
		} );

		// Load claim form.
		add_action( 'template_redirect', function() {
			$page_id = mylisting_get_setting( 'claims_page_id' );
			if ( $page_id && is_page( $page_id ) ) {
				do_action( 'case27_claim_form_init' );
			}
		} );

		// render form
		add_action( 'case27_claim_form_init', [ $this, 'claim_form_init' ], 5 );
		add_action( 'case27_claim_form_output', [ $this, 'claim_form_output' ] );

		// add claims page in user dashboard
		add_action( 'after_setup_theme', function() {
			\MyListing\add_dashboard_page( [
				'endpoint' => _x( 'claim-requests', 'Claims user dashboard page slug', 'my-listing' ),
				'title' => _x( 'Claim Requests', 'Claims user dashboard page title', 'my-listing' ),
				'template' => locate_template( 'templates/dashboard/claim-requests.php' ),
				'show_in_menu' => false,
			]);
		});

		if ( is_admin() ) {
            add_action( 'load-post.php', [ $this, 'init_metabox' ] );
            // Save handler for native Claim Data metabox.
            add_action( 'save_post', [ $this, 'save_claim_data_metabox' ], 10, 2 );
        }
	}

	/**
	 * Register `Claim` post type.
	 *
	 * @since 1.6
	 */
	public function register_claim_post_type() {
		register_post_type( 'claim', [
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => false,
			'query_var'          => true,
			'rewrite'            => false,
			'capability_type'    => 'page',
			'has_archive'        => false,
			'hierarchical'       => false,
			'supports'           => [''],
			'labels'             => [
				'name'               => __( 'Claims', 'my-listing' ),
				'singular_name'      => __( 'Claim', 'my-listing' ),
				'menu_name'          => __( 'Claim Entries', 'my-listing' ),
				'name_admin_bar'     => __( 'Claims', 'my-listing' ),
				'add_new'            => __( 'Add New', 'my-listing' ),
				'add_new_item'       => __( 'Add New Claim', 'my-listing' ),
				'new_item'           => __( 'New Claim', 'my-listing' ),
				'edit_item'          => __( 'Edit Claim', 'my-listing' ),
				'view_item'          => __( 'View Claim', 'my-listing' ),
				'all_items'          => __( 'All Claims', 'my-listing' ),
				'search_items'       => __( 'Search Claims', 'my-listing' ),
				'parent_item_colon'  => __( 'Parent Claims:', 'my-listing' ),
				'not_found'          => __( 'No Claims found.', 'my-listing' ),
				'not_found_in_trash' => __( 'No Claims found in Trash.', 'my-listing' ),
			],
		] );
	}

	/**
	 * Claim Title.
	 *
	 * @since 1.6
	 */
	public function claim_title( $title, $id = null ) {
		if ( ! $id || 'claim' !== get_post_type( $id ) ) {
			return $title;
		}

		$status = static::get_claim_status( $id );
		return "#{$id} - {$status}";
	}

	/**
	 * Display `Claim Listing` as a listing quick action or cover detail.
	 *
	 * @since 2.0
	 */
	public function display_claim_quick_action( $action, $listing ) {
		$claim_url = static::get_claim_url( $listing->get_id() );
		if ( ! $listing->is_claimable() || empty( trim( $claim_url ) ) ) {
			return;
		}
		?>
		<li id="<?php echo esc_attr( $action['id'] ) ?>" class="<?php echo esc_attr( $action['class'] ) ?>">
		    <a href="<?php echo esc_url( $claim_url ) ?>">
		    	<?php echo c27()->get_icon_markup( $action['icon'] ) ?>
		    	<span><?php echo $action['label'] ?></span>
		    </a>
		</li>
		<?php
	}

	/**
	 * Claim Listing Form Shortcode
	 *
	 * @since 1.6
	 */
	public function claim_listing_shortcode() {
		ob_start();

		$listing_id = absint( ! empty( $_GET['listing_id'] ) ? $_GET['listing_id'] : null );
		$post = get_post( $listing_id );
		if ( 'job_listing' !== $post->post_type ) {
			echo wpautop( __( 'Listing invalid or cannot be claimed.', 'my-listing' ) );
		} else {
			do_action( 'case27_claim_form_output' );
		}

		return ob_get_clean();
	}

	/**
	 * Claim Form Init
	 * To setup and process the data. This is loaded only on claim page.
	 *
	 * @since 1.6
	 */
	public function claim_form_init() {
		// Make sure registration enabled and account required in claim page.
		add_filter( 'mylisting/settings/submission_requires_account', '__return_true' );
		\MyListing\Src\Claims\Claim_Listing_Form::instance()->process();
	}

	/**
	 * Load Claim Form
	 *
	 * @since 1.6
	 */
	public function claim_form_output() {
		\MyListing\Src\Claims\Claim_Listing_Form::instance()->render();
	}

	/**
	 * Retrieve the `Claim Listing` url for given listing id.
	 *
	 * @since 2.1
	 */
	public static function get_claim_url( $listing_id ) {
		$listing = \MyListing\Src\Listing::get( $listing_id );
		$page_id = mylisting_get_setting( 'claims_page_id' );
		$page_url = $page_id ? get_permalink( $page_id ) : '';

		// validate
		if ( ! ( $listing && $page_url ) ) {
			return '';
		}

		return esc_url( add_query_arg( 'listing_id', $listing->get_id(), $page_url ) );
	}

	/**
	 * Get a valid post type status for Claims post type.
	 *
	 * @since 2.1
	 */
	public static function get_claim_status( $claim_id ) {
		$statuses = static::get_valid_statuses();
		$status = get_post_meta( $claim_id, '_status', true );
		return $status && isset( $statuses[ $status ] ) ? $statuses[ $status ] : $statuses['pending'];
	}

	/**
	 * Get listing of valid post stauses for Claims post type.
	 *
	 * @since 2.1
	 */
	public static function get_valid_statuses() {
		return [
			'pending'  => esc_html__( 'Pending', 'my-listing' ),
			'approved' => esc_html__( 'Approved', 'my-listing' ),
			'declined' => esc_html__( 'Declined', 'my-listing' ),
		];
	}

	/**
	 * Create a new claim.
	 *
	 * @since 2.1
	 */
	public static function create( $args = [] ) {
		$args = wp_parse_args( $args, [
			'listing_id'       => false,
			'user_id'          => get_current_user_id(),
			'user_package_id'  => false,
			'status'           => mylisting_get_setting( 'claims_require_approval' ) ? 'pending' : 'approved',
		] );

		// validate
		if ( empty( $args['listing_id'] ) || empty( $args['user_id'] ) ) {
			return false;
		}

		// check if claim already exists for this user
		$existing_claim = static::get_user_claim( $args['user_id'], $args['listing_id'] );
		if ( $existing_claim !== false ) {
			return $existing_claim;
		}

		// create new claim
		$claim_id = wp_insert_post( [
			'post_author'  => 0,
			'post_title'   => '',
			'post_type'    => 'claim',
			'post_status'  => 'publish',
		] );

		// validate
		if ( ! $claim_id || is_wp_error( $claim_id ) ) {
			return false;
		}

		// success, set claim metadata
		update_post_meta( $claim_id, '_status', $args['status'] );
		update_post_meta( $claim_id, '_listing_id', absint( $args['listing_id'] ) );
		update_post_meta( $claim_id, '_user_id', absint( $args['user_id'] ) );
		update_post_meta( $claim_id, '_user_package_id', absint( $args['user_package_id'] ) );

		// send claim status email
		if ( 'approved' === $args['status'] ) {
			\MyListing\Src\Claims\Claims::approve( $claim_id );
		}

		do_action( 'mylisting/claim:submitted', $claim_id );

		return $claim_id;
	}

	/**
	 * Approve a claim.
	 *
	 * @since 1.6
	 */
	public static function approve( $claim_id ) {
		$claim = get_post( $claim_id );
		if ( ! $claim || 'claim' !== $claim->post_type ) {
			return false;
		}
		// Read required meta explicitly (do not rely on runtime properties).
		$listing_id  = absint( get_post_meta( $claim_id, '_listing_id', true ) );
		$user_id     = absint( get_post_meta( $claim_id, '_user_id', true ) );
		$package_id  = absint( get_post_meta( $claim_id, '_user_package_id', true ) );

		if ( ! $listing_id ) {
			return false;
		}

		$listing = \MyListing\Src\Listing::get( $listing_id );
		if ( ! mylisting_get_setting( 'paid_listings_enabled' ) || ! $listing->type->settings['packages']['enabled'] ) {
			$duration = absint( mylisting_get_setting( 'submission_default_duration' ) );
			$new_expiry = '';
			if ( $duration ) {
				$new_expiry = date( 'Y-m-d', strtotime( "+{$duration} days", current_time( 'timestamp' ) ) );
			}
			update_post_meta( $listing_id, '_job_expires', $new_expiry );
		} else {
			$package = \MyListing\Src\Package::get( $package_id );

			// apply user package, and set listing to approved/publish
			if ( $package ) {
				wp_update_post( [
					'ID' => $listing_id,
					'post_status' => 'publish',
				] );

				$package->assign_to_listing( $listing_id );
			}
		}

		// update verified status
		if ( mylisting_get_setting( 'mylisting_claims_mark_verified' ) ) {
			update_post_meta( $listing_id, '_claimed', 1 );
		}

		// update listing author
		if ( $user_id ) {
			wp_update_post( [
				'ID'          => $listing_id,
				'post_author' => $user_id,
			] );
		}
	}

	/**
	 * Get the user claim ID for given listing.
	 *
	 * @since 2.1
	 */
	public static function get_user_claim( $user_id, $listing_id ) {
		$claim = get_posts( [
			'post_type' => 'claim',
			'posts_per_page' => 1,
			'fields' => 'ids',
			'meta_query' => [
				'relation' => 'AND',
				[ 'key' => '_user_id', 'value' => absint( $user_id ) ],
				[ 'key' => '_listing_id', 'value' => absint( $listing_id ) ],
			],
		] );

		if ( ! empty( $claim ) ) {
			return absint( reset( $claim ) );
		}

		return false;
	}

	public function init_metabox() {
        add_action( 'add_meta_boxes', [ $this, 'add_metabox' ], 100 );
	}

	public function add_metabox() {
        add_meta_box(
            'case27-claim',
            _x( 'Claim Form Details', 'Claim listings', 'my-listing' ),
            [ $this, 'render_metabox' ],
            'claim',
            'advanced',
            'high'
        );

        // Native replacement for ACF "Claim Data" group.
        add_meta_box(
            'ml-claim-data',
            _x( 'Claim Data', 'Claim listings', 'my-listing' ),
            [ $this, 'render_claim_data_metabox' ],
            'claim',
            'normal',
            'high'
        );

        // Hide legacy ACF group metabox if present (to prevent duplicate UI).
        remove_meta_box( 'acf-group_5a684b75ab588', 'claim', 'normal' );
    }

    public function render_metabox( $post ) {
    	global $thepostid;
		// Use meta fallback to avoid depending on ACF-populated properties.
		$listingID = isset( $post->_listing_id ) && $post->_listing_id ? $post->_listing_id : get_post_meta( $post->ID, '_listing_id', true );
		if ( ! $listingID ) {
			return false;
		}

		$listing = \MyListing\Src\Listing::get( $listingID );
		$type = \MyListing\Src\Listing_Type::get_by_name( $listing->type->get_slug() );

		$fields = $type->get_claim_fields();

    	echo '<div class="ml-admin-listing-form ml-admin-claim-form">';
    	echo '<div class="listing-report">';
    		wp_enqueue_style( 'mylisting-admin-form' );
			wp_enqueue_script( 'mylisting-admin-form' );
			foreach ( $fields as $key => $field ) {
				$field->set_claim( $post->ID );
				$field['value'] = $field->get_value();

				if ( $field['type'] == 'file' ) {
					echo $this->get_file_field( $field );
				} else {
					echo $this->get_input_field( $field );
				}
			}

			$this->print_action_buttons();

		echo '</div>';
		echo '</div>';
    }

    public function get_file_field( $field ) {

    	// get file list
		$files = array_filter( (array) $field->get_value() );
		if ( empty( $files ) ) {
			return;
		}

		?>
		<div class="row reported-listing">
			<span class="label"><?php echo esc_html( $field['label'] ) ?></span>
			<?php foreach ( $files as $file ):
				if ( ! ( $basename = pathinfo( $file, PATHINFO_BASENAME ) ) || ! ( $extension = pathinfo( $file, PATHINFO_EXTENSION ) ) ) {
					continue;
				} ?>

				<a href="<?php echo esc_url( $file ) ?>" target="_blank">
					<span class="file-name"><?php echo esc_html( $basename ) ?></span>
					<span class="file-link"><?php _e( 'View', 'my-listing' ) ?><i class="mi open_in_new"></i></span>
				</a>
			<?php endforeach ?>
		</div>

		<?php

    }

    public function get_input_field( $field ) {

    	$block_content = $field->get_value();
    	if ( ! empty( $GLOBALS['wp_embed'] ) ) {
			$block_content = $GLOBALS['wp_embed']->autoembed( $block_content );
		}

		$block_content = do_shortcode( $block_content );

    	?>
    	<div class="row reported-listing">
    		<span class="label"><?php echo esc_html( $field['label'] ) ?></span>
    		<span class="value"><?php echo wp_kses( $block_content, [] ) ?></span>
    	</div>
    	<?php
    }

    public function print_action_buttons() {
    ?>
    <div class="row report-actions">
    	<?php	foreach ( static::get_valid_statuses() as $key => $value ) : 
    		if( $key === 'pending' ) continue;	?>
    		<input name="<?php echo $key.'_claim'; ?>" type="hidden" id="<?php echo $key.'_claim'; ?>" value="<?php echo $key; ?>">
    		<button type="submit" name="save" id="publish" class="button button-large <?php echo $key === 'approved' ? 'button-primary' : '' ?>" value="<?php echo $value; ?>"><?php echo $key === 'approved' ? _e( 'Approve claim', 'my-listing' ) : _e( 'Decline Claim', 'my-listing' ); ?></button>
    	<?php endforeach; ?>
    </div>
	<?php 
	}

	/**
	 * Render native Claim Data metabox (replaces ACF group "Claim Data").
	 */
	public function render_claim_data_metabox( $post ) {
		if ( ! $post || 'claim' !== $post->post_type ) {
			return;
		}

		wp_nonce_field( 'ml_claim_data_save', 'ml_claim_data_nonce' );

		$listing_id = absint( get_post_meta( $post->ID, '_listing_id', true ) );
		$user_id    = absint( get_post_meta( $post->ID, '_user_id', true ) );
		$package_id = absint( get_post_meta( $post->ID, '_user_package_id', true ) );

		$listing_select_params = c27()->encode_attr( [
			'post_type'   => 'job_listing',
			'item_key'    => 'ID',
			'post-status' => [ 'publish', 'pending', 'pending_payment', 'expired', 'draft', 'private' ],
		] );

		$package_select_params = c27()->encode_attr( [
			'user_id' => $user_id,
		] );

		?>
		<p>
			<label class="ml-backend-label" for="ml_claim_listing_id"><?php esc_html_e( 'Listing', 'my-listing' ); ?></label>
			<select name="ml_claim_listing_id" style="width:100%" id="ml_claim_listing_id" class="custom-select"
				data-mylisting-ajax="true" data-mylisting-ajax-url="mylisting_list_posts"
				data-mylisting-ajax-params="<?php echo esc_attr( $listing_select_params ); ?>"
				placeholder="<?php echo esc_attr__( 'Select listing', 'my-listing' ); ?>"
			>
				<option></option>
				<?php if ( $listing_id && ( $title = get_the_title( $listing_id ) ) ) : ?>
					<option value="<?php echo esc_attr( $listing_id ); ?>" selected="selected">#<?php echo esc_html( $listing_id ); ?> - <?php echo esc_html( $title ); ?></option>
				<?php endif; ?>
			</select>
		</p>

		<p>
			<label class="ml-backend-label" for="ml_claim_user_id"><?php esc_html_e( 'Claimer', 'my-listing' ); ?></label>
			<select name="ml_claim_user_id" style="width:100%" id="ml_claim_user_id" class="custom-select"
				data-mylisting-ajax="true" data-mylisting-ajax-url="mylisting_list_users"
				placeholder="<?php echo esc_attr__( 'Select user', 'my-listing' ); ?>"
			>
				<option></option>
				<?php if ( $user_id && ( $user = get_userdata( $user_id ) ) ) : ?>
					<option value="<?php echo esc_attr( $user_id ); ?>" selected="selected"><?php echo esc_html( $user->display_name ); ?></option>
				<?php endif; ?>
			</select>
		</p>

		<p>
			<label class="ml-backend-label" for="ml_claim_user_package_id"><?php esc_html_e( 'User Package', 'my-listing' ); ?></label>
			<select name="ml_claim_user_package_id" style="width:100%" id="ml_claim_user_package_id" class="custom-select"
				data-mylisting-ajax="true" data-mylisting-ajax-url="mylisting_list_packages"
				data-mylisting-ajax-params="<?php echo esc_attr( $package_select_params ); ?>"
				placeholder="<?php echo esc_attr__( 'Select package', 'my-listing' ); ?>"
			>
				<option></option>
				<?php if ( $package_id && ( $ptitle = get_the_title( $package_id ) ) ) : ?>
					<option value="<?php echo esc_attr( $package_id ); ?>" selected="selected">#<?php echo esc_html( $package_id ); ?> - <?php echo esc_html( $ptitle ); ?></option>
				<?php endif; ?>
			</select>
		</p>
		<?php
	}

	/**
	 * Save handler for native Claim Data metabox.
	 */
	public function save_claim_data_metabox( $post_id, $post ) {
		if ( ! $post || 'claim' !== $post->post_type ) {
			return;
		}

		// Nonce and capability checks.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reading nonce from $_POST to verify it.
		if ( empty( $_POST['ml_claim_data_nonce'] ) || ! wp_verify_nonce( $_POST['ml_claim_data_nonce'], 'ml_claim_data_save' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified above; safe to read $_POST values below.
		$listing_id = isset( $_POST['ml_claim_listing_id'] ) ? absint( $_POST['ml_claim_listing_id'] ) : 0;
		$user_id    = isset( $_POST['ml_claim_user_id'] ) ? absint( $_POST['ml_claim_user_id'] ) : 0;
		$package_id = isset( $_POST['ml_claim_user_package_id'] ) ? absint( $_POST['ml_claim_user_package_id'] ) : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		// Validate referenced objects before saving.
		if ( $listing_id && 'job_listing' !== get_post_type( $listing_id ) ) {
			$listing_id = 0;
		}
		if ( $package_id && 'case27_user_package' !== get_post_type( $package_id ) ) {
			$package_id = 0;
		}
		if ( $user_id && ! get_userdata( $user_id ) ) {
			$user_id = 0;
		}

		// Persist.
		$listing_id ? update_post_meta( $post_id, '_listing_id', $listing_id ) : delete_post_meta( $post_id, '_listing_id' );
		$user_id ? update_post_meta( $post_id, '_user_id', $user_id ) : delete_post_meta( $post_id, '_user_id' );
		$package_id ? update_post_meta( $post_id, '_user_package_id', $package_id ) : delete_post_meta( $post_id, '_user_package_id' );
	}
}
