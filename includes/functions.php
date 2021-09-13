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

add_action( 'admin_enqueue_scripts', 'cwvpsb_admin_style' );
function cwvpsb_admin_style($check) {
    if ( !is_admin() ) {
        return;
    }
    if($check != 'toplevel_page_cwvpsb_options'){
        return; 
    }
    wp_register_style( 'cwvpsb_admin_css', CWVPSB_PLUGIN_DIR_URI . 'includes/admin/style.css', true, CWVPSB_VERSION );
    wp_enqueue_style( 'cwvpsb_admin_css' );
}

$cwvpsb_settings = (array) get_option( $cwvpsb_settings->cache );

if ($cwvpsb_settings["cache_option"] == "1") {
    add_action(
        'plugins_loaded',
        array(
            'CWV_Cache',
            'instance'
    )
    );
}

register_activation_hook(
    __FILE__,
    array(
        'CWV_Cache',
        'on_activation'
    )
);
register_deactivation_hook(
    __FILE__,
    array(
        'CWV_Cache',
        'on_deactivation'
    )
);
register_uninstall_hook(
    __FILE__,
    array(
        'CWV_Cache',
        'on_uninstall'
    )
);
// autoload register
spl_autoload_register('cwvpsb_cache_autoload');

// autoload function
function cwvpsb_cache_autoload($class) {
    if ( in_array($class, array('CWV_Cache', 'CWV_Cache_Disk')) ) {
        require_once(
            sprintf(
                '%s/includes/cache/%s.class.php',
                CWVPSB_DIR,
                strtolower($class)
            )
        );
    }
}

//Load plugin textdomain
add_action( 'init', 'cwvpsb_load_textdomain' );
function cwvpsb_load_textdomain() {
  load_plugin_textdomain( 'cwvpsb_textdomain', false, dirname( CWVPSB_BASE ) . '/languages' ); 
}