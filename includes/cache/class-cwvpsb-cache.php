<?php
/**
* CWVPSB_Cache
*
* @since 1.0.0
*/
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

final class CWVPSB_Cache {

	private static $disk;

	public static function instance()
	{
		new self();
	}

	public function __construct()
	{
		// set default vars
		self::_set_default_vars();

		// register publish hook
		add_action(
			'init',
			array(
				__CLASS__,
				'register_publish_hooks'
			),
			99
		);
		add_action(
			'_core_updated_successfully',
			array(
				__CLASS__,
				'clear_total_cache'
			)
		);
		add_action(
			'switch_theme',
			array(
				__CLASS__,
				'clear_total_cache'
			)
		);
		add_action(
			'wp_trash_post',
			array(
				__CLASS__,
				'clear_total_cache'
			)
		);

		add_action(
			'init',
			array(
				__CLASS__,
				'process_clear_request'
			)
		);
		
		add_action(
			'transition_post_status',
			array(
				__CLASS__,
				'clear_homepage_category_cache_on_publish'
			),
			10,
			3
		);

		// caching
		if ( !is_admin() ) {
			add_action(
				'sanitize_comment_cookies',
				array(
					__CLASS__,
					'handle_serving_cache'
				),
				0
			);
			add_filter(
				'cwvpsb_complete_html_after_dom_loaded',
				array(
					__CLASS__,
					'handle_cache'
				),
				99
			);
			add_action( 'init',array(__CLASS__,'cwvpsb_autoclear_scheduler_activation'));
			add_action( 'cwvpsb_autoclear_cron', array(__CLASS__,'cwvpsb_autoclear_cron') );
			

		}

	if ( isset( $_POST['submit'] ) || isset( $_POST['cache-btn'] ) ) {  //phpcs:ignore -- Reason: Check for submit button
            self::clear_total_cache(true);
        }	

	}

	public static function  cwvpsb_autoclear_scheduler_activation() {
		$options =  cwvpsb_defaults();
			if ( ( ! wp_next_scheduled( 'cwvpsb_autoclear_cron' ) ) ) {
					if(isset($options['cache_autoclear']) && $options['cache_autoclear'] != 'never'){
						wp_schedule_event( time(), 'hourly', 'cwvpsb_autoclear_cron' );
					}  
				}
	}
	public static function cwvpsb_autoclear_cron() {
		$settings = cwvpsb_defaults();
	
		if (isset($settings['cache_autoclear']) && $settings['cache_autoclear'] != 'never') {
			$last_autoclear_time = (isset($settings['cache_last_autoclear']) && $settings['cache_last_autoclear'] > 0) ? $settings['cache_last_autoclear'] : time();
	
			if ($last_autoclear_time < time()) {
				$diff = self::cwvpsb_unixtimestamp_diff($last_autoclear_time, time(), 6, 'hour');
	
				if ($diff > 0) {
					switch ($settings['cache_autoclear']) {
						case 'hourly':
							self::clear_total_cache(true);
							$settings['cache_last_autoclear'] = time();
							update_option('cwvpsb_get_settings', $settings, false);
							break;
	
						case '6hourly':
							if(($diff*6) > $last_autoclear_time){
								self::clear_total_cache(true);
								$settings['cache_last_autoclear'] = time();
								update_option('cwvpsb_get_settings', $settings, false);
							}
							break;
	
						case '12hourly':
							if(($diff*12) > $last_autoclear_time){
								self::clear_total_cache(true);
								$settings['cache_last_autoclear'] = time();
								update_option('cwvpsb_get_settings', $settings, false);
							}
							break;
	
						case 'daily':
							if(($diff*24) > $last_autoclear_time){
								self::clear_total_cache(true);
								$settings['cache_last_autoclear'] = time();
								update_option('cwvpsb_get_settings', $settings, false);
							}
							break;
	
						case 'weekly':
							if(($diff*24*7) > $last_autoclear_time){
								self::clear_total_cache(true);
								$settings['cache_last_autoclear'] = time();
								update_option('cwvpsb_get_settings', $settings, false);
							}
							break;
	
						case 'monthly':
							if(($diff*24*7*28) > $last_autoclear_time){
								self::clear_total_cache(true);
								$settings['cache_last_autoclear'] = time();
								update_option('cwvpsb_get_settings', $settings, false);
							}
							break;
	
						default:
							// Handle unknown autoclear option
							break;
					}
				}
			}
		}
	}
	
