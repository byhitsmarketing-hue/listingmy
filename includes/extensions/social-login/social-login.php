<?php

namespace MyListing\Ext\Social_Login;

class Social_Login {

    public $networks = [];

    public static function boot() {
    	new self;
    }

	public function __construct() {
		// Setup native (non-ACF) settings page.
		add_action( 'mylisting/init', [ $this, 'setup_options_page' ] );

		// Initialize social login.
		add_action( 'init', [ $this, 'initialize' ], 30 );
	}

	/**
	 * Initialize social login.
	 *
	 * @since 1.6.6
	 */
	public function initialize() {
		// Only run past this for logged out users, or for logged in users when editing account details.
		if ( ! class_exists( 'WooCommerce' ) ) {
			return false;
		}

		// Setup supported networks.
		$this->setup_networks();

		// Setup login endpoints.
		add_action( 'mylisting_ajax_cts_login_endpoint', [ $this, 'login_endpoint' ] );
		add_action( 'mylisting_ajax_nopriv_cts_login_endpoint', [ $this, 'login_endpoint' ] );

        // Display login buttons.
        add_action( 'woocommerce_after_login_form_fields', [ $this, 'display_buttons' ] );

        // Display connected accounts.
        add_action( 'mylisting/connected-accounts-section', [ $this, 'display_connected_accounts' ], 10 );
        add_filter( 'mylisting/social-login-enabled', [ $this, 'get_networks' ] );

        if ( apply_filters( 'mylisting/enable-user-avatars', true ) !== false ) {
	        // profile picture settings
	        add_action( 'mylisting/account-details/before-profile-picture', [ $this, 'display_profile_picture_settings' ], 10 );
	        add_action( 'woocommerce_save_account_details', [ $this, 'save_profile_picture_settings' ], 25 );

	        // modify user avatar based on picture settings
			add_filter( 'get_avatar_url', [ $this, 'set_user_picture' ], 35, 3 );
        }
	}

	/**
	 * Init supported social login networks.
	 *
	 * @since 1.6.3
	 */
	public function setup_networks() {
		$this->networks['google']   = new Networks\Google();
		$this->networks['facebook'] = new Networks\Facebook();

		$this->networks = array_filter( $this->networks, function( $network ) {
			return $network->is_enabled();
		} );
	}

	/**
	 * Social login endpoint. Handles general request validation,
	 * and instantiates the requested network's class.
	 *
	 * @since 1.6.3
	 */
	public function login_endpoint() {
		check_ajax_referer( 'c27_ajax_nonce', 'security' );

		if ( empty( $_POST['network'] ) || empty( $this->networks[ $_POST['network'] ] ) ) {
			return false;
		}

		$network = $this->networks[ $_POST['network'] ];
		$network->handle_request( $_POST );
	}

	/**
	 * Get list of active networks.
	 *
	 * @since  1.6.6
	 * @return array $networks
	 */
	public function get_networks() {
		$networks = apply_filters( 'mylisting\social-login\networks', array_keys( $this->networks ) );

		// Filter out networks that aren't active or don't exist.
		foreach ( $networks as $key => $network ) {
			if ( empty( $this->networks[ $network ] ) ) {
				unset( $networks[ $key ] );
				continue;
			}
		}

		return $networks;
	}

	/**
	 * Output social login buttons.
	 *
	 * @since 1.6.3
	 */
	public function display_buttons() {
		if ( ! ( $networks = $this->get_networks() ) ) {
			return false;
		}

		// Output buttons.
		?><div class="cts-social-login-wrapper">
			<p class="connect-with"><?php _ex( 'Or connect with', 'Social login message', 'my-listing' ) ?></p>
			<div class="cts-network-wrapper">
				<?php foreach ( $networks as $network ): ?>
				<?php echo $this->networks[ $network ]->display_button() ?>
			<?php endforeach ?>
		</div>
		</div><?php
	}

