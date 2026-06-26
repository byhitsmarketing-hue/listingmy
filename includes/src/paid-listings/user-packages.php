<?php
/**
 * User Packages
 *
 * @since 1.6
 */

namespace MyListing\Src\Paid_Listings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class User_Packages {
	use \MyListing\Src\Traits\Instantiatable;

	public function __construct() {

		// Register user package post type.
		add_action( 'init', [ $this, 'register_user_package_post_type' ] );

		// Register custom post statuses.
		add_action( 'init', [ $this, 'register_user_package_statuses' ], 7 );
		foreach ( [ 'post', 'post-new' ] as $hook ) {
			add_action( "admin_footer-{$hook}.php", [ $this, 'extend_submitdiv_post_status' ] );
		}

		// Add this menu to listings.
		add_action( 'admin_menu',  [ $this, 'add_user_packages_as_listings_submenu' ], 51 );

		// Fix active menu when visiting user package screen.
		add_filter( 'parent_file', [ $this, 'set_user_package_parent_menu_edit_screen' ] );
		add_filter( 'submenu_file', [ $this, 'set_user_package_submenu_edit_screen' ] );

		// Add title.
		add_filter( 'the_title', [ $this, 'user_package_title' ], 10, 2 );
		add_action( 'edit_form_after_title', [ $this, 'display_package_id_edit_screen' ] );

		// Admin columns.
		add_filter( 'manage_case27_user_package_posts_columns',  [ $this, 'user_package_posts_columns' ] );
		add_action( 'manage_case27_user_package_posts_custom_column',  [ $this, 'user_package_posts_custom_column' ], 5, 2 );
		add_filter( 'post_row_actions', [ $this, 'remove_user_package_quick_edit' ], 10, 2 );
		add_filter( 'bulk_actions-edit-case27_user_package', [ $this, 'remove_user_package_bulk_action_edit' ] );

		// Delete packages with user.
		add_action( 'deleted_user', [ $this, 'delete_user_packages_with_user' ], 10, 2 );

		// Save post action.
		add_action( 'save_post', [ $this, 'save_user_package' ], 99, 2 );

		// Native replacement for ACF: Register metabox and save handler for User Package fields.
		add_action( 'add_meta_boxes', [ $this, 'add_user_package_metabox' ], 100 );
		// Save early so other handlers (e.g. status change logic) can read updated meta.
		add_action( 'save_post', [ $this, 'save_user_package_metabox' ], 10, 2 );

		// @todo: Handle cases when listing goes from published to pending when edited (new wpjm setting).
		// Decrease package count for listings that go from pending approval to trash.
		add_action( 'pending_to_trash', [ $this, 'pending_to_trash' ] );

		// Increase package count when listing is untrashed and status is set to pending approval.
		add_action( 'trash_to_pending', [ $this, 'trash_to_pending' ] );

		if ( is_admin() ) {
			add_action( 'request', [ $this, 'add_keyword_search' ] );
		}

		add_action( 'wp_insert_post', [ $this, 'change_package_author' ], 999, 3 );
	}

	/**
	 * Register Post Type for User Packages.
	 *
	 * @since 1.0.0
	 * @link https://codex.wordpress.org/Function_Reference/register_post_type
	 */
	public static function register_user_package_post_type() {
		$args = array(
			'description'           => '',
			'public'                => false, // Private.
			'publicly_queryable'    => false,
			'show_in_nav_menus'     => false,
			'show_in_admin_bar'     => false,
			'exclude_from_search'   => false, // Need this for WP_Query.
			'show_ui'               => true,
			'show_in_menu'          => false,
			//'menu_position'         => 99,
			'menu_icon'             => 'dashicons-screenoptions',
			'can_export'            => true,
			'delete_with_user'      => false,
			'hierarchical'          => false,
			'has_archive'           => false,
			'query_var'             => true,
			'rewrite'               => false,
			'capability_type'       => 'page',
			'supports'              => array( '' ),
			'labels'                => array(
				'name'                      => __( 'Packages', 'my-listing' ),
				'singular_name'             => __( 'Package', 'my-listing' ),
				'add_new'                   => __( 'Add New', 'my-listing' ),
				'add_new_item'              => __( 'Add New Package', 'my-listing' ),
				'edit_item'                 => __( 'Edit Package', 'my-listing' ),
				'new_item'                  => __( 'New Package', 'my-listing' ),
				'all_items'                 => __( 'All Packages', 'my-listing' ),
				'view_item'                 => __( 'View Package', 'my-listing' ),
				'search_items'              => __( 'Search Packages', 'my-listing' ),
				'not_found'                 => __( 'Not Found', 'my-listing' ),
				'not_found_in_trash'        => __( 'Not Found in Trash', 'my-listing' ),
				'menu_name'                 => __( 'Paid Listing Packages', 'my-listing' ),
			),
		);

		register_post_type( 'case27_user_package', apply_filters( 'case27_user_package_register_post_type_args', $args ) );
	}

