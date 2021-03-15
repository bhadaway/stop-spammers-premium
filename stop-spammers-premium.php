<?php
/*
Plugin Name: Stop Spammers Premium
Plugin URI: https://stopspammers.io/downloads/stop-spammers-premium/
Description: Add even more features to the popular Stop Spammers plugin. Firewall, honeypot, themable login, import/export tool, and more.
Version: 2021.2
Author: Trumani
Author URI: https://stopspammers.io/
License: https://www.gnu.org/licenses/gpl.html
Domain Path: /languages
Text Domain: stop-spammers-premium
*/

$composer = __DIR__ . '/vendor/autoload.php';

if ( file_exists( $composer ) ) {
	require $composer;
}

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

if ( !defined( 'ABSPATH' ) ) {
	http_response_code( 404 );
	die();
}

// making translation-ready
function ssp_load_plugin_textdomain() {
    load_plugin_textdomain( 'stop-spammers-premium', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'ssp_load_plugin_textdomain' );

// settings successfully updated message
function ssp_admin_notice_success() {
	?>
	<div class="notice notice-success is-dismissible">
		<p><?php _e( 'Options Updated', 'stop-spammers-premium' ); ?></p>
	</div>
	<?php
}

// checks if the Stop Spammers plugin is activated
function ssprem_activate() {
	if ( !function_exists( 'is_plugin_active_for_network' ) ) {
		include_once( ABSPATH . '/wp-admin/includes/plugin.php' );
	}
	if ( current_user_can( 'activate_plugins' ) && ( !class_exists( 'be_module' ) and !is_plugin_active( 'stop-spammer-registrations-plugin/stop-spammer-registrations-new.php' ) ) ) {
		// deactivate the plugin
		deactivate_plugins( plugin_basename( __FILE__ ) );
		// throw an error in the WordPress admin console
		$error_message = '<p class="dependency">' . esc_html__( 'This plugin requires the ', 'stop-spammers-premium' ) . '<a href="' . esc_url( 'https://wordpress.org/plugins/stop-spammer-registrations-plugin/' ) . '" target="_blank">Stop Spammers</a>' . esc_html__( ' plugin to be active.', 'stop-spammers-premium' ) . '</p>';
		die( $error_message ); // WPCS: XSS ok.
	}
}
register_activation_hook( __FILE__, 'ssprem_activate' ); 

define( 'SSP_STORE_URL', 'https://stopspammers.io' ); 
define( 'SSP_ITEM_ID', 104 ); 
define( 'SSP_ITEM_NAME', 'STOP SPAMMERS PREMIUM' ); 
define( 'SSP_LICENSE_PAGE', 'ssp_license' );

if ( !class_exists( 'EDD_SL_Plugin_Updater' ) ) {
	include( dirname( __FILE__ ) . '/EDD_SL_Plugin_Updater.php' );
}

function ssp_plugin_updater() {
	$license_key = trim( get_option( 'ssp_license_key' ) );
	$edd_updater = new EDD_SL_Plugin_Updater( SSP_STORE_URL, __FILE__,
		array(
			'version' => '2021.2',
			'license' => $license_key,
			'item_id' => SSP_ITEM_ID,
			'author'  => 'Trumani',
			'beta'    => false,
		)
	);
}
add_action( 'admin_init', 'ssp_plugin_updater', 0 );

function ssp_license_menu() {
	add_submenu_page( 
		'stop_spammers', //parent_slug
		__( 'Stop Spammers License', 'stop-spammers-premium' ), //page_title
		__( 'License Key', 'stop-spammers-premium' ),  //menu_title
		'manage_options', //capability
		SSP_LICENSE_PAGE, //menu_slug
		'ssp_license_page' //function  
	);
	$license = get_option( 'ssp_license_key' );
	$status  = get_option( 'ssp_license_status' );
	if ( $status !== false && $status == 'valid' ) { 
		add_submenu_page( 
			'stop_spammers', //parent_slug
			__( 'Stop Spammers Premium Features', 'stop-spammers-premium' ), //page_title
			__( 'Premium Features', 'stop-spammers-premium' ),  //menu_title
			'manage_options', //capability
			'ssp_premium', //menu_slug
			'ss_export_excel' //function  
		);
	}
}
add_action( 'admin_menu', 'ssp_license_menu', 11 );

// action links
$license = get_option( 'ssp_license_key' );
if ( empty( $license ) ) {
	add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'ssp_license_link' );
	function ssp_license_link( $links ) {
		$links = array_merge( array( '<a href="' . admin_url( 'admin.php?page=ssp_license' ) . '">' . __( 'Enter License Key', 'stop-spammers-premium' ) . '</a>' ), $links );
		return $links;
	}
}

