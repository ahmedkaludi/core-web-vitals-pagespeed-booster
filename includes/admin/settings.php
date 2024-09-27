<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}
class cwvpsb_admin_settings{

public function __construct() {
    add_action( 'admin_menu', array($this, 'cwvpsb_add_menu_links'));
    add_action('admin_init', array($this, 'cwvpsb_settings_init'));
    add_action( 'init', array( $this, 'load_settings' ) );
    add_action('wp_ajax_list_files_to_convert', array($this, 'get_list_convert_files'));
    add_action('wp_ajax_webvital_webp_convert_file', array($this, 'webp_convert_file'));
    add_action( 'admin_bar_menu',  array($this, 'all_admin_bar_settings'), PHP_INT_MAX - 10 );

}

function load_settings() {
    $settings = cwvpsb_defaults();
    if(function_exists('imagewebp') && $settings['webp_support'] == 'auto' && !is_user_logged_in()){
       require_once CWVPSB_PLUGIN_DIR."includes/images/convert-webp.php";
    }
    if(isset($settings['lazyload_support']) && $settings['lazyload_support']==1 && !is_user_logged_in() ){
       require_once CWVPSB_PLUGIN_DIR."includes/images/lazy-loading.php";
    }
    if(isset($settings['minification_support']) && $settings['minification_support']==1){

       require_once CWVPSB_PLUGIN_DIR."includes/css/minify.php";
    }
    if(isset($settings['unused_css_support']) && $settings['unused_css_support']==1){
       require_once CWVPSB_PLUGIN_DIR."includes/css/unused-css.php";
    }
    if(isset($settings['google_fonts_support']) && $settings['google_fonts_support']==1){
       require_once CWVPSB_PLUGIN_DIR."includes/css/google-fonts.php";
    }
    if(isset($settings['critical_css_support']) && $settings['critical_css_support']==1){
       require_once CWVPSB_PLUGIN_DIR."includes/css/critical_css.php";
    }
    if(cwvpsb_is_mobile())
    {
        if(isset($settings['delay_js_mobile']) && $settings['delay_js_mobile'] == 'php'){
            require_once CWVPSB_PLUGIN_DIR."includes/javascript/delay-js.php";
         }
         if(isset($settings['delay_js_mobile']) && $settings['delay_js_mobile'] == 'js'){
            require_once CWVPSB_PLUGIN_DIR."includes/javascript/delay-jswithjs.php";
         }
    }else{
        if(isset( $settings['delay_js']) && $settings['delay_js'] == 'php'){
            require_once CWVPSB_PLUGIN_DIR."includes/javascript/delay-js.php";
         }
         if(isset($settings['delay_js']) && $settings['delay_js'] == 'js'){
            require_once CWVPSB_PLUGIN_DIR."includes/javascript/delay-jswithjs.php";
         }
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
    if ( isset( $_GET['settings-updated'] ) ) {  //phpcs:ignore WordPress.Security.NonceVerification -- Nonce verification is not required here.
        $this->cwvpsb_reWriteCacheHtaccess();
        $settings = cwvpsb_defaults();  
        settings_errors();
    }
    $tab = cwvpsb_get_tab('images', array('images', 'css', 'urls', 'javascript','cache','advance')); ?>
     <div id="cwv-wrap">
    <h1><?php echo esc_html__('Core Web Vitals & PageSpeed Booster Settings', 'cwvpsb'); ?></h1>
     <div id="left-sidebar">
    <h2 class="nav-tab-wrapper cwvpsb-tabs">
    <?php
        echo '<a href="' . esc_url(cwvpsb_admin_link('images')) . '" class="nav-tab ' . esc_attr( $tab == 'images' ? 'nav-tab-active' : '') . '">' . esc_html__('Images','cwvpsb') . '</a>';
        echo '<a href="' . esc_url(cwvpsb_admin_link('urls')) . '" class="nav-tab ' . esc_attr( $tab == 'urls' ? 'nav-tab-active' : '') . '">' . esc_html__('Urls','cwvpsb') . '</a>';
                    
        echo '<a href="' . esc_url(cwvpsb_admin_link('css')) . '" class="nav-tab ' . esc_attr( $tab == 'css' ? 'nav-tab-active' : '') . '">' . esc_html__('CSS','cwvpsb') . '</a>';

        echo '<a href="' . esc_url(cwvpsb_admin_link('javascript')) . '" class="nav-tab ' . esc_attr( $tab == 'javascript' ? 'nav-tab-active' : '') . '">' . esc_html__('Javascript','cwvpsb') . '</a>';

        echo '<a href="' . esc_url(cwvpsb_admin_link('cache')) . '" class="nav-tab ' . esc_attr( $tab == 'cache' ? 'nav-tab-active' : '') . '">' . esc_html__('Cache','cwvpsb') . '</a>';

        echo '<a href="' . esc_url(cwvpsb_admin_link('advance')) . '" class="nav-tab ' . esc_attr( $tab == 'advance' ? 'nav-tab-active' : '') . '">' . esc_html__('Advance','cwvpsb') . '</a>';

        echo '<a href="' . esc_url(cwvpsb_admin_link('support')) . '" class="nav-tab ' . esc_attr( $tab == 'support' ? 'nav-tab-active' : '') . '">' . esc_html__('Support','cwvpsb') . '</a>';                                  
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

            echo "<div class='cwvpsb-urls' ".( $tab != 'urls' ? 'style="display:none;"' : '').">";
            do_settings_sections( 'cwvpsb_urls_section' );
            echo "</div>";

            echo "<div class='cwvpsb-javascript' ".( $tab != 'javascript' ? 'style="display:none;"' : '').">";
            do_settings_sections( 'cwvpsb_javascript_section' ); 
            echo "</div>";

            echo "<div class='cwvpsb-cache' ".( $tab != 'cache' ? 'style="display:none;"' : '').">";
            do_settings_sections( 'cwvpsb_cache_section' );
            echo "</div>"; 

            echo "<div class='cwvpsb-advance' ".( $tab != 'advance' ? 'style="display:none;"' : '').">";
            do_settings_sections( 'cwvpsb_advance_section' ); 
            echo "</div>";
            echo "<div class='cwvpsb-support' ".( $tab != 'support' ? 'style="display:none;"' : '').">";
            do_settings_sections( 'cwvpsb_support_section' ); 
            echo "</div>";
            ?>
        </div>
        <div class="button-wrapper">                            
        <?php
            submit_button( esc_html__('Save', 'cwvpsb') );
        ?>
        </div>
    </form>
    </div>
    <div id="right-sidebar">
     <div class="boxsidebar boxsidebar-1">
         <h2 class="vision"><?php esc_html__('Vision & Mission', 'cwvpsb');?> </h2>
         <p><?php esc_html__('We breath and live CWV technology and no body can beat us in this game.', 'cwvpsb');?></p>
         <section class="bio">
          <div class="bio-wrap">
            <img width="50" height="50" src="<?php echo esc_url(CWVPSB_IMAGE_DIR . '/ahmed-kaludi.jpg'); ?>" alt="Ahmed Kaludi">
            <p><?php esc_html__('Vision & Mission', 'cwvpsb');?>Ahmed Kaludi <br><b>Lead Developer</b></p>
          </div>
          <div class="bio-wrap">
             <img width="50" height="50" src="<?php echo esc_url(CWVPSB_IMAGE_DIR . '/Mohammed-kaludi.jpeg'); ?>" alt="Mohammed Kaludi">
                <p><?php esc_html__('Vision & Mission', 'cwvpsb');?>Mohammed Kaludi <br><b>Developer</b></p>
          </div>
        </section>
        <section class="bio">
          <div class="bio-wrap">
             <img width="50" height="50" src="<?php echo esc_url(CWVPSB_IMAGE_DIR . '/sanjeev-kumar.jpg'); ?>" alt="Sanjeev Kumar">
              <p><?php esc_html__('Vision & Mission', 'cwvpsb');?>Sanjeev Kumar<br><b>Developer</b></p>
          </div>
          <div class="bio-wrap">
             <img width="50" height="50" src="<?php echo esc_url(CWVPSB_IMAGE_DIR . '/akshay-wali.jpg'); ?>" alt="Akshay Wali">
             <p><?php esc_html__('Vision & Mission', 'cwvpsb');?>Akshay Wali <br><b>Developer</b></p>
          </div>
        </section>
    <p class="boxdesc"><?php esc_html__('Vision & Mission', 'cwvpsb');?>Delivering a good user experience means a lot to us, so we try our best to reply each and every question.</p>
    </div>
    </div>
    </div>  
    <?php }

public function cwvpsb_settings_init(){

    register_setting( 'cwvpsb_setting_dashboard_group', 'cwvpsb_get_settings' );
 
    add_settings_section('cwvpsb_images_section', '', '__return_false', 'cwvpsb_images_section');
    
        add_settings_field(
            'image_optimization',
            'Image Optimization',
             array($this, 'image_optimization_callback'), 
            'cwvpsb_images_section',
            'cwvpsb_images_section'
         );   
    
    $settings = cwvpsb_defaults();         
    add_settings_field(
            'webp_support_manually',
            esc_html__('Manual' ,'cwvpsb'),  
            array($this, 'image_convert_webp_bulk'),            
            'cwvpsb_images_section',                     
            'cwvpsb_images_section',
            array('class' => 'child-opt-bulk')                       
        );
    if ($settings['webp_support'] == 'manual') {
        add_settings_field(
            'webp_support_manually',
            esc_html__('Manual' ,'cwvpsb'),  
            array($this, 'image_convert_webp_bulk'),            
            'cwvpsb_images_section',                     
            'cwvpsb_images_section',
            array('class' => 'child-opt-bulk2')                       
        );
    }  

    add_settings_field(
        'images_add_alttags',
        'Add Missing Alt Tags',
         array($this, 'images_add_alttags_callback'),
        'cwvpsb_images_section',
        'cwvpsb_images_section'
    );

    add_settings_field(
        'lazyload_support',
        'Lazy Load',
         array($this, 'lazyload_callback'),
        'cwvpsb_images_section',
        'cwvpsb_images_section'
    );

    add_settings_field(
        'lazyload_exclude',
        'Lazy Load Exclude Urls',
         array($this, 'lazyload_exclude_callback'),
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
        'cwvpsb_css_section',
        ["class"=>"hidden"]
    );
    add_settings_field(
        'google_fonts_support',
        'Google Fonts Optimizations',
         array($this, 'google_fonts_callback'),
        'cwvpsb_css_section',
        'cwvpsb_css_section'
    );
    add_settings_field(
        'critical_css_support',
        'Critical CSS Optimizations',
         array($this, 'critical_css_callback'),
        'cwvpsb_css_section',
        'cwvpsb_css_section'
    );    
    add_settings_section('cwvpsb_urls_section', '', '__return_false', 'cwvpsb_urls_section');

    add_settings_field(
            'urls_optimization_support',
            '',
             array($this, 'urlslist_callback'),
            'cwvpsb_urls_section',
            'cwvpsb_urls_section'
        );  

    add_settings_section('cwvpsb_javascript_section', '', '__return_false', 'cwvpsb_javascript_section');     
    add_settings_field(
            'js_optimization',
            'Delay JS Method',
             array($this, 'js_optimization_callback'), 
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

    add_settings_field(
        'cache_support_method',
        'Cache Method',
         array($this, 'cache_strategy_callback'),
        'cwvpsb_cache_section',
        'cwvpsb_cache_section',
        ["class"=>(isset($settings['cache_support']) && $settings['cache_support']==1 ? "": 'hidden')]
    ); 
    
    add_settings_field(
        'cache_flush_on',
        'Flush Cache On',
         array($this, 'cache_flush_callback'),
        'cwvpsb_cache_section',
        'cwvpsb_cache_section',
        ["class"=>(isset($settings['cache_support']) && $settings['cache_support']==1 ? "": 'hidden')]
    );

    add_settings_field(
        'cache_autoclear',
        'Auto Clear Cache',
         array($this, 'cache_autoclear_callback'),
        'cwvpsb_cache_section',
        'cwvpsb_cache_section',
        ["class"=>(isset($settings['cache_support']) && $settings['cache_support']==1 ? "": 'hidden')]
    );
     

    add_settings_section('cwvpsb_advance_section', '', '__return_false', 'cwvpsb_advance_section');                    
    add_settings_field(
        'advance_support',
        'Specific URL',
         array($this, 'advance_url_callback'),
        'cwvpsb_advance_section',
        'cwvpsb_advance_section'
    ); 
    add_settings_field(
        'critical_css_for',
        'Generate Critical Css For',
         array($this, 'generate_critical_css_callback'),
        'cwvpsb_advance_section',
        'cwvpsb_advance_section'
    ); 

    add_settings_field(
        'delete_on_uninstall',
        'Delete Data on Uninstall',
         array($this, 'delete_on_uninstall_callback'),
        'cwvpsb_advance_section',
        'cwvpsb_advance_section'
    ); 
    
    add_settings_section('cwvpsb_support_section', '','__return_false','cwvpsb_support_section');
    add_settings_field(
        'critical_css_for',
        '',
         array($this, 'support_section_callback'),
        'cwvpsb_support_section',
        'cwvpsb_support_section'
    );
    
}

public function support_section_callback(){


    
    echo '<tr>
            <td> </td><td width="100%"><p class="support_heading">'.esc_html__('We are dedicated to provide Technical support &amp; Help to our users. Use the below form for sending your questions. ', 'cwvpsb').'</p></td></tr>
          <tr><th scope="row">'.esc_html__('Email', 'cwvpsb').'<span class="cwvpsb-star-mark"> *</span></th><td> 
                <input type="text" id="cwvpsb_query_email" name="cwvpsb_query_email" size="47" placeholder="Enter your Email"></td>
         </tr>
    <tr><th scope="row">'.esc_html__('Query', 'cwvpsb').'<span class="cwvpsb-star-mark"> *</span></th><td> 
    <textarea rows="5" cols="50" id="cwvpsb_query_message" name="cwvpsb_query_message" placeholder="Write your query"></textarea>
    </td></tr>
    <tr><th scope="row"><button class="button button-primary cwvpsb-send-query">'.esc_html__('Send Support Request', 'cwvpsb').'</button><br>
    <div class="clear"> </div>
    <span class="cwvpsb-query-success cwvpsb-result cwvpsb-hide">'.esc_html__('Message sent successfully, Please wait we will get back to you shortly', 'cwvpsb').'</span>
    <span class="cwvpsb-query-error cwvpsb-result cwvpsb-hide">'.esc_html__('Message not sent. please check your network connection', 'cwvpsb').'</span></th></tr>';
}
public function image_optimization_callback(){
   $settings = cwvpsb_defaults(); ?>  
    <div class="label-align">
    <select class="webp_support" name="cwvpsb_get_settings[webp_support]" >
     <?php
        $delay = array('auto' => esc_html__('Automatic (Recommended)', 'cwvpsb'),'manual' => esc_html__('Manual', 'cwvpsb'));
        foreach ($delay as $key => $value ) {
        ?>
            <option value="<?php echo esc_attr($key);?>" <?php selected( $settings['webp_support'], $key);?>><?php echo esc_html($value);?></option>
        <?php
        }
        ?>
    </select>
    </div>
    <fieldset><label class="switch"> 
        <?php
        if(isset($settings['image_optimization_alt']) && $settings['image_optimization_alt'] ==1){
            echo '<input type="checkbox" name="cwvpsb_get_settings[image_optimization_alt]" class="regular-text" value="1" checked> ';
        }else{
            echo '<input type="checkbox" name="cwvpsb_get_settings[image_optimization_alt]" class="regular-text" value="1" >';
        } ?>
       
        <span class="slider round"></span></label>
        <?php echo '<b>'.esc_html__('Alternate Method','cwvpsb').'</b>'.esc_html__(" (Enable this option only if html/css/images are breaking)", 'cwvpsb');?>
    </fieldset>
         
    <?php }

function image_convert_webp_bulk(){
    echo "<div><div id='bulkconverUpload-wrap'><div class='bulkconverUpload'>".esc_html__('This tool will automatically convert your images in webp format and it will take some mintues please do not close this window or click the back button until all images converted
','cwvpsb')."</div></div></div>";
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

public function images_add_alttags_callback(){
    $settings = cwvpsb_defaults(); ?>
    <fieldset><label class="switch">
        <?php
        if(empty($settings['images_add_alttags']) || $settings['images_add_alttags'] == 1){
            echo '<input type="checkbox" name="cwvpsb_get_settings[images_add_alttags]" class="regular-text" value="1" checked> ';
        }else{
            echo '<input type="checkbox" name="cwvpsb_get_settings[images_add_alttags]" class="regular-text" value="1" >';
        } ?>
        <span class="slider round"></span></label>
        <p class="description"><?php echo esc_html__("This will add missing alt tags in images ", 'cwvpsb');?></p>
    </fieldset>
    <?php }

public function lazyload_exclude_callback(){
    $settings = cwvpsb_defaults(); ?> 
    <textarea rows='5' cols='70' name="cwvpsb_get_settings[lazyload_exclude]" id='cwvpsb_lazyload_exclude'><?php echo isset($settings['lazyload_exclude'])? esc_html($settings['lazyload_exclude']) : ''; ?></textarea>
    <p class="description"><?php echo esc_html__("Enter (,)comma separated urls of images that you  want to exclude from lazyload", 'cwvpsb');?></p>
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
    $settings = cwvpsb_defaults(); 
    ?>
    <fieldset><label class="switch">
        <?php
        if(isset($settings['unused_css_support']) && $settings['unused_css_support']==1){
            echo '<input type="checkbox" name="cwvpsb_get_settings[unused_css_support]" class="regular-text" value="1" checked> ';
        }else{
            echo '<input type="checkbox" name="cwvpsb_get_settings[unused_css_support]" class="regular-text" value="1" >';
        } ?>
        <span class="slider round"></span></label>
        <p class="description"><?php echo esc_html__("Makes your site even faster and lighter by automatically removing unused CSS from your website", 'cwvpsb');?></p>
        <?php if(isset($settings['unused_css_support']) && $settings['unused_css_support']==1){?>
        <br/><textarea rows='5' cols='70' name="cwvpsb_get_settings[whitelist_css]" id='cwvpsb_add_whitelist_css'><?php if(isset($settings['whitelist_css'])){ echo esc_html($settings['whitelist_css']); }  ?></textarea>
            <p class="description"><?php echo esc_html__("Add the CSS selectors line by line which you don't want to remove", 'cwvpsb');?></p><br/>
            <div style='display:inline-block;'><span class='button button-secondry' id='clear-css-cache' data-cleaningtype='css' data-nonce='<?php echo esc_attr($webp_nonce);?>' ><?php echo esc_html__("Clear Cached CSS", 'cwvpsb');?></span><span class='clear-cache-msg'></span></div>
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
public function critical_css_callback(){
    $settings = cwvpsb_defaults();?>
    <fieldset><label class="switch">
        <?php
        if(isset($settings['critical_css_support']) && $settings['critical_css_support']==1){
            echo '<input type="checkbox" name="cwvpsb_get_settings[critical_css_support]" class="regular-text" value="1" checked> ';
        }else{
            echo '<input type="checkbox" name="cwvpsb_get_settings[critical_css_support]" class="regular-text" value="1" >';
        }?>
        <span class="slider round"></span></label>
        <p class="description"><?php echo esc_html__("Grab critical css and inline on webpage to better experience first impression", 'cwvpsb');?></p>
    </fieldset>
    <?php }
 public function js_optimization_callback(){

    $settings = cwvpsb_defaults(); ?>  
    <div class="label-align delay_js">
    <table class="cwvpsb_inner_tb"><tr><th> <label for="cwvpsb_get_settings[delay_js]"><?php  echo esc_html__('Desktop', 'cwvpsb')?></label></th><td>
   <select name="cwvpsb_get_settings[delay_js]">
        <option value=""><?php echo esc_html__('Select Method', 'cwvpsb');?></option>
     <?php
        $delay = array('php' => 'PHP (Recommended)','js' => 'JS');
        foreach ($delay as $key => $value ) {
        ?>
            <option value="<?php echo esc_attr($key);?>" <?php selected( $settings['delay_js'], $key);?>><?php echo esc_html($value);?></option>
        <?php
        }
        ?>
    </tr><tr>
    </select></td><th> <label for="cwvpsb_get_settings[delay_js_mobile]"><?php  echo esc_html__('Mobile', 'cwvpsb')?></label></th><td>
   <select name="cwvpsb_get_settings[delay_js_mobile]">
        <option value=""><?php echo esc_html__('Select Method', 'cwvpsb');?></option>
     <?php
        $delay = array('php' => 'PHP (Recommended)','js' => 'JS');
        foreach ($delay as $key => $value ) {
        ?>
            <option value="<?php echo esc_attr($key);?>" <?php isset($settings['delay_js_mobile'])?selected( $settings['delay_js_mobile'], $key):'';?>><?php echo esc_html($value);?></option>
        <?php
        }
        ?>
    </select></td></tr></table>
    <br/>
    <br/>
    <b><?php echo esc_html__('Exclude JS from Delay method', 'cwvpsb');?></b>
    <br/>
    <textarea cols="70" rows="5" class="" placeholder="Enter JS URL One per line (Ex: popup.js)" name="cwvpsb_get_settings[exclude_delay_js]"><?php echo isset($settings['exclude_delay_js'])? esc_html($settings['exclude_delay_js']):''; ?></textarea>
    </div>  
             
    <?php 
}   

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
public function cache_strategy_callback(){
    $settings = cwvpsb_defaults(); ?>
    <fieldset><?php
        $options = array("Highly Optimized"=>esc_html__("Highly Optimized (Recommended)", 'cwvpsb'), "Aggressively Optimized"=>esc_html__("Aggressively Optimized", 'cwvpsb'));
        ?><select name="cwvpsb_get_settings[cache_support_method]">
                <?php foreach($options as $key=>$opt){
                    $sel = '';
                    if($settings['cache_support_method']==$key){ $sel = "selected"; }
                 ?>
                    <option value="<?php echo esc_attr($key); ?>" <?php echo esc_attr($sel); ?>><?php echo esc_html($opt); ?></option>
                <?php } ?>
            </select>
    <p class="description"><?php echo esc_html__("Highly Optimized will serve by PHP", 'cwvpsb')."<br/>".esc_html__(" Aggressively Optimized will serve cache via htaccess", 'cwvpsb');?></p>
    </fieldset>
    <?php }

public function cache_flush_callback(){
    $settings = cwvpsb_defaults(); ?>
    <fieldset><?php
        $options = array("_core_updated_successfully"=>esc_html__("Wordpress Core Update", 'cwvpsb'), "switch_theme"=>esc_html__("Switching Theme", 'cwvpsb'), "wp_trash_post"=>esc_html__("Post/Page Deletion", 'cwvpsb'));
        ?>
                <?php foreach($options as $key=>$opt){
                    $sel = '';
                    if(isset($settings['cache_flush_on']) && is_array($settings['cache_flush_on']) && in_array($key,$settings['cache_flush_on'])){ $sel = "checked"; }
                 ?>
                   <input type="checkbox" name="cwvpsb_get_settings[cache_flush_on][]" value="<?php echo esc_attr($key); ?>" <?php echo esc_attr($sel); ?>><?php echo esc_attr($opt); ?></option>
                <?php } ?>
    <p class="description"><?php echo esc_html__("Note: By default , on post deletion only homepage and category page will be cleared ", 'cwvpsb')?></p>
    </fieldset>
    <?php }
    public function cache_autoclear_callback(){
    $settings = cwvpsb_defaults(); ?>
    <fieldset><?php
    $autoclear_options = array("never"=>esc_html__("Never", 'cwvpsb'), "hourly"=>esc_html__("Hourly", 'cwvpsb'), "6hourly"=>esc_html__("Every 6 Hours", 'cwvpsb'), "12hourly"=>esc_html__("Every 12 Hours", 'cwvpsb'),"daily"=>esc_html__("Daily", 'cwvpsb'),"weekly"=>esc_html__("Weekly", 'cwvpsb'),"monthly"=>esc_html__("Monthly", 'cwvpsb'));
        ?>
               <select name="cwvpsb_get_settings[cache_autoclear]">
                <?php foreach($autoclear_options as $key=>$opt){
                    $sel = '';
                    if(isset($settings['cache_autoclear']) && $settings['cache_autoclear']==$key){ $sel = "selected"; } 
                    echo esc_html($sel);?>
                    <option value="<?php echo esc_attr($key); ?>" <?php echo esc_attr($sel); ?>><?php echo esc_attr($opt); ?></option>
                <?php } ?>
            </select>
    </fieldset>
    <?php }

public function generate_critical_css_callback(){
    
    $settings = cwvpsb_defaults();     
    
    $taxonomies = get_taxonomies(array( 'public' => true ), 'names');    

    $post_types = array();
    $post_types = get_post_types( array( 'public' => true ), 'names' );    
    $unsetdpost = array(
        'attachment',
        'cwvpb',
        'cwvpb_reviews',
        'cwvpb-collections',
    );
    foreach ($unsetdpost as $value) {
        unset($post_types[$value]);
    }
    
    if($post_types){

            echo '<ul>';
            echo '<li>';
            echo '<input class="" type="checkbox" name="cwvpsb_get_settings[critical_css_on_home]" value="1" '.(isset($settings["critical_css_on_home"]) ? "checked": "").' /> ' . esc_html('Home');
            echo '</li>';

            foreach ($post_types as $key => $value) {
                echo '<li>';
                echo '<input class="" type="checkbox" name="cwvpsb_get_settings[critical_css_on_cp_type]['.esc_attr($key).']" value="1" '.(isset($settings["critical_css_on_cp_type"][$key]) ? "checked": "").' /> ' . esc_html(ucwords($value));
                echo '</li>';
            }            

            if($taxonomies){
                foreach ($taxonomies as $key => $value) {
                    echo '<li>';
                    echo '<input class="" type="checkbox" name="cwvpsb_get_settings[critical_css_on_tax_type]['.esc_attr($key).']" value="1" '.(isset($settings["critical_css_on_tax_type"][$key]) ? "checked": "").' /> ' . esc_html(ucwords($value));
                    echo '</li>';
                }
            }

        echo '</ul>';
    }
    
    ?> 

    <?php

}    

public function advance_url_callback(){
    $settings = cwvpsb_defaults(); ?> 
    <textarea rows='5' cols='70' name="cwvpsb_get_settings[advance_support]" id='cwvpsb_add_advance_support'><?php echo isset($settings['advance_support'])? esc_html($settings['advance_support']) : ''; ?></textarea>
    <p class="description"><?php echo esc_html__("The Core Web Vital will only work on this URL, So that you can compare the speed on this URL with others", 'cwvpsb');?></p>
    <?php }
public function delete_on_uninstall_callback(){
    $settings = cwvpsb_defaults(); ?> 
    <input class="" type="checkbox" name="cwvpsb_get_settings[delete_on_uninstall]" value="1" <?php echo (isset($settings["delete_on_uninstall"]) && $settings["delete_on_uninstall"] == 1 ? "checked": "");?> />
    <?php echo esc_html__("This will delete all Core Web Vital generated files and settings when you uninstall the plugin", 'cwvpsb');?>
    <?php }        
    function get_list_convert_files(){
        if(isset($_POST['nonce_verify']) && !wp_verify_nonce(wp_unslash($_POST['nonce_verify']),'web-vitals-security-nonce')){ //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- using input as nonce
            wp_send_json(array('status'=>500 ,"msg"=>esc_html__('Request Security not verified', 'cwvpsb' ) ) );
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
        $years = array(gmdate("Y"),gmdate("Y",strtotime("-1 year")),gmdate("Y",strtotime("-2 year")),gmdate("Y",strtotime("-3 year")),gmdate("Y",strtotime("-4 year")), gmdate("Y",strtotime("-5 year")),gmdate("Y",strtotime("-6 year")) );

        $fileArray = array();
        foreach ($years as $key => $year) {
            $images = $this->getFilesListRecursively($year, $listOpt);
            $fileArray = array_merge($fileArray, $images);
        }
        $sort = array();
        if(!empty($fileArray) && is_array($fileArray)){
            foreach($fileArray as $keys=>$file){
                $sort[$file[1]][] = $file[0];
            }
        }
        krsort($sort);
        $files = array();
        if(!empty($sort) && is_array($sort)){
            foreach($sort as $asort){
                foreach($asort as $file){
                    $files[] = $file;
                }
            }
        }
        $response['files'] = array_filter($files);

        $response['status'] = 200;
        $response['message'] = ($response['files'])? esc_html__('Files are available to convert', 'cwvpsb'): esc_html__('All files are converted', 'cwvpsb');
        $response['count'] = count($response['files']);
        wp_send_json($response);
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

                            $destinationPath = $listOpt['root']."/cwv-webp-images";
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
          if(!empty($keys) && is_array($keys)){
            foreach($keys as $keypos => $key) {
                array_splice($parts, $key - ($keypos * 2 + 1), 2);
              }
          }
          return implode('/', $parts);
        
    }

    function webp_convert_file(){
        if(isset($_POST['nonce_verify']) && !wp_verify_nonce( wp_unslash( $_POST['nonce_verify'],'web-vitals-security-nonce' ) ) ){  //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- using input as nonce
            wp_send_json(array('status'=>500 ,"msg"=>esc_html__('Request Security not verified' , 'cwvpsb') ) );
        }
        $filename = isset($_POST['filename'])?sanitize_text_field(wp_unslash($_POST['filename'])):'';

        $upload = wp_upload_dir();
        $destinationPath = $upload['basedir']."/cwv-webp-images";
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
        wp_send_json(array('status'=>200 ,'filename'=> $filename,'source'=>$source,'destination'=>$destination,"msg"=>esc_html__('File converted successfully', 'cwvpsb') ));
    }

    public function cwvpsb_reWriteCacheHtaccess(){
        $settings = cwvpsb_defaults();
        $staticEnabled = true;
        if(isset($settings['cache_support']) && $settings['cache_support']==1){
             if(isset($settings['cache_support_method']) && $settings['cache_support_method']=='Aggressively Optimized'){
                $staticEnabled = true;
            }else{$staticEnabled = false;}
        }else{$staticEnabled = false;} 
        //Remove rules if option is off
            if ( ! function_exists( 'get_home_path' ) ) {
                    require_once ABSPATH . 'wp-admin/includes/file.php';
                }
                $htaccess_file = get_home_path() . '.htaccess';
                if ( ! $this->direct_filesystem()->is_writable( $htaccess_file ) ) {
                    // The file is not writable or does not exist.
                    return false;
                }
                // Get content of .htaccess file.
                $ftmp = $this->direct_filesystem()->get_contents( $htaccess_file );

                if ( false === $ftmp ) {
                    // Could not get the file contents.
                    return false;
                }
                // Check if the file contains the WP rules, before modifying anything.
                $has_wp_rules = $this->check_wp_has_htaccess_rules( $ftmp );

                // Remove the WP Rocket marker.
                $ftmp = preg_replace( '/\s*# BEGIN Core WebVital.*# END Core WebVital\s*?/isU', PHP_EOL . PHP_EOL, $ftmp );
                $ftmp = ltrim( $ftmp );

                if ( $staticEnabled ) {
                    $ftmp = $this->get_corewebvital_cache_htaccess() . PHP_EOL . $ftmp;
                }

                // Make sure the WP rules are still there.
                if ( $has_wp_rules && ! $this->check_wp_has_htaccess_rules( $ftmp ) ) {
                    return false;
                }

                // Update the .htacces file.
                return $this->direct_filesystem()->put_contents( $htaccess_file, $ftmp, 0644 );
        //Remove or add rules are added 
    }
    protected function direct_filesystem(){
        require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
        require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
        return new WP_Filesystem_Direct( new StdClass() );
    }
    protected function check_wp_has_htaccess_rules( $content ) {
        if ( is_multisite() ) {
            $has_wp_rules = strpos( $content, '# add a trailing slash to /wp-admin' ) !== false;
        } else {
            $has_wp_rules = strpos( $content, '# BEGIN WordPress' ) !== false;
        }

        return  $has_wp_rules;
    }
    protected function get_corewebvital_cache_htaccess(){
        $host = parse_url(
                get_site_url(),
                PHP_URL_HOST
            );
        // Recreate rules.
        $rule = '# BEGIN Core WebVital'. PHP_EOL;
        $rule .= '<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /wp-content/cache/cwvpsb/
    RewriteRule ^static/ - [L]
    RewriteCond %{HTTP_COOKIE} !wordpress_logged_in_.*=[^;]+ [NC]
    RewriteCond %{REQUEST_METHOD} !POST
    RewriteCond %{QUERY_STRING} !.*=.*
    RewriteCond %{DOCUMENT_ROOT}'.CWVPSB_CACHE_AGGRESIVE_DIR.$host.'/$1 -f
    RewriteRule ^(.*)$ '.CWVPSB_CACHE_AGGRESIVE_DIR.$host.'/$1 [L]
    RewriteCond %{REQUEST_METHOD} !POST
    RewriteCond %{QUERY_STRING} !.*=.*
    RewriteCond %{DOCUMENT_ROOT}'.CWVPSB_CACHE_AGGRESIVE_DIR.$host.'/$1/index.html -f
    RewriteRule ^(.*)$ '.CWVPSB_CACHE_AGGRESIVE_DIR.$host.'/$1/index.html [L]
</IfModule>'.PHP_EOL;

        $rule .= '# END Core WebVital' . PHP_EOL;
        $rule = apply_filters( 'cwvpb_aggressive_cache_htaccess_marker', $rule );
        return $rule;
        
    }

    function all_admin_bar_settings( $wp_admin_bar ){
        require_once( CWVPSB_PLUGIN_DIR.'includes/admin/admin-bar-settings.php');
    }

    public function generate_time($total_count){
        
        $estimate_time = '';      
        if($total_count > 0){
            $hours = '';
            if(intdiv($total_count, 300) > 0){
                $hours = intdiv($total_count, 300).' Hours, ';
            }
            
            if($hours){
                $estimate_time = $hours. ($total_count % 60). ' Min';
            }else{
                
                if(($total_count % 60) > 0){
                    $estimate_time = ($total_count % 60). ' Min';
                }                
            }            
            
        }
        return $estimate_time;  
    }
    /**
     * Url list will be shows
     */ 
    public function urlslist_callback(){

        global $wpdb;
        $table_name_escaped = esc_sql( $wpdb->prefix . 'cwvpb_critical_urls' );  
        $total_count       = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name_escaped}"); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Reason: $table_name_escaped is already escaped 
        $cached_count      = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table_name_escaped} Where `status`=%s", 'cached')); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $table_name_escaped is already escaped 
        $inprogress        = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table_name_escaped} Where `status`=%s", 'inprocess')); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $table_name_escaped is already escaped      
        $failed_count       = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table_name_escaped} Where `status`=%s", 'failed')); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $table_name_escaped is already escaped        
        $queue_count     = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table_name_escaped} Where `status`=%s", 'queue')); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $table_name_escaped is already escaped     
        $inprogress         = 0;
        $percentage         = 0;
                
        if($cached_count > 0 && $total_count){            
            $percentage      = ($cached_count/$total_count) * 100;        
            $percentage      = floor($percentage);
        }        
                        
        ?>
        <div class="cwvpbs_urls_section">            
            <!-- process section -->
            <div class="cwvpsb-css-optimization-wrapper">
            
            <strong style="font-size:18px;"><?php echo esc_html__('CSS Optimisation Status', 'cwvpsb') ?></strong>
                <p><?php echo esc_html__('Optimisation is running in background.', 'cwvpsb') ?></p>
                <br>
                <div class="cwvpsb_progress_bar">
                    <div class="cwvpsb_progress_bar_body" style="width: <?php echo esc_attr($percentage); ?>%;"><?php echo esc_attr($percentage).esc_html__('%', 'cwvpsb');?></div>
                </div>
                <br>
                <div class="cwvpsb_cached_status_bar">
                <div style="margin-top:20px;"><strong><?php echo esc_html__('Total :', 'cwvpsb') ?></strong> <span id="cwvpsb_css_total_count"><?php echo esc_attr($total_count).  esc_html__('URLs:', 'cwvpsb').' </span>';                                         
                 ?></div>
                 <div><strong><?php echo esc_html__('In Progress :', 'cwvpsb') ?></strong> <span id="cwvpsb_css_queue_count"><?php echo esc_attr($queue_count).  esc_html__('URLs:', 'cwvpsb').' </span>';                                         
                 ?></div>
                <div><strong><?php echo esc_html__('Critical CSS Optimized  :', 'cwvpsb') ?></strong> <span id="cwvpsb_css_cached_count"><?php echo esc_attr($cached_count). esc_html__('URLs:', 'cwvpsb').' </span>';                 
                ?></div>
                <?php
                    if($this->generate_time($queue_count)){
                        ?>
                        <div>
                        <strong><?php echo esc_html__('Remaining Time :', 'cwvpsb') ?></strong>
                        <span id="cwvpsb_css_generate_time"><?php
                            esc_html( $this->generate_time($queue_count));
                        ?> </span>
                        </div>                        
                        <?php
                    }

                    if($failed_count > 0){
                        ?>   
                            <div>
                                <strong><?php echo esc_html__('Failed      :', 'cwvpsb') ?></strong> <span id="cwvpsb_css_failed_count"><?php echo esc_attr($failed_count);?>,</span>
                                <a href="#" class="cwvpbs-resend-urls button button-secondary"><?php echo esc_html__('Resend', 'cwvpsb');?></a>
                            </div>                                                        
                        <?php     
                    }
                ?>                                                
                </div>                                                                
            </div> 
            <!-- DataTable section -->
            <div class="cwvpsb-table-url-wrapper">                         
             <div id="cwvpb-global-tabs" style="margin-top: 10px;">
                <a data-id="cwvpb-general-container"><?php echo esc_html__('All', 'cwvpsb');?> (<?php echo esc_attr($total_count); ?>)</a> |
                <a data-id="cwvpb-queue-container"><?php echo esc_html__('In Queue', 'cwvpsb');?> (<?php echo esc_attr($queue_count); ?>)</a> |
                <a data-id="cwvpb-knowledge-container"><?php echo esc_html__('Completed', 'cwvpsb');?> (<?php echo esc_attr($cached_count); ?>)</a> |
                <a data-id="cwvpb-default-container" ><?php echo esc_html__('Failed', 'cwvpsb');?> (<?php echo esc_attr($failed_count); ?>)</a>
             </div>
                                                                        
                <div class="cwvpb-global-container" id="cwvpb-general-container">
                <table class="table cwvpsb-table-class" id="table_page_cc_style_all" style="width:100%">
                <thead>
                    <tr>
                        <th><?php  esc_html_e('URL', 'cwvpsb');?></th>
                        <th><?php  esc_html_e('Status', 'cwvpsb');?></th>
                        <th><?php  esc_html_e('Size', 'cwvpsb');?></th>
                        <th><?php  esc_html_e('Created date', 'cwvpsb');?></th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <th><?php  esc_html_e('URL', 'cwvpsb');?></th>
                        <th><?php  esc_html_e('Status', 'cwvpsb');?></th>
                        <th><?php  esc_html_e('Size', 'cwvpsb');?></th>
                        <th><?php  esc_html_e('Created date', 'cwvpsb');?></th>
                    </tr>
                </tfoot>
                </table>
                </div>

                <div class="cwvpb-global-container" id="cwvpb-queue-container">
                <table class="table cwvpsb-table-class" id="table_page_cc_style_queue" style="width:100%">
                <thead>
                    <tr>
                        <th><?php  esc_html_e('URL', 'cwvpsb');?></th>
                        <th><?php  esc_html_e('Status', 'cwvpsb');?></th>
                        <th><?php  esc_html_e('Size', 'cwvpsb');?></th>
                        <th><?php  esc_html_e('Created date', 'cwvpsb');?></th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <th><?php  esc_html_e('URL', 'cwvpsb');?></th>
                        <th><?php  esc_html_e('Status', 'cwvpsb');?></th>
                        <th><?php  esc_html_e('Size', 'cwvpsb');?></th>
                        <th><?php  esc_html_e('Created date', 'cwvpsb');?></th>
                    </tr>
                </tfoot>
                </table>
                </div>

                <div class="cwvpb-global-container" id="cwvpb-knowledge-container">
                <table class="table cwvpsb-table-class" id="table_page_cc_style_completed" style="width:100%">
            <thead>
                    <tr>
                        <th><?php  esc_html_e('URL', 'cwvpsb');?></th>
                        <th><?php  esc_html_e('Status', 'cwvpsb');?></th>
                        <th><?php  esc_html_e('Size', 'cwvpsb');?></th>
                        <th><?php  esc_html_e('Created date', 'cwvpsb');?></th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <th><?php  esc_html_e('URL', 'cwvpsb');?></th>
                        <th><?php  esc_html_e('Status', 'cwvpsb');?></th>
                        <th><?php  esc_html_e('Size', 'cwvpsb');?></th>
                        <th><?php  esc_html_e('Created date', 'cwvpsb');?></th>
                    </tr>
                </tfoot>
                </table>
                </div>

                <div class="cwvpb-global-container" id="cwvpb-default-container">
                <table class="table cwvpsb-table-class" id="table_page_cc_style_failed" style="width:100%">
                <thead>
                    <tr>
                        <th><?php  esc_html_e('URL', 'cwvpsb');?></th>
                        <th><?php  esc_html_e('Status', 'cwvpsb');?></th>
                        <th><?php  esc_html_e('Failed Date', 'cwvpsb');?></th>
                        <th><?php  esc_html_e('Error', 'cwvpsb');?></th>
                        
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <th><?php  esc_html_e('URL', 'cwvpsb');?></th>
                        <th><?php  esc_html_e('Status', 'cwvpsb');?></th>
                        <th><?php  esc_html_e('Failed Date', 'cwvpsb');?></th>
                        <th><?php  esc_html_e('Error', 'cwvpsb');?></th>                       
                    </tr>
                </tfoot>
                </table>
                </div>
            
            </div>

             <div class="cwvpbs-advance-urls-container">
                <span class="cwvpbs-advance-toggle"> <?php  esc_html_e('Advance Settings', 'cwvpsb');?> <span class="dashicons dashicons-admin-generic"></span></span>
                <div class="cwvpbs-advance-btn-div cwvpb-display-none">
                <a class="button button-primary cwvpsb-recheck-url-cache"><?php esc_html_e('Recheck', 'cwvpsb'); ?></a>                                
                    <a class="button button-primary cwvpsb-reset-url-cache"><?php esc_html_e('Reset Cache', 'cwvpsb'); ?></a>                                
                </div>
             </div>       
            
        </div>
                                
        <?php

    }
}//Class closed

if (class_exists('cwvpsb_admin_settings')) {
    new cwvpsb_admin_settings;
};

if(is_admin()){
    add_action('wp_ajax_cwvpsb_purge_cache', 'cwvpsb_purge_cache', 0 );
    add_action('wp_ajax_cwvpsb_update_critical_css_stat', 'cwvpsb_update_critical_css_stat');
    add_action("wp_ajax_cwvpsb_showdetails_data",'cwvpsb_showdetails_data',10);
}

function cwvpsb_update_critical_css_stat()
{
    $response=array('status'=>'fail');
    $cwvpsb_security_nonce = isset($_POST['cwvpsb_security_nonce']) ?sanitize_text_field( wp_unslash( $_POST['cwvpsb_security_nonce'])):'';
    if( wp_verify_nonce( $cwvpsb_security_nonce, 'cwvpsb_security_nonce') && current_user_can( 'manage_options' )){
        global $wpdb;
		$table_name_escaped = esc_sql( $wpdb->prefix . 'cwvpb_critical_urls' );  
        $response['total_count']        = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name_escaped}"); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Reason: $table_name_escaped is already escaped
        $response['cached_count']       = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table_name_escaped} Where `status`=%s", 'cached')); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $table_name_escaped is already escaped 
        $response['inprogress']        = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table_name_escaped} Where `status`=%s", 'inprocess')); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $table_name_escaped is already escaped   
        $response['failed_count']       = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table_name_escaped} Where `status`=%s", 'failed')); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $table_name_escaped is already escaped       
        $response['queue_count']      = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table_name_escaped} Where `status`=%s", 'queue')); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $table_name_escaped is already escaped 
        $percentage         = 0;
                
        if($response['cached_count'] > 0 && $response['total_count'] ){            
            $percentage      = ($response['cached_count']/$response['total_count'] ) * 100;        
            $percentage      = floor($percentage);
        }  
        $response['percentage'] = $percentage ;
        $response['status'] = 'success' ;
        $server_x_requested = isset($_SERVER['HTTP_X_REQUESTED_WITH']) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_REQUESTED_WITH'] ) ):'';
        if(!empty($server_x_requested) && strtolower($server_x_requested) == 'xmlhttprequest') {
            wp_send_json( $response );
         }
         else {
            $http_reffer = isset($_SERVER["HTTP_REFERER"]) ? sanitize_text_field( wp_unslash( $_SERVER["HTTP_REFERER"] ) ):'';
            header("Location: ".$http_reffer);
         }
      
         wp_die();
        
    }
    else{
            exit("No naughty business please");
    }

}

