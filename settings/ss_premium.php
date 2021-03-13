<?php

if ( !defined( 'ABSPATH' ) ) {
	http_response_code( 404 );
	die();
}

if ( !current_user_can( 'manage_options' ) ) {
	die( __( 'Access Blocked', 'stop-spammers-premium' ) );
}

?>

<div id="ss-plugin" class="wrap">
	<h1 class="ss_head" style="text-align:center"><?php _e( 'Premium Options', 'stop-spammers-premium' ); ?></h1>
	<br />
	<br />
	<div class="ss_admin_info_boxes_3row">
		<div class="ss_admin_info_boxes_3col">
			<h3><?php _e( 'Restore Default Settings', 'stop-spammers-premium' ); ?></h3>
			<img src="<?php echo plugin_dir_url( dirname( __FILE__ ) ); ?>/images/restore-settings_stop-spammers_trumani.png" class="center_thumb" />
			<?php _e( 'Too fargone? Revert to the out-of-the box configurations.', 'stop-spammers-premium' ); ?>
		</div>
		<div class="ss_admin_info_boxes_3col">
			<h3><?php _e( 'Import / Export', 'stop-spammers-premium' ); ?></h3>
			<img src="<?php echo plugin_dir_url( dirname( __FILE__ ) ); ?>/images/import-export_stop-spammers_trumani.png" class="center_thumb" />
			<?php _e( 'You can download your personalized configurations and upload them to all of your other sites.', 'stop-spammers-premium' ); ?>
    	</div>
		<div class="ss_admin_info_boxes_3col">
			<h3><?php _e( 'Export to Excel', 'stop-spammers-premium' ); ?></h3>
			<img src="<?php echo plugin_dir_url( dirname( __FILE__ ) ); ?>/images/export-to-excel_stop-spammers_trumani.png" class="center_thumb" />
			<?php _e( 'Save the log report returns for future reference.', 'stop-spammers-premium' ); ?>
		</div>
	</div>
</div>