	/**
	 * Register User Package Statuses
	 *
	 * @since 1.0.0
	 */
	public function register_user_package_statuses() {
		register_post_status( 'case27_full', array(
			'label'                     => esc_html__( 'Full', 'my-listing' ),
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			// translators: %s is label count.
			'label_count'               => _n_noop( 'Full <span class="count">(%s)</span>', 'Full <span class="count">(%s)</span>', 'my-listing' ),
		) );
		register_post_status( 'case27_cancelled', array(
			'label'                     => esc_html__( 'Order Cancelled', 'my-listing' ),
			'public'                    => false,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => false,
			'show_in_admin_status_list' => true,
			// translators: %s is label count.
			'label_count'               => _n_noop( 'Order Cancelled <span class="count">(%s)</span>', 'Order Cancelled <span class="count">(%s)</span>', 'my-listing' ),
		) );
	}

	/**
	 * Adds post status to the "submitdiv" Meta Box and post type WP List Table screens.
	 * Based on code by franz-josef-kaiser
	 *
	 * @since 1.0.0
	 *
	 * @link https://gist.github.com/franz-josef-kaiser/2930190
	 */
	public function extend_submitdiv_post_status() {
		global $post, $post_type;

		// Abort if we're on the wrong post type, but only if we got a restriction
		if ( 'case27_user_package' !== $post_type ) {
			return;
		}

		$statuses = \MyListing\Src\Package::get_statuses();

		// Get all non-builtin post status and add them as <option>
		$options = $display = '';
		foreach ( $statuses as $status => $name ) {
			$selected = selected( $post->post_status, $status, false );

			// If we one of our custom post status is selected, remember it
			$selected AND $display = $name;

			// Build the options
			$options .= "<option{$selected} value='{$status}'>{$name}</option>";
		}
		?>
		<script type="text/javascript">
			jQuery( document ).ready( function($) {
				<?php if ( ! empty( $display ) ) : ?>
					jQuery( '#post-status-display' ).html( '<?php echo $display; ?>' );
				<?php endif; ?>

				var select = jQuery( '#post-status-select' ).find( 'select' );
				jQuery( select ).html( "<?php echo $options; ?>" );
			} );
		</script>
		<?php
	}

	/**
	 * Add Listing Packages as Listings Submenu.
	 *
	 * @since 1.0.0
	 * @link https://shellcreeper.com/how-to-add-wordpress-cpt-admin-menu-as-sub-menu/
	 */
	public function add_user_packages_as_listings_submenu() {
		$cpt_obj = get_post_type_object( 'case27_user_package' );
		add_submenu_page(
			'users.php',                              // Parent slug.
			$cpt_obj->labels->name,                   // Page title.
			$cpt_obj->labels->menu_name,              // Menu title.
			$cpt_obj->cap->edit_posts,                // Capability.
			'edit.php?post_type=case27_user_package'  // Menu slug.
		);
	}

	/**
	 * Set user package parent menu edit screen.
	 *
	 * @since 1.0.0
	 *
	 * @param string $parent_file Parent menu slug.
	 * @return string
	 */
	public function set_user_package_parent_menu_edit_screen( $parent_file ) {
		global $current_screen;
		if ( in_array( $current_screen->base, [ 'post', 'edit' ] ) && 'case27_user_package' === $current_screen->post_type ) {
			$parent_file = 'users.php';
		}
		return $parent_file;
	}

