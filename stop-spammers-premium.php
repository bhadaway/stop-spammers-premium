<?php
/*
Plugin Name: Stop Spammers Premium
Plugin URI: https://trumani.com/downloads/stop-spammers-premium/
Description: Add even more features to the popular Stop Spammers plugin. Import/Export settings, reset options to default, and more.
Author: Trumani
Author URI: https://trumani.com/
Version: 2020.5
License: GNU General Public License v2.0 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

$composer = __DIR__ . '/vendor/autoload.php';

if ( file_exists( $composer ) ) {
	require $composer;
}

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Checks if the Stop Spammers plugin is activated
 *
 * If the Stop Spammers plugin is not active, then don't allow the
 * activation of this plugin.
 *
 * @since 1.0.0
 */
function ssprem_activate() {
  if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
	include_once( ABSPATH . '/wp-admin/includes/plugin.php' );
  }
  if ( current_user_can( 'activate_plugins' ) && ! class_exists( 'be_module' ) ) {
	// Deactivate the plugin.
	deactivate_plugins( plugin_basename( __FILE__ ) );
	// Throw an error in the WordPress admin console.
	$error_message = '<p class="dependency">' . esc_html__( 'This plugin requires the ', 'ssprem' ) . '<a href="' . esc_url( 'https://wordpress.org/plugins/stop-spammer-registrations-plugin/' ) . '" target="_blank">Stop Spammers</a>' . esc_html__( ' plugin to be active.', 'ssprem' ) . '</p>';
	die( $error_message ); // WPCS: XSS ok.
  }
}
register_activation_hook( __FILE__, 'ssprem_activate' ); 

define( 'SSP_STORE_URL', 'https://trumani.com' ); 
define( 'SSP_ITEM_ID', 21210 ); 
define( 'SSP_ITEM_NAME', 'STOP SPAMMERS PREMIUM' ); 
define( 'SSP_LICENSE_PAGE', 'ssp_license' );

if ( !class_exists( 'EDD_SL_Plugin_Updater' ) ) {
	include( dirname( __FILE__ ) . '/EDD_SL_Plugin_Updater.php' );
}

