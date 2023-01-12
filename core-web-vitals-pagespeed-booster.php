<?php
/*
Plugin Name: Core Web Vitals & PageSpeed Booster
Description: Do you want to speed up your WordPress site? Fast loading pages improve user experience, increase your pageviews, and help with your WordPress SEO.
Version: 1.0.8
Author: Magazine3
Author URI: https://magazine3.company/
Donate link: https://www.paypal.me/Kaludi/25
Text Domain: cwvpsb
Domain Path: /languages
License: GPL2
*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

define('CWVPSB_PLUGIN_DIR', plugin_dir_path( __FILE__ ));
define('CWVPSB_PLUGIN_DIR_URI', plugin_dir_url(__FILE__));
define('CWVPSB_VERSION','1.0.8');
define('CWVPSB_DIR', dirname(__FILE__));
define('CWVPSB_BASE', plugin_basename(__FILE__));


/**
 * Static cache path
 **/
define('CWVPSB_CACHE_DIR', WP_CONTENT_DIR. '/cache/cwvpsb/static/');
define('CWVPSB_CACHE_AGGRESIVE_DIR',  'wp-content/cache/cwvpsb/static/');
/**
 * Core images 
 **/
define('CWVPSB_IMAGE_DIR',plugin_dir_url(__FILE__).'images/');
$host = parse_url(get_site_url())['host'];
/**
 * Font cache path
 **/
define('CWVPSB_CACHE_FONTS_DIR', WP_CONTENT_DIR . "/cache/cwvpsb/fonts/");
define('CWVPSB_CACHE_FONTS_URL', site_url("/wp-content/cache/cwvpsb/fonts/"));
/**
 * Critical css cache path
 **/
define('CWVPSB_CRITICAL_CSS_CACHE_DIR', WP_CONTENT_DIR . "/cache/cwvpsb/css/");
/**
 * Js Exclude Cache 
 **/ 

define('CWVPSB_JS_EXCLUDE_CACHE_DIR', WP_CONTENT_DIR . "/cache/cwvpsb/excluded-js/");
define('CWVPSB_JS_EXCLUDE_CACHE_URL', site_url("/wp-content/cache/cwvpsb/excluded-js/"));

/**
 * Js Merging File Cache
 **/ 
define('CWVPSB_JS_MERGE_FILE_CACHE_DIR', WP_CONTENT_DIR . "/cache/cwvpsb/merged-js/");
define('CWVPSB_JS_MERGE_FILE_CACHE_CACHE_URL', site_url("/wp-content/cache/cwvpsb/merged-js/"));
/**
 * CSS Merging File Cache
 **/ 
define('CWVPSB_CSS_MERGE_FILE_CACHE_DIR', WP_CONTENT_DIR . "/cache/cwvpsb/merged-css/");
define('CWVPSB_CSS_MERGE_FILE_CACHE_CACHE_URL', site_url("/wp-content/cache/cwvpsb/merged-css/"));
/**
 * Cache transient 
 **/
define('CWVPSB_CACHE_NAME', 'cwvpsb_cleared_timestamp');

require_once CWVPSB_PLUGIN_DIR."includes/functions.php";
require_once CWVPSB_PLUGIN_DIR."includes/admin/helper-function.php";

add_action('plugins_loaded', 'cwv_pse_initiate');
function cwv_pse_initiate(){
	require_once CWVPSB_PLUGIN_DIR."/includes/helper-section.php";
	add_filter('wp_handle_upload', array('Core_Web_Vital_Helper_Section', 'do_upload_with_webp'), 10, 2);
}

register_activation_hook( __FILE__, 'cwvpb_on_activate' );

function cwvpb_on_activate( $network_wide ) {
    global $wpdb;

    if ( is_multisite() && $network_wide ) {
        $blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
        foreach ( $blog_ids as $blog_id ) {
            switch_to_blog( $blog_id );
            cwvpb_on_install();
            restore_current_blog();
        }
    } else {
        cwvpb_on_install();
    }
}

function cwvpb_on_install(){

	global $wpdb;
	
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

	$charset_collate = $engine = '';	
	
	if(!empty($wpdb->charset)) {
		$charset_collate .= " DEFAULT CHARACTER SET {$wpdb->charset}";
	} 
	if($wpdb->has_cap('collation') AND !empty($wpdb->collate)) {
		$charset_collate .= " COLLATE {$wpdb->collate}";
	}

	$found_engine = $wpdb->get_var("SELECT ENGINE FROM `information_schema`.`TABLES` WHERE `TABLE_SCHEMA` = '".DB_NAME."' AND `TABLE_NAME` = '{$wpdb->prefix}posts';");
        
	if(strtolower($found_engine) == 'innodb') {
		$engine = ' ENGINE=InnoDB';
	}

	$found_tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}cwvpb%';");	
    
    if(!in_array("{$wpdb->prefix}cwvpb_critical_urls", $found_tables)) {
            
		dbDelta("CREATE TABLE `{$wpdb->prefix}cwvpb_critical_urls` (
			`id` bigint( 20 ) unsigned NOT NULL AUTO_INCREMENT,
			`url_id` bigint( 20 ) unsigned NOT NULL,			
			`type` varchar(20),
			`type_name` varchar(50),
			`url` varchar(300) NOT NULL,			
			`status` varchar(20) NOT NULL default 'queue',											
			`cached_name` varchar(100),
			`created_at` datetime NOT NULL,
			`updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
			`failed_error` text  NOT NULL Default '',
			 KEY `url` ( `url` ),				
			 PRIMARY KEY (`id`),
			 CONSTRAINT cwvpb_unique UNIQUE (`url`)
		) ".$charset_collate.$engine.";");                
    }	

}