<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

global $pagenow, $post;

	if ( ! empty( $_SERVER['REQUEST_URI'] ) ) {
		$referer = filter_var( wp_unslash( $_SERVER['REQUEST_URI'] ), FILTER_SANITIZE_URL );
		$referer = '&_wp_http_referer=' . rawurlencode( remove_query_arg( 'fl_builder', $referer ) );
	} else {
		$referer = '';
	}

	$has_cap = false;

	$capabilities = [
		'manage_options',
	];

	foreach ( $capabilities as $cap ) {
		if ( current_user_can( $cap ) ) {
			$has_cap = true;
			break;
		}
	}

	if ( $has_cap ) {
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
				'title'  => __( 'Settings', 'cwvpsb' ),
				'href'   => admin_url( 'options-general.php?page=cwvpsb'  ),
			]
		);
	}

	if ( current_user_can( 'manage_options' ) ) {
		/**
		 * Purge Cache.
		 */
		$action = 'cwvpsb_purge_cache';

		if ( cwvpsb_valid_key() ) {
			// Purge All.
			$wp_admin_bar->add_menu(
				[
					'parent' => 'wp-cwvpsb',
					'id'     => 'purge-all',
					'title'  => __( 'Clear all cache', 'cwvpsb' ),
					'href'   => wp_nonce_url( admin_url( 'admin-ajax.php?action=' . $action . '&type=all' . $referer ), $action . '_all' ),
				]
			);

		}
	}

function cwvpsb_valid_key() {
	return true;
}