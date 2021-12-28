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

$settings = cwvpsb_defaults();

if(isset($settings['cache_support'])){
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
    $page = 'cwvpsb';
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

function cwvpsb_defaults(){
    $defaults = array(
       'image_optimization' =>1,
       'webp_support' => 'auto',
       'lazyload_support'  => 1,
       'minification_support'  => 1,
       'unused_css_support'  => 1,
       'google_fonts_support'  => 1,
       'js_optimization' => 1,
       'delay_js' => 'js'
    );        
    $settings = get_option( 'cwvpsb_get_settings', $defaults );         
    return $settings;
}

add_action('admin_enqueue_scripts','cwvpsb_admin_enqueue');
function cwvpsb_admin_enqueue($check) {
    if ( !is_admin() ) {
        return;
    }
    if($check != 'toplevel_page_cwvpsb'){
        return; 
    }
    wp_register_style( 'cwvpsb-admin-css', CWVPSB_PLUGIN_DIR_URI . '/includes/admin/style.css', false, CWVPSB_VERSION );
    wp_enqueue_style( 'cwvpsb-admin-css' );

    wp_register_script( 'cwvpsb-admin-js', CWVPSB_PLUGIN_DIR_URI . 'includes/admin/script.js', array(), CWVPSB_VERSION , true );
    wp_enqueue_script( 'cwvpsb-admin-js' );
}

add_filter('cwvpsb_complete_html_after_dom_loaded','cwvpsb_google_fonts_swap');
function cwvpsb_google_fonts_swap( $html ) {
    $html = str_replace("&#038;display=swap", "", $html);
    $html = str_replace("googleapis.com/css?family", "googleapis.com/css?display=swap&family", $html);
    $html = str_replace("googleapis.com/css2?family", "googleapis.com/css2?display=swap&family", $html);
    $html = preg_replace("/(WebFontConfig\['google'\])(.+[\w])(.+};)/", '$1$2&display=swap$3', $html);
    return $html;
}

add_filter('cwvpsb_complete_html_after_dom_loaded','web_vitals_changes');
function web_vitals_changes($html){
    $settings = cwvpsb_defaults();
    if($settings['webp_support'] == 'auto'){
        return $html;
    }
        $guessurl = site_url();
        if ( ! $guessurl ) {
            $guessurl = wp_guess_url();
        }
        $base_url   = untrailingslashit( $guessurl );
        $upload     = wp_upload_dir();

        $tmpDoc     = new DOMDocument();
        libxml_use_internal_errors(true);
        $tmpDoc->loadHTML($html);

        $xpath      = new DOMXPath( $tmpDoc );
        $domImg     = $xpath->query( "//img[@src]");
            
        if(count($domImg)>0){
            foreach ($domImg as $key => $element) {
                $srcupdate = $element->getAttribute("src");
                if(strpos($srcupdate, $base_url)!==false){
                    //test page exists or not
                    $srcupdatePath = str_replace($upload['baseurl'], $upload['basedir'].'/web-vital-webp', $srcupdate);
                    $srcupdatePath = "$srcupdatePath.webp";
                    if(file_exists($srcupdatePath)){
                        $srcupdate = str_replace($upload['baseurl'], $upload['baseurl'].'/web-vital-webp', $srcupdate);
                        $srcupdate = "$srcupdate.webp";
                        $element->setAttribute("src", $srcupdate);  
                    }
                    
                }
                if($element->hasAttribute('srcset')){
                    $attrValue = $element->getAttribute("srcset");
                    
                    $srcsetArr = explode(',', $attrValue);
                    foreach ($srcsetArr as $i => $srcSetEntry) {
                        // $srcSetEntry is ie "image.jpg 520w", but can also lack width, ie just "image.jpg"
                        // it can also be ie "image.jpg 2x"
                        $srcSetEntry = trim($srcSetEntry);
                        $entryParts = preg_split('/\s+/', $srcSetEntry, 2);
                        if (count($entryParts) == 2) {
                            list($src, $descriptors) = $entryParts;
                        } else {
                            $src = $srcSetEntry;
                            $descriptors = null;
                        }

                        if(strpos($src, $base_url)!==false){
                            //test page exists or not
                            $srcupdatePath = str_replace($upload['baseurl'], $upload['basedir'].'/web-vital-webp', $src);
                            $srcupdatePath = "$srcupdatePath.webp";
                            if(file_exists($srcupdatePath)){
                                $webpUrl = str_replace($upload['baseurl'], $upload['baseurl'].'/web-vital-webp', $src);
                                $webpUrl .= '.webp';
                            }else{ $webpUrl = $src; }
                        }else{ $webpUrl = $src; }
                        if ($webpUrl !== false) {
                            $srcsetArr[$i] = $webpUrl . (isset($descriptors) ? ' ' . $descriptors : '');
                        }
                    }
                    $newSrcsetArr = implode(', ', $srcsetArr);
                    $attrValue = $element->setAttribute("srcset", $newSrcsetArr);
                }
            }
        }
        $html = $tmpDoc->saveHTML();
        return $html;
}