function ss_export_excel() {
	$ss_firewall_setting = '';
	if ( get_option( 'ssp_enable_firewall', '' ) === 'yes' ) {
		$ss_firewall_setting = "checked='checked'";
	}
	$ss_hide_admin_notices = '';
	if ( get_option( 'ss_hide_admin_notices', '' ) === 'yes' ) {
		$ss_hide_admin_notices = "checked='checked'";
	}
	$ss_disable_admin_emails = '';
	if ( get_option( 'ss_disable_admin_emails', '' ) === 'yes' ) {
		$ss_disable_admin_emails = "checked='checked'";
	}
	$ss_disable_admin_emails_update = '';
	if ( get_option( 'ss_disable_admin_emails_update', '' ) === 'yes' ) {
		$ss_disable_admin_emails_update = "checked='checked'";
	}
	$ss_disable_admin_emails_comment = '';
	if ( get_option( 'ss_disable_admin_emails_comment', '' ) === 'yes' ) {
		$ss_disable_admin_emails_comment = "checked='checked'";
	}
	$ss_disable_admin_emails_password_reset = '';
	if ( get_option( 'ss_disable_admin_emails_password_reset', '' ) === 'yes' ) {
		$ss_disable_admin_emails_password_reset = "checked='checked'";
	}
	$ss_disable_admin_emails_new_user = '';
	if ( get_option( 'ss_disable_admin_emails_new_user', '' ) === 'yes' ) {
		$ss_disable_admin_emails_new_user = "checked='checked'";
	}
	$ss_disable_core_nudge = '';
	if ( get_option( 'ss_disable_core_nudge', '' ) === 'yes' ) {
		$ss_disable_core_nudge = "checked='checked'";
	}
	$ss_disable_theme_nudge = '';
	if ( get_option( 'ss_disable_theme_nudge', '' ) === 'yes' ) {
		$ss_disable_theme_nudge = "checked='checked'";
	}
	$ss_disable_plugin_nudge = '';
	if ( get_option( 'ss_disable_plugin_nudge', '' ) === 'yes' ) {
		$ss_disable_plugin_nudge = "checked='checked'";
	}
	$ss_login_setting = '';
	if ( get_option( 'ssp_enable_custom_login', '' ) === 'yes' ) {
		$ss_login_setting = "checked='checked'";
	}
	$ssp_login_attempts = '';
	if ( get_option( 'ssp_login_attempts', '' ) === 'yes' ) {
		$ssp_login_attempts = "checked='checked'";
	}
	$ss_login_type_default = "";
	$ss_login_type_username = "";
	$ss_login_type_email = "";
	if ( get_option( 'ssp_login_type', '' ) == 'username' ) {
		$ss_login_type_username = "checked='checked'";
	} else if ( get_option( 'ssp_login_type', '' ) == 'email'  ) {
		$ss_login_type_email = "checked='checked'";
	} else {
		$ss_login_type_default = "checked='checked'";
	}
	?>
	<div id="ss-plugin" class="wrap">
		<h1 class="ss_head"><?php _e( 'Stop Spammers Premium Features', 'stop-spammers-premium' ); ?></h1>
		<div class="metabox-holder">
			<div class="postbox">
				<h3 style="font-size:18px"><span><?php _e( 'Shortcodes', 'stop-spammers-premium' ); ?></span></h3>
				<div class="inside">
					<p><?php _e( 'Add a lightweight, secure contact form to any page, post, or text widget by adding the following: <strong>[ssp-contact-form]</strong>', 'stop-spammers-premium' ); ?></p>
					<p><?php _e( 'Add our secure, themable login form to any page, post, or text widget by adding the following: <strong>[ssp-login]</strong>', 'stop-spammers-premium' ); ?></p>
					<p><?php _e( 'Show Display Name for Logged In Visitors: <strong>[show_displayname_as]</strong>', 'stop-spammers-premium' ); ?></p>
					<p><?php _e( 'Show First Name and Last Name for Logged In Visitors: <strong>[show_fullname_as]</strong>', 'stop-spammers-premium' ); ?></p>
					<p><?php _e( 'Show Email Address for Logged In Visitors: <strong>[show_email_as]</strong>', 'stop-spammers-premium' ); ?></p>
				</div>
			</div>
			<div class="postbox">
				<form method="post">
					<div class="inside">
						<h3 style="font-size:16px!important"><span><?php _e( 'Firewall Settings', 'stop-spammers-premium' ); ?></span></h3>
						<div class="checkbox switcher">
							<label for="ss_firewall_setting">
								<input type="checkbox" name="ss_firewall_setting" id="ss_firewall_setting" value="yes" <?php echo $ss_firewall_setting; ?>>
								<span><small></small></span>
								<?php _e( 'Enable Firewall', 'stop-spammers-premium' ); ?>
							</label>
						</div>
						<p><input type="hidden" name="ss_firewall_setting_placeholder" value="ss_firewall_setting" /></p>
					</div>
					<hr />
					<div class="inside">
						<h3 style="font-size:16px!important"><span><?php _e( 'Notification Control', 'stop-spammers-premium' ); ?></span></h3>
						<div class="checkbox switcher">
							<label for="ss_hide_admin_notices">
								<input type="checkbox" name="ss_hide_admin_notices" id="ss_hide_admin_notices" value="yes" <?php echo $ss_hide_admin_notices; ?>>
								<span><small></small></span>
								<?php _e( 'Hide All Admin Notices', 'stop-spammers-premium' ); ?>
							</label>
						</div>
						<p></p>
						<div class="checkbox switcher">
							<label for="ss_disable_admin_emails">
								<input type="checkbox" name="ss_disable_admin_emails" id="ss_disable_admin_emails" value="yes" <?php echo $ss_disable_admin_emails; ?>>
								<span><small></small></span>
								<?php _e( 'Disable Admin Emails', 'stop-spammers-premium' ); ?>
							</label>
						</div>
						<p></p>
						<div class="ss_disable_admin_emails_wraps"<?php echo ( get_option( 'ss_disable_admin_emails', 'no' ) == 'yes' ? '': ' style="display:none"' ); ?>>
							<div class="checkbox switcher">
								<label for="ss_disable_admin_emails_update">
									<input type="checkbox" name="ss_disable_admin_emails_update" id="ss_disable_admin_emails_update" value="yes" <?php echo $ss_disable_admin_emails_update; ?>>
									<span><small></small></span>
									<?php _e( 'Disable Update Emails', 'stop-spammers-premium' ); ?>
								</label>
							</div>
							<p></p>
							<div class="checkbox switcher">
								<label for="ss_disable_admin_emails_comment">
									<input type="checkbox" name="ss_disable_admin_emails_comment" id="ss_disable_admin_emails_comment" value="yes" <?php echo $ss_disable_admin_emails_comment; ?>>
									<span><small></small></span>
									<?php _e( 'Disable Comment Emails', 'stop-spammers-premium' ); ?>
								</label>
							</div>
							<p></p>
							<div class="checkbox switcher">
								<label for="ss_disable_admin_emails_password_reset">
									<input type="checkbox" name="ss_disable_admin_emails_password_reset" id="ss_disable_admin_emails_password_reset" value="yes" <?php echo $ss_disable_admin_emails_password_reset; ?>>
									<span><small></small></span>
									<?php _e( 'Disable Password Reset Emails', 'stop-spammers-premium' ); ?>
								</label>
							</div>
							<p></p>
							<div class="checkbox switcher">
								<label for="ss_disable_admin_emails_new_user">
									<input type="checkbox" name="ss_disable_admin_emails_new_user" id="ss_disable_admin_emails_new_user" value="yes" <?php echo $ss_disable_admin_emails_new_user; ?>>
									<span><small></small></span>
									<?php _e( 'Disable New User Emails', 'stop-spammers-premium' ); ?>
								</label>
							</div>
							<p></p>
						</div>
						<div class="checkbox switcher">
							<label for="ss_disable_core_nudge">
								<input type="checkbox" name="ss_disable_core_nudge" id="ss_disable_core_nudge" value="yes" <?php echo $ss_disable_core_nudge; ?>>
								<span><small></small></span>
								<?php _e( 'Disable Core Updates Nudge', 'stop-spammers-premium' ); ?>
							</label>
						</div>
						<p></p>
						<div class="checkbox switcher">
							<label for="ss_disable_theme_nudge">
								<input type="checkbox" name="ss_disable_theme_nudge" id="ss_disable_theme_nudge" value="yes" <?php echo $ss_disable_theme_nudge; ?>>
								<span><small></small></span>
								<?php _e( 'Disable Theme Updates Nudge', 'stop-spammers-premium' ); ?>
							</label>
						</div>
						<p></p>
						<div class="checkbox switcher">
							<label for="ss_disable_plugin_nudge">
								<input type="checkbox" name="ss_disable_plugin_nudge" id="ss_disable_plugin_nudge" value="yes" <?php echo $ss_disable_plugin_nudge; ?>>
								<span><small></small></span>
								<?php _e( 'Disable Plugin Updates Nudge', 'stop-spammers-premium' ); ?>
							</label>
						</div>
						<p><input type="hidden" name="ss_notification_control_setting" value="ss_notification_control_update" /></p>
					</div>
					<div class="inside ss_reset_hidden_notice_wrap"<?php echo ( get_option( 'ss_hide_admin_notices', 'no' ) != 'yes' ? '': ' style="display:none"' ); ?>>
						<hr>
						<h3 style="font-size:16px!important"><span><?php _e( 'Reset hidden notices for ', 'stop-spammers-premium' ); ?></span></h3>	
						<p><input type="radio" name="ss_reset_hidden_notice" value="current" checked="checked"> Current User</p>
						<p><input type="radio" name="ss_reset_hidden_notice" value="all"> All Users</p>
						<?php submit_button( __( 'Reset', 'stop-spammers-premium' ), 'secondary', 'submit', false ); ?>
					</div>
					<hr />
					<div class="inside">
						<h3 style="font-size:16px!important"><span><?php _e( 'Login Settings', 'stop-spammers-premium' ); ?></span></h3>
						<div class="checkbox switcher">
							<label for="ss_login_setting">
								<input type="checkbox" name="ss_login_setting" id="ss_login_setting" value="yes" <?php echo $ss_login_setting; ?> />
								<span><small></small></span>
								<?php _e( 'Enable themed registration and login pages (disables the default wp-login.php).', 'stop-spammers-premium' ); ?>
							</label>
						</div>
						<br />
						<div class="checkbox switcher">
							<label for="ssp_login_attempts">
								<input type="checkbox" name="ssp_login_attempts" id="ssp_login_attempts" value="yes" <?php echo $ssp_login_attempts; ?> />
								<span><small></small></span>
								<strong><?php _e( 'Login Attempts:', 'stop-spammers-premium' ); ?></strong>
								<?php _e( 'After', 'stop-spammers-premium' ); ?>
								<input type="text" name="ssp_login_attempts_threshold" id="ssp_login_attempts_duration" class="ss-small-box" value="<?php echo get_option( 'ssp_login_attempts_threshold', 5 ); ?>" />
								<?php _e( 'failed login attempts within', 'stop-spammers-premium' ); ?>
								<input type="text" name="ssp_login_attempts_duration" id="ssp_login_attempts_duration" class="ss-small-box" value="<?php echo get_option( 'ssp_login_attempts_duration', 1 ); ?>" />
								<select name="ssp_login_attempts_unit" id="ssp_login_attempts_unit" class="ss-small-dropbox">
									<option value="minute" <?php if ( get_option( 'ssp_login_attempts_unit', 'hour' ) == 'minute' ) { echo 'selected="selected"'; } ?>><?php _e( 'minute(s)', 'stop-spammers-premium' ); ?></option>
									<option value="hour" <?php if ( get_option( 'ssp_login_attempts_unit', 'hour' ) == 'hour' ) { echo 'selected="selected"'; } ?>><?php _e( 'hour(s)', 'stop-spammers-premium' ); ?></option>
									<option value="day" <?php if ( get_option( 'ssp_login_attempts_unit', 'hour' ) == 'day' ) { echo 'selected="selected"'; } ?>><?php _e( 'day(s)', 'stop-spammers-premium' ); ?></option>
								</select>,
								<?php _e( 'lockout the account for', 'stop-spammers-premium' ); ?>
								<input type="text" name="ssp_login_lockout_duration" id="ssp_login_lockout_duration" class="ss-small-box" value="<?php echo get_option( 'ssp_login_lockout_duration', 24 ); ?>" /> 
								<select name="ssp_login_lockout_unit" id="ssp_login_lockout_unit" class="ss-small-dropbox">
									<option value="minute" <?php if ( get_option( 'ssp_login_lockout_unit', 'hour' ) == 'minute' ) { echo 'selected="selected"'; } ?>><?php _e( 'minute(s)', 'stop-spammers-premium' ); ?></option>
									<option value="hour" <?php if ( get_option( 'ssp_login_lockout_unit', 'hour' ) == 'hour' ) { echo 'selected="selected"'; } ?>><?php _e( 'hour(s)', 'stop-spammers-premium' ); ?></option>
									<option value="day" <?php if ( get_option( 'ssp_login_lockout_unit', 'hour' ) == 'day' ) { echo 'selected="selected"'; } ?>><?php _e( 'day(s)', 'stop-spammers-premium' ); ?></option>
								</select>.
							</label>
						</div>
						<p><input type="hidden" name="ss_login_setting_placeholder" value="ss_login_setting" /></p>
					</div>
					<hr />
					<div class="inside">
						<h3 style="font-size:16px!important"><span><?php _e( 'Allow users to log in using their username and/or email address', 'stop-spammers-premium' ); ?></span></h3>
						<p><input type="hidden" name="ssp_login_type_field" value="ssp_login_type" /></p>
						<ul class="ss-spacer">
							<li>
								<div class="checkbox switcher">
									<label for="ssp-login-type-default">
										<input name="ssp_login_type" type="radio" id="ssp-login-type-default" value="default" <?php echo $ss_login_type_default; ?>>
										<span><small></small></span>
										<?php _e( 'Username or Email', 'stop-spammers-premium' ); ?>
									</label>
								</div>
							</li>
							<li>
								<div class="checkbox switcher">
									<label for="ssp-login-type-username">
										<input name="ssp_login_type" type="radio" id="ssp-login-type-username" value="username" <?php echo $ss_login_type_username; ?>>
										<span><small></small></span>
										<?php _e( 'Username Only', 'stop-spammers-premium' ); ?>
									</label>
								</div>
							</li>
							<li>
								<div class="checkbox switcher">
									<label for="ssp-login-type-email">
										<input name="ssp_login_type" type="radio" id="ssp-login-type-email" value="email" <?php echo $ss_login_type_email; ?>>
										<span><small></small></span>
										<?php _e( 'Email Only', 'stop-spammers-premium' ); ?>
									</label>
								</div>
							</li>
						</ul>
						<hr />						
						<p>
							<?php wp_nonce_field( 'ssp_login_type_nonce', 'ssp_login_type_nonce' ); ?>
							<?php submit_button( __( 'Save Changes', 'stop-spammers-premium' ), 'primary', 'submit', false ); ?>
						</p>
					</div>
				</form>
			</div>
			<div class="postbox">
				<h3 style="font-size:16px!important"><span><?php _e( 'Export Log Settings', 'stop-spammers-premium' ); ?></span></h3>
				<div class="inside">
					<p><?php _e( 'Export the Log records to an Excel file.', 'stop-spammers-premium' ); ?></p>
					<form method="post">
						<p><input type="hidden" name="export_log" value="export_log_data" /></p>
						<hr />
						<p>
							<?php wp_nonce_field( 'ssp_export_action', 'ssp_export_action' ); ?>
							<?php submit_button( __( 'Export', 'stop-spammers-premium' ), 'secondary', 'submit', false ); ?>
						</p>
					</form>
				</div>
			</div>
			<div class="postbox">
				<h3 style="font-size:16px!important"><span><?php _e( 'Export Settings', 'stop-spammers-premium' ); ?></span></h3>
				<div class="inside">
					<p><?php _e( 'Export the plugin settings for this site as a .json file. This allows you to easily import the configuration into another site.', 'stop-spammers-premium' ); ?></p>
					<form method="post">
						<p><input type="hidden" name="ssp_action" value="export_settings" /></p>
						<hr />
						<p>
							<?php wp_nonce_field( 'ssp_export_nonce', 'ssp_export_nonce' ); ?>
							<?php submit_button( __( 'Export', 'stop-spammers-premium' ), 'secondary', 'submit', false ); ?>
						</p>
					</form>
				</div><!-- .inside -->
			</div><!-- .postbox -->
			<div class="postbox">
				<h3 style="font-size:16px!important"><span><?php _e( 'Import Settings', 'stop-spammers-premium' ); ?></span></h3>
				<div class="inside">
					<p><?php _e( 'Import the plugin settings from a .json file. This file can be obtained by exporting the settings on another site using the form above.', 'stop-spammers-premium' ); ?></p>
					<form method="post" enctype="multipart/form-data">
						<p><input type="file" name="import_file" /></p>
						<hr />
						<p>
							<input type="hidden" name="ssp_action" value="import_settings" />
							<?php wp_nonce_field( 'ssp_import_nonce', 'ssp_import_nonce' ); ?>
							<?php submit_button( __( 'Import', 'stop-spammers-premium' ), 'secondary', 'submit', false ); ?>
						</p>
					</form>
				</div><!-- .inside -->
			</div><!-- .postbox -->
			<div class="postbox">
				<h3 style="font-size:16px!important"><span><?php _e( 'Reset Settings', 'stop-spammers-premium' ); ?></span></h3>
				<div class="inside">
					<p><?php _e( 'Reset the plugin settings for this site. This allows you to easily reset the configuration.', 'stop-spammers-premium' ); ?></p>
					<form method="post">
						<p><input type="hidden" name="ssp_action" value="reset_settings" /></p>
						<hr />
						<p>
							<?php wp_nonce_field( 'ssp_reset_nonce', 'ssp_reset_nonce' ); ?>
							<?php submit_button( __( 'Reset', 'stop-spammers-premium' ), 'secondary', 'submit', false ); ?>
						</p>
					</form>
				</div><!-- .inside -->
			</div><!-- .postbox -->			
		</div><!-- .metabox-holder -->
	</div>
	<?php
}

