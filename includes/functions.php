<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Add Settings Page
require_once CWVPSB_PLUGIN_DIR."includes/admin/settings.php";
require_once CWVPSB_PLUGIN_DIR."includes/gravatar.php";

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
    wp_enqueue_script( 'cwvpsb_admin-script', CWVPSB_PLUGIN_DIR_URI . 'includes/admin/script.js', array('jquery'), CWVPSB_VERSION, true );
}

$cwvpsb_settings = (array) get_option( $cwvpsb_settings->cache );

if (isset($cwvpsb_settings["cache_option"]) && $cwvpsb_settings["cache_option"] == "1") {
    add_action(
        'plugins_loaded',
        array(
            'CWVPSB_Cache',
            'instance'
    )
    );
}

register_activation_hook(
    __FILE__,
    array(
        'CWVPSB_Cache',
        'on_activation'
    )
);
register_deactivation_hook(
    __FILE__,
    array(
        'CWVPSB_Cache',
        'on_deactivation'
    )
);
register_uninstall_hook(
    __FILE__,
    array(
        'CWVPSB_Cache',
        'on_uninstall'
    )
);
// autoload register
spl_autoload_register('cwvpsb_cache_autoload');

// autoload function
function cwvpsb_cache_autoload($class) {
    require_once(
        sprintf(
            '%s/includes/cache/cache-class.php',
            CWVPSB_DIR,
            strtolower($class)
        )
    );
    require_once(
        sprintf(
            '%s/includes/cache/disk-cache-class.php',
            CWVPSB_DIR,
             strtolower($class)
        )
    );
}

//Load plugin textdomain
add_action( 'init', 'cwvpsb_load_textdomain' );
function cwvpsb_load_textdomain() {
  load_plugin_textdomain( 'cwvpsb_textdomain', false, dirname( CWVPSB_BASE ) . '/languages' ); 
}

add_action('wp_ajax_cwvpsb_clear_cached_css', 'cwvpsb_clear_cached_css');
function cwvpsb_clear_cached_css(){
        if(isset($_POST['nonce_verify']) && !wp_verify_nonce($_POST['nonce_verify'],'cwv-security-nonce')){
            echo json_encode(array("status"=> 400, "msg"=>esc_html__("Security verification failed, Refresh the page", 'cwvpsb') ));die;
        }
        $clean_types = array('css');
        if(!in_array($_POST['cleaning'], $clean_types)){
            echo json_encode(array("status"=> 400, "msg"=>esc_html__("Cache type not found", 'cwvpsb') ));die;
        }
        $cleaning = $_POST['cleaning'];

        $upload_dir = wp_upload_dir(); 
        
        //Clean css
        if($cleaning == 'css'){
            $user_dirname = $upload_dir['basedir'] . '/' . 'web_vital';
            $dir_handle = opendir($user_dirname);
            if (!$dir_handle){
              echo json_encode(array("status"=> 400, "msg"=>esc_html__("cache not found", 'cwvpsb') ));die;
            }
            while($file = readdir($dir_handle)) {
                if (strpos($file, '.css') !== false){
                    unlink($user_dirname."/".$file);
                }
            }
            closedir($dir_handle);
        }
        echo json_encode(array("status"=> 200, "msg"=>esc_html__("CSS Cleared", 'cwvpsb') ));die;
    }

function cwvpsb_admin_link($tab = '', $args = array()){   
    $page = 'cwvpsb_options';
    if ( ! is_multisite() ) {
        $link = admin_url( 'admin.php?page=' . $page );
    }
    else {
        $link = network_admin_url( 'admin.php?page=' . $page );
    }

    if ( $tab ) {
        $link .= '&tab=' . $tab;
    }

    if ( $args ) {
        foreach ( $args as $arg => $value ) {
            $link .= '&' . $arg . '=' . urlencode( $value );
        }
    }

    return esc_url($link);
}
function cwvpsb_get_tab( $default = '', $available = array() ) {

    $tab = isset( $_GET['tab'] ) ? sanitize_text_field($_GET['tab']) : $default;
        
    if ( ! in_array( $tab, $available ) ) {
        $tab = $default;
    }
    return $tab;
}
function cwvpsb_default_settings(){
    
    $defaults_images = array(
    'webp_option' => 'on',
    'lazyload_option' => 'on',
    );
    if(get_option('images') == ''){
        return update_option('images', $defaults_images);
    }

    $defaults_css = array(
    'minify_option' => 'on',
    'unused_css_option' => 'on',
    'fonts_option' => 'on'
    );
    if(get_option('css') == ''){
        return update_option('css', $defaults_css);
    }

    $defaults_js = array(
    'delayjs_option' => 'on'
    );
    if(get_option('js') == ''){
        return update_option('js', $defaults_js);
    }

    $defaults_cache = array(
    'cache_option' => 'on'
    );
    if(get_option('cache') == ''){
        return update_option('cache', $defaults_cache);
    }
}