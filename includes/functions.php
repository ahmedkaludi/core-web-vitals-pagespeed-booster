<?php

// Add Settings Page
require_once CWVPSB_PLUGIN_DIR."includes/admin/settings.php";

add_filter('plugin_action_links_core-web-vitals-page-speed-booster/core-web-vitals-page-speed-booster.php', 'cwvpsb_add_settings_link');
function cwvpsb_add_settings_link( $links ) {
    $links[] = '<a href="' .
        admin_url( 'admin.php?page=cwvpsb-images' ) .
        '">' . 'Settings' . '</a>';
    return $links;
}

function cwvpsb_complete_html_after_dom_loaded( $content ) {
    $content = apply_filters('cwvpsb_complete_html_after_dom_loaded', $content);
    return $content;
}
add_action('wp', function(){ ob_start('cwvpsb_complete_html_after_dom_loaded'); }, 999);

add_action('init', 'cwvpsb_include_options_file' );
function cwvpsb_include_options_file(){ 
    $options = get_option( 'cwvpsb_settings' );  
    if ($options['cwvpsb_checkbox_webp'] == 1) {
       require_once CWVPSB_PLUGIN_DIR."includes/images/convert_webp.php";
    }
    if ($options['cwvpsb_checkbox_minify'] == 1) {
       require_once CWVPSB_PLUGIN_DIR."includes/css/minify.php";
    }
    if ($options['cwvpsb_checkbox_delayjs'] == 1) {
       require_once CWVPSB_PLUGIN_DIR."includes/javascript/delay_js.php";
    }
    if ($options['cwvpsb_checkbox_lazyload'] == 1) {
       require_once CWVPSB_PLUGIN_DIR."includes/images/lazy-loading.php";
    }
    if ($options['cwvpsb_checkbox_unused_css'] == 1) {
        require_once CWVPSB_PLUGIN_DIR."includes/css/unused_css.php";
    }
    if ($options['cwvpsb_checkbox_fonts'] == 1) {
        require_once CWVPSB_PLUGIN_DIR."includes/css/google-fonts.php";
    }
}

add_action( 'admin_enqueue_scripts', 'cwvpsb_admin_style' );
function cwvpsb_admin_style() {
    wp_register_style( 'cwvpsb_admin_css', CWVPSB_PLUGIN_DIR_URI . 'includes/admin/style.css', true, CWVPSB_VERSION );
    wp_enqueue_style( 'cwvpsb_admin_css' );
}
