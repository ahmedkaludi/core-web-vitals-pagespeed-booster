<?php

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

if ( ! defined( 'CWVPSB_CACHE_DIR' ) ) {
	define( 'CWVPSB_CACHE_DIR', WP_CONTENT_DIR. '/cache/cwvpsb/static/' );
}

if ( ! defined( 'CWVPSB_IMAGE_DIR' ) ) {
	define('CWVPSB_IMAGE_DIR',plugin_dir_url(__FILE__).'images/');
}

global $wpdb, $table_prefix;
if (function_exists('is_multisite') && is_multisite()) {	
	$original_blog_id = get_current_blog_id();
	$blog_ids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->blogs}");

	foreach ($blog_ids as $blog_id) {
	    switch_to_blog($blog_id);
	    $cached_table = $table_prefix . 'cwvpb_critical_urls';
	    $wpdb->query("DROP TABLE `$cached_table`");
	}

	switch_to_blog($original_blog_id);
} else {
	$cached_table = $table_prefix . 'cwvpb_critical_urls';
	$wpdb->query("DROP TABLE $cached_table");
}