	/**
	 * Display Connected Accounts section in WooCommerce edit account page.
	 *
	 * @since 1.6.6
	 */
	public function display_connected_accounts() {
		if ( ! ( $networks = $this->get_networks() ) || ! is_user_logged_in() ) {
			return false;
		}

		// Output buttons.
		?><div class="cts-connected-accounts">
			<?php foreach ( $networks as $network ): ?>
				<?php echo $this->networks[ $network ]->display_connected_account() ?>
			<?php endforeach ?>
		</div><?php
	}

	/**
	 * Display profile picture settings in WooCommerce edit account page.
	 *
	 * @since 1.6.6
	 */
	public function display_profile_picture_settings() {
		if ( ! ( $networks = $this->get_networks() ) || ! is_user_logged_in() ) {
			return false;
		}

		// Get all networks with a user picture.
		$networks = array_filter( $networks, function( $network ) {
			return $this->networks[ $network ]->get_user_picture();
		} );

		if ( empty( $networks ) ) {
			return false;
		}

		$current_value = get_user_meta( get_current_user_id(), 'mylisting_profile_picture', true );
		if ( ! in_array( $current_value, $networks ) ) {
			$current_value = 'default';
		}

		// Output settings.
		?><div class="cts-user-picture-settings">
			<h5><?php _e( 'Profile Picture', 'my-listing' ) ?></h5>
			<?php foreach ( $networks as $network ): $network_id = sprintf( 'cts-user-picture-use-%s', $this->networks[ $network ]->name ); ?>
				<div class="md-checkbox">
					<input id="<?php echo esc_attr( $network_id ) ?>" type="radio" name="cts-user-picture-settings" value="<?php echo esc_attr( $this->networks[ $network ]->name ) ?>" <?php checked( $network, $current_value ); ?>>
					<label for="<?php echo esc_attr( $network_id ) ?>"><?php printf( __( 'Use my %s account picture', 'my-listing' ), ucwords( $this->networks[ $network ]->name ) ) ?></label>
				</div>
			<?php endforeach ?>
			<div class="md-checkbox">
				<input id="cts-user-picture-use-default" type="radio" name="cts-user-picture-settings" value="default" <?php checked( 'default', $current_value ); ?>>
				<label for="cts-user-picture-use-default"><?php _ex( 'Use a custom picture', 'Profile picture settings', 'my-listing' ) ?></label>
			</div>
		</div><?php
	}

	/**
	 * Save profile picture settings.
	 *
	 * @since 1.6.6
	 */
	public function save_profile_picture_settings( $user_id ) {
		if ( ! ( $networks = $this->get_networks() ) || ! is_user_logged_in() || empty( $_POST['cts-user-picture-settings'] ) ) {
			return false;
		}

		// Picture settings.
		$picture_settings = sanitize_text_field( $_POST['cts-user-picture-settings'] );

		// Get all networks with a user picture.
		$networks = array_filter( $networks, function( $network ) {
			return $this->networks[ $network ]->get_user_picture();
		} );

		if ( empty( $networks ) || ! in_array( $picture_settings, array_merge( $networks, ['default'] ) ) ) {
			return false;
		}

		// Save settings.
		update_user_meta( $user_id, 'mylisting_profile_picture', $picture_settings );
	}

	/**
	 * Set user avatar based on profile picture settings.
	 *
	 * @since 1.6.6
	 */
	public function set_user_picture( $url, $id_or_email, $args ) {
		if ( (bool) $args['force_default'] === true ) {
			return $url;
		}

		if ( ! ( $user = c27()->get_user_by_id_or_email( $id_or_email ) ) ) {
			return $url;
		}

		// use google/facebook photo if configured by the user
		if ( $networks = $this->get_networks() ) {
			$picture_to_use = get_user_meta( $user->ID, 'mylisting_profile_picture', true );
			if ( in_array( $picture_to_use, $networks ) ) {
				$picture_url = $this->networks[ $picture_to_use ]->get_user_picture( $user->ID );
				if ( ! empty( $picture_url ) ) {
					return $picture_url;
				}
			}
		}

		// if a custom avatar has been uploaded by the user, use it
        $custom_picture = get_user_meta( $user->ID, '_mylisting_profile_photo_url', true );
        if ( ! empty( $custom_picture ) ) {
        	return $custom_picture;
        }

		return $url;
	}