// add contact form shortcode
function ssp_contact_form_shortcode() {
	ob_start();
	_e( '
		<form id="ssp-contact-form" method="post" action="#send">
    		<p id="name"><input type="text" name="sign" placeholder="' . __( 'Name', 'stop-spammers-premium' ) . '" autocomplete="off" size="35" required /></p>
    		<p id="email"><input type="email" name="email" placeholder="' . __( 'Email', 'stop-spammers-premium' ) . '" autocomplete="off" size="35" required /></p>
    		<p id="phone"><input type="tel" name="phone" placeholder="' . __( 'Phone (optional)', 'stop-spammers-premium' ) . '" autocomplete="off" size="35" /></p>
    		<p id="url"><input type="url" name="url" placeholder="' . __( 'URL', 'stop-spammers-premium' ) . '" value="https://example.com/" autocomplete="off" tabindex="-1" size="35" required /></p>
    		<p id="message"><textarea name="message" placeholder="' . __( 'Message', 'stop-spammers-premium' ) . '" rows="5" cols="100"></textarea></p>
    		<p id="submit"><input type="submit" value="' . __( 'Submit', 'stop-spammers-premium' ) . '" /></p>
		</form>
		<style>
    	#ssp-contact-form, #ssp-contact-form * {
        	box-sizing: border-box;
        	transition: all 0.5s ease
    	}
    	#ssp-contact-form input, #ssp-contact-form textarea {
			width: 100%;
			font-family: arial, sans-serif;
			font-size: 14px;
			color: #767676;
			padding: 15px;
			border: 1px solid transparent;
			background: #f6f6f6
    	}
    	#ssp-contact-form input:focus, #ssp-contact-form textarea:focus {
			color: #000;
			border: 1px solid #007acc
		}
		#ssp-contact-form #submit input {
			display: inline-block;
			font-size: 18px;
			color: #fff;
			text-align: center;
			text-decoration: none;
			padding: 15px 25px;
			background: #007acc;
			cursor: pointer
    	}
    	#ssp-contact-form #submit input:hover, #submit input:focus {
			opacity: 0.8
    	}
    	#ssp-contact-form #url {
			position: absolute;
			top: 0;
			left: 0;
			width: 0;
			height: 0;
			opacity: 0;
			z-index: -1
    	}
    	#send {
			text-align: center;
			padding: 5%
    	}
    	#send.success {
			color: green
    	}
    	#send.fail {
			color: red
    	}
		</style>
	', 'stop-spammers-premium' );
	$url = isset( $_POST['url'] ) ? $_POST['url'] : '';
	if ( esc_url( $url ) == 'https://example.com/' ) {
		$to        = sanitize_email( get_option( 'admin_email' ) );
		$subject   = '' . __( 'Inquiry', 'stop-spammers-premium' ) . ' | ' . esc_html( get_option( 'blogname' ) ) . '';
		$name      = sanitize_text_field( $_POST['sign'] );
		$email     = sanitize_email( $_POST['email'] );
		$phone     = sanitize_text_field( $_POST['phone'] );
		$message   = esc_textarea( $_POST['message'] );
		$validated = true;
		if ( !$validated ) {
			print '<p id="send" class="fail">' . __( 'Message Failed', 'stop-spammers-premium' ) . '</p>';
			exit;
		}
		$body    = "";
		$body   .= "" . __( 'Name: ', 'stop-spammers-premium' ) . "";
		$body   .= $name;
		$body   .= "\n";
		$body   .= "" . __( 'Email: ', 'stop-spammers-premium' ) . "";
		$body   .= $email;
		$body   .= "\n";
		$body   .= "" . __( 'Phone: ', 'stop-spammers-premium' ) . "";
		$body   .= $phone;
		$body   .= "\n\n";
		$body   .= $message;
		$body   .= "\n";
		$success = wp_mail( $to, $subject, $body, "" . __( 'From: ', 'stop-spammers-premium' ) . "<$email>" );
		if ( $success ) {
			print '<p id="send" class="success">' . __( 'Message Sent Successfully', 'stop-spammers-premium' ) . '</p>';
		} else {
			print '<p id="send" class="fail">' . __( 'Message Failed', 'stop-spammers-premium' ) . '</p>';
		}
	}
	$output = ob_get_clean();
	return $output;
}
add_shortcode( 'ssp-contact-form', 'ssp_contact_form_shortcode' );
add_filter( 'widget_text', 'do_shortcode' );

// add honeypot to Contact Form 7
function ssp_cf7_add_honeypot( $form ) {
	$html  = '';
	$html .= '<p class="ssp-user">';
	$html .= '<label>' . __( 'Your Website (required)', 'stop-spammers-premium' ) . '<br />';
	$html .= '<span class="wpcf7-form-control-wrap your-website">';
	$html .= '<input type="text" name="your-website" value="https://example.com/" autocomplete="off" tabindex="-1" size="40" class="wpcf7-form-control wpcf7-text wpcf7-validates-as-required" aria-required="true" aria-invalid="false" required />';
	$html .= '</span>';
	$html .= '<label>';
	$html .= '</p>';
	$html .= '<style>.ssp-user{position:absolute;top:0;left:0;width:0;height:0;opacity:0;z-index:-1}</style>';
	return $html.$form;
}
add_filter( 'wpcf7_form_elements', 'ssp_cf7_add_honeypot', 10, 1 );

function ssp_cf7_verify_honeypot( $spam ) {
	if ( $spam ) {
		return $spam;
	}
	if ( $_POST['your-website'] != 'https://example.com/' ) {
		return true;
	}
	return $spam;
}
add_filter( 'wpcf7_spam', 'ssp_cf7_verify_honeypot', 10, 1 );

// add honeypot to bbPress
function ssp_bbp_add_honeypot() {
	$html  = '';
	$html .= '<p class="ssp-user">';
	$html .= '<label for="bbp_your-website">' . __( 'Your Website:', 'stop-spammers-premium' ) . '</label><br />';
	$html .= '<input type="text" value="https://example.com/" autocomplete="off" tabindex="-1" size="40" name="bbp_your-website" id="bbp_your-website" required />';
	$html .= '</p>';
	$html .= '<style>.ssp-user{position:absolute;top:0;left:0;width:0;height:0;opacity:0;z-index:-1}</style>';
	echo $html;
}
add_action( 'bbp_theme_before_reply_form_submit_wrapper', 'ssp_bbp_add_honeypot' );
add_action( 'bbp_theme_before_topic_form_submit_wrapper', 'ssp_bbp_add_honeypot' );

function ssp_bbp_verify_honeypot() {
	if ( $_POST['bbp_your-website'] != 'https://example.com/' ) {
		bbp_add_error( 'bbp_throw_error', __( "<strong>ERROR</strong>: Something went wrong!", 'stop-spammers-premium' ) );
	}
}
add_action( 'bbp_new_reply_pre_extras', 'ssp_bbp_verify_honeypot' );
add_action( 'bbp_new_topic_pre_extras', 'ssp_bbp_verify_honeypot' );

// add honeypot to Elementor form
function ssp_elementor_add_honeypot( $content, $widget ) {
	if ( 'form' === $widget->get_name() ) {
		$html    = '';
		$html   .= '<div class="elementor-field-type-text">';
		$html   .= '<input size="40" type="text" value="https://example.com/" autocomplete="off" tabindex="-1" name="form_fields[your-website]" id="form-field-your-website" class="elementor-field elementor-size-sm" />';
		$html   .= '</div>';
		$html   .= '<style>#form-field-your-website{position:absolute;top:0;left:0;width:0;height:0;opacity:0;z-index:-1}</style>';
		$content = str_replace( '<div class="elementor-field-group', $html . '<div class="elementor-field-group', $content );
		return $content;
	}
	return $content;
}
add_action( 'elementor/widget/render_content', 'ssp_elementor_add_honeypot', 10, 2 );

function ssp_elementor_verify_honeypot( $record, $ajax_handler ) {
	if ( $_POST['form_fields']['your-website'] != 'https://example.com/' ) {
		$ajax_handler->add_error( 'your-website', __( 'Something went wrong!', 'stop-spammers-premium' ) );
	}
}
add_action( 'elementor_pro/forms/validation', 'ssp_elementor_verify_honeypot', 10, 2 );

// add honeypot to Divi contact form and opt-in
function ssp_et_add_honeypot( $output, $render_slug, $module ) {
	if ( isset( $_POST['et_pb_contact_your_website'] ) and $_POST['et_pb_contact_your_website'] == 'https://example.com/' ) {
		unset( $_POST['et_pb_contact_your_website'] );
		$post_last_key = array_key_last( $_POST );
		$form_json	   = json_decode( stripslashes( $_POST[$post_last_key] ) );
		array_pop( $form_json );
		$_POST[$post_last_key] = json_encode( $form_json );
	}
	$html = '';
	if ( $render_slug == 'et_pb_contact_form' ) {
		$html  .= '<p class="et_pb_contact_field et_pb_contact_your_website">';
		$html  .= '<label for="et_pb_contact_your_website" class="et_pb_contact_form_label">' . __( 'Your Website', 'stop-spammers-premium' ) . '</label>';
		$html  .= '<input type="text" name="et_pb_contact_your_website" id="et_pb_contact_your_website" placeholder="' . __( 'Your Website', 'stop-spammers-premium' ) . '" value="https://example.com/" autocomplete="off" tabindex="-1" required />';
		$html  .= '</p>';
		$html  .= '<style>.et_pb_contact_your_website{position:absolute;top:0;left:0;width:0;height:0;opacity:0;z-index:-1}</style>';
		$html  .= '<input type="hidden" value="et_contact_proccess" name="et_pb_contactform_submit';
		$output = str_replace( '<input type="hidden" value="et_contact_proccess" name="et_pb_contactform_submit', $html, $output );
	} else if ( $render_slug == 'et_pb_signup' ) {
		$html   = '';
		$html  .= '<p class="et_pb_signup_custom_field et_pb_signup_your_website et_pb_newsletter_field et_pb_contact_field_last et_pb_contact_field_last_tablet et_pb_contact_field_last_phone">';
		$html  .= '<label for="et_pb_signup_your_website" class="et_pb_contact_form_label">' . __( 'Your Website', 'stop-spammers-premium' ) . '</label>';
		$html  .= '<input type="text" class="input" id="et_pb_signup_your_website" placeholder="' . __( 'Your Website', 'stop-spammers-premium' ) . '" value="https://example.com/" autocomplete="off" tabindex="-1" data-original_id="your-website" required />';
		$html  .= '</p>';
		$html  .= '<style>.et_pb_signup_your_website{position:absolute;top:0;left:0;width:0;height:0;opacity:0;z-index:-1}</style>';
		$html  .= '<p class="et_pb_newsletter_button_wrap">';
		$output = str_replace( '<p class="et_pb_newsletter_button_wrap">', $html, $output );
	}
	return $output;
}
add_filter( 'et_module_shortcode_output', 'ssp_et_add_honeypot', 20, 3 );

function ssp_divi_email_optin_verify_honeypot() {
	if ( isset( $_POST['et_custom_fields']['your-website'] ) and $_POST['et_custom_fields']['your-website'] != 'https://example.com/' ) { 
		echo '{"error":"Subscription Error: An error occurred, please try later."}';
		exit;
	} else if ( isset( $_POST['et_custom_fields']['your-website'] ) and $_POST['et_custom_fields']['your-website'] == 'https://example.com/' ) { 
		unset( $_POST['et_custom_fields']['your-website'] );
	}
}
add_action( 'admin_init', 'ssp_divi_email_optin_verify_honeypot' );

