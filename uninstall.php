<?php

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

if ( ! defined( 'CWVPSB_CACHE_DIR' ) ) {
	define( 'CWVPSB_CACHE_DIR', WP_CONTENT_DIR. '/cache/cwvpsb/static/' );
}

if ( ! defined( 'CWVPSB_IMAGE_DIR' ) ) {
	define('CWVPSB_IMAGE_DIR',plugin_dir_url(__FILE__).'images/');
}