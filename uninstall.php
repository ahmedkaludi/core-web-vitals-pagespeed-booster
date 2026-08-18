<?php

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

if ( ! defined( 'CWVPSB_CACHE_DIR' ) ) {
	define( 'CWVPSB_CACHE_DIR', WP_CONTENT_DIR. '/cache/cwvpsb/' );
}

if ( ! defined( 'CWVPSB_IMAGE_DIR' ) ) {
	define('CWVPSB_GRAVATARS_DIR',WP_CONTENT_DIR.'/gravatars/');
}
$cwvpsb_settings = get_option( 'cwvpsb_get_settings',false);

if($cwvpsb_settings && isset($cwvpsb_settings['delete_on_uninstall']) && $cwvpsb_settings['delete_on_uninstall'] == 1){
	global $wpdb, $table_prefix;
	if (function_exists('is_multisite') && is_multisite()) {	
		$cwvpsb_original_blog_id = get_current_blog_id();
		$cwvpsb_blog_ids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->blogs}"); //phpcs:ignore 
	
		foreach ($cwvpsb_blog_ids as $cwvpsb_blog_id) {
			switch_to_blog($cwvpsb_blog_id);
			$wpdb->query("DROP TABLE IF EXISTS `{$wpdb->prefix}cwvpb_critical_urls`"); //phpcs:ignore --Reason: Direct DB call to delete table
			delete_option('cwvpsb_get_settings');
		}
	
		switch_to_blog($cwvpsb_original_blog_id);
	} else {
		$wpdb->query("DROP TABLE IF EXISTS `{$wpdb->prefix}cwvpb_critical_urls`"); //phpcs:ignore  --Reason: Direct DB call to delete table
	}
	require_once ( ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php' );
	require_once ( ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php' );
	$cwvpsb_filesystem_direct = new WP_Filesystem_Direct(false);
	if($cwvpsb_filesystem_direct){
		$cwvpsb_filesystem_direct->rmdir(CWVPSB_CACHE_DIR, true);
		$cwvpsb_filesystem_direct->rmdir(CWVPSB_GRAVATARS_DIR, true);
	}
}
