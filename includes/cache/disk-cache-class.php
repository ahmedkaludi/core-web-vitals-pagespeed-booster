<?php
/**
* CWVPSB_Cache_Disk
*
* @since 1.0.0
*/
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// load if network
if ( ! function_exists('is_plugin_active_for_network') ) {
	require_once( ABSPATH. 'wp-admin/includes/plugin.php' );
}

final class CWVPSB_Cache_Disk {

	const FILE_HTML = 'index.html';
	const FILE_GZIP = 'index.html.gz';

	public static function is_permalink() {
		if(is_multisite() && is_plugin_active_for_network(CWVPSB_BASE)){
			return get_site_option('permalink_structure');
		}
		else{
			return get_option('permalink_structure');
		}
		
	}

	public static function store_asset($data) {

		// check if empty
		if ( empty($data) ) {
			wp_die('Asset is empty.');
		}

		// save asset
		self::_create_files(
			$data
		);

	}

	public static function check_asset() {
		return is_readable(
			self::_file_html()
		);
	}

	public static function delete_asset($url) {

		// check if url empty
		if ( empty($url) ) {
			wp_die('URL is empty.');
		}

		// delete
		self::_clear_dir(
			self::_file_path($url)
		);
	}

	public static function clear_cache() {
		self::_clear_dir(
			CWVPSB_CACHE_DIR
		);
	}

	public static function clear_home() {
		
		if ( is_multisite() && is_plugin_active_for_network(CWVPSB_BASE) ) {
			$cwvps_siteurl=get_option('siteurl');
		}
		else{
			$cwvps_siteurl=get_site_option('siteurl');
		}
		$path = sprintf(
			'%s%s%s%s',
			CWVPSB_CACHE_DIR,
			DIRECTORY_SEPARATOR,
			preg_replace('#^https?://#', '', $cwvps_siteurl),
			DIRECTORY_SEPARATOR
		);

		@unlink($path.self::FILE_HTML);
		@unlink($path.self::FILE_GZIP);
	}

