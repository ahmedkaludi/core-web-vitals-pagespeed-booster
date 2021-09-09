<?php
/*
Plugin Name: Core Web Vitals & PageSpeed Booster
Plugin URI: https://wordpress.org/plugins/core-web-vitals-page-speed-booster/
Description: Do you want to speed up your WordPress site? Fast loading pages improve user experience, increase your pageviews, and help with your WordPress SEO.
Version: 1.0
Author: AMPforWP Team
Author URI: https://ampforwp.com/
Donate link: https://www.paypal.me/Kaludi/25
Domain Path: /languages
License: GPL2+
Text Domain: cwvpsb
*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

define('CWVPSB_PLUGIN_DIR', plugin_dir_path( __FILE__ ));
define('CWVPSB_PLUGIN_DIR_URI', plugin_dir_url(__FILE__));
define('CWVPSB_VERSION','1.0');
define('CWVPSB_DIR', dirname(__FILE__));
define('CWVPSB_BASE', plugin_basename(__FILE__));
define('CWVPSB_CACHE_DIR', WP_CONTENT_DIR. '/cache/cache-cwvpsb');

require_once CWVPSB_PLUGIN_DIR."includes/functions.php";