	/**
	 * Set active sub menu when visiting parent menu edit screen.
	 *
	 * @since 1.0.0
	 *
	 * @param string $submenu_file Active submenu slug.
	 * @return string
	 */
	public function set_user_package_submenu_edit_screen( $submenu_file ) {
		global $current_screen;
		if ( in_array( $current_screen->base, [ 'post', 'edit' ] ) && 'case27_user_package' === $current_screen->post_type ) {
			$submenu_file = 'edit.php?post_type=case27_user_package';
		}
		return $submenu_file;
	}

	/**
	 * User Package Title.
	 * User package post type do not support title, but admin still show it.
	 *
	 * @since 1.0.0
	 *
	 * @param string $title The title string.
	 * @param int    $id    Post ID.
	 * @return string
	 */
	public function user_package_title( $title, $id = null ) {
		if ( ! $id || 'case27_user_package' !== get_post_type( $id ) ) {
			return $title;
		}

		$statuses = \MyListing\Src\Package::get_statuses();
		$status = get_post_status( $id );
		$status = isset( $statuses[ $status ] ) ? $statuses[ $status ] : $status;

		return "#{$id} - {$status}";
	}

	/**
	 * Display Package ID in Edit Screen
	 *
	 * @since 1.0.0
	 */
	public function display_package_id_edit_screen( $post ) {
		if ( $post && $post->ID && 'case27_user_package' === $post->post_type && isset( $_GET['action'] ) && 'edit' === $_GET['action'] ) {
			?>
			<h1 class="wp-heading-inline-package"><?php printf( __( 'Edit Package #%d', 'my-listing' ), $post->ID ); ?> <a href="<?php echo esc_url( add_query_arg( 'post_type','case27_user_package', admin_url( 'post-new.php' ) ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'my-listing' ); ?></a></h1>
			<style>.wrap h1.wp-heading-inline {display:none;} .wrap > .page-title-action {display:none;} #poststuff {margin-top: 30px;}</style>
			<?php
		}
	}

	/**
	 * Register native User Package metabox (replaces ACF group "User Package").
	 */
	public function add_user_package_metabox() {
		add_meta_box(
			'ml-user-package',
			_x( 'User Package', 'User Package metabox title', 'my-listing' ),
			[ $this, 'render_user_package_metabox' ],
			'case27_user_package',
			'normal',
			'high'
		);

		// Hide legacy ACF group metabox if present (to prevent duplicate UI).
		remove_meta_box( 'acf-group_5a471067bee5c', 'case27_user_package', 'normal' );
	}