// enable firewall
function ssp_enable_firewall() {
	if ( empty( $_POST['ss_firewall_setting_placeholder'] ) || 'ss_firewall_setting' != $_POST['ss_firewall_setting_placeholder'] )
		return;
	if ( !wp_verify_nonce( $_POST['ssp_login_type_nonce'], 'ssp_login_type_nonce' ) )
		return;
	if ( !current_user_can( 'manage_options' ) )
		return;
	if ( isset( $_POST['ss_firewall_setting'] ) and $_POST['ss_firewall_setting'] == 'yes' ) {
		update_option( 'ssp_enable_firewall', 'yes' );
		add_action( 'admin_notices', 'ssp_admin_notice_success' );
		$insertion = array(
			'<IfModule mod_headers.c>',
			'Header set X-XSS-Protection "1; mode=block"',
			'Header always append X-Frame-Options SAMEORIGIN',
			'Header set X-Content-Type-Options nosniff',
			'</IfModule>',
			'ServerSignature Off',
			'Options -Indexes',
			'RewriteEngine On',
			'RewriteBase /',
			'<IfModule mod_rewrite.c>',
			'RewriteCond %{QUERY_STRING} ([a-z0-9]{2000,}) [NC,OR]',
			'RewriteCond %{QUERY_STRING} (/|%2f)(:|%3a)(/|%2f) [NC,OR]',
			'RewriteCond %{QUERY_STRING} (/|%2f)(\*|%2a)(\*|%2a)(/|%2f) [NC,OR]',
			'RewriteCond %{QUERY_STRING} (~|`|<|>|\^|\|\\|0x00|%00|%0d%0a) [NC,OR]',
			'RewriteCond %{QUERY_STRING} (cmd|command)(=|%3d)(chdir|mkdir)(.*)(x20) [NC,OR]',
			'RewriteCond %{QUERY_STRING} (fck|ckfinder|fullclick|ckfinder|fckeditor) [NC,OR]',
			'RewriteCond %{QUERY_STRING} (/|%2f)((wp-)?config)((\.|%2e)inc)?((\.|%2e)php) [NC,OR]',
			'RewriteCond %{QUERY_STRING} (thumbs?(_editor|open)?|tim(thumbs?)?)((\.|%2e)php) [NC,OR]',
			'RewriteCond %{QUERY_STRING} (absolute_|base|root_)(dir|path)(=|%3d)(ftp|https?) [NC,OR]',
			'RewriteCond %{QUERY_STRING} (localhost|loopback|127(\.|%2e)0(\.|%2e)0(\.|%2e)1) [NC,OR]',
			'RewriteCond %{QUERY_STRING} (\.|20)(get|the)(_|%5f)(permalink|posts_page_url)(\(|%28) [NC,OR]',
			'RewriteCond %{QUERY_STRING} (s)?(ftp|http|inurl|php)(s)?(:(/|%2f|%u2215)(/|%2f|%u2215)) [NC,OR]',
			'RewriteCond %{QUERY_STRING} (globals|mosconfig([a-z_]{1,22})|request)(=|\[|%[a-z0-9]{0,2}) [NC,OR]',
			'RewriteCond %{QUERY_STRING} ((boot|win)((\.|%2e)ini)|etc(/|%2f)passwd|self(/|%2f)environ) [NC,OR]',
			'RewriteCond %{QUERY_STRING} (((/|%2f){3,3})|((\.|%2e){3,3})|((\.|%2e){2,2})(/|%2f|%u2215)) [NC,OR]',
			'RewriteCond %{QUERY_STRING} (benchmark|char|exec|fopen|function|html)(.*)(\(|%28)(.*)(\)|%29) [NC,OR]',
			'RewriteCond %{QUERY_STRING} (php)([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}) [NC,OR]',
			'RewriteCond %{QUERY_STRING} (e|%65|%45)(v|%76|%56)(a|%61|%31)(l|%6c|%4c)(.*)(\(|%28)(.*)(\)|%29) [NC,OR]',
			'RewriteCond %{QUERY_STRING} (/|%2f)(=|%3d|$&|_mm|cgi(\.|-)|inurl(:|%3a)(/|%2f)|(mod|path)(=|%3d)(\.|%2e)) [NC,OR]',
			'RewriteCond %{QUERY_STRING} (<|%3c)(.*)(e|%65|%45)(m|%6d|%4d)(b|%62|%42)(e|%65|%45)(d|%64|%44)(.*)(>|%3e) [NC,OR]',
			'RewriteCond %{QUERY_STRING} (<|%3c)(.*)(i|%69|%49)(f|%66|%46)(r|%72|%52)(a|%61|%41)(m|%6d|%4d)(e|%65|%45)(.*)(>|%3e) [NC,OR]',
			'RewriteCond %{QUERY_STRING} (<|%3c)(.*)(o|%4f|%6f)(b|%62|%42)(j|%4a|%6a)(e|%65|%45)(c|%63|%43)(t|%74|%54)(.*)(>|%3e) [NC,OR]',
			'RewriteCond %{QUERY_STRING} (<|%3c)(.*)(s|%73|%53)(c|%63|%43)(r|%72|%52)(i|%69|%49)(p|%70|%50)(t|%74|%54)(.*)(>|%3e) [NC,OR]',
			'RewriteCond %{QUERY_STRING} (\+|%2b|%20)(d|%64|%44)(e|%65|%45)(l|%6c|%4c)(e|%65|%45)(t|%74|%54)(e|%65|%45)(\+|%2b|%20) [NC,OR]',
			'RewriteCond %{QUERY_STRING} (\+|%2b|%20)(i|%69|%49)(n|%6e|%4e)(s|%73|%53)(e|%65|%45)(r|%72|%52)(t|%74|%54)(\+|%2b|%20) [NC,OR]',
			'RewriteCond %{QUERY_STRING} (\+|%2b|%20)(s|%73|%53)(e|%65|%45)(l|%6c|%4c)(e|%65|%45)(c|%63|%43)(t|%74|%54)(\+|%2b|%20) [NC,OR]',
			'RewriteCond %{QUERY_STRING} (\+|%2b|%20)(u|%75|%55)(p|%70|%50)(d|%64|%44)(a|%61|%41)(t|%74|%54)(e|%65|%45)(\+|%2b|%20) [NC,OR]',
			'RewriteCond %{QUERY_STRING} (\\x00|(\"|%22|\'|%27)?0(\"|%22|\'|%27)?(=|%3d)(\"|%22|\'|%27)?0|cast(\(|%28)0x|or%201(=|%3d)1) [NC,OR]',
			'RewriteCond %{QUERY_STRING} (g|%67|%47)(l|%6c|%4c)(o|%6f|%4f)(b|%62|%42)(a|%61|%41)(l|%6c|%4c)(s|%73|%53)(=|[|%[0-9A-Z]{0,2}) [NC,OR]',
			'RewriteCond %{QUERY_STRING} (_|%5f)(r|%72|%52)(e|%65|%45)(q|%71|%51)(u|%75|%55)(e|%65|%45)(s|%73|%53)(t|%74|%54)(=|[|%[0-9A-Z]{0,2}) [NC,OR]',
			'RewriteCond %{QUERY_STRING} (j|%6a|%4a)(a|%61|%41)(v|%76|%56)(a|%61|%31)(s|%73|%53)(c|%63|%43)(r|%72|%52)(i|%69|%49)(p|%70|%50)(t|%74|%54)(:|%3a)(.*)(;|%3b|\)|%29) [NC,OR]',
			'RewriteCond %{QUERY_STRING} (b|%62|%42)(a|%61|%41)(s|%73|%53)(e|%65|%45)(6|%36)(4|%34)(_|%5f)(e|%65|%45|d|%64|%44)(e|%65|%45|n|%6e|%4e)(c|%63|%43)(o|%6f|%4f)(d|%64|%44)(e|%65|%45)(.*)(\()(.*)(\)) [NC,OR]',
			'RewriteCond %{QUERY_STRING} (allow_url_(fopen|include)|auto_prepend_file|blexbot|browsersploit|(c99|php)shell|curltest|disable_functions?|document_root|elastix|encodeuricom|exec|exploit|fclose|fgets|fputs|fsbuff|fsockopen|gethostbyname|grablogin|hmei7|input_file|load_file|null|open_basedir|outfile|passthru|popen|proc_open|quickbrute|remoteview|root_path|safe_mode|shell_exec|site((.){0,2})copier|sux0r|trojan|wget|xertive) [NC,OR]',
			'RewriteCond %{QUERY_STRING} (;|<|>|\'|\"|\)|%0a|%0d|%22|%27|%3c|%3e|%00)(.*)(/\*|alter|base64|benchmark|cast|char|concat|convert|create|encode|declare|delete|drop|insert|md5|order|request|script|select|set|union|update) [NC,OR]',
			'RewriteCond %{QUERY_STRING} ((\+|%2b)(concat|delete|get|select|union)(\+|%2b)) [NC,OR]',
			'RewriteCond %{QUERY_STRING} (union)(.*)(select)(.*)(\(|%28) [NC,OR]',
			'RewriteCond %{QUERY_STRING} (concat)(.*)(\(|%28) [NC]',
			'RewriteRule .* - [F,L]',
			'</IfModule>',
			'<IfModule mod_rewrite.c>',
			'RewriteCond %{REQUEST_URI} ([a-z0-9]{2000,}) [NC,OR]',
			'RewriteCond %{REQUEST_URI} (/)(\*|\"|\'|\.|,|&|&amp;?)/?$ [NC,OR]',
			'RewriteCond %{REQUEST_URI} (\.)(php)(\()?([0-9]+)(\))?(/)?$ [NC,OR]',
			'RewriteCond %{REQUEST_URI} (/)(vbulletin|boards|vbforum)(/)? [NC,OR]',
			'RewriteCond %{REQUEST_URI} (\^|~|`|<|>|,|%|\\|\{|\}|\[|\]|\|) [NC,OR]',
			'RewriteCond %{REQUEST_URI} (\.(s?ftp-?)config|(s?ftp-?)config\.) [NC,OR]',
			'RewriteCond %{REQUEST_URI} (\{0\}|\"?0\"?=\"?0|\(/\(|\.\.\.|\+\+\+|\\\") [NC,OR]',
			'RewriteCond %{REQUEST_URI} (thumbs?(_editor|open)?|tim(thumbs?)?)(\.php) [NC,OR]',
			'RewriteCond %{REQUEST_URI} (/)(fck|ckfinder|fullclick|ckfinder|fckeditor) [NC,OR]',
			'RewriteCond %{REQUEST_URI} (\.|20)(get|the)(_)(permalink|posts_page_url)(\() [NC,OR]',
			'RewriteCond %{REQUEST_URI} (///|\?\?|/&&|/\*(.*)\*/|/:/|\\\\|0x00|%00|%0d%0a) [NC,OR]',
			'RewriteCond %{REQUEST_URI} (/%7e)(root|ftp|bin|nobody|named|guest|logs|sshd)(/) [NC,OR]',
			'RewriteCond %{REQUEST_URI} (/)(etc|var)(/)(hidden|secret|shadow|ninja|passwd|tmp)(/)?$ [NC,OR]',
			'RewriteCond %{REQUEST_URI} (s)?(ftp|http|inurl|php)(s)?(:(/|%2f|%u2215)(/|%2f|%u2215)) [NC,OR]',
			'RewriteCond %{REQUEST_URI} (/)(=|\$&?|&?(pws|rk)=0|_mm|_vti_|cgi(\.|-)?|(=|/|;|,)nt\.) [NC,OR]',
			'RewriteCond %{REQUEST_URI} (\.)(conf(ig)?|ds_store|htaccess|htpasswd|init?|mysql-select-db)(/)?$ [NC,OR]',
			'RewriteCond %{REQUEST_URI} (/)(bin)(/)(cc|chmod|chsh|cpp|echo|id|kill|mail|nasm|perl|ping|ps|python|tclsh)(/)?$ [NC,OR]',
			'RewriteCond %{REQUEST_URI} (/)(::[0-9999]|%3a%3a[0-9999]|127\.0\.0\.1|localhost|loopback|makefile|pingserver|wwwroot)(/)? [NC,OR]',
			'RewriteCond %{REQUEST_URI} (\(null\)|\{\$itemURL\}|cAsT\(0x|echo(.*)kae|etc/passwd|eval\(|self/environ|\+union\+all\+select) [NC,OR]',
			'RewriteCond %{REQUEST_URI} (/)(awstats|(c99|php|web)shell|document_root|error_log|listinfo|muieblack|remoteview|site((.){0,2})copier|sqlpatch|sux0r) [NC,OR]',
			'RewriteCond %{REQUEST_URI} (/)((php|web)?shell|conf(ig)?|crossdomain|fileditor|locus7|nstview|php(get|remoteview|writer)|r57|remview|sshphp|storm7|webadmin)(.*)(\.|\() [NC,OR]',
			'RewriteCond %{REQUEST_URI} (/)(author-panel|bitrix|class|database|(db|mysql)-?admin|filemanager|htdocs|httpdocs|https?|mailman|mailto|msoffice|mysql|_?php-?my-?admin(.*)|sql|system|tmp|undefined|usage|var|vhosts|webmaster|www)(/) [NC,OR]',
			'RewriteCond %{REQUEST_URI} (base64_(en|de)code|benchmark|child_terminate|e?chr|eval|exec|function|fwrite|(f|p)open|html|leak|passthru|p?fsockopen|phpinfo|posix_(kill|mkfifo|setpgid|setsid|setuid)|proc_(close|get_status|nice|open|terminate)|(shell_)?exec|system)(.*)(\()(.*)(\)) [NC,OR]',
			'RewriteCond %{REQUEST_URI} (\.)(7z|ab4|afm|aspx?|bash|ba?k?|bz2|cfg|cfml?|cgi|conf(ig)?|ctl|dat|db|dll|eml|et2|exe|fec|fla|hg|inc|ini|inv|jsp|log|lqd|mbf|mdb|mmw|mny|old|one|out|passwd|pdb|pl|psd|pst|ptdb|pwd|py|qbb|qdf|rar|rdf|sdb|sql|sh|soa|swf|swl|swp|stx|tar|tax|tgz|tls|tmd|wow|zlib)$ [NC,OR]',
			'RewriteCond %{REQUEST_URI} (/)(^$|00.temp00|0day|3xp|70bex?|admin_events|bkht|(php|web)?shell|configbak|curltest|db|dompdf|filenetworks|hmei7|index\.php/index\.php/index|jahat|kcrew|keywordspy|mobiquo|mysql|nessus|php-?info|racrew|sql|ucp|webconfig|(wp-)?conf(ig)?(uration)?|xertive)(\.php) [NC]',
			'RewriteRule .* - [F,L]',
			'</IfModule>',
			'<IfModule mod_rewrite.c>',
			'RewriteCond %{HTTP_USER_AGENT} ([a-z0-9]{2000,}) [NC,OR]',
			'RewriteCond %{HTTP_USER_AGENT} (&lt;|%0a|%0d|%27|%3c|%3e|%00|0x00) [NC,OR]',
			'RewriteCond %{HTTP_USER_AGENT} ((c99|php|web)shell|remoteview|site((.){0,2})copier) [NC,OR]',
			'RewriteCond %{HTTP_USER_AGENT} (base64_decode|bin/bash|disconnect|eval|lwp-download|unserialize|\\\x22) [NC,OR]',
			'RewriteCond %{HTTP_USER_AGENT} (360Spider|acapbot|acoonbot|ahrefs|alexibot|asterias|attackbot|backdorbot|becomebot|binlar|blackwidow|blekkobot|blexbot|blowfish|bullseye|bunnys|butterfly|careerbot|casper|checkpriv|cheesebot|cherrypick|chinaclaw|choppy|clshttp|cmsworld|copernic|copyrightcheck|cosmos|crescent|cy_cho|datacha|demon|diavol|discobot|dittospyder|dotbot|dotnetdotcom|dumbot|emailcollector|emailsiphon|emailwolf|exabot|extract|eyenetie|feedfinder|flaming|flashget|flicky|foobot|g00g1e|getright|gigabot|go-ahead-got|gozilla|grabnet|grafula|harvest|heritrix|httrack|icarus6j|jetbot|jetcar|jikespider|kmccrew|leechftp|libweb|linkextractor|linkscan|linkwalker|loader|miner|majestic|mechanize|mj12bot|morfeus|moveoverbot|netmechanic|netspider|nicerspro|nikto|ninja|nutch|octopus|pagegrabber|planetwork|postrank|proximic|purebot|pycurl|python|queryn|queryseeker|radian6|radiation|realdownload|rogerbot|scooter|seekerspider|semalt|seznambot|siclab|sindice|sistrix|sitebot|siteexplorer|sitesnagger|skygrid|smartdownload|snoopy|sosospider|spankbot|spbot|sqlmap|stackrambler|stripper|sucker|surftbot|sux0r|suzukacz|suzuran|takeout|teleport|telesoft|true_robots|turingos|turnit|vampire|vikspider|voideye|webleacher|webreaper|webstripper|webvac|webviewer|webwhacker|winhttp|wwwoffle|woxbot|xaldon|xxxyy|yamanalab|yioopbot|youda|zeus|zmeu|zune|zyborg) [NC]',
			'RewriteRule .* - [F,L]',
			'</IfModule>',
			'<IfModule mod_rewrite.c>',
			'RewriteCond %{REMOTE_HOST} (163data|amazonaws|colocrossing|crimea|g00g1e|justhost|kanagawa|loopia|masterhost|onlinehome|poneytel|sprintdatacenter|reverse.softlayer|safenet|ttnet|woodpecker|wowrack) [NC]',
			'RewriteRule .* - [F,L]',
			'</IfModule>',
			'<IfModule mod_rewrite.c>',
			'RewriteCond %{HTTP_REFERER} (semalt.com|todaperfeita) [NC,OR]',
			'RewriteCond %{HTTP_REFERER} (ambien|blue\spill|cialis|cocaine|ejaculat|erectile|erections|hoodia|huronriveracres|impotence|levitra|libido|lipitor|phentermin|pro[sz]ac|sandyauer|tramadol|troyhamby|ultram|unicauca|valium|viagra|vicodin|xanax|ypxaieo) [NC]',
			'RewriteRule .* - [F,L]',
			'</IfModule>',
			'<IfModule mod_rewrite.c>',
			'RewriteCond %{REQUEST_METHOD} ^(connect|debug|delete|move|put|trace|track) [NC]',
			'RewriteRule .* - [F,L]',
			'</IfModule>',
		);
		$htaccess = ABSPATH . '.htaccess';
		if ( function_exists( 'insert_with_markers') ) {
			return insert_with_markers( $htaccess, 'Stop Spammers Premium', ( array ) $insertion );
		}
	}
	else {
		update_option( 'ssp_enable_firewall', 'no' );
		add_action( 'admin_notices', 'ssp_admin_notice_success' );
		$htaccess = ABSPATH . '.htaccess';
		return insert_with_markers( $htaccess, 'Stop Spammers Premium', '' );
	}
}
add_action( 'admin_init', 'ssp_enable_firewall' );