function cwvpsb_purge_cache(){
    $nonce = isset($_POST['nonce']) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
    if( isset($nonce) && wp_verify_nonce($nonce, 'cwvpsb_purge_cache_all' ) && current_user_can( 'manage_options' ) ){ 
        CWVPSB_Cache::clear_total_cache(true);
        cwvpsb_delete_folder(
                CWVPSB_CACHE_DIR
            );                
        cwvpsb_delete_folder(
            CWVPSB_CACHE_FONTS_DIR
            );
        // cwvpsb_delete_folder(
        //         CWVPSB_CRITICAL_CSS_CACHE_DIR
        //     );
        cwvpsb_delete_folder(
                CWVPSB_JS_EXCLUDE_CACHE_DIR
            );
        delete_transient( CWVPSB_CACHE_NAME );
        set_transient( CWVPSB_CACHE_NAME, time() );
        $wp_http_referer = isset($_POST['_wp_http_referer']) ? sanitize_text_field( wp_unslash( $_POST['_wp_http_referer'] ) ) : esc_url( admin_url( 'admin.php?page=cwvpsb' ) );
        wp_safe_redirect(  $wp_http_referer );
        exit;
    }
   
}


function cwvpsb_delete_folder($dir){
    // remove slashes
        $dir = untrailingslashit($dir);

        // check if dir
        if ( ! is_dir($dir) ) {
            return;
        }

        // get dir data
        $objects = array_diff(
            scandir($dir),
            array('..', '.')
        );

        if ( empty($objects) ) {
            return;
        }

        foreach ( $objects as $object ) {
            // full path
            $object = $dir. DIRECTORY_SEPARATOR .$object;

            // check if directory
            if ( is_dir($object) ) {
                cwvpsb_delete_folder($object);
            } else {
                unlink($object);
            }
        }

        // delete
        cwvpsb_remove_directory($dir);
}

