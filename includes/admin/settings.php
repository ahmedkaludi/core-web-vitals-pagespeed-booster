<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}
class cwvpsb_admin_settings{

public function __construct() {
    add_action( 'admin_menu', array($this, 'cwvpsb_add_menu_links'));
    add_action('admin_init', array($this, 'cwvpsb_settings_init'));
    add_action( 'init', array( &$this, 'load_settings' ) );
    add_action('wp_ajax_list_files_to_convert', array($this, 'get_list_convert_files'));
        add_action('wp_ajax_webvital_webp_convert_file', array($this, 'webp_convert_file'));
}

function load_settings() {
    $settings = cwvpsb_defaults();
    if(isset($settings['webp_support'])){
       require_once CWVPSB_PLUGIN_DIR."includes/images/convert-webp.php";;
    }
    if(isset($settings['lazyload_support'])){
       require_once CWVPSB_PLUGIN_DIR."includes/images/lazy-loading.php";
    }
    if(isset($settings['minification_support'])){

       require_once CWVPSB_PLUGIN_DIR."includes/css/minify.php";
    }
    if(isset($settings['unused_css_support'])){
       require_once CWVPSB_PLUGIN_DIR."includes/css/unused-css.php";
    }
    if(isset($settings['google_fonts_support'])){
       require_once CWVPSB_PLUGIN_DIR."includes/css/google-fonts.php";
    }
    if(isset($settings['delay_js_support'])){
       require_once CWVPSB_PLUGIN_DIR."includes/javascript/delay-js.php";
    }
    if(isset($settings['delay_js_support_js'])){
       require_once CWVPSB_PLUGIN_DIR."includes/javascript/delay-jswithjs.php";
    }
}

public function cwvpsb_add_menu_links() { 
    add_menu_page( esc_html__('Core Web Vitals', 'cwvpsb'), esc_html__('Core Web Vitals', 'cwvpsb'), 'manage_options', 'cwvpsb', array($this, 'cwvpsb_admin_interface_render'),'dashicons-superhero');
}

public function cwvpsb_admin_interface_render(){

    // Authentication
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }              
    // Handing save settings
    if ( isset( $_GET['settings-updated'] ) ) { 
        $settings = cwvpsb_defaults();  
        settings_errors();
    }
    $tab = cwvpsb_get_tab('images', array('images', 'css', 'javascript','cache')); ?>
                                    
    <h1><?php echo esc_html__('Core Web Vitals & PageSpeed Booster Settings', 'cwvpsb'); ?></h1>
    <h2 class="nav-tab-wrapper cwvpsb-tabs">
    <?php
        echo '<a href="' . esc_url(cwvpsb_admin_link('images')) . '" class="nav-tab ' . esc_attr( $tab == 'images' ? 'nav-tab-active' : '') . '">' . esc_html__('images','cwvpsb') . '</a>';
                    
        echo '<a href="' . esc_url(cwvpsb_admin_link('css')) . '" class="nav-tab ' . esc_attr( $tab == 'css' ? 'nav-tab-active' : '') . '">' . esc_html__('CSS','cwvpsb') . '</a>';

        echo '<a href="' . esc_url(cwvpsb_admin_link('javascript')) . '" class="nav-tab ' . esc_attr( $tab == 'javascript' ? 'nav-tab-active' : '') . '">' . esc_html__('Javascript','cwvpsb') . '</a>';

        echo '<a href="' . esc_url(cwvpsb_admin_link('cache')) . '" class="nav-tab ' . esc_attr( $tab == 'cache' ? 'nav-tab-active' : '') . '">' . esc_html__('Cache','cwvpsb') . '</a>';
                                                   
    ?>
    </h2>
    <form action="options.php" method="post" enctype="multipart/form-data" class="cwvpsb-settings-form">      
        <div class="form-wrap">
            <?php
            settings_fields( 'cwvpsb_setting_dashboard_group' );

            echo "<div class='cwvpsb-images' ".( $tab != 'images' ? 'style="display:none;"' : '').">";
            do_settings_sections( 'cwvpsb_images_section' );
            echo "</div>";
                        
            echo "<div class='cwvpsb-css' ".( $tab != 'css' ? 'style="display:none;"' : '').">";
            do_settings_sections( 'cwvpsb_css_section' );
            echo "</div>";

