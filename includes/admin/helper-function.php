<?php

/**
 * Helper Functions
 *
 * @package     saswp
 * @subpackage  Helper/Templates
 * @copyright   Copyright (c) 2016, René Hermenau
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.4.0
 */
// Exit if accessed directly
if( !defined( 'ABSPATH' ) )
    exit;

/**
 * Helper method to check if user is in the plugins page.
 *
 * @author René Hermenau
 * @since  1.4.0
 *
 * @return bool
 */
function cwv_is_plugins_page() {
    global $pagenow;

    return ( 'plugins.php' === $pagenow );
}

/**
 * display deactivation logic on plugins page
 * 
 * @since 1.4.0
 */


function cwv_add_deactivation_feedback_modal() {
    
    if( !is_admin() && !cwv_is_plugins_page()) {
        return;
    }

    $current_user = wp_get_current_user();
    if( !($current_user instanceof WP_User) ) {
        $email = '';
    } else {
        $email = trim( $current_user->user_email );
    }

    require_once CWVPSB_PLUGIN_DIR."includes/admin/deactivate-feedback.php";
}

/**
 * send feedback via email
 * 
 * @since 1.4.0
 */
function cwv_send_feedback() {

    if( isset( $_POST['data'] ) ) {
        parse_str( $_POST['data'], $form );
    }

    $text = '';
    if( isset( $form['cwv_disable_text'] ) ) {
        $text = implode( "\n\r", $form['cwv_disable_text'] );
    }

    $headers = array();

    $from = isset( $form['cwv_disable_from'] ) ? $form['cwv_disable_from'] : '';
    if( $from ) {
        $headers[] = "From: $from";
        $headers[] = "Reply-To: $from";
    }

    $subject = isset( $form['cwv_disable_reason'] ) ? $form['cwv_disable_reason'] : '(no reason given)';

    if($subject == 'technical issue'){

          $text = trim($text);

          if(!empty($text)){

            $text = 'technical issue description: '.$text;

          }else{

            $text = 'no description: '.$text;
          }
      
    }

    $success = wp_mail( 'makebetter@magazine3.in', $subject, $text, $headers );

    die();
}
add_action( 'wp_ajax_cwv_send_feedback', 'cwv_send_feedback' );



add_action( 'admin_enqueue_scripts', 'cwv_enqueue_makebetter_email_js' );

function cwv_enqueue_makebetter_email_js(){

    if( !is_admin() && !cwv_is_plugins_page()) {
        return;
    }

    wp_enqueue_script( 'cwv-make-better-js', CWVPSB_PLUGIN_DIR_URI . 'includes/admin/make-better-admin.js', array( 'jquery' ), CWVPSB_VERSION);

    wp_enqueue_style( 'cwv-make-better-css', CWVPSB_PLUGIN_DIR_URI . 'includes/admin/make-better-admin.css', false , CWVPSB_VERSION );
}

if( is_admin() && cwv_is_plugins_page()) {
    add_filter('admin_footer', 'cwv_add_deactivation_feedback_modal');
}