	/**
	 * Render native User Package metabox fields.
	 */
	public function render_user_package_metabox( $post ) {
		if ( ! ( $post && $post->post_type === 'case27_user_package' ) ) {
			return;
		}

		// Nonce for saving.
		wp_nonce_field( 'ml_user_package_save', 'ml_user_package_nonce' );

		// Read existing values.
		$limit         = get_post_meta( $post->ID, '_limit', true );
		$count         = get_post_meta( $post->ID, '_count', true );
		$duration      = get_post_meta( $post->ID, '_duration', true );
		$featured      = (bool) get_post_meta( $post->ID, '_featured', true );
		$mark_verified = (bool) get_post_meta( $post->ID, '_mark_verified', true );
		$use_for_claims= (bool) get_post_meta( $post->ID, '_use_for_claims', true );
		$is_claimable  = (bool) get_post_meta( $post->ID, '_is_claimable', true );
		$user_id       = absint( get_post_meta( $post->ID, '_user_id', true ) );
		$product_id    = absint( get_post_meta( $post->ID, '_product_id', true ) );
		$order_id      = absint( get_post_meta( $post->ID, '_order_id', true ) );

		$product_select_params = c27()->encode_attr( [
			'product-type' => [ 'job_package', 'job_package_subscription' ],
			'post-status'  => [ 'publish', 'private' ],
		] );

		$order_select_params = c27()->encode_attr( [
			'post_type' => post_type_exists( 'shop_subscription' )
				? [ 'shop_order', 'shop_subscription' ]
				: [ 'shop_order' ],
		] );

		echo '<div class="ml-admin-user-package">';

		// Section: Package Details
		echo '<h2 style="margin-top:0">' . esc_html__( 'Package Details', 'my-listing' ) . '</h2>';
		echo '<em>' . esc_html__( "Editing these values won't affect existing listings, only new listings added with this package.", 'my-listing' ) . '</em>';

		// Listing Limit
		echo '<p>';
		echo '<label class="ml-backend-label" for="ml_pkg_limit">' . esc_html__( 'Listing Limit', 'my-listing' ) . '</label>';
		echo '<input type="number" min="0" step="1" id="ml_pkg_limit" name="ml_pkg_limit" value="' . esc_attr( $limit ) . '" placeholder="' . esc_attr__( 'Unlimited', 'my-listing' ) . '" class="small-text" />';
		echo '<span class="description" style="display:block;margin-top:4px">' . esc_html__( 'How many listings should this package allow the user to post?', 'my-listing' ) . '</span>';
		echo '</p>';

		// Listing Count
		echo '<p>';
		echo '<label class="ml-backend-label" for="ml_pkg_count">' . esc_html__( 'Listing Count', 'my-listing' ) . '</label>';
		echo '<input type="number" min="0" step="1" id="ml_pkg_count" name="ml_pkg_count" value="' . esc_attr( $count ) . '" placeholder="0" class="small-text" />';
		echo '<span class="description" style="display:block;margin-top:4px">' . esc_html__( 'How many listings has the user already posted using this package?', 'my-listing' ) . '</span>';
		echo '</p>';

		// Listing Duration
		echo '<p>';
		echo '<label class="ml-backend-label" for="ml_pkg_duration">' . esc_html__( 'Listing Duration', 'my-listing' ) . '</label>';
		echo '<input type="number" min="0" step="1" id="ml_pkg_duration" name="ml_pkg_duration" value="' . esc_attr( $duration ) . '" placeholder="' . esc_attr__( 'Default', 'my-listing' ) . '" class="small-text" />';
		echo '<span class="description" style="display:block;margin-top:4px">' . esc_html__( 'How many days should listings posted with this package be active?', 'my-listing' ) . '</span>';
		echo '</p>';

		// Feature Listing (Yes/No switch)
		echo '<p>';
		echo '<label class="ml-backend-label" for="ml_pkg_featured">' . esc_html__( 'Feature Listing', 'my-listing' ) . '</label>';
		echo '<div class="ml-switch">'
			. '<input type="checkbox" id="ml_pkg_featured" name="ml_pkg_featured" value="1" ' . checked( $featured, true, false ) . ' />'
			. '<label class="ml-switch-label" for="ml_pkg_featured"></label>'
			. '<span class="ml-switch-text">' . esc_html__( 'Mark listings with this package as featured, gaining higher priority in search results.', 'my-listing' ) . '</span>'
			. '</div>';
		echo '</p>';

		// Mark Verified (Yes/No switch)
		echo '<p>';
		echo '<label class="ml-backend-label" for="ml_pkg_mark_verified">' . esc_html__( 'Mark verified', 'my-listing' ) . '</label>';
		echo '<div class="ml-switch">'
			. '<input type="checkbox" id="ml_pkg_mark_verified" name="ml_pkg_mark_verified" value="1" ' . checked( $mark_verified, true, false ) . ' />'
			. '<label class="ml-switch-label" for="ml_pkg_mark_verified"></label>'
			. '<span class="ml-switch-text">' . esc_html__( 'Listings that belong to this package will be marked as verified.', 'my-listing' ) . '</span>'
			. '</div>';
		echo '</p>';

		// Use for Claims (Yes/No switch)
		echo '<p>';
		echo '<label class="ml-backend-label" for="ml_pkg_use_for_claims">' . esc_html__( 'Use for Claims', 'my-listing' ) . '</label>';
		echo '<div class="ml-switch">'
			. '<input type="checkbox" id="ml_pkg_use_for_claims" name="ml_pkg_use_for_claims" value="1" ' . checked( $use_for_claims, true, false ) . ' />'
			. '<label class="ml-switch-label" for="ml_pkg_use_for_claims"></label>'
			. '<span class="ml-switch-text">' . esc_html__( 'The owner of this package can claim other listings using this package, if claim listings are enabled.', 'my-listing' ) . '</span>'
			. '</div>';
		echo '</p>';

		// Is claimable? (Yes/No switch)
		echo '<p>';
		echo '<label class="ml-backend-label" for="ml_pkg_is_claimable">' . esc_html__( 'Is claimable?', 'my-listing' ) . '</label>';
		echo '<div class="ml-switch">'
			. '<input type="checkbox" id="ml_pkg_is_claimable" name="ml_pkg_is_claimable" value="1" ' . checked( $is_claimable, true, false ) . ' />'
			. '<label class="ml-switch-label" for="ml_pkg_is_claimable"></label>'
			. '<span class="ml-switch-text">' . esc_html__( 'Listings that belong to this package can still be claimed by other users, if claim listings are enabled.', 'my-listing' ) . '</span>'
			. '</div>';
		echo '</p>';

		// Section: Payment Details
		echo '<h2>' . esc_html__( 'Payment Details', 'my-listing' ) . '</h2>';

		// User
		echo '<p>';
		echo '<label class="ml-backend-label" for="ml_pkg_user_id">' . esc_html__( 'User', 'my-listing' ) . '</label>';
		echo '<select '
			. 'class="custom-select" '
			. 'name="ml_pkg_user_id" '
			. 'id="ml_pkg_user_id" '
			. 'style="width:100%" '
			. 'data-mylisting-ajax="true" '
			. 'data-mylisting-ajax-url="mylisting_list_users" '
			. 'placeholder="' . esc_attr__( '- None -', 'my-listing' ) . '"'
			. '>';
		echo '<option></option>';
		if ( $user_id && ( $user = get_userdata( $user_id ) ) ) {
			printf( '<option value="%1$s" selected="selected">%2$s</option>', esc_attr( $user_id ), esc_html( $user->display_name ) );
		}
		echo '</select>';
		echo '</p>';

		// Product
		echo '<p>';
		echo '<label class="ml-backend-label" for="ml_pkg_product_id">' . esc_html__( 'Product', 'my-listing' ) . '</label>';
		echo '<select '
			. 'class="custom-select" '
			. 'name="ml_pkg_product_id" '
			. 'id="ml_pkg_product_id" '
			. 'style="width:100%" '
			. 'data-mylisting-ajax="true" '
			. 'data-mylisting-ajax-url="mylisting_list_products" '
			. 'data-mylisting-ajax-params="' . $product_select_params . '" '
			. 'placeholder="' . esc_attr__( '- None -', 'my-listing' ) . '"'
			. '>';
		echo '<option></option>';
		if ( $product_id && function_exists( 'wc_get_product' ) && ( $product = wc_get_product( $product_id ) ) ) {
			printf( '<option value="%1$s" selected="selected">#%1$s - %2$s</option>', esc_attr( $product_id ), esc_html( $product->get_name() ) );
		}
		echo '</select>';
		echo '</p>';

		// Order / Subscription
		echo '<p>';
		echo '<label class="ml-backend-label" for="ml_pkg_order_id">' . esc_html__( 'Order', 'my-listing' ) . '</label>';
		echo '<select '
			. 'class="custom-select" '
			. 'name="ml_pkg_order_id" '
			. 'id="ml_pkg_order_id" '
			. 'style="width:100%" '
			. 'data-mylisting-ajax="true" '
			. 'data-mylisting-ajax-url="mylisting_list_orders" '
			. 'data-mylisting-ajax-params="' . $order_select_params . '" '
			. 'placeholder="' . esc_attr__( '- None -', 'my-listing' ) . '"'
			. '>';
		echo '<option></option>';
		if ( $order_id && function_exists( 'wc_get_order' ) && ( $order = wc_get_order( $order_id ) ) ) {
			$created = $order->get_date_created() ? $order->get_date_created()->date_i18n( 'F j, Y g:i a' ) : '';
			$label = sprintf( '#%s - %s', $order->get_order_number(), $created );
			printf( '<option value="%1$s" selected="selected">%2$s</option>', esc_attr( $order_id ), esc_html( $label ) );
		}
		echo '</select>';
		echo '</p>';

		echo '</div>';
	}

