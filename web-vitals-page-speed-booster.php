<?php
/**
Plugin Name: Web Vitals & PageSpeed Booster
Plugin URI: https://wordpress.org/plugins/pwa-for-wp/
Description: Optimizing for quality of user experience is key to the long-term success of any site. Web Vitals can help you quantify the experience of your site and identify opportunities to improve.
Author: Magazine3
Version: 1.0
Author URI: 
Text Domain: web-vitals-page-speed-booster
Domain Path: /languages
License: GPL2+
*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

define('WEBVITAL_PAGESPEED_BOOSTER_FILE',  __FILE__ );
define('WEBVITAL_PAGESPEED_BOOSTER_DIR', plugin_dir_path( __FILE__ ));
define('WEBVITAL_PAGESPEED_BOOSTER_URL', plugin_dir_url( __FILE__ ));
define('WEBVITAL_PAGESPEED_BOOSTER_VERSION', '1.0');
define('WEBVITAL_PAGESPEED_BOOSTER_BASENAME', plugin_basename(__FILE__));

$webVital_settings = array();
function web_vital_defaultSettings(){
	global $webVital_settings;
	if( empty($webVital_settings) || (is_array($webVital_settings) && count($webVital_settings)==0) ){
        $webVital_settings = get_option( 'webvital_settings', false ); 
    }
   return $webVital_settings;
}

add_action("plugins_loaded", "initiate_web_vital");
function initiate_web_vital(){
		require_once WEBVITAL_PAGESPEED_BOOSTER_DIR."/inc/helper-section.php";
	if(is_admin()){
		require_once WEBVITAL_PAGESPEED_BOOSTER_DIR."/inc/admin-section.php";
	}else{
		require_once WEBVITAL_PAGESPEED_BOOSTER_DIR."/inc/front-section.php";
	}
}

add_filter('wp_handle_upload', array('webVitalHelperSection', 'doUploadWithWebp'), 10, 2);