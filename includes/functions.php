<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Add Settings Page
require_once CWVPSB_PLUGIN_DIR."includes/admin/settings.php";
require_once CWVPSB_PLUGIN_DIR."includes/gravatar.php";
// load if network
if ( ! function_exists('is_plugin_active_for_network') ) {
    require_once( ABSPATH. 'wp-admin/includes/plugin.php' );
}
add_filter('plugin_action_links_' . CWVPSB_BASE, 'cwvpsb_add_settings_link');
function cwvpsb_add_settings_link( $links ) {
    $links[] = '<a href="' .
        esc_url(admin_url( 'admin.php?page=cwvpsb' )) .
        '">' . esc_html__( 'Settings','cwvpsb' ). '</a>';
    return $links;
}

function cwvpsb_complete_html_after_dom_loaded( $content ) {
    global $wp;
    if(isset($wp->request) && strpos($wp->request,'robots.txt')!==false){return $content;}
    if(function_exists('is_feed')&& is_feed()){return $content;}
    $content = apply_filters('cwvpsb_complete_html_after_dom_loaded', $content);
    return $content;
}
add_action('wp', function(){
    
    if ( cwvpsb_amp_support_enabled() ) { return; }
 ob_start('cwvpsb_complete_html_after_dom_loaded'); }, 999);

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
            wp_send_json(array("status"=> 400, "msg"=>esc_html__("Security verification failed, Refresh the page", 'cwvpsb') ));
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json(array("status"=> 400, "msg"=>esc_html__("Permission verification failed", 'cwvpsb') ));
        }
        $clean_types = array('css');
        if(!in_array($_POST['cleaning'], $clean_types)){
            wp_send_json(array("status"=> 400, "msg"=>esc_html__("Cache type not found", 'cwvpsb') ));
        }
        $cleaning = isset($_POST['cleaning'])?sanitize_text_field($_POST['cleaning']):'';

        $upload_dir = wp_upload_dir(); 
        
        //Clean css
        if($cleaning == 'css'){
            $user_dirname = $upload_dir['basedir'] . '/' . 'web_vital';
            $dir_handle = opendir($user_dirname);
            if (!$dir_handle){
                wp_send_json(array("status"=> 400, "msg"=>esc_html__("cache not found", 'cwvpsb') ));
            }
            while($file = readdir($dir_handle)) {
                if (strpos($file, '.css') !== false){
                    unlink($user_dirname."/".$file);
                }
            }
            closedir($dir_handle);
        }
        wp_send_json(array("status"=> 200, "msg"=>esc_html__("CSS Cleared", 'cwvpsb') ));
    }

