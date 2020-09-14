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

define('WEB_VITALS_PAGESPEED_BOOSTER_FILE',  __FILE__ );
define('WEB_VITALS_PAGESPEED_BOOSTER_DIR', plugin_dir_path( __FILE__ ));
define('WEB_VITALS_PAGESPEED_BOOSTER_URL', plugin_dir_url( __FILE__ ));
define('WEB_VITALS_PAGESPEED_BOOSTER_VERSION', '1.0');
define('WEB_VITALS_PAGESPEED_BOOSTER_BASENAME', plugin_basename(__FILE__));

$web_vitals_settings = array();
function web_vitals_defaultSettings(){
	global $web_vitals_settings;
	if( empty($web_vitals_settings) || (is_array($web_vitals_settings) && count($web_vitals_settings)==0) ){
        $web_vitals_settings = get_option( 'webvitals_settings', false ); 
    }
   return $web_vitals_settings;
}

add_action('plugins_loaded', 'initiate_web_vitals');
function initiate_web_vitals(){
	require_once WEB_VITALS_PAGESPEED_BOOSTER_DIR."/inc/helper-section.php";
	add_filter('wp_handle_upload', array('Web_Vital_Helper_Section', 'do_upload_with_webp'), 10, 2);
	if(is_admin()){
		require_once WEB_VITALS_PAGESPEED_BOOSTER_DIR."/inc/admin-section.php";
	}else{
		require_once WEB_VITALS_PAGESPEED_BOOSTER_DIR."/inc/front-section.php";
	}
}