	/**
	 * Setup social login options page in WP Admin > Theme Options > Social Login.
	 *
	 * @since 1.6.3
	 */
	public function setup_options_page() {
		// Add submenu under Theme Tools parent.
		add_action( 'admin_menu', function() {
			add_submenu_page(
				'case27/tools.php',
				_x( 'Social Login', 'Social Login page title in WP Admin', 'my-listing' ),
				_x( 'Social Login', 'Social Login menu title in WP Admin', 'my-listing' ),
				'manage_options',
				'theme-social-login-settings',
				function() { $this->render_options_page(); }
			);
		} );

		// Save handler.
		add_action( 'admin_post_ml_save_social_login_settings', function() { $this->save_options_page(); } );
	}

	/**
	 * Render native settings page for Social Login (replacing ACF options page).
	 */
	protected function render_options_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$g_enabled_opt   = get_option( 'mylisting_social_login_google_enabled', null );
		$g_enabled       = is_null( $g_enabled_opt ) ? (bool) c27()->get_setting( 'social_login_google_enabled', false ) : (bool) $g_enabled_opt;
		$g_client_id_opt = get_option( 'mylisting_social_login_google_client_id', null );
		$g_client_id     = is_null( $g_client_id_opt ) ? (string) c27()->get_setting( 'social_login_google_client_id', '' ) : (string) $g_client_id_opt;

		$f_enabled_opt   = get_option( 'mylisting_social_login_facebook_enabled', null );
		$f_enabled       = is_null( $f_enabled_opt ) ? (bool) c27()->get_setting( 'social_login_facebook_enabled', false ) : (bool) $f_enabled_opt;
		$f_app_id_opt    = get_option( 'mylisting_social_login_facebook_app_id', null );
		$f_app_id        = is_null( $f_app_id_opt ) ? (string) c27()->get_setting( 'social_login_facebook_app_id', '' ) : (string) $f_app_id_opt;

		echo '<div class="wrap">';
		echo '<h1>' . esc_html_x( 'Social Login', 'admin', 'my-listing' ) . '</h1>';
		if ( ! empty( $_GET['updated'] ) ) {
			echo '<div id="message" class="updated notice is-dismissible"><p>' . esc_html__( 'Settings saved.', 'my-listing' ) . '</p></div>';
		}
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		echo '<input type="hidden" name="action" value="ml_save_social_login_settings" />';
		wp_nonce_field( 'ml_social_login_settings_save', 'ml_social_login_settings_nonce' );

		echo '<h2 class="title">' . esc_html__( 'Google', 'my-listing' ) . '</h2>';
		echo '<table class="form-table" role="presentation">';
		echo '<tr><th scope="row"><label class="ml-backend-label" for="ml_sl_google_enabled">' . esc_html__( 'Enable Sign-In with Google', 'my-listing' ) . '</label></th>';
		echo '<td><div class="ml-switch">'
			. '<input type="checkbox" id="ml_sl_google_enabled" name="ml_sl_google_enabled" value="1" ' . checked( $g_enabled, true, false ) . ' />'
			. '<label class="ml-switch-label" for="ml_sl_google_enabled"></label>'
			. '<span class="ml-switch-text">' . esc_html__( 'Allow users to sign in with their Google account.', 'my-listing' ) . '</span>'
			. '</div></td></tr>';
		echo '<tr><th scope="row"><label for="ml_sl_google_client_id" class="ml-backend-label">' . esc_html__( 'Client ID', 'my-listing' ) . '</label></th>';
		echo '<td><input type="text" id="ml_sl_google_client_id" name="ml_sl_google_client_id" class="regular-text" value="' . esc_attr( $g_client_id ) . '" />';
		echo '<p class="description">' . wp_kses_post( sprintf( __( 'Create a Google Client ID from <a href="%s" target="_blank">Google Identity Platform</a>.', 'my-listing' ), 'https://developers.google.com/identity/sign-in/web/sign-in' ) ) . '</p></td></tr>';
		echo '</table>';