function cwvpsb_admin_link($tab = '', $args = array()){   
    $page = 'cwvpsb';
    if ( ! is_multisite() ) {
        $link = admin_url( 'admin.php?page=' . $page );
    }
    else {
        $link = get_dashboard_url(0,'admin.php?page=' . $page );
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
       'unused_css_support'  => 0,
       'google_fonts_support'  => 1,
       'js_optimization' => 1,
       'delay_js' => 'php',
       'delay_js_mobile' => 'php',
       'whitelist_css'=>array(),
       'critical_css_support'=>1,
       'cache_support_method'=>'Highly Optimized',
       'cache_support'=>1,
       'advance_support'=>'',
       'exclude_delay_js'=>'',
       'critical_css_on_home' => 1,
       'critical_css_on_cp_type' => array(
            'post' => 1
       ),
       'delete_on_uninstall' => 0,
       'cache_flush_on'=>array(),
       'cache_autoclear'=>'never',
       'cache_last_autoclear'=> 0,
       'image_optimization_alt'=>0
    ); 
    if ( is_multisite() && is_plugin_active_for_network(CWVPSB_BASE) ) {
        $settings = get_site_option( 'cwvpsb_get_settings', $defaults );
    }  
    else
    {
        $settings = get_option( 'cwvpsb_get_settings', $defaults );
    }     
       
    $settings['unused_css_support'] = 0;
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
    $min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';	
    wp_enqueue_script('cwvpsb-datatable-script', CWVPSB_PLUGIN_DIR_URI . '/includes/admin/js/jquery.dataTables.min.js', ['jquery']);
    wp_enqueue_style( 'cwvpsb-datatable-style', CWVPSB_PLUGIN_DIR_URI . '/includes/admin/js/jquery.dataTables.min.css' );

    wp_register_style( 'cwvpsb-admin-css', CWVPSB_PLUGIN_DIR_URI . "/includes/admin/style{$min}.css", false, CWVPSB_VERSION );
    wp_enqueue_style( 'cwvpsb-admin-css' );

    $data = array(
        'cwvpsb_security_nonce'=> wp_create_nonce('cwvpsb_security_nonce') 
    );
    wp_register_script( 'cwvpsb-admin-js', CWVPSB_PLUGIN_DIR_URI . "includes/admin/script{$min}.js", array('cwvpsb-datatable-script'), CWVPSB_VERSION , true );
    $data = apply_filters('cwvpsb_localize_filter',$data,'cwvpsb_localize_data');		
    wp_localize_script( 'cwvpsb-admin-js', 'cwvpsb_localize_data', $data );
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

add_filter('cwvpsb_complete_html_after_dom_loaded','cwvpsb_web_vitals_changes');
function cwvpsb_web_vitals_changes($html){
    if(!$html){ return $html; }
    if(function_exists('is_feed')&& is_feed()){return $html;}
    $settings = cwvpsb_defaults();
    if (is_admin()) {
        return $html;
    }
    if($settings['webp_support'] == 'auto'){
        return $html;
    }
    if ( function_exists('is_checkout') && is_checkout() ) {
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
		$tmpDoc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
		libxml_use_internal_errors(false);
        $xpath      = new DOMXPath( $tmpDoc );
        $domImg     = $xpath->query( "//img[@src]");
            
        if(count($domImg)>0){
            foreach ($domImg as $key => $element) {
                $srcupdate = $element->getAttribute("src");
                if(strpos($srcupdate, $base_url)!==false){
                    //test page exists or not
                    $srcupdatePath = str_replace($upload['baseurl'], $upload['basedir'].'/cwv-webp-images', $srcupdate);
                    $srcupdatePath = "$srcupdatePath.webp";
                    if(file_exists($srcupdatePath)){
                        $srcupdate = str_replace($upload['baseurl'], $upload['baseurl'].'/cwv-webp-images', $srcupdate);
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
                            $srcupdatePath = str_replace($upload['baseurl'], $upload['basedir'].'/cwv-webp-images', $src);
                            $srcupdatePath = "$srcupdatePath.webp";
                            if(file_exists($srcupdatePath)){
                                $webpUrl = str_replace($upload['baseurl'], $upload['baseurl'].'/cwv-webp-images', $src);
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

add_action( 'current_screen', 'cwvpsb_remove_wp_footer_notice' );
function cwvpsb_remove_wp_footer_notice() {
    if ( is_admin() ) {
        $my_current_screen = get_current_screen();
        if ( isset( $my_current_screen->base ) && 'toplevel_page_cwvpsb' === $my_current_screen->base ) {
            add_filter( 'admin_footer_text', '__return_empty_string', 11 );
            add_filter( 'update_footer',     '__return_empty_string', 11 );
        }
    }
}

add_action('pre_amp_render_post','cwvpsb_amp_support');
function cwvpsb_amp_support(){
    remove_all_filters( 'cwvpsb_complete_html_after_dom_loaded' );
}

add_action('wp' , 'cwvpsb_on_specific_url');
function cwvpsb_on_specific_url(){
    $settings = cwvpsb_defaults(); 
    $url = isset($settings['advance_support'])?$settings['advance_support']:'';
    if (empty($url)) {
        return;
    }
    $url_id = url_to_postid( $url );
    $id = get_the_ID();
    if (is_home() &&  $url == home_url( '/' )) {
        if ( is_multisite() && is_plugin_active_for_network(CWVPSB_BASE) ) {
            $page_for_posts  =  get_site_option( 'page_for_posts' );
        }  
        else
        {
            $page_for_posts  =  get_option( 'page_for_posts' );
        }  
        
        $post = get_post($page_for_posts);
        if($post && isset($post->ID)){
            $url_id = $post->ID;
        }
    }
    if ($url_id != $id ) {
        add_filter( 'cwvpsb_complete_html_after_dom_loaded', '__return_false' );
    }
}

add_filter('the_content', 'cwvpsb_iframe_delay');
       
function cwvpsb_iframe_delay($content) {
    if((function_exists('is_feed')&& is_feed()) || (function_exists('ampforwp_is_amp_endpoint') && ampforwp_is_amp_endpoint()) || (function_exists( 'is_amp_endpoint' ) && is_amp_endpoint())){return $content;}
    $content = preg_replace('/<iframe[^>]*src="(?:https?:)?\/\/(?:www\.)?youtube\.com\/embed\/([^"?]+)"/', '<div class="cwvpsb_iframe"><div class="iframe_wrap"><div class="iframe_player" data-embed="${1}" id="player_${1}"><div class="play-button"></div></div></div></div>', $content); 
    
            global $iframe_check;
            $iframe_check = preg_match( '/iframe_player/i', $content, $result );
            return $content;
}

add_action("wp_footer", "cwvpsb_iframe_delay_enqueue");
 
function cwvpsb_iframe_delay_enqueue(){
    
    global $iframe_check;
    if ( $iframe_check == 1 ) {
        wp_enqueue_script( 'cwvpsb_iframe', plugin_dir_url(__FILE__) . 'cwvpsb_iframe.js', array(), CWVPSB_VERSION);
        wp_enqueue_style( 'cwvpsb_iframe', plugin_dir_url(__FILE__) . 'cwvpsb_iframe.css', array(), CWVPSB_VERSION);
    }
}

function cwvpsb_amp_support_enabled(){
    if(function_exists( 'ampforwp_is_amp_endpoint' ) && ampforwp_is_amp_endpoint()){
        return true;
    }elseif(function_exists('is_amp_endpoint') && is_amp_endpoint()){
        return true;
    }elseif(function_exists('get_post_type') && in_array(get_post_type(), array('web-stories', 
'web-story'))){
        return true;
    }elseif(function_exists('get_post_type') && get_post_type()=='ampforwp_story'){
        return true;
    }
    if(function_exists('vp_metabox')){
        $amp_story_activated = vp_metabox('amp_story_vp_metabox.amp_story_tg');
       $amp_story_primary = vp_metabox('amp_story_vp_metabox.amp_story_tg_primary');
       if ($amp_story_primary == 1 && $amp_story_activated == 1 && !is_admin() &&  (is_single() ||  is_page() ) ) {
            return true;
        }
        if (sanitize_text_field($_GET['amp'] == 1)  && $amp_story_activated == 1 ) {
          return true;
       }
    }
    
    return false;
}

function cwvpsb_is_mobile() {
    $userAgent = '';
    if(isset($_SERVER['HTTP_USER_AGENT'])){
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
    }

    // A list of common mobile device keywords
    $mobileKeywords = ['Mobile', 'Android', 'iPhone', 'iPad', 'Windows Phone', 'BlackBerry'];

    // Check if any of the mobile keywords exist in the user agent
    foreach ($mobileKeywords as $keyword) {
        if (stripos($userAgent, $keyword) !== false) {
            return true;
        }
    }

    // Check for some specific mobile strings
    if (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone)/i', $userAgent)) {
        return true;
    }

    // Check for common desktop browsers to exclude false positives
    $desktopBrowsers = ['Windows NT', 'Macintosh', 'Linux', 'X11'];
    foreach ($desktopBrowsers as $browser) {
        if (stripos($userAgent, $browser) !== false) {
            return false;
        }
    }

    // If no mobile keywords are found, assume it's a desktop device
    return false;
}