// Notification Control settings update
function ssp_update_notification_control() {
	if ( empty( $_POST['ss_notification_control_setting'] ) || 'ss_notification_control_update' != $_POST['ss_notification_control_setting'] )
		return;
	if ( !wp_verify_nonce( $_POST['ssp_login_type_nonce'], 'ssp_login_type_nonce' ) )
		return;
	if ( !current_user_can( 'manage_options' ) )
		return;
	if ( $_POST['submit'] == "Reset" ) {
		if ( $_POST['ss_reset_hidden_notice'] == 'all') {
			global $wpdb;
			delete_user_meta( get_current_user_id(), 'ss_notice_preference' );
			$wpdb->query( "DELETE FROM $wpdb->usermeta WHERE meta_key = 'ss_notice_preference'" );
		} else {
			delete_user_meta( get_current_user_id(), 'ss_notice_preference' );
		}
		return;
	}
	if ( isset( $_POST['ss_hide_admin_notices'] ) and $_POST['ss_hide_admin_notices'] == 'yes' )
		update_option( 'ss_hide_admin_notices', 'yes' );
	else
		update_option( 'ss_hide_admin_notices', 'no' );
	if ( ! isset( $_POST['ss_disable_admin_emails'] ) ) {
		update_option( 'ss_disable_admin_emails', 'no' );
		update_option( 'ss_disable_admin_emails_update', 'no' );
		update_option( 'ss_disable_admin_emails_comment', 'no' );
		update_option( 'ss_disable_admin_emails_password_reset', 'no' );
		update_option( 'ss_disable_admin_emails_new_user', 'no' );
	} else {
		if ( isset( $_POST['ss_disable_admin_emails_update'] ) and $_POST['ss_disable_admin_emails_update'] == 'yes' )
			update_option( 'ss_disable_admin_emails_update', 'yes' );
		else
			update_option( 'ss_disable_admin_emails_update', 'no' );
		if ( isset( $_POST['ss_disable_admin_emails_comment'] ) and $_POST['ss_disable_admin_emails_comment'] == 'yes' )
			update_option( 'ss_disable_admin_emails_comment', 'yes' );
		else
			update_option( 'ss_disable_admin_emails_comment', 'no' );
		if ( isset( $_POST['ss_disable_admin_emails_password_reset'] ) and $_POST['ss_disable_admin_emails_password_reset'] == 'yes' )
			update_option( 'ss_disable_admin_emails_password_reset', 'yes' );
		else
			update_option( 'ss_disable_admin_emails_password_reset', 'no' );
		if ( isset( $_POST['ss_disable_admin_emails_new_user'] ) and $_POST['ss_disable_admin_emails_new_user'] == 'yes' )
			update_option( 'ss_disable_admin_emails_new_user', 'yes' );
		else
			update_option( 'ss_disable_admin_emails_new_user', 'no' );
		if ( ( isset( $_POST['ss_disable_admin_emails_update'] ) and $_POST['ss_disable_admin_emails_update'] == 'yes' )
			|| ( isset( $_POST['ss_disable_admin_emails_comment'] ) and $_POST['ss_disable_admin_emails_comment'] == 'yes' )
			|| ( isset( $_POST['ss_disable_admin_emails_password_reset'] ) and $_POST['ss_disable_admin_emails_password_reset'] == 'yes' )
			|| ( isset( $_POST['ss_disable_admin_emails_new_user'] ) and $_POST['ss_disable_admin_emails_new_user'] == 'yes' )
		)
			update_option( 'ss_disable_admin_emails', 'yes' );
		else
			update_option( 'ss_disable_admin_emails', 'no' );
	}
	if ( isset( $_POST['ss_disable_core_nudge'] ) and $_POST['ss_disable_core_nudge'] == 'yes' )
		update_option( 'ss_disable_core_nudge', 'yes' );
	else
		update_option( 'ss_disable_core_nudge', 'no' );
	if ( isset( $_POST['ss_disable_theme_nudge'] ) and $_POST['ss_disable_theme_nudge'] == 'yes' )
		update_option( 'ss_disable_theme_nudge', 'yes' );
	else
		update_option( 'ss_disable_theme_nudge', 'no' );
	if ( isset( $_POST['ss_disable_plugin_nudge'] ) and $_POST['ss_disable_plugin_nudge'] == 'yes' )
		update_option( 'ss_disable_plugin_nudge', 'yes' );
	else
		update_option( 'ss_disable_plugin_nudge', 'no' );
}
add_action( 'admin_init', 'ssp_update_notification_control' );

// Notification Control: for core/plugin/theme updates
if ( get_option( 'ss_disable_admin_emails_update', 'no' ) === 'yes' ) {
	add_filter( 'auto_core_update_send_email', '__return_false' );
	add_filter( 'auto_theme_update_send_email', '__return_false' );
	add_filter( 'auto_plugin_update_send_email', '__return_false' );
}

// Notification Control: for comments
if ( get_option( 'ss_disable_admin_emails_comment', 'no' ) === 'yes' ) {
	function wp_notify_postauthor( $comment_id, $deprecated = null ) {}
	function wp_notify_moderator( $comment_id ) {}
}

// Notification Control: for reset password
if ( get_option( 'ss_disable_admin_emails_password_reset', 'no' ) === 'yes' ) {
	function wp_password_change_notification( $user ) {}
}

// Notification Control: for new user registration
if ( get_option( 'ss_disable_admin_emails_new_user', 'no' ) === 'yes' ) {
	remove_action( 'register_new_user', 'wp_send_new_user_notifications' );
	add_action( 'register_new_user', function( $user_id, $notify = 'user' ) {
		wp_send_new_user_notifications( $user_id, $notify );
	} );
}

// Notification Control: for native nudges
function ssp_remove_core_updates() {
	global $wp_version;
	return( object ) array( 'last_checked' => time(), 'version_checked' => $wp_version );
}
if ( get_option( 'ss_disable_core_nudge', 'no' ) === 'yes' ) {
	add_filter( 'pre_site_transient_update_core', 'ssp_remove_core_updates' );
}
if ( get_option( 'ss_disable_theme_nudge', 'no' ) === 'yes' ) {
	add_filter( 'pre_site_transient_update_themes', 'ssp_remove_core_updates' );
}
if ( get_option( 'ss_disable_plugin_nudge', 'no' ) === 'yes' ) {
	add_filter( 'pre_site_transient_update_plugins', 'ssp_remove_core_updates' );
}

// enable custom login
function ssp_enable_custom_login() {
	if ( empty( $_POST['ss_login_setting_placeholder'] ) || 'ss_login_setting' != $_POST['ss_login_setting_placeholder'] )
		return;
	if ( !wp_verify_nonce( $_POST['ssp_login_type_nonce'], 'ssp_login_type_nonce' ) )
		return;
	if ( !current_user_can( 'manage_options' ) )
		return;
	if ( isset( $_POST['ss_login_setting'] ) and $_POST['ss_login_setting'] == 'yes' ) {
		update_option( 'ssp_enable_custom_login', 'yes' );
		add_action( 'admin_notices', 'ssp_admin_notice_success' );
		ssp_install_custom_login();
	} else {
		update_option( 'ssp_enable_custom_login', 'no' );
		add_action( 'admin_notices', 'ssp_admin_notice_success' );
		ssp_uninstall_custom_login();
	}
}
add_action( 'admin_init', 'ssp_enable_custom_login' );

// process to setup login type
function ssp_login_type_func() {
	if ( empty( $_POST['ssp_login_type_field'] ) || 'ssp_login_type' != $_POST['ssp_login_type_field'] )
		return;
	if ( !wp_verify_nonce( $_POST['ssp_login_type_nonce'], 'ssp_login_type_nonce' ) )
		return;
	if ( !current_user_can( 'manage_options' ) )
		return;
	if ( isset( $_POST['ssp_login_type'] ) ) {
		update_option( 'ssp_login_type', $_POST['ssp_login_type'] );
		add_action( 'admin_notices', 'ssp_admin_notice_success' );
	}
}
add_action( 'admin_init', 'ssp_login_type_func' ); 

