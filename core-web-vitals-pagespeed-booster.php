<?php
/*
Plugin Name: Core Web Vitals & PageSpeed Booster
Description: Do you want to speed up your WordPress site? Fast loading pages improve user experience, increase your pageviews, and help with your WordPress SEO.
Version: 1.0.25
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
define('CWVPSB_VERSION','1.0.25');
define('CWVPSB_DIR', dirname(__FILE__));
define('CWVPSB_BASE', plugin_basename(__FILE__));


/**
 * Static cache path
 **/
define('CWVPSB_CACHE_DIR', WP_CONTENT_DIR. '/cache/cwvpsb/');
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
require_once CWVPSB_PLUGIN_DIR."includes/admin/class-cwvpb-newsletter.php";

add_action('plugins_loaded', 'cwv_pse_initiate');
function cwv_pse_initiate(){
	require_once CWVPSB_PLUGIN_DIR."/includes/helper-section.php";
	add_filter('wp_handle_upload', array('Core_Web_Vital_Helper_Section', 'do_upload_with_webp'), 10, 2);
}

register_activation_hook( __FILE__, 'cwvpsb_on_activate' );

function cwvpsb_on_activate( $network_wide ) {
    global $wpdb;

    if ( is_multisite() && $network_wide ) {
        $blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        foreach ( $blog_ids as $blog_id ) {
            switch_to_blog( $blog_id );
            cwvpsb_on_install();
            restore_current_blog();
        }
    } else {
        cwvpsb_on_install();
    }
}

register_deactivation_hook( __FILE__, 'cwvpsb_on_deactivate' );

function cwvpsb_on_deactivate( $network_wide ) {
	global $wpdb;

	if ( is_multisite() && $network_wide ) {
		$blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		foreach ( $blog_ids as $blog_id ) {
			switch_to_blog( $blog_id );
			cwvpsb_remove_htaccess_rules();
			restore_current_blog();
		}
	} else {
		cwvpsb_remove_htaccess_rules();
	}

	
}

function cwvpsb_on_install(){

	global $wpdb;
	
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

	$charset_collate = $engine = '';	
	
	if(!empty($wpdb->charset)) {
		$charset_collate .= " DEFAULT CHARACTER SET {$wpdb->charset}";
	} 
	if($wpdb->has_cap('collation') AND !empty($wpdb->collate)) {
		$charset_collate .= " COLLATE {$wpdb->collate}";
	}


	$found_engine = $wpdb->get_var("SELECT ENGINE FROM `information_schema`.`TABLES` WHERE `TABLE_SCHEMA` = '{$wpdb->dbname}' AND `TABLE_NAME` = '{$wpdb->prefix}posts';"); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        
	if(strtolower($found_engine) == 'innodb') {
		$engine = ' ENGINE=InnoDB';
	}

	$found_tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}cwvpb%';"); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
    
    if(!in_array("{$wpdb->prefix}cwvpb_critical_urls", $found_tables)) {
            
		dbDelta("CREATE TABLE `{$wpdb->prefix}cwvpb_critical_urls` (
			`id` bigint( 20 ) unsigned NOT NULL AUTO_INCREMENT,
			`url_id` bigint( 20 ) unsigned NOT NULL,
			`type` varchar(20),
			`type_name` varchar(50),
			`url` varchar(250) NOT NULL,
			`status` varchar(20) NOT NULL DEFAULT 'queue',					
			`cached_name` varchar(100),
			`created_at` datetime NOT NULL,
			`updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
			`failed_error` text  NOT NULL,
			 KEY `url` ( `url` ),
			 PRIMARY KEY (`id`),
			 CONSTRAINT cwvpb_unique UNIQUE (`url`)
		) ".$charset_collate.$engine.";");                
    }	

}

function cwvpsb_update_htaccess() {

	$custom_rules = [
		'<IfModule mod_expires.c>',
		'    ExpiresActive On',
		'    # Default expiration for all files (1 month)',
		'    ExpiresDefault "access plus 1 month"',
		'    # Expiration for images (1 year)',
		'    ExpiresByType image/jpg "access plus 1 year"',
		'    ExpiresByType image/jpeg "access plus 1 year"',
		'    ExpiresByType image/png "access plus 1 year"',
		'    ExpiresByType image/gif "access plus 1 year"',
		'    ExpiresByType image/webp "access plus 1 year"',
		'    # Expiration for CSS and JS (1 month)',
		'    ExpiresByType text/css "access plus 1 month"',
		'    ExpiresByType application/javascript "access plus 1 month"',
		'    # Expiration for fonts (1 year)',
		'    ExpiresByType application/font-woff2 "access plus 1 year"',
		'    ExpiresByType application/font-woff "access plus 1 year"',
		'    ExpiresByType font/ttf "access plus 1 year"',
		'    ExpiresByType font/otf "access plus 1 year"',
		'    ExpiresByType application/vnd.ms-fontobject "access plus 1 year"',
		'</IfModule>',
		'<IfModule mod_headers.c>',
		'    # Set Cache-Control headers for static files',
		'    <FilesMatch "\\.(jpg|jpeg|png|gif|webp|css|js|woff|woff2|ttf|otf|eot)$">',
		'        Header set Cache-Control "public, max-age=31536000, immutable"',
		'    </FilesMatch>',
		'</IfModule>'
	];
	

	// Add or update rules in .htaccess
	$htaccess_file = ABSPATH . '.htaccess';
	insert_with_markers( $htaccess_file, 'CWVPSB Rules', $custom_rules );
}
 function cwvpsb_remove_htaccess_rules() {
	// Remove custom rules from .htaccess
	$htaccess_file = ABSPATH . '.htaccess';
	insert_with_markers( $htaccess_file, 'CWVPSB Rules', [] );
}

add_action( 'upgrader_process_complete', 'cwvpsb_update_on_plugin_update', 10, 2 );
function cwvpsb_update_on_plugin_update( $upgrader_object, $options ) {
	if ( 'update' === $options['action'] && 'plugin' === $options['type'] ) {
		$plugin = $options['plugins'][0];
		if ( plugin_basename( __FILE__ ) === $plugin ) {
			//check if cache support is enabled
			$settings = cwvpsb_get_settings();
			if ( isset( $settings['cache_support'] ) && 1 == $settings['cache_support'] ) {
				cwvpsb_update_htaccess();
			}
		}
	}
}

add_action( 'update_option_cwvpsb_get_settings', 'cwvpsb_htaccess_on_setting_update' , 10 , 3 );
function cwvpsb_htaccess_on_setting_update( $old_value, $value, $option ) {
	if ( 'cwvpsb_get_settings' == $option ) {
		if ( isset( $value['cache_support'] ) && 1 == $value['cache_support'] ) {
			cwvpsb_update_htaccess();
		}else{
			cwvpsb_remove_htaccess_rules();
		}	
	}
	return $value;
}