function ssp_plugin_updater() {
	$license_key = trim( get_option( 'ssp_license_key' ) );
	$edd_updater = new EDD_SL_Plugin_Updater( SSP_STORE_URL, __FILE__,
		array(
			'version' => '2020.5',
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
		'Stop Spammers License', //page_title
		'License Key',  //menu_title
		'manage_options', //capability
		SSP_LICENSE_PAGE, //menu_slug
		'ssp_license_page' //function  
	);
	$license = get_option( 'ssp_license_key' );
	$status  = get_option( 'ssp_license_status' );
	if ( $status !== false && $status == 'valid' ) { 
		add_submenu_page( 
			'stop_spammers', //parent_slug
			'Stop Spammers Premium Features', //page_title
			'Premium Features',  //menu_title
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
		$links = array_merge( array( '<a href="' . admin_url( 'admin.php?page=ssp_license' ) . '">' . __( 'Enter License Key' ) . '</a>' ), $links );
		return $links;
	}
}

function ss_export_excel() {
	$ss_firewall_setting = '';
	if ( get_option( 'ssp_disable_firewall', '' ) == 'yes' ) {
		$ss_firewall_setting = "checked='checked'";
	}
	$ss_login_setting = '';
	if ( get_option( 'ssp_disable_default_login', '' ) == 'yes' ) {
		$ss_login_setting = "checked='checked'";
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
		<div class="metabox-holder">
			<div class="postbox">
				<h3><span><?php _e( 'Firewall Settings' ); ?></span></h3>
				<div class="inside">
					<form method="post">
						<p><input type="checkbox" name="ss_firewall_setting" value="yes" <?php echo $ss_firewall_setting; ?>><?php _e( 'Disable firewall.' ); ?></p>
						<p><input type="hidden" name="ss_firewall_setting_placeholder" value="ss_firewall_setting" /></p>
						<p>
							<?php wp_nonce_field( 'ssp_disable_firewall', 'ssp_disable_firewall' ); ?>
							<?php submit_button( __( 'Save' ), 'secondary', 'submit', false ); ?>
						</p>
					</form>
				</div>
			</div>
			<div class="postbox">
				<h3><span><?php _e( 'Login Settings' ); ?></span></h3>
				<div class="inside">
					<form method="post">
						<p><input type="checkbox" name="ss_login_setting" value="yes" <?php echo $ss_login_setting; ?>><?php _e( 'Disable wp-login.php.' ); ?></p>
						<p><input type="hidden" name="ss_login_setting_placeholder" value="ss_login_setting" /></p>
						<p>
							<?php wp_nonce_field( 'ssp_disable_default_login', 'ssp_disable_default_login' ); ?>
							<?php submit_button( __( 'Save' ), 'secondary', 'submit', false ); ?>
						</p>
					</form>
				</div>
			</div>

			<div class="postbox">
				<h3><span><?php _e( 'Allow users to log in using their username and/or email address' ); ?></span></h3>
				<div class="inside">
					<form method="post">
						<p><input type="hidden" name="ssp_login_type_field" value="ssp_login_type" /></p>
						<ul class="ss-spacer">
							<li>
								<input name="ssp_login_type" type="radio" id="ssp-login-type-default" value="default" <?php echo $ss_login_type_default; ?>>
								<label for="ssp-login-type-default"><?php _e('Username or Email');?></label>
							</li>
							<li>
								<input name="ssp_login_type" type="radio" id="ssp-login-type-username" value="username" <?php echo $ss_login_type_username; ?>>
								<label for="ssp-login-type-username"><?php _e('Username only');?></label>
							</li>
							<li>
								<input name="ssp_login_type" type="radio" id="ssp-login-type-email" value="email" <?php echo $ss_login_type_email; ?>>
								<label for="ssp-login-type-email"><?php _e('Email only');?></label>
							</li>
						</ul>
						<p>
							<?php wp_nonce_field( 'ssp_login_type_nonce', 'ssp_login_type_nonce' ); ?>
							<?php submit_button( __( 'Save' ), 'secondary', 'submit', false ); ?>
						</p>
					</form>
				</div>
			</div>

			


			<div class="postbox">
				<h3><span><?php _e( 'Export Log Settings' ); ?></span></h3>
				<div class="inside">
					<p><?php _e( 'Export the Log records to an Excel file.' ); ?></p>
					<form method="post">
						<p><input type="hidden" name="export_log" value="export_log_data" /></p>
						<p>
							<?php wp_nonce_field( 'ssp_export_action', 'ssp_export_action' ); ?>
							<?php submit_button( __( 'Export' ), 'secondary', 'submit', false ); ?>
						</p>
					</form>
				</div>
			</div>
			<div class="postbox">
				<h3><span><?php _e( 'Export Settings' ); ?></span></h3>
				<div class="inside">
					<p><?php _e( 'Export the plugin settings for this site as a .json file. This allows you to easily import the configuration into another site.' ); ?></p>
					<form method="post">
						<p><input type="hidden" name="ssp_action" value="export_settings" /></p>
						<p>
							<?php wp_nonce_field( 'ssp_export_nonce', 'ssp_export_nonce' ); ?>
							<?php submit_button( __( 'Export' ), 'secondary', 'submit', false ); ?>
						</p>
					</form>
				</div><!-- .inside -->
			</div><!-- .postbox -->
			<div class="postbox">
				<h3><span><?php _e( 'Import Settings' ); ?></span></h3>
				<div class="inside">
					<p><?php _e( 'Import the plugin settings from a .json file. This file can be obtained by exporting the settings on another site using the form above.' ); ?></p>
					<form method="post" enctype="multipart/form-data">
						<p><input type="file" name="import_file" /></p>
						<p>
							<input type="hidden" name="ssp_action" value="import_settings" />
							<?php wp_nonce_field( 'ssp_import_nonce', 'ssp_import_nonce' ); ?>
							<?php submit_button( __( 'Import' ), 'secondary', 'submit', false ); ?>
						</p>
					</form>
				</div><!-- .inside -->
			</div><!-- .postbox -->
			<div class="postbox">
				<h3><span><?php _e( 'Reset Settings' ); ?></span></h3>
				<div class="inside">
					<p><?php _e( 'Reset the plugin settings for this site. This allows you to easily reset the configuration.' ); ?></p>
					<form method="post">
						<p><input type="hidden" name="ssp_action" value="reset_settings" /></p>
						<p>
							<?php wp_nonce_field( 'ssp_reset_nonce', 'ssp_reset_nonce' ); ?>
							<?php submit_button( __( 'Reset' ), 'secondary', 'submit', false ); ?>
						</p>
					</form>
				</div><!-- .inside -->
			</div><!-- .postbox -->			
		</div><!-- .metabox-holder -->
	</div>
<?php
}

/**
 * Disable firewall
 */
function ssp_disable_firewall() {
	if ( empty( $_POST['ss_firewall_setting_placeholder'] ) || 'ss_firewall_setting' != $_POST['ss_firewall_setting_placeholder'] )
		return;
	if ( ! wp_verify_nonce( $_POST['ssp_disable_firewall'], 'ssp_disable_firewall' ) )
		return;
	if ( ! current_user_can( 'manage_options' ) )
		return;
	if ( isset( $_POST['ss_firewall_setting'] ) and $_POST['ss_firewall_setting'] == 'yes' ) {
		update_option( 'ssp_disable_firewall', 'yes' );
		$htaccess = ABSPATH . '.htaccess';
		return insert_with_markers( $htaccess, 'Stop Spammers Premium', '' );
	}
	else {
		update_option( 'ssp_disable_firewall', 'no' );
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
}
add_action( 'admin_init', 'ssp_disable_firewall' );

/**
 * Process a disable default login module
 */
function ssp_disable_default_login() {

	

	if ( empty( $_POST['ss_login_setting_placeholder'] ) || 'ss_login_setting' != $_POST['ss_login_setting_placeholder'] )
		return;
	if ( ! wp_verify_nonce( $_POST['ssp_disable_default_login'], 'ssp_disable_default_login' ) )
		return;
	if ( ! current_user_can( 'manage_options' ) )
		return;
	if ( isset( $_POST['ss_login_setting'] ) and $_POST['ss_login_setting'] == 'yes' ) {
		update_option( 'ssp_disable_default_login', 'yes' );
		ssp_install_custom_login();
	}
	else {
		update_option( 'ssp_disable_default_login', 'no' );
		ssp_uninstall_custom_login();
	}
}

add_action( 'admin_init', 'ssp_disable_default_login' );

/**
 * Process to setup login type
 */

function ssp_login_type_func() {
	
	if ( empty( $_POST['ssp_login_type_field'] ) || 'ssp_login_type' != $_POST['ssp_login_type_field'] )
		return;
	if ( ! wp_verify_nonce( $_POST['ssp_login_type_nonce'], 'ssp_login_type_nonce' ) )
		return;
	if ( ! current_user_can( 'manage_options' ) )
		return;

	if ( isset( $_POST['ssp_login_type'] ) ) {
		update_option( 'ssp_login_type', $_POST['ssp_login_type'] );
	}

}

add_action( 'admin_init', 'ssp_login_type_func' ); 



/**
 * Install default pages for custom login
 */
function ssp_install_custom_login() {
	$pages =  array(
		'login'        => __( 'Log In' ),
		'register'     => __( 'Register' ),
		'forgot-password' => __( 'Forgot Password' ),
	);
	foreach( $pages as $slug => $title ) {
		$page_id = ssp_get_page_id( $slug );
		if ( $page_id > 0 ){
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

/**
 * Uninstall default pages for custom login
 */
function ssp_uninstall_custom_login() {
	$pages = array(
		'login'        => __( 'Log In' ),
		'register'     => __( 'Register' ),
		'forgot-password' => __( 'Forgot Password' ),
	);
	foreach( $pages as $slug => $title ) {
		$page_id = ssp_get_page_id( $slug );
		wp_delete_post( $page_id, true );
	}	
}

function ssp_get_page_id( $slug ) {
	$page = get_page_by_path( $slug );
	if ( ! isset( $page->ID ) )
		return null;
	else 
		return $page->ID;
}

add_action( 'template_redirect', function() {
	global $post;
	if ( is_user_logged_in() && ( $post->post_name == 'login' or $post->post_name == 'register' or $post->post_name == 'forgot-password' ) ) {
		wp_redirect( admin_url() );
		exit;
	}
	if ( $post->post_name == 'login' )
		ssp_login();
	elseif ( $post->post_name == 'register' )
		ssp_register();
	elseif( $post->post_name == 'forgot-password' )
		ssp_forgot_password();
} );

function ssp_forgot_password() {
	global $wpdb, $wp_hasher;
	if ( empty( $_POST ) )
		return;
	$errors = new WP_Error();
	if ( empty( $_POST['user_login'] ) ) {
		$errors->add( 'empty_username', __( '<strong>ERROR</strong>: Enter a username or e-mail address.' ) );
	} else if ( strpos( $_POST['user_login'], '@' ) ) {
		$user_data = get_user_by( 'email', trim( wp_unslash( $_POST['user_login'] ) ) );
		if ( empty( $user_data ) )
			$errors->add( 'invalid_email', __( '<strong>ERROR</strong>: There is no user registered with that email address.' ) );
	} else {
		$login = trim( $_POST['user_login'] );
		$user_data = get_user_by( 'login', $login );
	}
	do_action( 'lostpassword_post', $errors );
	if ( $errors->get_error_code() ){
		$GLOBALS['ssp_error'] = $errors;
		return;
	}
	if ( ! $user_data ) {
		$errors->add( 'invalidcombo', __( '<strong>ERROR</strong>: Invalid username or e-mail.') );
		$GLOBALS['ssp_error'] = $errors;
		return;
	}
	$user_login = $user_data->user_login;
	$user_email = $user_data->user_email;
	$key = get_password_reset_key( $user_data );
	if ( is_wp_error( $key ) ) {
		$GLOBALS['ssp_error'] = $key;
	}
	$message = __( 'Someone requested that the password be reset for the following account:' ) . "\r\n\r\n";
	$message .= network_home_url( '/' ) . "\r\n\r\n";
	$message .= sprintf( __( 'Username: %s' ), $user_login ) . "\r\n\r\n";
	$message .= __( 'If this was a mistake, just ignore this email and nothing will happen.') . "\r\n\r\n";
	$message .= __( 'To reset your password, visit the following address:') . "\r\n\r\n";
	$message .= '<' . network_site_url( "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $user_login ), 'login' ) . ">\r\n";
	$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
	$title = sprintf( __( '[%s] Password Reset' ), $blogname );
	$title = apply_filters( 'retrieve_password_title', $title, $user_login, $user_data );
	$message = apply_filters( 'retrieve_password_message', $message, $key, $user_login, $user_data );
	if ( $message && ! wp_mail( $user_email, $title, $message ) )
		wp_die( __( 'The email could not be sent.' ) . "<br />\n" . __( 'Possible reason: your host may have disabled the mail() function...' ) );
	wp_redirect( home_url( '/login/?rp=link(target, link)-sent' ) );
	exit;
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
	if ( ! get_option( 'users_can_register' ) ) {
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
		if ( ! is_wp_error( $register_error ) ) {
			$redirect_to = ! empty( $_POST['redirect_to'] ) ? $_POST['redirect_to'] : site_url( 'wp-login.php?checkemail=registered' );
			wp_safe_redirect( $redirect_to );
			exit;
		}
		$GLOBALS['ssp_error'] = $register_error;
	}
}

function ssp_login() {
	$secure_cookie = '';
	$interim_login = isset( $_REQUEST['interim-login'] );
	if ( ! empty( $_REQUEST['redirect_to'] ) ) {
		$redirect_to = $_REQUEST['redirect_to'];
		// Redirect to https if user wants ssl
		if ( $secure_cookie && false !== strpos( $redirect_to, 'wp-admin' ) )
			$redirect_to = preg_replace( '|^http://|', 'https://', $redirect_to );
	} else {
		$redirect_to = admin_url();
	}
	$reauth = empty( $_REQUEST['reauth'] ) ? false : true;
	if ( isset( $_POST['log'] ) || isset( $_GET['testcookie'] ) ) {
		$user = wp_signon( array(), $secure_cookie );
		$redirect_to = apply_filters( 'login_redirect', $redirect_to, isset( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : '', $user );
		//$user = wp_get_current_user();
		if ( ! is_wp_error( $user ) && ! $reauth ) {
			if ( ( empty( $redirect_to ) || $redirect_to == 'wp-admin/' || $redirect_to == admin_url() ) ) {
				// If the user doesn't belong to a blog, send them to user admin. If the user can't edit posts, send them to their profile.
				if ( is_multisite() && ! get_active_blog_for_user( $user->ID ) && ! is_super_admin( $user->ID ) )
					$redirect_to = user_admin_url();
				elseif ( is_multisite() && ! $user->has_cap( 'read' ) )
					$redirect_to = get_dashboard_url( $user->ID );
				elseif ( ! $user->has_cap( 'edit_posts' ) )
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

function ssp_custom_login() {
	if ( get_option( 'ssp_disable_default_login', '' ) == 'yes' and !isset( $_GET['registration'] ) and ( @$_GET['action'] != 'rp' ) )
		echo header( "Location: " . home_url( 'login' ) );
}
add_action( 'login_head', 'ssp_custom_login' );

function ssp_login_url( $url ) {
	if ( get_option( 'ssp_disable_default_login', '' ) == 'yes' )
		$url = home_url( 'login' );
	return $url;
}
add_filter( 'login_url', 'ssp_login_url', 10, 2 );

/**
 * Process a settings export that generates a .json file of the shop settings
 */
function ssp_process_settings_export() {
	if ( empty( $_POST['ssp_action'] ) || 'export_settings' != $_POST['ssp_action'] )
		return;
	if ( ! wp_verify_nonce( $_POST['ssp_export_nonce'], 'ssp_export_nonce' ) )
		return;
	if ( ! current_user_can( 'manage_options' ) )
		return;
	$settings = get_option( 'ssp_settings' );
	$optins = ss_get_options();
	ignore_user_abort( true );
	nocache_headers();
	header( 'Content-Type: application/json; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=ssp-settings-export-' . date( 'm-d-Y H:i:s' ) . '.json' );
	header( "Expires: 0" );
	echo json_encode( $optins );
	exit;
}
add_action( 'admin_init', 'ssp_process_settings_export' );

function ss_export_excel_data(){
	if ( empty( $_POST['export_log'] ) || 'export_log_data' != $_POST['export_log'] )
		return;
	if ( ! wp_verify_nonce( $_POST['ssp_export_action'], 'ssp_export_action' ) )
		return;
	if ( ! current_user_can( 'manage_options' ) )
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
		// Redirect output to a client’s web browser (Xlsx)
		header( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
		header( 'Content-Disposition: attachment;filename="ss_premium_log_'.time().'.xlsx"' );
		header( 'Cache-Control: max-age=0' );
		// If you're serving to IE 9, then the following may be needed
		header( 'Cache-Control: max-age=1' );
		$writer = IOFactory::createWriter( $spreadsheet, 'Xlsx' );
		$writer->save( 'php://output' );
		exit;
}
add_action( 'admin_init', 'ss_export_excel_data' );

/**
 * Process a settings import from a json file
 */
function ssp_process_settings_import() {
	if ( empty( $_POST['ssp_action'] ) || 'import_settings' != $_POST['ssp_action'] )
		return;
	if ( ! wp_verify_nonce( $_POST['ssp_import_nonce'], 'ssp_import_nonce' ) )
		return;
	if ( ! current_user_can( 'manage_options' ) )
		return;
// $extension = end( explode( '.', $_FILES['import_file']['name'] ) );
	$extension = $_FILES['import_file']['type'] ;
// if ( $extension != 'json' ) {
	if ( $extension != 'application/json' ) {
		wp_die( __( 'Please upload a valid .json file' ) );
	}
	$import_file = $_FILES['import_file']['tmp_name'];
	if ( empty( $import_file ) ) {
		wp_die( __( 'Please upload a file to import' ) );
	}
	// Retrieve the settings from the file and convert the json object to an array.
	$options = ( array ) json_decode( file_get_contents( $import_file ) );	
	ss_set_options( $options );
	add_action( 'admin_notices', 'ssp_admin_notice__success' );
	// wp_safe_redirect( admin_url( 'admin.php?page=ssp_premium' ) ); 
	// add_action( 'admin_notices', 'ssp_admin_notice__success' );
	// exit;
}
add_action( 'admin_init', 'ssp_process_settings_import' );

/**
 * Process a settings import from a json file
 */
function ssp_process_settings_reset() {
	if ( empty( $_POST['ssp_action'] ) || 'reset_settings' != $_POST['ssp_action'] )
		return;
	if ( ! wp_verify_nonce( $_POST['ssp_reset_nonce'], 'ssp_reset_nonce' ) )
		return;
	if ( ! current_user_can( 'manage_options' ) )
		return;
	$url = plugin_dir_path( __FILE__ ) . '/modules/ssp-default.json'; 
	$options = (array) json_decode( file_get_contents( $url ) );
	ss_set_options( $options );
	add_action( 'admin_notices', 'ssp_admin_notice__success' );
}
add_action( 'admin_init', 'ssp_process_settings_reset' );

function ssp_admin_notice__success() {
	?>
	<div class="notice notice-success is-dismissible">
		<p><?php _e( 'Updates have been made!', 'stop-spammers-premium' ); ?></p>
	</div>
	<?php
}

// license flow start
function ssp_license_page() {
	$license = get_option( 'ssp_license_key' );
	$status  = get_option( 'ssp_license_status' );
	?>
	<div class="wrap">
		<h2><?php _e( 'Stop Spammers Premium Plugin License Options' ); ?></h2>
		<form method="post" action="options.php">
			<?php settings_fields( 'ssp_license' ); ?>
			<table class="form-table">
				<tbody>
					<tr valign="top">
						<th scope="row" valign="top">
							<?php _e( 'License Key' ); ?>
						</th>
						<td>
							<input id="ssp_license_key" name="ssp_license_key" type="text" class="regular-text" value="<?php esc_attr_e( $license ); ?>" />
							<label class="description" for="ssp_license_key"><?php _e( 'Enter your license key' ); ?></label>
						</td>
					</tr>
					<?php if( false !== $license ) { ?>
						<tr valign="top">
							<th scope="row" valign="top">
								<?php _e( 'Activate License' ); ?>
							</th>
							<td>
								<?php if ( $status !== false && $status == 'valid' ) { ?>
									<span style="color:green"><?php _e( 'active' ); ?></span>
									<?php wp_nonce_field( 'ssp_nonce', 'ssp_nonce' ); ?>
									<input type="submit" class="button-secondary" name="ssp_license_deactivate" value="<?php _e( 'Deactivate License' ); ?>"/>
								<?php } else {
									wp_nonce_field( 'ssp_nonce', 'ssp_nonce' ); ?>
									<input type="submit" class="button-secondary" name="ssp_license_activate" value="<?php _e( 'Activate License' ); ?>"/>
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
	if( $old && $old != $new ) {
		delete_option( 'ssp_license_status' ); // new license has been entered, so must reactivate
	}
	return $new;
}

/************************************
* this illustrates how to activate
* a license key
*************************************/

function ssp_activate_license() {
	// listen for our activate button to be clicked
	if ( isset( $_POST['ssp_license_activate'] ) ) {
		// run a quick security check
		if ( ! check_admin_referer( 'ssp_nonce', 'ssp_nonce' ) )
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
		// Call the custom API.
		$response = wp_remote_post( SSP_STORE_URL, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );
		// make sure the response came back okay
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {

			if ( is_wp_error( $response ) ) {
				$message = $response->get_error_message();
			} else {
				$message = __( 'An error occurred, please try again.' );
			}
		} else {
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );
			if ( false === $license_data->success ) {
				switch( $license_data->error ) {
					case 'expired' :
						$message = sprintf(
							__( 'Your license key expired on %s.' ),
							date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires, current_time( 'timestamp' ) ) )
						);
						break;
					case 'disabled' :
					case 'revoked' :
						$message = __( 'Your license key has been disabled.' );
						break;
					case 'missing' :
						$message = __( 'Invalid license.' );
						break;
					case 'invalid' :
					case 'site_inactive' :
						$message = __( 'Your license is not active for this URL.' );
						break;
					case 'item_name_mismatch' :
						$message = sprintf( __( 'This appears to be an invalid license key for %s.' ), SSP_ITEM_NAME );
						break;
					case 'no_activations_left':
						$message = __( 'Your license key has reached its activation limit.' );
						break;
					default :
						$message = __( 'An error occurred, please try again.' );
						break;
				}
			}
		}
		// Check if anything passed on a message constituting a failure
		if ( ! empty( $message ) ) {
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
		if( ! check_admin_referer( 'ssp_nonce', 'ssp_nonce' ) )
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
		// Call the custom API.
		$response = wp_remote_post( SSP_STORE_URL, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );
		// make sure the response came back okay
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			if ( is_wp_error( $response ) ) {
				$message = $response->get_error_message();
			} else {
				$message = __( 'An error occurred, please try again.' );
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
* this illustrates how to check if
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
		'license' => $license,
		'item_name' => urlencode( SSP_ITEM_NAME ),
		'url'       => home_url()
	);
	// Call the custom API.
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

/**
 * This is a means of catching errors from the activation method above and displaying it to the customer
 */
function ssp_admin_notices() {
	if ( isset( $_GET['sl_activation'] ) && ! empty( $_GET['message'] ) ) {
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
					<p><?php echo 'Success'; ?></p>
				</div>
				<?php
				// Developers can put a custom success message here for when activation is successful if they way.
				break;
		}
	}
}
add_action( 'admin_notices', 'ssp_admin_notices' );

/**
 * This is to enable custom login module 
 */
add_action('init', 'ssp_custom_login_module');

function ssp_custom_login_module() {

	if ( get_option( 'ssp_login_type', '' ) == "username" ) {
		remove_filter( 'authenticate', 'wp_authenticate_email_password', 20 );
	} else if ( get_option( 'ssp_login_type', '' ) == "email" ) {
		remove_filter( 'authenticate', 'wp_authenticate_username_password', 20 );
	}
}



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