// install default pages for custom login
function ssp_install_custom_login() {
	$pages =  array(
		'login'           => __( 'Log In', 'stop-spammers-premium' ),
		'logout'          => __( 'Log Out', 'stop-spammers-premium' ),
		'register'        => __( 'Register', 'stop-spammers-premium' ),
		'forgot-password' => __( 'Forgot Password', 'stop-spammers-premium' ),
	);
	foreach( $pages as $slug => $title ) {
		$page_id = ssp_get_page_id( $slug );
		if ( $page_id > 0 ) {
			wp_update_post( array(
				'ID'			 => $page_id,
				'post_title'     => $title,
				'post_name'      => $slug,
				'post_status'    => 'publish',
				'post_type'      => 'page',
				'post_content'   => '[ssp-login]',
				'comment_status' => 'closed',
				'ping_status'    => 'closed'
			) );
		} else {
			wp_insert_post( array(
				'post_title'     => $title,
				'post_name'      => $slug,
				'post_status'    => 'publish',
				'post_type'      => 'page',
				'post_content'   => '[ssp-login]',
				'comment_status' => 'closed',
				'ping_status'    => 'closed'
			) );
		}
	}
}

// uninstall default pages for custom login
function ssp_uninstall_custom_login() {
	$pages = array(
		'login'           => __( 'Log In', 'stop-spammers-premium' ),
		'logout'          => __( 'Log Out', 'stop-spammers-premium' ),
		'register'        => __( 'Register', 'stop-spammers-premium' ),
		'forgot-password' => __( 'Forgot Password', 'stop-spammers-premium' ),
	);
	foreach( $pages as $slug => $title ) {
		$page_id = ssp_get_page_id( $slug );
		wp_delete_post( $page_id, true );
	}	
}

function ssp_get_page_id( $slug ) {
	$page = get_page_by_path( $slug );
	if ( !isset( $page->ID ) )
		return null;
	else 
		return $page->ID;
}

add_action( 'template_redirect', function() {
	global $post;
	if ( is_page( 'logout' ) ) {
		$user = wp_get_current_user();
		wp_logout();
		if ( !empty( $_REQUEST['redirect_to'] ) ) {
			$redirect_to = $requested_redirect_to = $_REQUEST['redirect_to'];
		} else {
			$redirect_to = site_url( 'login/?loggedout=true' );
			$requested_redirect_to = '';
		}
		$redirect_to = apply_filters( 'logout_redirect', $redirect_to, $requested_redirect_to, $user );
		wp_safe_redirect( $redirect_to );
		exit;
	}
	if ( is_user_logged_in() && ( $post->post_name == 'login' or $post->post_name == 'register' or $post->post_name == 'forgot-password' ) ) {
		wp_redirect( admin_url() );
		exit;
	}
	if ( $post->post_name == 'login' )
		ssp_login();
	elseif ( $post->post_name == 'register' )
		ssp_register();
	elseif ( $post->post_name == 'forgot-password' )
		ssp_forgot_password();
} );