            echo "<div class='cwvpsb-javascript' ".( $tab != 'javascript' ? 'style="display:none;"' : '').">";
            do_settings_sections( 'cwvpsb_javascript_section' ); 
            echo "</div>";

            echo "<div class='cwvpsb-cache' ".( $tab != 'cache' ? 'style="display:none;"' : '').">";
            do_settings_sections( 'cwvpsb_cache_section' );
            echo "</div>"; ?>
        </div>
        <div class="button-wrapper">                            
        <?php
            submit_button( esc_html__('Save', 'cwvpsb') );
        ?>
        </div>
    </form>
    </div>   
    <?php }

public function cwvpsb_settings_init(){

    register_setting( 'cwvpsb_setting_dashboard_group', 'cwvpsb_get_settings' );
 
    add_settings_section('cwvpsb_images_section', '', '__return_false', 'cwvpsb_images_section');
    if (function_exists('imagewebp')) {                    
        add_settings_field(
            'webp_support',
            'Webp Images [ On The Fly]',
             array($this, 'webp_callback'),
            'cwvpsb_images_section',
            'cwvpsb_images_section'
        );
    }
    $settings = cwvpsb_defaults(); 
    if(!isset($settings['webp_support'])){
    add_settings_field(
            'webp_support_manually',
            esc_html__('Convert all images to WEBP [ Manually ]','web-vitals-page-speed-enhancer'),  
            array($this, 'image_convert_webp_bulk'),            
            'cwvpsb_images_section',                     
            'cwvpsb_images_section'                          
        );
        add_settings_field(
            'webp_support_manually_display',
            esc_html__('Display WEBP Images','web-vitals-page-speed-enhancer'),
            array($this, 'image_convert_webp'),                 
            'cwvpsb_images_section',
            'cwvpsb_images_section'                      
        );
    }
    add_settings_field(
        'lazyload_support',
        'Lazy Load',
         array($this, 'lazyload_callback'),
        'cwvpsb_images_section',
        'cwvpsb_images_section'
    );
    
    add_settings_section('cwvpsb_css_section', '', '__return_false', 'cwvpsb_css_section');                     
    add_settings_field(
        'minification_support',
        'Minification',
         array($this, 'minification_callback'),
        'cwvpsb_css_section',
        'cwvpsb_css_section'
    );  
    add_settings_field(
        'unused_css_support',
        'Remove Unused CSS',
         array($this, 'unused_css_callback'),
        'cwvpsb_css_section',
        'cwvpsb_css_section'
    );
    add_settings_field(
        'google_fonts_support',
        'Google Fonts Optimizations',
         array($this, 'google_fonts_callback'),
        'cwvpsb_css_section',
        'cwvpsb_css_section'
    );    

    add_settings_section('cwvpsb_javascript_section', '', '__return_false', 'cwvpsb_javascript_section');                    
    add_settings_field(
        'delay_js_support',
        'Delay JS Execution [PHP Method]',
         array($this, 'delay_js_callback'),
        'cwvpsb_javascript_section',
        'cwvpsb_javascript_section'
    );
    add_settings_field(
        'delay_js_support_js',
        'Delay JS Execution [JS Method]',
         array($this, 'delay_js_callback_js'),
        'cwvpsb_javascript_section',
        'cwvpsb_javascript_section'
    );

    add_settings_section('cwvpsb_cache_section', '', '__return_false', 'cwvpsb_cache_section');                    
    add_settings_field(
        'cache_support',
        'Cache',
         array($this, 'cache_callback'),
        'cwvpsb_cache_section',
        'cwvpsb_cache_section'
    );                                           
}
 
