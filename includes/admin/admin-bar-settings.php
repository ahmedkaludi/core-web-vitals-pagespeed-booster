<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

global $pagenow, $post;

	if ( ! empty( $_SERVER['REQUEST_URI'] ) ) {
		$cwvpsb_referer = filter_var( wp_unslash( $_SERVER['REQUEST_URI'] ), FILTER_SANITIZE_URL );
		$cwvpsb_referer = '&_wp_http_referer=' . rawurlencode( remove_query_arg( 'fl_builder', $cwvpsb_referer ) );
	} else {
		$cwvpsb_referer = '';
	}

	$cwvpsb_has_cap = false;

	$cwvpsb_capabilities = [
		'manage_options',
	];

	foreach ( $cwvpsb_capabilities as $cwvpsb_cap ) {
		if ( current_user_can( $cwvpsb_cap ) ) {
			$cwvpsb_has_cap = true;
			break;
		}
	}

	if ( $cwvpsb_has_cap ) {
		/**
		 * Parent.
		 */
		$wp_admin_bar->add_menu(
			[
				'id'    => 'wp-cwvpsb',
				'title' => 'CWV',
				'href'  => current_user_can( 'manage_options' ) ? admin_url( 'options-general.php?page=cwvpsb' ) : false,
			]
		);
	}

	if ( current_user_can( 'manage_options' ) ) {
		/**
		 * Settings.
		 */
		$wp_admin_bar->add_menu(
			[
				'parent' => 'wp-cwvpsb',
				'id'     => 'cwvpsb-settings',
				'title'  => __( 'Settings', 'core-web-vitals-pagespeed-booster' ),
				'href'   => admin_url( 'options-general.php?page=cwvpsb'  ),
			]
		);
	}

	$cwvpsb_settings = cwvpsb_defaults();
	if ( current_user_can( 'manage_options' ) && isset($cwvpsb_settings['cache_support']) && $cwvpsb_settings['cache_support'] == 1 ) {
		/**
		 * Purge Cache.
		 */
		$cwvpsb_action = 'cwvpsb_purge_cache';

		if ( cwvpsb_valid_key() ) {
			// Purge All.
			$wp_admin_bar->add_menu(
				[
					'parent' => 'wp-cwvpsb',
					'id'     => 'purge-all',
					'title'  => __( 'Clear all cache', 'core-web-vitals-pagespeed-booster' ),
					'href'   => wp_nonce_url( admin_url( 'admin-ajax.php?action=' . $cwvpsb_action . '&type=all' . $cwvpsb_referer ), $cwvpsb_action . '_all' ),
				]
			);

		}
	}

function cwvpsb_valid_key() {
	return true;
}