function cwvpsb_cachepath(){
    if(defined(CWVPSB_CRITICAL_CSS_CACHE_DIR)){
        return CWVPSB_CRITICAL_CSS_CACHE_DIR;
    }else{
        return WP_CONTENT_DIR . "/cache/cwvpsb/css/";
    }
}
function cwvpsb_showdetails_data(){
		

    if ( ! isset( $_POST['cwvpsb_security_nonce'] ) ){
        return; 
    }
    if ( !wp_verify_nonce( wp_unslash( $_POST['cwvpsb_security_nonce'] ) , 'cwvpsb_security_nonce' ) ){ //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- using input as nonce
        return;  
    }
    if(! isset( $_POST['cwvpsb_type']) || empty($_POST['cwvpsb_type']))
    {
      return;  
    }

    if(!current_user_can( 'manage_options' ))
    {
      return;  
    }
    $type   = isset($_POST['cwvpsb_type'])?sanitize_text_field( wp_unslash( $_POST['cwvpsb_type'] ) ):'';
    $start  = isset($_POST['start']) ? intval( $_POST['start'] ) : 0; 
    $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
    $page   = $start > 0 ? $start / $length : 1;
    $page   = $page + 1;
    $offset = isset($_POST['start']) ? intval($_POST['start']) : 0;
    $draw = isset($_POST['draw'])?intval($_POST['draw']):0;						
    
    global $wpdb, $table_prefix;
    $table_name_escaped = esc_html($table_prefix . 'cwvpb_critical_urls');
    $search = isset( $_POST['search']['value'] ) ?  sanitize_text_field( wp_unslash( $_POST['search']['value'] ) ): '';                                                        
    if($search){
        if($type=="all")
        {
            $total_count  = $wpdb->get_var($wpdb->prepare(  //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
                "SELECT COUNT(*) FROM {$table_name_escaped} WHERE `url` LIKE %s ", //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $table_name_escaped is already escaped
            '%' . $wpdb->esc_like($search) . '%'));
        }
        else{
			$total_count  = $wpdb->get_var($wpdb->prepare(  //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
                "SELECT COUNT(*) FROM {$table_name_escaped} WHERE `url` LIKE %s AND `status`=%s", //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $table_name_escaped is already escaped
			'%' . $wpdb->esc_like($search) . '%',$type)); 
        }
        if($type=="all")
        {
        
        $result = $wpdb->get_results(  //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare( 
                "SELECT * FROM {$table_name_escaped} WHERE `url` LIKE %s LIMIT %d, %d", //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $table_name_escaped is already escaped
                '%' . $wpdb->esc_like($search) . '%', $offset, $length
            )
        , ARRAY_A);

        }
        else{
            $result = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->prepare( 
                    "SELECT * FROM {$table_name_escaped} WHERE `url` LIKE %s AND `status`=%s LIMIT %d, %d", //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $table_name_escaped is already escaped
                    '%' . $wpdb->esc_like($search) . '%', $type,$offset, $length
                )
            , ARRAY_A);
        }

    }else
    {
        if($type=="all")
        {
        
            $total_count  = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name_escaped}"); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $table_name_escaped is already escaped
            $result = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->prepare( 
                    "SELECT * FROM {$table_name_escaped} LIMIT %d, %d", $offset, $length //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $table_name_escaped is already escaped
                )
            , ARRAY_A);
        }
        else
        {
            $total_count  = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table_name_escaped} Where `status`=%s",$type)); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $table_name_escaped is already escaped
			$result = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare( 
					"SELECT * FROM {$table_name_escaped} Where `status`=%s LIMIT %d, %d", $type,$offset, $length //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $table_name_escaped is already escaped
				)
			, ARRAY_A);
        }
    }
    
    $formated_result = array();

    if(!empty($result)){

        foreach ($result as $value) {
            $size="NA";
            if($value['status'] == 'cached'){
                $user_dirname = cwvpsb_cachepath();
                if (file_exists($user_dirname.md5($value['url']).'.css')) {
                    $size = round(filesize($user_dirname.md5($value['url']).'.css')/1024,2).esc_html__('KB','cwvpsb');
                }else{
                    $size = '<abbr title="'.esc_attr__('File is not in cached directory. Please recheck in advance option','cwvpsb').'">'.esc_html__('Deleted','cwvpsb').'</abbr>';
                }
            }
                
            $formated_result[] = array(
                '<div><abbr title="'.esc_attr($value['cached_name']).'">'.esc_html($value['url']).'</abbr>'.(esc_attr($value['status']) == 'failed' ? '<a href="#" data-section="all" data-id="'.esc_attr($value['id']).'" class="cwvpb-resend-single-url dashicons dashicons-controls-repeat"></a>' : '').' </div>',								   
                '<span class="cwvpb-status-t">'.esc_html($value['status']).'</span>',
                $size,
                $value['updated_at']
        );
        }				

    }	
            
    $retuernData = array(	
        "draw"            => $draw,
        "recordsTotal"    => $total_count,
        "recordsFiltered" => $total_count,
        "data"            => $formated_result
    );

    wp_send_json($retuernData);

}