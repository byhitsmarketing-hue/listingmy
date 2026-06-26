<?php
/**
 * User login form template.
 *
 * @since 1.0
 */
if ( ! defined('ABSPATH') ) {
	exit;
} ?>
<form class="sign-in-form woocomerce-form woocommerce-form-login login" method="POST"
	action="<?php echo esc_url( wc_get_page_permalink('myaccount') ) ?>"
	<?php if ( mylisting()->get('roles.login_captcha') ): ?>
	data-recaptcha="true"
	data-recaptcha-action="login_form"
	<?php endif ?>
	>

	<?php do_action( 'woocommerce_login_form_start' ) ?>

	<div class="form-group">
		<input type="text" name="username" id="username" placeholder=" " autocomplete="username"
			value="<?php echo ! empty( $_POST['username'] ) ? esc_attr( $_POST['username'] ) : '' ?>">
		<label for="username"><?php _ex( 'Username', 'Login form', 'my-listing' ) ?></label>
	</div>

	<div class="form-group">
		<input type="password" name="password" id="password" placeholder=" ">
		<label for="password"><?php _ex( 'Password', 'Login form', 'my-listing' ) ?></label>
	</div>

	<?php do_action( 'woocommerce_login_form' ); ?>

	<?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>

	<?php if ( mylisting()->get('roles.login_captcha') ): ?>
		<?php \MyListing\display_recaptcha() ?>
	<?php endif ?>
	<div class="form-group">
		<button type="submit" class="buttons button-2 full-width" name="login" value="Login"
			<?php if ( mylisting()->get('roles.login_captcha') && mylisting_get_setting('mylisting_recaptcha_type') === 'v3' ): ?>
			disabled
			data-recaptcha-submit
			title="<?php _e( 'Loading...', 'my-listing' ) ?>"
			<?php endif ?>
		>
			<i class="mi person user-area-icon"></i>
			<?php _e( 'Sign in', 'my-listing' ) ?>
		</button>
	</div>

	<div class="form-info">
		<div class="md-checkbox">
			<input type="checkbox" name="rememberme" type="checkbox" id="rememberme" value="forever">
			<label for="rememberme" class="">
				<?php _e( 'Remember me', 'my-listing' ) ?>
			</label>
		</div>
	</div>

	<?php do_action( 'woocommerce_login_form_end' ); ?>

	<div class="forgot-password">
		<a href="<?php echo esc_url( wp_lostpassword_url() ) ?>">
			<i class="mi lock"></i>
			<?php _e( 'Forgot password?', 'my-listing' ) ?>
		</a>
	</div>
</form>