function ssp_forgot_password() {
	global $wpdb, $wp_hasher;
	if ( empty( $_POST ) )
		return;
	$errors = new WP_Error();
	if ( empty( $_POST['user_login'] ) ) {
		$errors->add( 'empty_username', __( '<strong>ERROR</strong>: Enter a username or e-mail address.', 'stop-spammers-premium' ) );
	} else if ( strpos( $_POST['user_login'], '@' ) ) {
		$user_data = get_user_by( 'email', trim( wp_unslash( $_POST['user_login'] ) ) );
		if ( empty( $user_data ) )
			$errors->add( 'invalid_email', __( '<strong>ERROR</strong>: There is no user registered with that email address.', 'stop-spammers-premium' ) );
	} else {
		$login = trim( $_POST['user_login'] );
		$user_data = get_user_by( 'login', $login );
	}
	do_action( 'lostpassword_post', $errors );
	if ( $errors->get_error_code() ){
		$GLOBALS['ssp_error'] = $errors;
		return;
	}
	if ( !$user_data ) {
		$errors->add( 'invalidcombo', __( '<strong>ERROR</strong>: Invalid username or email.', 'stop-spammers-premium' ) );
		$GLOBALS['ssp_error'] = $errors;
		return;
	}
	$user_login = $user_data->user_login;
	$user_email = $user_data->user_email;
	$key = get_password_reset_key( $user_data );
	if ( is_wp_error( $key ) ) {
		$GLOBALS['ssp_error'] = $key;
	}
	$message  = __( 'Someone requested that the password be reset for the following account:', 'stop-spammers-premium' ) . "\r\n\r\n";
	$message .= network_home_url( '/' ) . "\r\n\r\n";
	$message .= sprintf( __( 'Username: %s', 'stop-spammers-premium' ), $user_login ) . "\r\n\r\n";
	$message .= __( 'If this was a mistake, just ignore this email and nothing will happen.', 'stop-spammers-premium' ) . "\r\n\r\n";
	$message .= __( 'To reset your password, visit the following address:', 'stop-spammers-premium' ) . "\r\n\r\n";
	$message .= '<' . network_site_url( "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $user_login ), 'login' ) . ">\r\n";
	$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
	$title    = sprintf( __( '[%s] Password Reset', 'stop-spammers-premium' ), $blogname );
	$title    = apply_filters( 'retrieve_password_title', $title, $user_login, $user_data );
	$message  = apply_filters( 'retrieve_password_message', $message, $key, $user_login, $user_data );
	if ( $message && !wp_mail( $user_email, $title, $message ) ) {
		wp_die( __( 'The email could not be sent.', 'stop-spammers-premium' ) . "<br />\n" . __( 'Possible reason: your host may have disabled the mail() function...', 'stop-spammers-premium' ) );
		wp_redirect( home_url( '/login/?rp=link(target, link)-sent' ) );
		exit;
	}
}
add_shortcode( 'ssp-login', 'ssp_login_cb' );

function ssp_login_cb() {
	global $post;
	if ( !is_page() )
		return;
	switch ( $post->post_name ) {
		case 'login':
			ssp_login_page();
			break;
		case 'register':
			ssp_register_page();
			break;
		case 'forgot-password':
			ssp_forgot_password_page();
			break;
		default:
			break;
	}
}

function ssp_login_page() {
	include( 'templates/login.php' );
}

function ssp_show_error() {
	global $ssp_error;
	if ( isset( $ssp_error->errors ) ) {
		foreach( $ssp_error->errors as $errors ) {
			foreach( $errors as $e ) {
				echo "<div style='color:#721c24;background-color:#f8d7da;padding:.75rem 1.25rem;margin-bottom:1rem;border:1px solid #f5c6cb'>$e</div>";
			}
		}
	}
}

function ssp_register() {
	if ( !get_option( 'users_can_register' ) ) {
		$redirect_to = site_url( 'wp-login.php?registration=disabled' );
		wp_redirect( $redirect_to );
		exit;
	}	
	$user_login = '';
	$user_email = '';
	if ( !empty( $_POST ) && ( $_POST['user_url'] == 'https://example.com/' ) ) {
		$user_login = isset( $_POST['user_login'] ) ? $_POST['user_login'] : '';
		$user_email = isset( $_POST['user_email'] ) ? $_POST['user_email'] : '';
		$register_error = register_new_user( $user_login, $user_email );
		if ( !is_wp_error( $register_error ) ) {
			$redirect_to = !empty( $_POST['redirect_to'] ) ? $_POST['redirect_to'] : site_url( 'wp-login.php?checkemail=registered' );
			wp_safe_redirect( $redirect_to );
			exit;
		}
		$GLOBALS['ssp_error'] = $register_error;
	}
}

function ssp_login() {
	$secure_cookie = '';
	$interim_login = isset( $_REQUEST['interim-login'] );
	if ( !empty( $_REQUEST['redirect_to'] ) ) {
		$redirect_to = $_REQUEST['redirect_to'];
		// redirect to https if user wants SSL
		if ( $secure_cookie && false !== strpos( $redirect_to, 'wp-admin' ) )
			$redirect_to = preg_replace( '|^http://|', 'https://', $redirect_to );
	} else {
		$redirect_to = admin_url();
	}
	$reauth = empty( $_REQUEST['reauth'] ) ? false : true;
	if ( isset( $_POST['log'] ) || isset( $_GET['testcookie'] ) ) {
		$user = wp_signon( array(), $secure_cookie );
		$redirect_to = apply_filters( 'login_redirect', $redirect_to, isset( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : '', $user );
		// $user = wp_get_current_user();
		if ( !is_wp_error( $user ) && !$reauth ) {
			if ( ( empty( $redirect_to ) || $redirect_to == 'wp-admin/' || $redirect_to == admin_url() ) ) {
				// If the user doesn't belong to a blog, send them to user admin. If the user can't edit posts, send them to their profile.
				if ( is_multisite() && !get_active_blog_for_user( $user->ID ) && !is_super_admin( $user->ID ) )
					$redirect_to = user_admin_url();
				elseif ( is_multisite() && !$user->has_cap( 'read' ) )
					$redirect_to = get_dashboard_url( $user->ID );
				elseif ( !$user->has_cap( 'edit_posts' ) )
					$redirect_to = $user->has_cap( 'read' ) ? admin_url( 'profile.php' ) : home_url();
					wp_redirect( $redirect_to );
				exit;
			}
			wp_safe_redirect( $redirect_to );
			exit;
		}
		$GLOBALS['ssp_error'] = $user;
	}
}

function ssp_register_page() {
	include( 'templates/register.php' );
}

function ssp_forgot_password_page() {
	include( 'templates/forgot-password.php' );
}

function ssp_login_url( $url ) {
	if ( get_option( 'ssp_enable_custom_login', '' ) == 'yes' and !is_user_logged_in() ) {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		include( get_query_template( '404' ) );
		exit;
	}
	return $url;
}
add_filter( 'login_url', 'ssp_login_url', 10, 2 );

function ssp_logout_url( $url, $redirect ) {
	if ( get_option( 'ssp_enable_custom_login', '' ) == 'yes' )
		$url = home_url( 'logout' );
	return $url;
}
add_filter( 'logout_url', 'ssp_logout_url', 10, 2 );

// enable custom login module 
function ssp_custom_login_module() {
	if ( get_option( 'ssp_login_type', '' ) == "username" ) {
		remove_filter( 'authenticate', 'wp_authenticate_email_password', 20 );
	} else if ( get_option( 'ssp_login_type', '' ) == "email" ) {
		remove_filter( 'authenticate', 'wp_authenticate_username_password', 20 );
	}
}
add_action( 'init', 'ssp_custom_login_module' );

// add_filter( 'gettext', 'ss_login_text' );
function ss_login_text( $translating ) {
	if ( get_option( 'ssp_login_type', '' ) == "username" ) {	
		return str_ireplace( 'Username or Email Address', 'Username', $translating );
	} else if ( get_option( 'ssp_login_type', '' ) == "email" ) {
		return str_ireplace( 'Username or Email Address', 'Email Address', $translating );
	} else {
		return $translating;
	}
}

// add menu option for login/logout links
function ssp_add_nav_menu_metabox() {
	if ( get_option( 'ssp_enable_custom_login', '' ) == 'yes' ) {
		add_meta_box( 'ssp_menu_option', 'Stop Spammers', 'ssp_nav_menu_metabox', 'nav-menus', 'side', 'default' );
	}
}
add_action( 'admin_head-nav-menus.php', 'ssp_add_nav_menu_metabox' );

function ssp_nav_menu_metabox( $object ) {
	global $nav_menu_selected_id;
	$elems = array(
		'#ssp-nav-login'    => __( 'Log In', 'stop-spammers-premium' ),
		'#ssp-nav-logout'   => __( 'Log Out', 'stop-spammers-premium' ),
		'#ssp-nav-register' => __( 'Register', 'stop-spammers-premium' ),
		'#ssp-nav-loginout' => __( 'Log In', 'stop-spammers-premium' ) . '/' . __( 'Log Out', 'stop-spammers-premium' )
	);
	$temp = ( object ) array(
				'ID'			   => 1,
				'object_id'		   => 1,
				'type_label'	   => '',
				'title'			   => '',
				'url'			   => '',
				'type'			   => 'custom',
				'object'		   => 'ssp-slug',
				'db_id'			   => 0,
				'menu_item_parent' => 0,
				'post_parent'	   => 0,
				'target'		   => '',
				'attr_title'	   => '',
				'description'	   => '',
				'classes'		   => array(),
				'xfn'			   => '',
			);
	// create an array of objects that imitate post objects
	$ssp_items = array();
	$i = 0;
	foreach ( $elems as $k => $v ) {
		$ssp_items[$i] = ( object ) array();
		$ssp_items[$i]->ID				 = 1;
		$ssp_items[$i]->url 			 = esc_attr( $k );
		$ssp_items[$i]->title 			 = esc_attr( $v );
		$ssp_items[$i]->object_id		 = esc_attr( $k );
		$ssp_items[$i]->type_label 		 = "Dynamic Link";
		$ssp_items[$i]->type 			 = 'custom';
		$ssp_items[$i]->object 			 = 'ssp-slug';
		$ssp_items[$i]->db_id 			 = 0;
		$ssp_items[$i]->menu_item_parent = 0;
		$ssp_items[$i]->post_parent 	 = 0;
		$ssp_items[$i]->target 			 = '';
		$ssp_items[$i]->attr_title 		 = '';
		$ssp_items[$i]->description 	 = '';
		$ssp_items[$i]->classes 		 = array();
		$ssp_items[$i]->xfn 			 = '';
		$i++;
	}
	$walker = new Walker_Nav_Menu_Checklist( array() );
	?>
	<div id="ssp-div">
		<div id="tabs-panel-ssp-all" class="tabs-panel tabs-panel-active">
			<ul id="ssp-checklist-pop" class="categorychecklist form-no-clear" >
				<?php echo walk_nav_menu_tree( array_map( 'wp_setup_nav_menu_item', $ssp_items ), 0, ( object ) array( 'walker' => $walker ) ); ?>
			</ul>
			<p class="button-controls">
				<span class="add-to-menu">
					<input type="submit"<?php wp_nav_menu_disabled_check( $nav_menu_selected_id ); ?> class="button-secondary submit-add-to-menu right" value="<?php esc_attr_e( 'Add to Menu', 'stop-spammers-premium' ); ?>" name="ssp-menu-item" id="submit-ssp-div" />
					<span class="spinner"></span>
				</span>
			</p>
		</div>
	</div>
	<?php
}

function ssp_nav_menu_type_label( $menu_item ) {
	$elems = array( '#ssp-nav-login', '#ssp-nav-logout', '#ssp-nav-register', '#ssp-nav-loginout' );
	if ( isset( $menu_item->object, $menu_item->url ) && 'custom' == $menu_item->object && in_array( $menu_item->url, $elems ) ) {
		$menu_item->type_label = 'Dynamic Link';
	}
	return $menu_item;
}
add_filter( 'wp_setup_nav_menu_item', 'ssp_nav_menu_type_label' );

function ssp_loginout_title( $title ) {
	$titles = explode( '/', $title );
	if ( !is_user_logged_in() ) {
		return esc_html( isset( $titles[0] ) ? $titles[0]: __( 'Log In', 'stop-spammers-premium' ) );
	} else {
		return esc_html( isset($titles[1] ) ? $titles[1] : __( 'Log Out', 'stop-spammers-premium' ) );
	}
}

function ssp_setup_nav_menu_item( $item ) {
	global $pagenow;
	if ( $pagenow != 'nav-menus.php' && !defined( 'DOING_AJAX' ) && isset( $item->url ) && strstr( $item->url, '#ssp-nav' ) and get_option( 'ssp_enable_custom_login', '' ) != 'yes' ) {
		$item->_invalid = true;	
	} else if ( $pagenow != 'nav-menus.php' && !defined( 'DOING_AJAX' ) && isset( $item->url ) && strstr( $item->url, '#ssp-nav' ) != '' ) {	
		$login_url 	= get_permalink( get_page_by_path( 'login' ) );
		$logout_url = get_permalink( get_page_by_path( 'logout' ) );
		switch( $item->url ) {
			case '#ssp-nav-login':
				$item->url = get_permalink( get_page_by_path( 'login' ) );
				$item->_invalid = ( is_user_logged_in() ) ?  true : false;
				break;
			case '#ssp-nav-logout':
				$item->url = get_permalink( get_page_by_path( 'logout' ) );
				$item->_invalid = ( !is_user_logged_in() ) ?  true : false;
				break;
			case '#ssp-nav-register':
				$item->url = get_permalink( get_page_by_path( 'register' ) );
				$item->_invalid = ( is_user_logged_in() ) ?  true : false;
			break;
			default: 
			$item->url = ( is_user_logged_in() ) ? $logout_url : $login_url;
			$item->title = ssp_loginout_title( $item->title );
		}
	}
	return $item;
}
add_filter( 'wp_setup_nav_menu_item', 'ssp_setup_nav_menu_item' );

// enable limit login attempts
function ssp_limit_login_attempts() {
	if ( empty( $_POST['ss_login_setting_placeholder'] ) || 'ss_login_setting' != $_POST['ss_login_setting_placeholder'] )
		return;
	if ( !wp_verify_nonce( $_POST['ssp_login_type_nonce'], 'ssp_login_type_nonce' ) )
		return;
	if ( !current_user_can( 'manage_options' ) )
		return;

	if ( isset( $_POST['ssp_login_attempts'] ) and $_POST['ssp_login_attempts'] == 'yes' ) {
		update_option( 'ssp_login_attempts', 'yes' );
	} else {
		update_option( 'ssp_login_attempts', 'no' );
	}
	update_option( 'ssp_login_attempts_threshold', $_POST['ssp_login_attempts_threshold'] );
	update_option( 'ssp_login_attempts_duration', $_POST['ssp_login_attempts_duration'] );
	update_option( 'ssp_login_attempts_unit', $_POST['ssp_login_attempts_unit'] );
	update_option( 'ssp_login_lockout_duration', $_POST['ssp_login_lockout_duration'] );
	update_option( 'ssp_login_lockout_unit', $_POST['ssp_login_lockout_unit'] );
}
add_action( 'admin_init', 'ssp_limit_login_attempts' );

function ssp_authenticate( $user, $username, $password ) {
	$field = is_email( $username ) ? 'email' : 'login';
	$time  = time();
	if ( !$userdata = get_user_by( $field, $username ) )
		return $user;
	if ( ssp_is_user_locked( $userdata->ID ) && get_option( 'ssp_login_attempts', 'no' ) === 'yes'  ) {
		if ( $expiration = ssp_get_user_lock_expiration( $userdata->ID ) ) {
			return new WP_Error( 'locked_account', sprintf( __( '<strong>ERROR</strong>: This account has been locked because of too many failed login attempts. You may try again in %s.', 'stop-spammers-premium' ), human_time_diff( $time, $expiration ) ) );
		} else {
			return new WP_Error( 'locked_account', __( '<strong>ERROR</strong>: This account has been locked.', 'stop-spammers-premium' ) );
		}
	} elseif ( is_wp_error( $user ) && 'incorrect_password' == $user->get_error_code() && get_option( 'ssp_login_attempts', 'no') === 'yes' ) {
		ssp_add_failed_login_attempt( $userdata->ID );
		$attempts = get_user_meta( $userdata->ID, 'ssp_failed_login_attempts', true);
		if ( count( $attempts ) >= ( get_option( 'ssp_login_attempts_threshold', 5 ) * 2 ) ) {
			$lockout_expiry = '+' . get_option( 'ssp_login_lockout_duration', 24 ) . ' ' . get_option( 'ssp_login_lockout_unit', 'hour' );
			$expiration = strtotime( $lockout_expiry );
			ssp_lock_user( $userdata->ID, $expiration );
			return new WP_Error( 'locked_account', sprintf( __( '<strong>ERROR</strong>: This account has been locked because of too many failed login attempts. You may try again in %s.', 'stop-spammers-premium' ), human_time_diff( $time, $expiration ) ) );
		}
	}
	return $user;
}
add_action( 'authenticate', 'ssp_authenticate', 100, 3 );

function ssp_add_failed_login_attempt( $user_id ) {
	$new_attempts = array();
	$threshold = '-'. get_option( 'ssp_login_attempts_duration', 5 ) . ' ' . get_option( 'ssp_login_attempts_unit', 'hour' );
	$threshold_date_time = strtotime( $threshold );
	$attempts = get_user_meta( $user_id, 'ssp_failed_login_attempts', true );
	if ( !is_array( $attempts ) )
		$attempts = array();
	$attempts[] = array( 'time' => time(), 'ip' => $_SERVER['REMOTE_ADDR'] );
	foreach( $attempts as $a ) {
		if ( $threshold_date_time < $a['time'] )
			$new_attempts[] = $a;
	}
	update_user_meta( $user_id, 'ssp_failed_login_attempts', array() );
	update_user_meta( $user_id, 'ssp_failed_login_attempts', $new_attempts );
}

function ssp_is_user_locked( $user_id ) {
	if ( get_user_meta( $user_id, 'ssp_is_locked', true ) == false )
		return false;
	if ( !$expires = ssp_get_user_lock_expiration( $user_id ) )
		return true;
	$time = time();
	if ( $time > $expires ) {
		ssp_unlock_user( $user_id );
		return false;
	}
	return true;
}

function ssp_get_user_lock_expiration( $user_id ) {
	return get_user_meta( $user_id, 'ssp_lock_expiration', true );
}

function ssp_lock_user ( $user_id, $expiration ) {	
	update_user_meta( $user_id, 'ssp_is_locked', true );
	update_user_meta( $user_id, 'ssp_lock_expiration', $expiration );
	update_user_meta( $user_id, 'ssp_failed_login_attempts', array() );
}

function ssp_unlock_user( $user_id ) {
	update_user_meta( $user_id, 'ssp_is_locked', false );
	update_user_meta( $user_id, 'ssp_lock_expiration', '' );
	update_user_meta( $user_id, 'ssp_failed_login_attempts', array() );
}

// export that generates a .json file of the shop settings
function ssp_process_settings_export() {
	if ( empty( $_POST['ssp_action'] ) || 'export_settings' != $_POST['ssp_action'] )
		return;
	if ( !wp_verify_nonce( $_POST['ssp_export_nonce'], 'ssp_export_nonce' ) )
		return;
	if ( !current_user_can( 'manage_options' ) )
		return;
	$settings = get_option( 'ssp_settings' );
	$options  = ss_get_options();
	ignore_user_abort( true );
	nocache_headers();
	header( 'Content-Type: application/json; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=ssp-settings-export-' . date( 'm-d-Y H:i:s' ) . '.json' );
	header( "Expires: 0" );
	echo json_encode( $options );
	exit;
}
add_action( 'admin_init', 'ssp_process_settings_export' );

function ss_export_excel_data(){
	if ( empty( $_POST['export_log'] ) || 'export_log_data' != $_POST['export_log'] )
		return;
	if ( !wp_verify_nonce( $_POST['ssp_export_action'], 'ssp_export_action' ) )
		return;
	if ( !current_user_can( 'manage_options' ) )
		return;
		$spreadsheet = new Spreadsheet();
		$sheet = $spreadsheet->getActiveSheet();
		$sheet->setCellValue( 'A1', 'Date/Time' );
		$sheet->setCellValue( 'B1', 'Email' );
		$sheet->setCellValue( 'C1', 'IP' );
		$sheet->setCellValue( 'D1', 'Author, User/Pwd' );
		$sheet->setCellValue( 'E1', 'Script' );
		$sheet->setCellValue( 'F1', 'Reason' );
		$stats = ss_get_stats();
		extract( $stats );
		$index = 2;
		foreach ( $stats['hist'] as $key => $value ) {
		$sheet->setCellValue( 'A'.$index, $key );
		$sheet->setCellValue( 'B'.$index, $value[1] );
		$sheet->setCellValue( 'C'.$index, $value[0] );
		$sheet->setCellValue( 'D'.$index, $value[2] );
		$sheet->setCellValue( 'E'.$index, $value[3] );
		$sheet->setCellValue( 'F'.$index, $value[4] );
		$index++;
		}
		// redirect output to a client's web browser (xlsx)
		header( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
		header( 'Content-Disposition: attachment;filename="ss_premium_log_' . time() . '.xlsx"' );
		header( 'Cache-Control: max-age=0' );
		// if you're serving to IE 9, then the following may be needed
		header( 'Cache-Control: max-age=1' );
		$writer = IOFactory::createWriter( $spreadsheet, 'Xlsx' );
		$writer->save( 'php://output' );
		exit;
}
add_action( 'admin_init', 'ss_export_excel_data' );

// settings import from a json file
function ssp_process_settings_import() {
	if ( empty( $_POST['ssp_action'] ) || 'import_settings' != $_POST['ssp_action'] )
		return;
	if ( !wp_verify_nonce( $_POST['ssp_import_nonce'], 'ssp_import_nonce' ) )
		return;
	if ( !current_user_can( 'manage_options' ) )
		return;
	// $extension = end( explode( '.', $_FILES['import_file']['name'] ) );
	$extension = $_FILES['import_file']['type'] ;
	// if ( $extension != 'json' ) {
	if ( $extension != 'application/json' ) {
		wp_die( __( 'Please upload a valid .json file', 'stop-spammers-premium' ) );
	}
	$import_file = $_FILES['import_file']['tmp_name'];
	if ( empty( $import_file ) ) {
		wp_die( __( 'Please upload a file to import', 'stop-spammers-premium' ) );
	}
	// retrieve the settings from the file and convert the json object to an array
	$options = ( array ) json_decode( file_get_contents( $import_file ) );	
	ss_set_options( $options );
	add_action( 'admin_notices', 'ssp_admin_notice_success' );
	// wp_safe_redirect( admin_url( 'admin.php?page=ssp_premium' ) ); 
	// add_action( 'admin_notices', 'ssp_admin_notice_success' );
	// exit;
}
add_action( 'admin_init', 'ssp_process_settings_import' );

// settings reset from a json file
function ssp_process_settings_reset() {
	if ( empty( $_POST['ssp_action'] ) || 'reset_settings' != $_POST['ssp_action'] )
		return;
	if ( !wp_verify_nonce( $_POST['ssp_reset_nonce'], 'ssp_reset_nonce' ) )
		return;
	if ( !current_user_can( 'manage_options' ) )
		return;
	$url	 = plugin_dir_path( __FILE__ ) . '/modules/ssp-default.json'; 
	$options = ( array ) json_decode( file_get_contents( $url ) );
	ss_set_options( $options );
	add_action( 'admin_notices', 'ssp_admin_notice_success' );
}
add_action( 'admin_init', 'ssp_process_settings_reset' );

// license flow start
function ssp_license_page() {
	$license = get_option( 'ssp_license_key' );
	$status  = get_option( 'ssp_license_status' );
	?>
	<div class="wrap">
		<h2><?php _e( 'Stop Spammers Premium Plugin License Options', 'stop-spammers-premium' ); ?></h2>
		<form method="post" action="options.php">
			<?php settings_fields( 'ssp_license' ); ?>
			<table class="form-table">
				<tbody>
					<tr valign="top">
						<th scope="row" valign="top">
							<?php _e( 'License Key', 'stop-spammers-premium' ); ?>
						</th>
						<td>
							<input id="ssp_license_key" name="ssp_license_key" type="text" class="regular-text" value="<?php esc_attr_e( $license ); ?>" />
							<label class="description" for="ssp_license_key"><?php _e( 'Enter your license key', 'stop-spammers-premium' ); ?></label>
						</td>
					</tr>
					<?php if( false !== $license ) { ?>
						<tr valign="top">
							<th scope="row" valign="top">
								<?php _e( 'Activate License', 'stop-spammers-premium' ); ?>
							</th>
							<td>
								<?php if ( $status !== false && $status == 'valid' ) { ?>
									<span style="color:green"><?php _e( 'active' ); ?></span>
									<?php wp_nonce_field( 'ssp_nonce', 'ssp_nonce' ); ?>
									<input type="submit" class="button-secondary" name="ssp_license_deactivate" value="<?php _e( 'Deactivate License', 'stop-spammers-premium' ); ?>"/>
								<?php } else {
									wp_nonce_field( 'ssp_nonce', 'ssp_nonce' ); ?>
									<input type="submit" class="button-secondary" name="ssp_license_activate" value="<?php _e( 'Activate License', 'stop-spammers-premium' ); ?>"/>
								<?php } ?>
							</td>
						</tr>
					<?php } ?>
				</tbody>
			</table>
			<?php submit_button(); ?>
		</form>
	<?php
}

function ssp_register_option() {
	// creates our settings in the options table
	register_setting( 'ssp_license', 'ssp_license_key', 'ssp_sanitize_license' );
}
add_action( 'admin_init', 'ssp_register_option' );

function ssp_sanitize_license( $new ) {
	$old = get_option( 'ssp_license_key' );
	if ( $old && $old != $new ) {
		delete_option( 'ssp_license_status' ); // new license has been entered, so must reactivate
	}
	return $new;
}

// shortcodes to print the username, name, and email
function show_loggedin_function( $atts ) {
	global $current_user, $user_login;
    wp_get_current_user();
	add_filter( 'widget_text', 'do_shortcode' );
	if ( $user_login ) 
		return $current_user->display_name;
}
add_shortcode( 'show_displayname_as', 'show_loggedin_function' );

function show_fullname_function( $atts ) {
	global $current_user, $user_login;
    wp_get_current_user();
	add_filter( 'widget_text', 'do_shortcode' );
	if ( $user_login ) 
		return $current_user->user_firstname . ' ' . $current_user->user_lastname;
}
add_shortcode( 'show_fullname_as', 'show_fullname_function' );

function show_id_function( $atts ) {
	global $current_user, $user_login;
    wp_get_current_user();
	add_filter( 'widget_text', 'do_shortcode' );
	if ( $user_login ) 
		return $current_user->ID;
}
add_shortcode( 'show_id_as', 'show_id_function' );

function show_level_function( $atts ) {
	global $current_user, $user_login;
    wp_get_current_user();
	add_filter( 'widget_text', 'do_shortcode' );
	if ( $user_login ) 
		return $current_user->user_level;	
}
add_shortcode( 'show_level_as', 'show_level_function' );

function show_email_function( $atts ) {
	global $current_user, $user_login;
    wp_get_current_user();
	add_filter( 'widget_text', 'do_shortcode' );
	if ( $user_login ) 
		return $current_user->user_email;
}
add_shortcode( 'show_email_as', 'show_email_function' );

/************************************
* this illustrates how to activate
* a license key
*************************************/
function ssp_activate_license() {
	// listen for our activate button to be clicked
	if ( isset( $_POST['ssp_license_activate'] ) ) {
		// run a quick security check
		if ( !check_admin_referer( 'ssp_nonce', 'ssp_nonce' ) )
			return; // get out if we didn't click the Activate button
		// retrieve the license from the database
		$license = trim( get_option( 'ssp_license_key' ) );
		// data to send in our API request
		$api_params = array(
			'edd_action' => 'activate_license',
			'license'    => $license,
			'item_name'  => urlencode( SSP_ITEM_NAME ),
			'url'        => home_url()
		);
		// call the custom API
		$response = wp_remote_post( SSP_STORE_URL, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );
		// make sure the response came back okay
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			if ( is_wp_error( $response ) ) {
				$message = $response->get_error_message();
			} else {
				$message = __( 'An error occurred, please try again.', 'stop-spammers-premium' );
			}
		} else {
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );
			if ( false === $license_data->success ) {
				switch( $license_data->error ) {
					case 'expired' :
						$message = sprintf(
							__( 'Your license key expired on %s.', 'stop-spammers-premium' ),
							date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires, current_time( 'timestamp' ) ) )
						);
						break;
					case 'disabled' :
					case 'revoked' :
						$message = __( 'Your license key has been disabled.', 'stop-spammers-premium' );
						break;
					case 'missing' :
						$message = __( 'Invalid license.', 'stop-spammers-premium' );
						break;
					case 'invalid' :
					case 'site_inactive' :
						$message = __( 'Your license is not active for this URL.', 'stop-spammers-premium' );
						break;
					case 'item_name_mismatch' :
						$message = sprintf( __( 'This appears to be an invalid license key for %s.', 'stop-spammers-premium' ), SSP_ITEM_NAME );
						break;
					case 'no_activations_left':
						$message = __( 'Your license key has reached its activation limit.', 'stop-spammers-premium' );
						break;
					default :
						$message = __( 'An error occurred, please try again.', 'stop-spammers-premium' );
						break;
				}
			}
		}
		// check if anything passed on a message constituting a failure
		if ( !empty( $message ) ) {
			$base_url = admin_url( 'admin.php?page=' . SSP_LICENSE_PAGE );
			$redirect = add_query_arg( array( 'sl_activation' => 'false', 'message' => urlencode( $message ) ), $base_url );
			wp_redirect( $redirect );
			exit();
		}
		// $license_data->license will be either "valid" or "invalid"
		update_option( 'ssp_license_status', $license_data->license );
		wp_redirect( admin_url( 'admin.php?page=' . SSP_LICENSE_PAGE ) );
		exit();
	}
}
add_action( 'admin_init', 'ssp_activate_license' );