	/**
	 * Save native User Package metabox.
	 */
	public function save_user_package_metabox( $post_id, $post ) {
		if ( ! ( $post && $post->post_type === 'case27_user_package' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reading nonce from $_POST to verify it.
		if ( empty( $_POST['ml_user_package_nonce'] ) || ! wp_verify_nonce( $_POST['ml_user_package_nonce'], 'ml_user_package_save' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified above; safe to read $_POST values below.
		$limit         = isset( $_POST['ml_pkg_limit'] ) ? absint( $_POST['ml_pkg_limit'] ) : '';
		$count         = isset( $_POST['ml_pkg_count'] ) ? absint( $_POST['ml_pkg_count'] ) : '';
		$duration      = isset( $_POST['ml_pkg_duration'] ) ? absint( $_POST['ml_pkg_duration'] ) : '';
		$featured      = ! empty( $_POST['ml_pkg_featured'] ) ? 1 : 0;
		$mark_verified = ! empty( $_POST['ml_pkg_mark_verified'] ) ? 1 : 0;
		$use_for_claims= ! empty( $_POST['ml_pkg_use_for_claims'] ) ? 1 : 0;
		$is_claimable  = ! empty( $_POST['ml_pkg_is_claimable'] ) ? 1 : 0;
		$user_id       = isset( $_POST['ml_pkg_user_id'] ) ? absint( $_POST['ml_pkg_user_id'] ) : 0;
		$product_id    = isset( $_POST['ml_pkg_product_id'] ) ? absint( $_POST['ml_pkg_product_id'] ) : 0;
		$order_id      = isset( $_POST['ml_pkg_order_id'] ) ? absint( $_POST['ml_pkg_order_id'] ) : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		// Validate referenced objects.
		if ( $user_id && ! get_userdata( $user_id ) ) {
			$user_id = 0;
		}
		if ( $product_id && 'product' !== get_post_type( $product_id ) ) {
			$product_id = 0;
		} else {
			// Ensure product type is one of job_package or job_package_subscription.
			if ( $product_id ) {
				$terms = wp_get_post_terms( $product_id, 'product_type', [ 'fields' => 'slugs' ] );
				if ( is_wp_error( $terms ) || ! array_intersect( (array) $terms, [ 'job_package', 'job_package_subscription' ] ) ) {
					$product_id = 0;
				}
			}
		}
		if ( $order_id && ! in_array( get_post_type( $order_id ), [ 'shop_order', 'shop_subscription' ], true ) ) {
			$order_id = 0;
		}

		// Persist meta (add/update or delete when empty), keep behavior close to legacy ACF.
		( $limit !== '' )    ? update_post_meta( $post_id, '_limit', $limit )           : delete_post_meta( $post_id, '_limit' );
		( $count !== '' )    ? update_post_meta( $post_id, '_count', $count )           : delete_post_meta( $post_id, '_count' );
		( $duration !== '' ) ? update_post_meta( $post_id, '_duration', $duration )     : delete_post_meta( $post_id, '_duration' );
		update_post_meta( $post_id, '_featured', $featured );
		update_post_meta( $post_id, '_mark_verified', $mark_verified );
		update_post_meta( $post_id, '_use_for_claims', $use_for_claims );
		update_post_meta( $post_id, '_is_claimable', $is_claimable );
		$user_id    ? update_post_meta( $post_id, '_user_id', $user_id )       : delete_post_meta( $post_id, '_user_id' );
		$product_id ? update_post_meta( $post_id, '_product_id', $product_id ) : delete_post_meta( $post_id, '_product_id' );
		$order_id   ? update_post_meta( $post_id, '_order_id', $order_id )     : delete_post_meta( $post_id, '_order_id' );
	}

	/**
	 * User Packages Columns
	 *
	 * @since 1.0.0
	 *
	 * @param array $columns Post Columns.
	 * @return array
	 */
	public function user_package_posts_columns( $columns ) {
		unset( $columns['date'] );
		$columns['title']         = esc_html__( 'Package ID', 'my-listing' );
		$columns['user']          = esc_html__( 'User', 'my-listing' );
		$columns['limit']         = esc_html__( 'Limit', 'my-listing' );
		$columns['duration']      = esc_html__( 'Duration', 'my-listing' );
		$columns['featured']      = esc_html__( 'Featured', 'my-listing' );
		$columns['use_for_claim'] = esc_html__( 'Use for Claim', 'my-listing' );
		$columns['product']       = esc_html__( 'Product', 'my-listing' );
		$columns['order']         = esc_html__( 'Order ID', 'my-listing' );
		return $columns;
	}

	/**
	 * User Packages Custom Columns.
	 *
	 * @since 1.0.0
	 *
	 * @param string $column  Column ID.
	 * @param int    $post_id Post ID.
	 */
	public function user_package_posts_custom_column(  $column, $post_id  ) {
		switch ( $column ) {

			case 'user':
				$title = esc_html__( 'n/a', 'my-listing' );
				$user_id = absint( get_post_meta( $post_id, '_user_id', true ) );
				if ( $user_id ) {
					$user = get_userdata( $user_id );
					if ( $user ) {
						$user_id = '<a target="_blank" href="' . esc_url( get_edit_user_link( $user_id ) ) . '">#' . $user_id . '</a>';
						$title = "{$user_id} - {$user->user_login} ({$user->user_email})";
					}
				}

				echo $title;
			break;

			case 'limit':
				$count = absint( get_post_meta( $post_id, '_count', true ) );
				$limit = absint( get_post_meta( $post_id, '_limit', true ) );

				$package_count = $count ? sprintf( __( '%s Posted', 'my-listing' ), $count ) : '';
				$package_limit = $limit ? $limit : __( 'Unlimited', 'my-listing' );

				$text = $package_count ? $package_count . ' / ' . $package_limit : $package_limit;

				$url = add_query_arg( array(
					'post_type'        => 'job_listing',
					'_user_package_id' => $post_id,
				), admin_url( 'edit.php' ) );

				echo '<a target="_blank" href="' . esc_url( $url ) . '">' . $text . '</a>';
			break;

			case 'duration':
				$duration = absint( get_post_meta( $post_id, '_duration', true ) );
				echo $duration ? sprintf( __( '%s Days', 'my-listing' ), $duration ) : '&ndash;';
			break;

			case 'featured':
				$featured = get_post_meta( $post_id, '_featured', true );
				echo $featured ? __( 'Yes', 'my-listing' ) : __( 'No', 'my-listing' );
			break;

			case 'use_for_claim':
				$claim = get_post_meta( $post_id, '_use_for_claims', true );
				echo $claim ? __( 'Yes', 'my-listing' ) : __( 'No', 'my-listing' );
			break;

			case 'product':
				$link = esc_html__( 'n/a', 'my-listing' );
				$product_id = get_post_meta( $post_id, '_product_id', true );
				if ( $product_id ) {
					$product = wc_get_product( $product_id );
					if ( $product ) {
						$link = '<a target="_blank" href="' . esc_url( get_edit_post_link( $product_id ) ) . '">' . $product->get_name() . '</a>';
					}
				}
				echo $link;
			break;

			case 'order':
				$link = esc_html__( 'n/a', 'my-listing' );
				$order_id = absint( get_post_meta( $post_id, '_order_id', true ) );
				if ( $order_id ) {
					$link = '<a target="_blank" href="' . esc_url( get_edit_post_link( $order_id ) ) . '">#' . $order_id . '</a>';
				}
				echo $link;
			break;
		}
	}

	/**
	 * Remove User Packages Quick Edit.
	 *
	 * @since 1.0.0
	 *
	 * @param array   $actions Row Actions.
	 * @param WP_Post #post    Post Object.
	 * @return array
	 */
	public function remove_user_package_quick_edit( $actions, $post ) {
		if ( 'case27_user_package' === $post->post_type ) {
			unset( $actions['inline hide-if-no-js'] );
		}
		return $actions;
	}

	/**
	 * Remove User Packages Edit Bulk Actions
	 *
	 * @since 1.0.0
	 *
	 * @param array $actions Actions list.
	 * @return array
	 */
	public function remove_user_package_bulk_action_edit( $actions ) {
		unset( $actions['edit'] );
		return $actions;
	}

	/**
	 * Delete User Packages when user is deleted.
	 *
	 * @since 1.0.0
	 *
	 * @param int      $id       ID of the deleted user.
	 * @param int|null $reassign ID of the user to reassign posts and links to.
	 */
	public function delete_user_packages_with_user( $id, $reassign ) {
		$packages = get_posts( [
			'post_type'        => 'case27_user_package',
			'post_status'      => 'any',
			'posts_per_page'   => -1,
			'suppress_filters' => false,
			'fields'           => 'ids',
			'meta_query' => [ [
				'key'     => '_user_id',
				'value'   => $id,
				'compare' => 'IN',
			] ],
		] );

		foreach ( (array) $packages as $package_id ) {
			wp_delete_post( $package_id, false ); // Move to trash.
		}
	}

	/**
	 * Save User Package
	 * Currently only to set post status.
	 *
	 * @param int     $post_id User Package ID.
	 * @param WP_Post $post    Post Object
	 */
	public function save_user_package( $post_id, $post = null ) {
		$package = \MyListing\Src\Package::get( $post_id );
		if ( ! $package ) {
			return;
		}

		remove_action( 'save_post', [ $this, __FUNCTION__ ] );
		$package->maybe_update_status();
		add_action( 'save_post', [ $this, __FUNCTION__ ] );
	}

	/**
	 * Decrease package count for listings that go from pending approval to trash.
	 * This should only have effect if done from the WP Admin backend, so regular users
	 * can't change package counts by deleting their listings.
	 *
	 * @since 1.6
	 */
	public function pending_to_trash( $post ) {
		$listing = \MyListing\Src\Listing::get( $post );
		if ( ! ( is_admin() && $listing && $listing->get_package() ) ) {
			return;
		}

		$listing->get_package()->decrease_count();
	}

	/**
	 * Increase package count when listing is untrashed and status is set to pending approval.
	 * This should only have effect if done from the WP Admin backend, so regular users
	 * can't change package counts by deleting their listings.
	 *
	 * @since 1.6
	 */
	public function trash_to_pending( $post ) {
		$listing = \MyListing\Src\Listing::get( $post );
		if ( ! ( is_admin() && $listing && $listing->get_package() ) ) {
			return;
		}

		$listing->get_package()->increase_count();
	}

	/**
	 * Search packages by keyword.
	 *
	 * @since 2.6.5
	 */
	public function add_keyword_search( $vars ) {
		$screen = get_current_screen();
		if ( ! ( $screen && $screen->id === 'edit-case27_user_package' ) || empty( $vars['s'] ) ) {
			return $vars;
		}

		add_filter( 'posts_join', function( $join ) {
			global $wpdb;
			$join .= " LEFT JOIN {$wpdb->users} AS user ON ( {$wpdb->posts}.post_author = user.ID ) ";
			return $join;
		} );

		add_filter( 'posts_search', function( $search ) use ( $vars ) {
			global $wpdb;
			$keyword = sanitize_text_field( $vars['s'] );
			$keyword_query = $wpdb->prepare( "
				OR (
					user.user_login LIKE %s OR
					user.user_nicename LIKE %s OR
					user.user_email LIKE %s OR
					user.display_name LIKE %s
				)
			", '%'.$keyword.'%', '%'.$keyword.'%', '%'.$keyword.'%', '%'.$keyword.'%' );

			$search = str_replace(
				"OR ({$wpdb->posts}.post_excerpt",
				"{$keyword_query} OR ({$wpdb->posts}.post_excerpt",
				$search
			);

			return $search;
		} );

		return $vars;
	}

	public function change_package_author( $post_id, $post, $update ) {
		if ( ! $update ) {
			return $post_id;
		}

		if ( 'case27_user_package' !== $post->post_type ) {
            return $post_id;
	    }

		$user_id = absint( get_post_meta( $post_id, '_user_id', true ) );

		if ( $user_id ) {
			global $wpdb;
            $wpdb->update(
                $wpdb->posts,
                [ 'post_author' => $user_id ],
                [ 'ID' => $post_id ]
            );
		}
	}

}
