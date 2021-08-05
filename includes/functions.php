<?php

// Register settings menu
function cwvpsb_register_menu() {
    add_menu_page('Speed Booster', 'Speed Booster', 'manage_options', 'cwvpsb-images', 'cwvpsb_add_settings','dashicons-superhero');
}
add_action('admin_menu', 'cwvpsb_register_menu');

// Settings page
function cwvpsb_add_settings() {

    // Validate nonce
    if (isset($_POST['submit']) && !wp_verify_nonce($_POST['cwvpsb-nonce-settings'], 'cwvpsb-nonce')) {
        echo '<div class="notice notice-error"><p>Nonce verification failed</p></div>';
        exit;
    }
    // Add Settings Page
    require_once CWVPSB_PLUGIN_DIR."includes/admin/settings.php";
}

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

add_action('plugins_loaded', 'ampforwp_include_options_file' );
function ampforwp_include_options_file(){   
    $check_webp = get_option('cwvpsb_check_webp');
    if ($check_webp == true) {
       require_once CWVPSB_PLUGIN_DIR."includes/images/convert_webp.php";
    }
    $check_minify = get_option('cwvpsb_check_minification');
    if ($check_minify == true) {
       require_once CWVPSB_PLUGIN_DIR."includes/minification/minify.php";
    }
    $check_js = get_option('cwvpsb_check_javascript_delay');
    if ($check_js  == true) {
       require_once CWVPSB_PLUGIN_DIR."includes/javascript/delay_js.php";
    }
}