		echo '<h2 class="title">' . esc_html__( 'Facebook', 'my-listing' ) . '</h2>';
		echo '<table class="form-table" role="presentation">';
		echo '<tr><th scope="row"><label class="ml-backend-label" for="ml_sl_facebook_enabled">' . esc_html__( 'Enable Sign-In with Facebook', 'my-listing' ) . '</label></th>';
		echo '<td><div class="ml-switch">'
			. '<input type="checkbox" id="ml_sl_facebook_enabled" name="ml_sl_facebook_enabled" value="1" ' . checked( $f_enabled, true, false ) . ' />'
			. '<label class="ml-switch-label" for="ml_sl_facebook_enabled"></label>'
			. '<span class="ml-switch-text">' . esc_html__( 'Allow users to sign in with their Facebook account.', 'my-listing' ) . '</span>'
			. '</div></td></tr>';
		echo '<tr><th scope="row"><label for="ml_sl_facebook_app_id" class="ml-backend-label">' . esc_html__( 'App ID', 'my-listing' ) . '</label></th>';
		echo '<td><input type="text" id="ml_sl_facebook_app_id" name="ml_sl_facebook_app_id" class="regular-text" value="' . esc_attr( $f_app_id ) . '" />';
		echo '<p class="description">' . wp_kses_post( sprintf( __( 'Create a Facebook App ID from <a href="%s" target="_blank">Facebook for Developers</a>.', 'my-listing' ), 'https://developers.facebook.com/docs/apps/register' ) ) . '</p></td></tr>';
		echo '</table>';

		submit_button( _x( 'Save Changes', 'admin', 'my-listing' ) );
		echo '</form>';
		echo '</div>';
	}

	/**
	 * Save handler for native Social Login settings.
	 */
	protected function save_options_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to perform this action.', 'my-listing' ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reading nonce from $_POST to verify it.
		if ( empty( $_POST['ml_social_login_settings_nonce'] ) || ! wp_verify_nonce( $_POST['ml_social_login_settings_nonce'], 'ml_social_login_settings_save' ) ) {
			wp_die( esc_html__( 'Invalid request.', 'my-listing' ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified above; safe to read $_POST values below.
		$g_enabled   = isset( $_POST['ml_sl_google_enabled'] ) ? 1 : 0;
		$g_client_id = isset( $_POST['ml_sl_google_client_id'] ) ? sanitize_text_field( wp_unslash( $_POST['ml_sl_google_client_id'] ) ) : '';
		$f_enabled   = isset( $_POST['ml_sl_facebook_enabled'] ) ? 1 : 0;
		$f_app_id    = isset( $_POST['ml_sl_facebook_app_id'] ) ? sanitize_text_field( wp_unslash( $_POST['ml_sl_facebook_app_id'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		update_option( 'mylisting_social_login_google_enabled', (int) $g_enabled );
		$g_client_id ? update_option( 'mylisting_social_login_google_client_id', $g_client_id ) : delete_option( 'mylisting_social_login_google_client_id' );
		update_option( 'mylisting_social_login_facebook_enabled', (int) $f_enabled );
		$f_app_id ? update_option( 'mylisting_social_login_facebook_app_id', $f_app_id ) : delete_option( 'mylisting_social_login_facebook_app_id' );

		$redirect = add_query_arg( [ 'page' => 'theme-social-login-settings', 'updated' => 1 ], admin_url( 'admin.php' ) );
		wp_safe_redirect( $redirect );
		exit;
	}
}
