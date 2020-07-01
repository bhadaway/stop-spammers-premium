<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // just in case

if ( ! current_user_can( 'manage_options' ) ) {
	die( 'Access Denied' );
}
?>

<div id="ss-plugin" class="wrap">
	<h1 class="ss_head" style="text-align:center">Premium Options</h1>
	<br /><br />
	<div class="ss_admin_info_boxes_3row">
		<h3>Restore Default Settings</h3>
		<img src="<?php echo plugin_dir_url( dirname( __FILE__ ) ) . 'images/restore-settings_stop-spammers_trumani.png'; ?>" class="center_thumb" />
        Too fargone? Revert to the out-of-the box configurations.
	</div>
	<div class="ss_admin_info_boxes_3col">
		<h3>Import / Export</h3>
		<img src="<?php echo plugin_dir_url( dirname( __FILE__ ) ) . 'images/import-export_stop-spammers_trumani.png'; ?>" class="center_thumb" />
        You can download your personalized configurations and upload them to all of your other sites.
    </div>
	<div class="ss_admin_info_boxes_3col">
		<h3>Export to Excel</h3>
		<img src="<?php echo plugin_dir_url( dirname( __FILE__ ) ) . 'images/export-to-excel_stop-spammers_trumani.png'; ?>" class="center_thumb" />
        Save the log report returns for future reference.
	</div>
</div>