	public static function on_deactivation() {
		self::clear_total_cache(true);
	}

	public static function on_activation() {
		$networkwide = isset($_GET['networkwide'])?sanitize_text_field(wp_unslash( $_GET['networkwide'] ) ):''; //phpcs:ignore
		// multisite and network
		if ( is_multisite() && ! empty($networkwide) ) {
			// blog ids
			$ids = self::_get_blog_ids();

			// switch to blog
			foreach ($ids as $id) {
				switch_to_blog($id);
				self::_install_backend();
			}

			// restore blog
			restore_current_blog();

		} else {
			self::_install_backend();
		}
	}
	private static function _install_backend() {

		add_option(
			'cache',
			array()
		);

		// clear
		self::clear_total_cache(true);
	}

	public static function on_uninstall() {
		global $wpdb;
		$networkwide = sanitize_text_field( wp_unslash( $_GET['networkwide'] ) ); //phpcs:ignore 
		// multisite and network
		if ( is_multisite() && ! empty($networkwide) ) {
			// legacy blog
			$old = $wpdb->blogid;

			// blog id
			$ids = self::_get_blog_ids();

			// uninstall per blog
			foreach ($ids as $id) {
				switch_to_blog($id);
				self::_uninstall_backend();
			}

			// restore
			switch_to_blog($old);
		} else {
			self::_uninstall_backend();
		}
	}