	public static function get_asset($data) {
		// set cache handler header
		header('X-Cache-Handler: php');

		// get if-modified request headers
		if ( function_exists( 'apache_request_headers' ) ) {
			$headers = apache_request_headers();
			$http_if_modified_since = ( isset( $headers[ 'If-Modified-Since' ] ) ) ? sanitize_text_field( wp_unslash( $headers[ 'If-Modified-Since' ] ) ) : '';
			$http_accept = ( isset( $headers[ 'Accept' ] ) ) ? sanitize_text_field( wp_unslash( $headers[ 'Accept' ] ) ) : '';
			$http_accept_encoding = ( isset( $headers[ 'Accept-Encoding' ] ) ) ? sanitize_text_field( wp_unslash( $headers[ 'Accept-Encoding' ] ) )  : '';
		} else {
			$http_if_modified_since = ( isset( $_SERVER[ 'HTTP_IF_MODIFIED_SINCE' ] ) ) ? sanitize_text_field( wp_unslash( $_SERVER[ 'HTTP_IF_MODIFIED_SINCE' ] ) )  : '';
			$http_accept = ( isset( $_SERVER[ 'HTTP_ACCEPT' ] ) ) ? sanitize_text_field( wp_unslash( $_SERVER[ 'HTTP_ACCEPT' ] ) )  : '';
			$http_accept_encoding = ( isset( $_SERVER[ 'HTTP_ACCEPT_ENCODING' ] ) ) ? sanitize_text_field( wp_unslash( $_SERVER[ 'HTTP_ACCEPT_ENCODING' ] ) )  : '';
		}
		$server_protocol = isset( $_SERVER['SERVER_PROTOCOL']  ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_PROTOCOL'] ) ) : 'http/1.1';
		// check modified since with cached file and return 304 if no difference
		if ( $http_if_modified_since && ( strtotime( $http_if_modified_since ) == filemtime( self::_file_html() ) ) ) {
			header( $server_protocol  . ' 304 Not Modified', true, 304 );
			return $data;
		}
		// check encoding and deliver gzip file if support
		if ( $http_accept_encoding && ( strpos($http_accept_encoding, 'gzip') !== false ) && is_readable( self::_file_gzip() )  ) {
			header('Content-Encoding: gzip');
			return cwvpsb_read_file_contents( self::_file_gzip() );
		}

		// deliver cached file (default)
		return cwvpsb_read_file_contents( self::_file_html() );
	
	}
	
	public static function get_asset_readfile() {
		// set cache handler header
		header('X-Cache-Handler: php');

		// get if-modified request headers
		if ( function_exists( 'apache_request_headers' ) ) {
			$headers = apache_request_headers();
			$http_if_modified_since =  isset( $headers[ 'If-Modified-Since' ] ) ? sanitize_textarea_field( wp_unslash( $headers[ 'If-Modified-Since' ] ) ) : '';
			$http_accept =  isset( $headers[ 'Accept' ] )  ? sanitize_textarea_field( wp_unslash( $headers[ 'Accept' ] ) ) : '';
			$http_accept_encoding =  isset( $headers[ 'Accept-Encoding' ] )  ? sanitize_textarea_field( wp_unslash( $headers[ 'Accept-Encoding' ] ) )  : '';
		} else {
			$http_if_modified_since =  isset( $_SERVER[ 'HTTP_IF_MODIFIED_SINCE' ] )  ? sanitize_textarea_field( wp_unslash( $_SERVER[ 'HTTP_IF_MODIFIED_SINCE' ]) ) : '';
			$http_accept = isset( $_SERVER[ 'HTTP_ACCEPT' ] )  ? sanitize_textarea_field( wp_unslash( $_SERVER[ 'HTTP_ACCEPT' ] ) ) : '';
			$http_accept_encoding =  isset( $_SERVER[ 'HTTP_ACCEPT_ENCODING' ] )  ? sanitize_textarea_field( wp_unslash( $_SERVER[ 'HTTP_ACCEPT_ENCODING' ] ) ) : '';
		}

		// check modified since with cached file and return 304 if no difference
		$server_protocol = isset( $_SERVER['SERVER_PROTOCOL']  ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_PROTOCOL'] ) ) : 'http/1.1'; 
		if ( $http_if_modified_since && ( strtotime( $http_if_modified_since ) == filemtime( self::_file_html() ) ) ) {
			header( $server_protocol . ' 304 Not Modified', true, 304 );
			exit;
		}
		// check encoding and deliver gzip file if support
		if ( $http_accept_encoding && ( strpos($http_accept_encoding, 'gzip') !== false ) && is_readable( self::_file_gzip() )  ) {
			header('Content-Encoding: gzip');
			$op_content = cwvpsb_read_file_contents( self::_file_gzip() );
			echo $op_content; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped --  Reason: Output is already escaped
			exit;
		}

		// deliver cached file (default)
		$op_content = cwvpsb_read_file_contents( self::_file_html() );
		echo $op_content; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped --  Reason: Output is already escaped
		exit;
	}

	private static function _cache_signatur() {
		return sprintf(
			"\n\n<!-- %s @ %s",
			'Cached Copy, Generated By Core Web Vitals & PageSpeed Booster',
			date_i18n(
				'd.m.Y H:i:s',
				current_time('timestamp')
			)
		);
	}

	private static function _create_files($data) {

		// create folder
		if ( ! wp_mkdir_p( self::_file_path() ) ) {
			wp_die('Unable to create directory.');
		}

		// get base signature
		$cache_signature = self::_cache_signatur();

		// create files
		self::_create_file( self::_file_html(), $data.$cache_signature." -->" ); 
	}

	private static function _create_file($file, $data)
	{
		if (!$handle = @fopen($file, 'wb')) {
			wp_die('Can not write to file.');

			@fwrite($handle, $data); //phpcs:ignore
			fclose($handle); //phpcs:ignore
			clearstatcache();

			// set permissions
			$stat = @stat(dirname($file)); //phpcs:ignore
			$perms = $stat['mode'] & 0007777;
			$perms = $perms & 0000666;
			@chmod($file, $perms); //phpcs:ignore

			clearstatcache();
		}
}
	private static function _clear_dir($dir) {

		// remove slashes
		$dir = untrailingslashit($dir);

		// check if dir
		if ( ! is_dir($dir) ) {
			return;
		}

		// get dir data
		$objects = array_diff(
			scandir($dir),
			array('..', '.')
		);

		if ( empty($objects) ) {
			return;
		}

		foreach ( $objects as $object ) {
			// full path
			$object = $dir. DIRECTORY_SEPARATOR .$object;

			// check if directory
			if ( is_dir($object) ) {
				self::_clear_dir($object);
			} else {
				unlink($object);
			}
		}

		// delete
		cwvpsb_remove_directory($dir);

		// clears file status cache
		clearstatcache();
	}

	private static function _file_path($path = NULL) {

		$path = sprintf(
			'%s%s%s%s',
			CWVPSB_CACHE_DIR,
			DIRECTORY_SEPARATOR,
			parse_url(
				'http://' .strtolower(sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST']) ) ), //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
				PHP_URL_HOST
			),
			parse_url(
				( $path ? $path : sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) ), //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
				PHP_URL_PATH
			)
		);

		if ( is_file($path) > 0 ) {
			wp_die('Path is not valid.');
		}

		return trailingslashit($path);
	}

	private static function _file_html() {
		return self::_file_path(). self::FILE_HTML;
	}

	private static function _file_gzip() {
		return self::_file_path(). self::FILE_GZIP;
	}
}