/***********************************************
* Illustrates how to deactivate a license key.
* This will decrease the site count
***********************************************/
function ssp_deactivate_license() {
	// listen for our activate button to be clicked
	if ( isset( $_POST['ssp_license_deactivate'] ) ) {
		// run a quick security check
		if( !check_admin_referer( 'ssp_nonce', 'ssp_nonce' ) )
			return; // get out if we didn't click the Activate button
		// retrieve the license from the database
		$license = trim( get_option( 'ssp_license_key' ) );
		// data to send in our API request
		$api_params = array(
			'edd_action' => 'deactivate_license',
			'license'    => $license,
			'item_name'  => urlencode( SSP_ITEM_NAME ), // the name of our product in EDD
			'url'        => home_url()
		);
		// call the custom API
		$response = wp_remote_post( SSP_STORE_URL, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );
		// make sure the response came back okay
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			if ( is_wp_error( $response ) ) {
				$message = $response->get_error_message();
			} else {
				$message = __( 'An error occurred, please try again.', 'stop-spammers-premium' );
			}
			$base_url = admin_url( 'admin.php?page=' . SSP_LICENSE_PAGE );
			$redirect = add_query_arg( array( 'sl_activation' => 'false', 'message' => urlencode( $message ) ), $base_url );
			wp_redirect( $redirect );
			exit();
		}
		// decode the license data
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );
		// $license_data->license will be either "deactivated" or "failed"
		if ( $license_data->license == 'deactivated' ) {
			delete_option( 'ssp_license_status' );
		}
		wp_redirect( admin_url( 'admin.php?page=' . SSP_LICENSE_PAGE ) );
		exit();
	}
}
add_action( 'admin_init', 'ssp_deactivate_license' );

/************************************
* This illustrates how to check if
* a license key is still valid
* the updater does this for you,
* so this is only needed if you
* want to do something custom
*************************************/
function ssp_check_license() {
	global $wp_version;
	$license = trim( get_option( 'ssp_license_key' ) );
	$api_params = array(
		'edd_action' => 'check_license',
		'license'    => $license,
		'item_name'  => urlencode( SSP_ITEM_NAME ),
		'url'        => home_url()
	);
	// call the custom API
	$response = wp_remote_post( SSP_STORE_URL, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );
	if ( is_wp_error( $response ) )
		return false;
	$license_data = json_decode( wp_remote_retrieve_body( $response ) );
	if ( $license_data->license == 'valid' ) {
		echo 'valid'; exit;
		// this license is still valid
	} else {
		echo 'invalid'; exit;
		// this license is no longer valid
	}
}

// catch errors from the activation method above and display it to the customer
function ssp_admin_notices() {
	if ( isset( $_GET['sl_activation'] ) && !empty( $_GET['message'] ) ) {
		switch( $_GET['sl_activation'] ) {
			case 'false':
				$message = urldecode( $_GET['message'] );
				?>
				<div class="error">
					<p><?php echo $message; ?></p>
				</div>
				<?php
				break;
			case 'true':
			default:
			?>
			<div class="success">
				<p><?php _e( 'Success', 'stop-spammers-premium' ); ?></p>
			</div>
			<?php
			// developers can put a custom success message here for when activation is successful if they way
			break;
		}
	}
}
add_action( 'admin_notices', 'ssp_admin_notices' );