public function webp_callback(){
            
    $settings = cwvpsb_defaults(); ?>  
    <fieldset><label class="switch">
        <?php
        if(isset($settings['webp_support'])){
            echo '<input type="checkbox" name="cwvpsb_get_settings[webp_support]" class="regular-text" value="1" checked>';
        }else{
            echo '<input type="checkbox" name="cwvpsb_get_settings[webp_support]" class="regular-text" value="1" >';
        } ?>
        <span class="slider round"></span></label>    
        <p class="description"><?php echo esc_html__("Images are converted to WebP on the fly if the browser supports it. You don't have to do anything", 'cwvpsb');?></p>
    </fieldset>
    <?php    
}
function image_convert_webp_bulk(){
   $settings = cwvpsb_defaults(); 
   if(!isset($settings['webp_support'])){
    $webp_nonce = wp_create_nonce('web-vitals-security-nonce');
    echo "<button type='button' class='bulk_convert_webp' data-nonce='".$webp_nonce."'>".esc_html__('Bulk convert to WEBP','web-vitals-page-speed-enhancer')."<span id='bulk_convert_message'></span></button>
        <div><div id='bulkconverUpload-wrap'><div class='bulkconverUpload'>".esc_html__('Convert all your images to webp by clicking on this button and please wait for few seconds to load and then click on start convertion','web-vitals-page-speed-enhancer')."</div></div></div>";
    }
}

    function image_convert_webp(){
        // Get Settings
        $settings = cwvpsb_defaults(); 
       if(!isset($settings['webp_support'])){?>      
     
    
    <fieldset><label class="switch">
        <?php
        if(isset($settings['webp_support_manually_display'])){
            echo '<input type="checkbox" name="cwvpsb_get_settings[webp_support_manually_display]" class="regular-text" value="1" checked>';
        }else{
            echo '<input type="checkbox" name="cwvpsb_get_settings[webp_support_manually_display]" class="regular-text" value="1" >';
        } ?>
        <span class="slider round"></span></label>    
        <p class="description"><?php echo esc_html__("Display WEBP Images once you have converted it manually", 'cwvpsb');?></p>
    </fieldset>

    <?php  }
    }
public function lazyload_callback(){
    $settings = cwvpsb_defaults(); ?>
    <fieldset><label class="switch">
        <?php
        if(isset($settings['lazyload_support'])){
            echo '<input type="checkbox" name="cwvpsb_get_settings[lazyload_support]" class="regular-text" value="1" checked> ';
        }else{
            echo '<input type="checkbox" name="cwvpsb_get_settings[lazyload_support]" class="regular-text" value="1" >';
        } ?>
        <span class="slider round"></span></label>
        <p class="description"><?php echo esc_html__("Lazy Load delays loading of images and iframes in long web pages. which are outside of viewport and will not be loaded before user scrolls to them", 'cwvpsb');?></p>
    </fieldset>
    <?php }

public function minification_callback(){
    $settings = cwvpsb_defaults(); ?>
    <fieldset><label class="switch">
        <?php
        if(isset($settings['minification_support'])){
            echo '<input type="checkbox" name="cwvpsb_get_settings[minification_support]" class="regular-text" value="1" checked> ';
        }else{
            echo '<input type="checkbox" name="cwvpsb_get_settings[minification_support]" class="regular-text" value="1" >';
        }?>
        <span class="slider round"></span></label>
        <p class="description"><?php echo esc_html__("You will see the source of your HTML, CSS and JavaScript are now compressed and the size will be smaller which will be helpful to improve your page load speed", 'cwvpsb');?></p>
    </fieldset>
    <?php }
 
public function unused_css_callback(){
    $webp_nonce = wp_create_nonce('cwv-security-nonce');
    $settings = cwvpsb_defaults(); ?>
    <fieldset><label class="switch">
        <?php
        if(isset($settings['unused_css_support'])){
            echo '<input type="checkbox" name="cwvpsb_get_settings[unused_css_support]" class="regular-text" value="1" checked> ';
        }else{
            echo '<input type="checkbox" name="cwvpsb_get_settings[unused_css_support]" class="regular-text" value="1" >';
        } ?>
        <span class="slider round"></span></label>
        <p class="description"><?php echo esc_html__("Makes your site even faster and lighter by automatically removing unused CSS from your website", 'cwvpsb');?></p>
        <?php if(isset($settings['unused_css_support'])){?>
        <br/><textarea rows='5' cols='70' name="cwvpsb_get_settings[whitelist_css]" id='cwvpsb_add_whitelist_css'><?php echo esc_html($settings['whitelist_css']) ?></textarea>
            <p class="description"><?php echo esc_html__("Add the CSS selectors line by line which you don't want to remove", 'cwvpsb');?></p><br/>
            <div style='display:inline-block;'><span class='button button-secondry' id='clear-css-cache' data-cleaningtype='css' data-nonce='<?php echo $webp_nonce;?>' >Clear Cached CSS</span><span class='clear-cache-msg'></span></div>
        <?php } ?>
    </fieldset>
    <?php } 