	private static function _uninstall_backend() {

		// delete options
		delete_option('cache');

		// clear cache
		self::clear_total_cache(true);
	}
	private static function _get_blog_ids() {
		global $wpdb;

		return $wpdb->get_col("SELECT blog_id FROM `$wpdb->blogs`"); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	private static function _set_default_vars() {

		// disk cache
		if ( CWVPSB_Cache_Disk::is_permalink() ) {
			self::$disk = new CWVPSB_Cache_Disk;
		}
	}

	private static function _get_options() {

		return wp_parse_args(
			get_option('cache'),
			array(
				'new_post'		=> 0
			)
		);
	}

	public static function process_clear_request($data) {
		$cache = "";
		if (isset($_GET['_cache'])){
			$cache = sanitize_text_field( wp_unslash( $_GET['_cache'] ) );  //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		}
		// check if clear request
		if ( empty($cache) OR ( $cache !== 'clear' && $cache !== 'clearurl' ) ) {
			return;
		}

        // validate nonce
        if ( empty( $_GET['_wpnonce'] ) OR ! wp_verify_nonce( wp_unslash( $_GET['_wpnonce'] ), '_cache__clear_nonce') ) {  //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            return;
        }

		// check user role
		if ( ! is_admin_bar_showing() OR ! apply_filters('user_can_clear_cache', current_user_can('manage_options')) ) {
			return;
		}

		// load if network
		if ( ! function_exists('is_plugin_active_for_network') ) {
			require_once( ABSPATH. 'wp-admin/includes/plugin.php' );
		}

		// set clear url w/o query string
		$clear_url = preg_replace('/\?.*/', '', home_url( add_query_arg( NULL, NULL ) ));

		// multisite and network setup
		if ( is_multisite() && is_plugin_active_for_network(CWVPSB_BASE) ) {

			if ( is_network_admin() ) {

				// legacy blog
				$legacy = $GLOBALS['wpdb']->blogid;

				// blog ids
				$ids = self::_get_blog_ids();

				// switch blogs
				foreach ($ids as $id) {
					switch_to_blog($id);
					self::clear_page_cache_by_url(home_url());
				}

				// restore
				switch_to_blog($legacy);

				// clear notice
				if ( is_admin() ) {
					add_action(
						'network_admin_notices',
						array(
							__CLASS__,
							'clear_notice'
						)
					);
				}
			} else {
				if ($cache == 'clearurl') {
					// clear specific multisite url cache
					self::clear_page_cache_by_url($clear_url);
				} else {
					// clear specific multisite cache
					self::clear_page_cache_by_url(home_url());

					// clear notice
					if ( is_admin() ) {
						add_action(
							'admin_notices',
							array(
								__CLASS__,
								'clear_notice'
							)
						);
					}
				}
			}
		} else {
			if ($cache == 'clearurl') {
				// clear url cache
				self::clear_page_cache_by_url($clear_url);
			} else {
				// clear cache
				self::clear_total_cache();

				// clear notice
				if ( is_admin() ) {
					add_action(
						'admin_notices',
						array(
							__CLASS__,
							'clear_notice'
						)
					);
				}
			}
		}

		if ( ! is_admin() ) {
			wp_safe_redirect(
				remove_query_arg(
					'_cache',
					wp_get_referer()
				)
			);

			exit();
		}
	}

	public static function clear_notice() {

		// check if admin
		if ( ! is_admin_bar_showing() OR ! apply_filters('user_can_clear_cache', current_user_can('manage_options')) ) {
			return false;
		}

		echo sprintf(
			'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
			esc_html__('The cache has been cleared.', 'cwvpsb')
		);
	}

	public static function register_publish_hooks() {

		// get post types
		$post_types = get_post_types(
			array('public' => true)
		);

		// check if empty
		if ( empty($post_types) ) {
			return;
		}

		// post type actions
		foreach ( $post_types as $post_type ) {
			add_action(
				'publish_' .$post_type,
				array(
					__CLASS__,
					'publish_post_types'
				),
				10,
				2
			);
		}
	}

	public static function publish_post_types($post_ID, $post) {
		// validate user role
		if ( ! current_user_can('publish_posts') ) {
			return;
		}

		// check if post id or post is empty
		if ( empty($post_ID) OR empty($post) ) {
			return;
		}

		// check post status
		if ( ! in_array( $post->post_status, array('publish', 'future') ) ) {
			return;
		}

		if ( the_modified_time() == the_time() ) {
			self::clear_page_cache_by_post_id( $post_ID );
		}
	}

	public static function clear_page_cache_by_post_id($post_ID) {

		// is int
		if ( ! $post_ID = (int)$post_ID ) {
			return;
		}

		// clear cache by URL
		self::clear_page_cache_by_url(
			get_permalink( $post_ID )
		);
	}

	public static function clear_page_cache_by_url($url) {

		// validate string
		if ( ! $url = (string)$url ) {
			return;
		}

		call_user_func(
			array(
				self::$disk,
				'delete_asset'
			),
			$url
		);
	}

	public static function clear_homepage_category_cache_on_publish($new_status, $old_status, $post){

		   	if ( 'publish' !== $new_status ){
		        	return;
				}
			$post_types = array('post');

			if(!in_array(get_post_type($post), $post_types)){
	        	return;
			}
			// On new publish only
			if ( $new_status !== $old_status) {
			    $category = get_the_category($post->ID);
			    if(!empty($category)){
				    foreach ($category as $key => $value) {
				    $cat_url = get_category_link( $category[$key]->term_id );
					self::clear_page_cache_by_url($cat_url);
				   }
			    }
				self::clear_home_page_cache();
			}
	    return;
	}

	public static function clear_home_page_cache() {

		call_user_func(
			array(
				self::$disk,
				'clear_home'
			)
		);

	}

	private static function _is_index() {
		return basename($_SERVER['SCRIPT_NAME']) != 'index.php'; //phpcs:ignore -- Reason: Check for just filename
	}

	private static function _is_logged_in() {

		// check if logged in
		if ( is_user_logged_in() ) {
			return true;
		}

		// check cookie
		if ( empty($_COOKIE) ) {
			return false;
		}

		// check cookie values
		foreach ( $_COOKIE as $k => $v) {
			if ( preg_match('/^(wp-postpass|wordpress_logged_in|comment_author)_/', $k) ) {
				return true;
			}
		}
	}

	private static function _bypass_cache() {

		// bypass cache hook
		if ( apply_filters('bypass_cache', false) ) {
			return true;
		}

		// if logged in
		if ( self::_is_logged_in() ) {
			return true;
		}

		// conditional tags
		/*if ( self::_is_index() OR (function_exists('is_search') && is_search()) OR is_404() OR is_feed() OR is_trackback() OR is_robots() OR is_preview() OR post_password_required() ) {
			return true;
		}*/

		// DONOTCACHEPAGE check e.g. woocommerce
		if ( defined('DONOTCACHEPAGE') && DONOTCACHEPAGE ) {
			return true;
		}

		// Request method GET
		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || $_SERVER['REQUEST_METHOD'] != 'GET' ) {
			return true;
		}

		// Request with query strings
		if ( ! empty($_GET) && ! isset( $_GET['utm_source'], $_GET['utm_medium'], $_GET['utm_campaign'] ) && get_option('permalink_structure') ) {  //phpcs:ignore 	WordPress.Security.NonceVerification.Recommended -- Reason: Only check for query strings
			return true;
		}

		return false;
	}

	private static function _store_asset_cache($data) {
		return $data;
	}

	public static function clear_total_cache( ) {
		// clear disk cache
		
			$current_filter = current_filter();
			$filters_to_work = ['_core_updated_successfully','switch_theme','wp_trash_post'];
			if($current_filter && in_array($current_filter,$filters_to_work)){
				$settings = cwvpsb_defaults();
					if(isset($settings['cache_flush_on']) && in_array($current_filter,$settings['cache_flush_on'])){
						CWVPSB_Cache_Disk::clear_cache();
					}
			}else{

				CWVPSB_Cache_Disk::clear_cache();
			}

	}

	public static function set_cache($data) {

		// check if empty
		if ( empty($data) ) {
			return '';
		}

		// store as asset
		call_user_func(
			array(
				self::$disk,
				'store_asset'
			),
			self::_store_asset_cache($data)
		);

		return $data;
	}

	public static function handle_cache($data) {
		// bypass cache
		if ( self::_bypass_cache() ) {
			return $data;
		}
		$settings = cwvpsb_defaults();
		if(isset($settings['critical_css_support']) && $settings['critical_css_support']==1){
           global $wp, $cwvpbCriticalCss;
           $url = home_url( $wp->request );
    	   $url = trailingslashit($url);	
    		if(!file_exists(CWVPSB_CRITICAL_CSS_CACHE_DIR.md5($url).'.css')){
    		    return $data;
    		}
        }
		
		// get asset cache status
		$cached = call_user_func(
			array(
				self::$disk,
				'check_asset'
			)
		);
		
		// check if cache empty
		if ( empty($cached) ) {
			CWVPSB_Cache::set_cache($data);
			
			return $data;
		}
        return self::$disk::get_asset($data);
	}
	
	public static function handle_serving_cache(){
	    // bypass cache
		if ( self::_bypass_cache() || empty(self::$disk)) {
			return;
		}
		$cached = call_user_func(
			array(
				self::$disk,
				'check_asset'
			)
		);
		if ( !empty($cached) ) {
		    /*echo "runs on setup_theme ";*/ //To track cache serve on setup theme
		    call_user_func(
			array(
				self::$disk,
				'get_asset_readfile'
			));
		    exit();
		}
		
	}
	public static function cwvpsb_unixtimestamp_diff($time1, $time2, $precision = 6, $format = 'hour') {
		// If not numeric then convert texts to unix timestamps
		if (!is_int($time1)) {
			$time1 = strtotime($time1);
		}
		if (!is_int($time2)) {
			$time2 = strtotime($time2);
		}
	
		// If time1 is bigger than time2
		// Then swap time1 and time2
		if ($time1 > $time2) {
			list($time1, $time2) = [$time2, $time1];
		}
	
		// Set up intervals and diffs arrays
		$intervals = array(
			'second' => 1,
			'minute' => 60,
			'hour'   => 3600,
			'day'    => 86400,
			'week'   => 604800,
			'month'  => 2592000,
			'year'   => 31536000
		);
	
		// Validate format parameter
		if (!array_key_exists($format, $intervals)) {
			return "Invalid format parameter.";
		}
	
		$diffInSeconds = $time2 - $time1;
	
		// Calculate the difference in the specified format
		$diff = floor($diffInSeconds / $intervals[$format]);
	
		return $diff;
	}
}