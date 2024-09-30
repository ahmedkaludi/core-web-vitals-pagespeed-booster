<?php

/**
 * Helper Functions
 *
 * @package     cwvpb
 * @subpackage  Helper/Templates
 * @copyright   Copyright (c) 2016, René Hermenau
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.4.0
 */
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helper method to check if user is in the plugins page.
 *
 * @author René Hermenau
 * @since  1.4.0
 *
 * @return bool
 */
function cwv_is_plugins_page() {
	if ( function_exists( 'get_current_screen' ) ) {
		$screen = get_current_screen();
		if ( is_object( $screen ) ) {
			if ( $screen->id == 'plugins' || $screen->id == 'plugins-network' ) {
				return true;
			}
		}
	}
	return false;
}

/**
 * display deactivation logic on plugins page
 *
 * @since 1.4.0
 */
function cwv_add_deactivation_feedback_modal() {

	if ( ! is_admin() && ! cwv_is_plugins_page() ) {
		return;
	}

	$current_user = wp_get_current_user();
	if ( ! ( $current_user instanceof WP_User ) ) {
		$email = '';
	} else {
		$email = trim( $current_user->user_email );
	}

	require_once CWVPSB_PLUGIN_DIR . 'includes/admin/deactivate-feedback.php';
}

/**
 * send feedback via email
 *
 * @since 1.4.0
 */