public function google_fonts_callback(){
    $settings = cwvpsb_defaults();?>
    <fieldset><label class="switch">
        <?php
        if(isset($settings['google_fonts_support'])){
            echo '<input type="checkbox" name="cwvpsb_get_settings[google_fonts_support]" class="regular-text" value="1" checked> ';
        }else{
            echo '<input type="checkbox" name="cwvpsb_get_settings[google_fonts_support]" class="regular-text" value="1" >';
        }?>
        <span class="slider round"></span></label>
        <p class="description"><?php echo esc_html__("Locally hosting Google fonts for Pagespeed Insights or GT Metrix improvements", 'cwvpsb');?></p>
    </fieldset>
    <?php }   
    
public function delay_js_callback(){
    $settings = cwvpsb_defaults(); ?>
    <fieldset><label class="switch">
        <?php
        if(isset($settings['delay_js_support'])){
            echo '<input type="checkbox" name="cwvpsb_get_settings[delay_js_support]" class="regular-text" value="1" checked> ';
        }else{
            echo '<input type="checkbox" name="cwvpsb_get_settings[delay_js_support]" class="regular-text" value="1" >';
        }?>
        <span class="slider round"></span></label>
        <p class="description"><?php echo esc_html__("Delays the loading of JavaScript files until the user interacts like scroll, click etc, which improves performance", 'cwvpsb');?></p>
    </fieldset>
    <?php } 

public function delay_js_callback_js(){
    $settings = cwvpsb_defaults(); ?>
    <fieldset><label class="switch">
        <?php
        if(isset($settings['delay_js_support_js'])){
            echo '<input type="checkbox" name="cwvpsb_get_settings[delay_js_support_js]" class="regular-text" value="1" checked> ';
        }else{
            echo '<input type="checkbox" name="cwvpsb_get_settings[delay_js_support_js]" class="regular-text" value="1" >';
        }?>
        <span class="slider round"></span></label>
        <p class="description"><?php echo esc_html__("Delays the loading of JavaScript files until the user interacts like scroll, click etc, which improves performance", 'cwvpsb');?></p>
    </fieldset>
    <?php }    

