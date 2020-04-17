<?php
/*


Plugin Name: Stop Spammers Premium

Plugin URI: https://trumani.com/downloads/stop-spammers-premium/

Description: Add even more features to the popular Stop Spammers plugin. Export/Import settings, Reset options to default, and More

Author: Trumani

Author URI: https://trumani.com

Version: 2020.1

License: GNU General Public License v2.0 or later

License URI: http://www.gnu.org/licenses/gpl-2.0.html


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
    $error_message = '<p class="dependency">' . esc_html__( 'This plugin requires ', 'ssprem' ) . '<a href="' . esc_url( 'https://wordpress.org/plugins/stop-spammer-registrations-plugin/' ) . '" target="_blank">Stop Spammers</a>' . esc_html__( ' plugin to be active.', 'ssprem' ) . '</p>';
    die( $error_message ); // WPCS: XSS ok.
  }
}
register_activation_hook( __FILE__, 'ssprem_activate' ); 

define( 'SSP_STORE_URL', 'https://trumani.com' ); 


define( 'SSP_ITEM_ID', 21210 ); 
define( 'SSP_ITEM_NAME', 'STOP SPAMMERS PREMIUM' ); 


define( 'SSP_LICENSE_PAGE', 'ssp_license' );

if( !class_exists( 'EDD_SL_Plugin_Updater' ) ) {

	include( dirname( __FILE__ ) . '/EDD_SL_Plugin_Updater.php' );

}



function ssp_plugin_updater() {

	$license_key = trim( get_option( 'ssp_license_key' ) );


	$edd_updater = new EDD_SL_Plugin_Updater( SSP_STORE_URL, __FILE__,


		array(

			'version' => '1.0',
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
		'Stop Spammer Plugin License', //page_title
		'Stop Spammer Plugin License',  //menu_title
		'manage_options', //capability
		SSP_LICENSE_PAGE, //menu_slug
		'ssp_license_page' //function  
	);


	$license = get_option( 'ssp_license_key' );
	$status  = get_option( 'ssp_license_status' );

if( $status !== false && $status == 'valid' ) { 
	add_submenu_page( 
		'stop_spammers', //parent_slug
		'Stop Spammer Premium Features', //page_title
		'Stop Spammer Premium Features',  //menu_title
		'manage_options', //capability
		'ssp_premium', //menu_slug
		'ss_export_excel' //function  
	);

}

}


add_action('admin_menu', 'ssp_license_menu',11);



function ss_export_excel(){

?>

		<div class="metabox-holder">
			<div class="postbox">
				<h3><span><?php _e( 'Export Log Settings' ); ?></span></h3>
				<div class="inside">
					<p><?php _e( 'Export the Log records  a Excel file. ' ); ?></p>
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
						<p>
							<input type="file" name="import_file"/>
						</p>
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
					<p><?php _e( 'Reset the plugin settings for this site . This allows you to easily reset the configuration.' ); ?></p>
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

<?php
}

function ss_export_excel_data(){

	if( empty( $_POST['export_log'] ) || 'export_log_data' != $_POST['export_log'] )
		return;

	if( ! wp_verify_nonce( $_POST['ssp_export_action'], 'ssp_export_action' ) )
		return;

if( ! current_user_can( 'manage_options' ) )
		return;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setCellValue('A1', 'Date/Time');
$sheet->setCellValue('B1', 'Email');
$sheet->setCellValue('C1', 'IP');
$sheet->setCellValue('D1', 'Author, User/Pwd');
$sheet->setCellValue('E1', 'Script');
$sheet->setCellValue('F1', 'Reason');

	$stats = ss_get_stats();
	extract( $stats );
	$index = 2;


foreach ($stats['hist'] as $key => $value) {

$sheet->setCellValue('A'.$index, $key);
$sheet->setCellValue('B'.$index, $value[1]);
$sheet->setCellValue('C'.$index, $value[0]);
$sheet->setCellValue('D'.$index, $value[2]);
$sheet->setCellValue('E'.$index, $value[3]);
$sheet->setCellValue('F'.$index, $value[4]);
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
 * Process a settings export that generates a .json file of the shop settings
 */
