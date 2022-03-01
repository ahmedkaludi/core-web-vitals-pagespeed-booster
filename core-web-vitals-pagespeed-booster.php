<?php
/*
Plugin Name: Core Web Vitals & PageSpeed Booster
Description: Do you want to speed up your WordPress site? Fast loading pages improve user experience, increase your pageviews, and help with your WordPress SEO.
Version: 1.0.3
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
define('CWVPSB_VERSION','1.0.3');
define('CWVPSB_DIR', dirname(__FILE__));
define('CWVPSB_BASE', plugin_basename(__FILE__));
define('CWVPSB_CACHE_DIR', WP_CONTENT_DIR. '/cache/cache-cwvpsb');
define('CWVPSB_IMAGE_DIR',plugin_dir_url(__FILE__).'images');

require_once CWVPSB_PLUGIN_DIR."includes/functions.php";
require_once CWVPSB_PLUGIN_DIR."includes/admin/helper-function.php";

add_action('plugins_loaded', 'cwv_pse_initiate');
function cwv_pse_initiate(){
	require_once CWVPSB_PLUGIN_DIR."/includes/helper-section.php";
	add_filter('wp_handle_upload', array('Core_Web_Vital_Helper_Section', 'do_upload_with_webp'), 10, 2);
}