function cwv_send_feedback() {

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json(
			array(
				'status' => 400,
				'msg'    => esc_html__( 'Permission verification failed', 'cwvpsb' ),
			)
		);
	}

	if ( isset( $_POST['data'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reason: Nonce verification is not required
		parse_str( wp_unslash( $_POST['data'] ) , $form ); //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing -- Reason: Content are sanitized later
	}

    

	$text = '';
	if ( isset( $form['cwv_disable_text'] ) ) {
		$text = implode( "\n\r", wp_unslash( $form['cwv_disable_text'] ) );
	}
	$headers = array();

	$from = isset( $form['cwv_disable_from'] ) ? $form['cwv_disable_from'] : '';
	if ( $from ) {
		$headers[] = "From: $from";
		$headers[] = "Reply-To: $from";
	}

	$subject = isset( $form['cwv_disable_reason'] ) ? $form['cwv_disable_reason'] : '(no reason given)';

	$subject = $subject . ' - Core Web Vitals & PageSpeed Booster';

	if ( $subject == 'technical - Core Web Vitals & PageSpeed Booster' ) {

			$text = trim( $text );

		if ( ! empty( $text ) ) {

			$text = 'technical issue description: ' . $text;

		} else {

			$text = 'no description: ' . $text;
		}
	}

	$success = wp_mail( 'makebetter@magazine3.in', $subject, $text, $headers );

	wp_die();
}
add_action( 'wp_ajax_cwv_send_feedback', 'cwv_send_feedback' );



add_action( 'admin_enqueue_scripts', 'cwv_enqueue_makebetter_email_js' );

function cwv_enqueue_makebetter_email_js() {

	if ( ! is_admin() && ! cwv_is_plugins_page() ) {
		return;
	}
	$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
	wp_enqueue_script( 'cwv-make-better-js', CWVPSB_PLUGIN_DIR_URI . "includes/admin/make-better-admin{$min}.js", array( 'jquery' ), CWVPSB_VERSION, true );
	wp_localize_script(
		'cwv-make-better-js',
		'cwvpsb_script_vars',
		array(
			'nonce' => wp_create_nonce( 'cwvpsb-admin-nonce' ),
		)
	);
	wp_enqueue_style( 'cwv-make-better-css', CWVPSB_PLUGIN_DIR_URI . "includes/admin/make-better-admin{$min}.css", false, CWVPSB_VERSION );
}


add_filter( 'admin_footer', 'cwv_add_deactivation_feedback_modal' );


function cwvpbs_get_total_urls() {

	global $wpdb;
	$total_count = 0;
	$settings    = cwvpsb_defaults();
	$urls_to     = array();
	if ( isset( $settings['critical_css_on_home'] ) && $settings['critical_css_on_home'] == 1 ) {
		$urls_to[] = get_home_url();
		$urls_to[] = get_home_url() . '/';
		$urls_to[] = home_url( '/' );
		$urls_to[] = site_url( '/' );
	}

	$total_count += count( array_unique( $urls_to ) );

	$post_types = array();
	if ( ! empty( $settings['critical_css_on_cp_type'] ) ) {
		foreach ( $settings['critical_css_on_cp_type'] as $key => $value ) {
			if ( $value ) {
				$post_types[] = $key;
			}
		}
	}

	if ( ! empty( $post_types ) ) {
		$postimp      = "'" . implode( "', '", $post_types ) . "'";
		$total_count += $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->posts Where post_status=%s AND post_type IN (%s);", 'publish', $postimp ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	$taxonomy_types = array();
	if ( ! empty( $settings['critical_css_on_tax_type'] ) ) {
		foreach ( $settings['critical_css_on_tax_type'] as $key => $value ) {
			if ( $value ) {
				$taxonomy_types[] = $key;
			}
		}
	}

	if ( ! empty( $taxonomy_types ) ) {
		$postimp = "'" . implode( "', '", $taxonomy_types ) . "'";

		$total_count += $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->term_taxonomy Where taxonomy IN (%s);", $postimp ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	return $total_count;
}

function cwvpb_get_current_url() {

	$link = 'http';

	if ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' ) {
		$link = 'https';
	}
	$link .= '://';

	if ( isset( $_SERVER['HTTP_HOST'] ) ) {
		$link .= wp_unslash( $_SERVER['HTTP_HOST'] ); //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Reason: Sanitization not required
	}

	if ( isset( $_SERVER['REQUEST_URI'] ) ) {
		$link .= wp_unslash( $_SERVER['REQUEST_URI'] ); //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Reason: Sanitization not required
	}
	return $link;
}

add_action( 'wp_ajax_cwvpsb_send_query_message', 'cwvpsb_send_query_message' );

function cwvpsb_sanitize_textarea_field( $str ) {

	if ( is_object( $str ) || is_array( $str ) ) {
		return '';
	}

	$str = (string) $str;

	$filtered = wp_check_invalid_utf8( $str );

	if ( strpos( $filtered, '<' ) !== false ) {
		$filtered = wp_pre_kses_less_than( $filtered );
		// This will strip extra whitespace for us.
		$filtered = wp_strip_all_tags( $filtered, false );

		// Use HTML entities in a special case to make sure no later
		// newline stripping stage could lead to a functional tag.
		$filtered = str_replace( "<\n", "&lt;\n", $filtered );
	}

	$filtered = trim( $filtered );

	$found = false;
	while ( preg_match( '/%[a-f0-9]{2}/i', $filtered, $match ) ) {
		$filtered = str_replace( $match[0], '', $filtered );
		$found    = true;
	}

	if ( $found ) {
		// Strip out the whitespace that may now exist after removing the octets.
		$filtered = trim( preg_replace( '/ +/', ' ', $filtered ) );
	}

	return $filtered;
}

function cwvpsb_send_query_message() {

	if ( ! isset( $_POST['cwvpsb_wpnonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( wp_unslash( $_POST['cwvpsb_wpnonce'] ), 'cwvpsb-admin-nonce' ) ) {  //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Reason: using custom Nonce verification
		return;
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$message = isset( $_POST['message'] ) ? cwvpsb_sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : ''; //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Reason: Sanitization is done using cwvpsb_sanitize_textarea_field
	$email   = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

	if ( function_exists( 'wp_get_current_user' ) ) {

		$user = wp_get_current_user();

		$message = '<p>' . $message . '</p><br><br>' . 'Query from Core Web Vitals  &amp; PageSpeed Booster plugin support tab';

		$user_data  = $user->data;
		$user_email = $user_data->user_email;

		if ( $email ) {
			$user_email = $email;
		}
		// php mailer variables
		$sendto  = 'team@magazine3.in';
		$subject = 'Core Web Vitals &amp; PageSpeed Booster Query';

		$headers[] = 'Content-Type: text/html; charset=UTF-8';
		$headers[] = 'From: ' . esc_attr( $user_email );
		$headers[] = 'Reply-To: ' . esc_attr( $user_email );
		// Load WP components, no themes.

		$sent = wp_mail( $sendto, $subject, $message, $headers );

		if ( $sent ) {

			wp_send_json( array( 'status' => 't' ) );

		} else {

			wp_send_json( array( 'status' => 'f' ) );

		}
	}

	wp_die();
}