function ssp_process_settings_export() {

	if( empty( $_POST['ssp_action'] ) || 'export_settings' != $_POST['ssp_action'] )
		return;

	if( ! wp_verify_nonce( $_POST['ssp_export_nonce'], 'ssp_export_nonce' ) )
		return;

	if( ! current_user_can( 'manage_options' ) )
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

/**
 * Process a settings import from a json file
 */
function ssp_process_settings_import() {

	if( empty( $_POST['ssp_action'] ) || 'import_settings' != $_POST['ssp_action'] )
		return;

	if( ! wp_verify_nonce( $_POST['ssp_import_nonce'], 'ssp_import_nonce' ) )
		return;

	if( ! current_user_can( 'manage_options' ) )
		return;

// $extension = end( explode( '.', $_FILES['import_file']['name'] ) );
$extension = $_FILES['import_file']['type'] ;

// if( $extension != 'json' ) {
if( $extension != 'application/json' ) {
wp_die( __( 'Please upload a valid .json file' ) );
}

	$import_file = $_FILES['import_file']['tmp_name'];


	if( empty( $import_file ) ) {
		wp_die( __( 'Please upload a file to import' ) );
	}

	// Retrieve the settings from the file and convert the json object to an array.
	$options = (array) json_decode( file_get_contents( $import_file ) );

	
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

	if( empty( $_POST['ssp_action'] ) || 'reset_settings' != $_POST['ssp_action'] )
		return;

	if( ! wp_verify_nonce( $_POST['ssp_reset_nonce'], 'ssp_reset_nonce' ) )
		return;

	if( ! current_user_can( 'manage_options' ) )
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

// License flow start

 function ssp_license_page() {
	$license = get_option( 'ssp_license_key' );
	$status  = get_option( 'ssp_license_status' );
	?>
	<div class="wrap">
		<h2><?php _e('Stop Spammers Premium Plugin License Options'); ?></h2>
		<form method="post" action="options.php">

			<?php settings_fields('ssp_license'); ?>

			<table class="form-table">
				<tbody>
					<tr valign="top">
						<th scope="row" valign="top">
							<?php _e('License Key'); ?>
						</th>
						<td>
							<input id="ssp_license_key" name="ssp_license_key" type="text" class="regular-text" value="<?php esc_attr_e( $license ); ?>" />
							<label class="description" for="ssp_license_key"><?php _e('Enter your license key'); ?></label>
						</td>
					</tr>
					<?php if( false !== $license ) { ?>
						<tr valign="top">
							<th scope="row" valign="top">
								<?php _e('Activate License'); ?>
							</th>
							<td>
								<?php if( $status !== false && $status == 'valid' ) { ?>
									<span style="color:green;"><?php _e('active'); ?></span>
									<?php wp_nonce_field( 'ssp_nonce', 'ssp_nonce' ); ?>
									<input type="submit" class="button-secondary" name="ssp_license_deactivate" value="<?php _e('Deactivate License'); ?>"/>
								<?php } else {
									wp_nonce_field( 'ssp_nonce', 'ssp_nonce' ); ?>
									<input type="submit" class="button-secondary" name="ssp_license_activate" value="<?php _e('Activate License'); ?>"/>
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
	register_setting('ssp_license', 'ssp_license_key', 'ssp_sanitize_license' );
}
add_action('admin_init', 'ssp_register_option');

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
	if( isset( $_POST['ssp_license_activate'] ) ) {

		// run a quick security check
	 	if( ! check_admin_referer( 'ssp_nonce', 'ssp_nonce' ) )
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
add_action('admin_init', 'ssp_activate_license');


/***********************************************
* Illustrates how to deactivate a license key.
* This will decrease the site count
***********************************************/

function ssp_deactivate_license() {

	// listen for our activate button to be clicked
	if( isset( $_POST['ssp_license_deactivate'] ) ) {

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
		if( $license_data->license == 'deactivated' ) {
			delete_option( 'ssp_license_status' );
		}

		wp_redirect( admin_url( 'admin.php?page=' . SSP_LICENSE_PAGE ) );
		exit();

	}
}
add_action('admin_init', 'ssp_deactivate_license');


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

	if( $license_data->license == 'valid' ) {
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
