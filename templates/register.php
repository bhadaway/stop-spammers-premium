<form name="registerform" id="registerform" action="<?php echo home_url( '/register/' ); ?>" method="post" novalidate="novalidate">
	<?php ssp_show_error(); ?>
	<p class="ssp-input-wrapper">
		<label for="user_login"><?php _e( 'Username', 'stop-spammers-premium' ); ?></label>
		<input type="text" name="user_login" id="user_login" class="input" size="20" autocapitalize="off" value="<?php echo ( isset( $_POST['user_login'] ) ? esc_attr( $_POST['user_login'] ) : '' ); ?>">
	</p>
	<p class="ssp-input-wrapper">
		<label for="user_email"><?php _e( 'Email', 'stop-spammers-premium' ); ?></label>
		<input type="email" name="user_email" id="user_email" class="input" value="<?php echo ( isset($_POST['user_email'] ) ? esc_attr( $_POST['user_email'] ) : '' ); ?>" size="25">
	</p>
	<p class="ssp-input-wrapper url">
		<label for="user_url"><?php _e( 'Website', 'stop-spammers-premium' ); ?></label>
		<input type="url" name="user_url" id="user_url" class="input" value="https://example.com/" size="25">
	</p>
	<?php do_action( 'register_form' ); ?>
	<p id="reg_passmail"><?php _e( 'Registration confirmation will be emailed to you.' ); ?></p>
	<br class="clear">
	<input type="hidden" name="redirect_to" value="">
	<p class="submit">
		<input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="<?php _e( 'Register', 'stop-spammers-premium' ); ?>">
	</p>
	<p class="ssp-link-wrapper">
		<a href="<?php echo home_url( '/login/' ); ?>"><?php _e( 'Login', 'stop-spammers-premium' ); ?></a> | <a href="<?php echo home_url( '/forgot-password/' ); ?>"><?php _e( 'Forgot Password?', 'stop-spammers-premium' ); ?></a>
	</p>
</form>

<style>
p.ssp-input-wrapper label, #registerform label {
    display: block;
}
p.ssp-input-wrapper .input, #registerform .input {
    padding: 15px;
    min-width: 50%;
}
p.ssp-input-wrapper.url {
	display: none !important;
}
</style>