public function cache_callback(){
    $settings = cwvpsb_defaults(); ?>
    <fieldset><label class="switch">
        <?php
        if(isset($settings['cache_support'])){
            echo '<input type="checkbox" name="cwvpsb_get_settings[cache_support]" class="regular-text" value="1" checked> ';
        }else{
            echo '<input type="checkbox" name="cwvpsb_get_settings[cache_support]" class="regular-text" value="1" >';
        }?>
        <span class="slider round"></span></label>
   <?php if(isset($settings['cache_support'])){?>      
    <button class="cache-btn" name="cache-btn"><i class="cache-trash"></i>&emsp;<?php echo esc_html__("Clear Site Cache", 'cwvpsb');?></button>
    <?php }  ?>
    <p class="description"><?php echo esc_html__("Caching pages will reduce the response time of your site and your web pages load much faster, directly from cache", 'cwvpsb');?></p>
    </fieldset>
    <?php }
    function get_list_convert_files(){
        if(isset($_POST['nonce_verify']) && !wp_verify_nonce($_POST['nonce_verify'],'web-vitals-security-nonce')){
            echo json_encode(array('status'=>500 ,"msg"=>esc_html__('Request Security not verified', 'web-vitals-page-speed-enhancer' ) ) );die;
        }
        $listOpt = array();
        $upload = wp_upload_dir();
        $listOpt['root'] = $upload['basedir'];
        $listOpt['filter'] = [
                'only-converted' => false,
                'only-unconverted' => true,
                'image' => 3,
                '_regexPattern'=> '#\.(jpe?g|png)$#'
            ];
        $years = array(date("Y"),date("Y",strtotime("-1 year")),date("Y",strtotime("-2 year")),date("Y",strtotime("-3 year")),date("Y",strtotime("-4 year")), date("Y",strtotime("-5 year")),date("Y",strtotime("-6 year")) );

        $fileArray = array();
        foreach ($years as $key => $year) {
            $images = $this->getFilesListRecursively($year, $listOpt);
            $fileArray = array_merge($fileArray, $images);
        }
        $sort = array();
        foreach($fileArray as $keys=>$file){
            $sort[$file[1]][] = $file[0];
        }
        krsort($sort);
        $files = array();
        foreach($sort as $asort){
            foreach($asort as $file){
                $files[] = $file;
            }
        }
        $response['files'] = array_filter($files);

        $response['status'] = 200;
        $response['message'] = ($response['files'])? esc_html__('Files are available to convert', 'web-vitals-page-speed-enhancer'): esc_html__('All files are converted', 'web-vitals-page-speed-enhancer');
        $response['count'] = count($response['files']);
        echo json_encode($response);die;
    }

    function getFilesListRecursively($currentDir, &$listOpt){
        $dir = $listOpt['root'] . '/' . $currentDir;
        $dir = $this->canonicalize($dir);
        if (!file_exists($dir) || !is_dir($dir)) {
            return [];
        }
        $fileIterator = new \FilesystemIterator($dir);
        $results = [];
        $filter = &$listOpt['filter'];
        while ($fileIterator->valid()) {
            $filename = $fileIterator->getFilename();
            $filedate = $fileIterator->getCTime();
            if (($filename != ".") && ($filename != "..")) {
                if (is_dir($dir . "/" . $filename)) {
                    $results = array_merge($results, $this->getFilesListRecursively($currentDir . "/" . $filename, $listOpt));
                } else {
                    // its a file - check if its a jpeg or png
                    if (preg_match('#\.(jpe?g|png)$#', $filename)) {
                        $addThis = true;

                        if (($filter['only-converted']) || ($filter['only-unconverted'])) {

                            $destinationPath = $listOpt['root']."/web-vital-webp";
                            if(!is_dir($destinationPath)) { wp_mkdir_p($destinationPath); }
                            
                            $destination = str_replace($listOpt['root'], $destinationPath, $dir);
                            $destination .= "/".$filename.'.webp';
                            $webpExists = file_exists($destination);
                            

                            if (!$webpExists && ($filter['only-converted'])) {
                                $addThis = false;
                            }
                            if ($webpExists && ($filter['only-unconverted'])) {
                                $addThis = false;
                            }
                        } else {
                            $addThis = true;
                        }
                        if ($addThis) {
                            $results[] = array($currentDir . "/" . $filename, $filedate);
                        }
                    }
                }
            }
            $fileIterator->next();
        }
        return $results;
    }

    function canonicalize($path){
        $parts = explode('/', $path);

        // Remove parts containing just '.' (and the empty holes afterwards)
        $parts = array_values(array_filter($parts, function($var) {
            return ($var != '.');
        }));

          // Remove parts containing '..' and the preceding
          $keys = array_keys($parts, '..');
          foreach($keys as $keypos => $key) {
            array_splice($parts, $key - ($keypos * 2 + 1), 2);
          }
          return implode('/', $parts);
        
    }

    function webp_convert_file(){
        if(isset($_POST['nonce_verify']) && !wp_verify_nonce($_POST['nonce_verify'],'web-vitals-security-nonce')){
            echo json_encode(array('status'=>500 ,"msg"=>esc_html__('Request Security not verified' , 'web-vitals-page-speed-enhancer') ) );die;
        }
        $filename = sanitize_text_field(stripslashes($_POST['filename']));
        $filename = wp_unslash($_POST['filename']);

        $upload = wp_upload_dir();
        $destinationPath = $upload['basedir']."/web-vital-webp";
        if(!is_dir($destinationPath)) { wp_mkdir_p($destinationPath); }
        $destination = $destinationPath.'/'. $filename.".webp";

        $source = $upload['basedir']."/".$filename;

        try {
            require_once CWVPSB_PLUGIN_DIR."/includes/vendor/autoload.php";
            $convertOptions = [];
            \WebPConvert\WebPConvert::convert($source, $destination, $convertOptions);
        } catch (\WebpConvert\Exceptions\WebPConvertException $e) {
            if(function_exists('error_log')){ error_log($e->getMessage()); }
        } catch (\Exception $e) {
            $message = 'An exception was thrown!';
            if(function_exists('error_log')){ error_log($e->getMessage()); }
        }
        echo json_encode(array('status'=>200 ,"msg"=>esc_html__('File converted successfully', 'web-vitals-page-speed-enhancer') ));die;
    }
}
if (class_exists('cwvpsb_admin_settings')) {
    new cwvpsb_admin_settings;
};