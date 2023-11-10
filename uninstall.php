<?php

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

if ( ! defined( 'CWVPSB_CACHE_DIR' ) ) {
	define( 'CWVPSB_CACHE_DIR', WP_CONTENT_DIR. '/cache/cwvpsb/' );
}

if ( ! defined( 'CWVPSB_IMAGE_DIR' ) ) {
	define('CWVPSB_GRAVATARS_DIR',WP_CONTENT_DIR.'/gravatars/');
}
$cwvpb_settings = get_option( 'cwvpsb_get_settings',false);

if($cwvpb_settings && isset($cwvpb_settings['delete_on_uninstall']) && $cwvpb_settings['delete_on_uninstall'] == 1){
	global $wpdb, $table_prefix;
	if (function_exists('is_multisite') && is_multisite()) {	
		$original_blog_id = get_current_blog_id();
		$blog_ids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->blogs}");
	
		foreach ($blog_ids as $blog_id) {
			switch_to_blog($blog_id);
			$cached_table = $table_prefix . 'cwvpb_critical_urls';
			$wpdb->query("DROP TABLE IF EXISTS `$cached_table`");
			delete_option('cwvpsb_get_settings');
		}
	
		switch_to_blog($original_blog_id);
	} else {
		$cached_table = $table_prefix . 'cwvpb_critical_urls';
		$wpdb->query("DROP TABLE IF EXISTS $cached_table");
	}
	require_once ( ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php' );
	require_once ( ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php' );
	$fileSystemDirect = new WP_Filesystem_Direct(false);
	$fileSystemDirect->rmdir(CWVPSB_CACHE_DIR, true);
	$fileSystemDirect->rmdir(CWVPSB_GRAVATARS_DIR, true);
}