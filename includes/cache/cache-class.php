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

		// caching
		if ( !is_admin() ) {
			add_action(
				'template_redirect',
				array(
					__CLASS__,
					'handle_cache'
				),
				0
			);
		} 

	if ( isset( $_POST['submit'] ) || isset( $_POST['cache-btn'] ) ) {
            self::clear_total_cache(true);
        }	

	}

	public static function on_deactivation() {
		self::clear_total_cache(true);
	}

	public static function on_activation() {

		// multisite and network
		if ( is_multisite() && ! empty($_GET['networkwide']) ) {
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

		// multisite and network
		if ( is_multisite() && ! empty($_GET['networkwide']) ) {
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

		return $wpdb->get_col("SELECT blog_id FROM `$wpdb->blogs`");
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

		// check if clear request
		if ( empty($_GET['_cache']) OR ( $_GET['_cache'] !== 'clear' && $_GET['_cache'] !== 'clearurl' ) ) {
			return;
		}

        // validate nonce
        if ( empty($_GET['_wpnonce']) OR ! wp_verify_nonce($_GET['_wpnonce'], '_cache__clear_nonce') ) {
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
				if ($_GET['_cache'] == 'clearurl') {
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
			if ($_GET['_cache'] == 'clearurl') {
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
			add_action(
				'publish_future_' .$post_type,
				array(
					__CLASS__,
					'clear_total_cache'
				)
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

	public static function clear_home_page_cache() {

		call_user_func(
			array(
				self::$disk,
				'clear_home'
			)
		);

	}

	private static function _is_index() {
		return basename($_SERVER['SCRIPT_NAME']) != 'index.php';
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

		// conditional tags
		if ( self::_is_index() OR is_search() OR is_404() OR is_feed() OR is_trackback() OR is_robots() OR is_preview() OR post_password_required() ) {
			return true;
		}

		// DONOTCACHEPAGE check e.g. woocommerce
		if ( defined('DONOTCACHEPAGE') && DONOTCACHEPAGE ) {
			return true;
		}

		// Request method GET
		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || $_SERVER['REQUEST_METHOD'] != 'GET' ) {
			return true;
		}

		// Request with query strings
		if ( ! empty($_GET) && ! isset( $_GET['utm_source'], $_GET['utm_medium'], $_GET['utm_campaign'] ) && get_option('permalink_structure') ) {
			return true;
		}

		// if logged in
		if ( self::_is_logged_in() ) {
			return true;
		}

		return false;
	}

	private static function _store_asset_cache($data) {
		return $data;
	}

	public static function clear_total_cache() {
		// clear disk cache
		CWVPSB_Cache_Disk::clear_cache();
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

	public static function handle_cache() {

		// bypass cache
		if ( self::_bypass_cache() ) {
			return;
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
			ob_start('CWVPSB_Cache::set_cache');
			return;
		}

		// return cached asset
		call_user_func(
			array(
				self::$disk,
				'get_asset'
			)
		);
	}
}