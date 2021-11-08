<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class cwvpsb_settings {

    public $images = 'images';
    public $css = 'css';
    public $js = 'js';
    public $cache = 'cache';
    public $plugin_options_key = 'cwvpsb_options';
    public $plugin_settings_tabs = array();

    function __construct() {
        add_action( 'init', array( &$this, 'load_settings' ) );
        add_action( 'admin_init', array( &$this, 'register_images_settings' ) );
        add_action( 'admin_init', array( &$this, 'register_css_settings' ) );
        add_action( 'admin_init', array( &$this, 'register_js_settings' ) );
        add_action( 'admin_init', array( &$this, 'register_cache_settings' ) );
        add_action( 'admin_menu', array( &$this, 'add_admin_menus' ) );
    }

    function load_settings() {
        $this->images_settings = (array) get_option( $this->images );
        $this->css_settings = (array) get_option( $this->css );
        $this->js_settings = (array) get_option( $this->js );
        $this->cache_settings = (array) get_option( $this->cache );

        if (!isset($this->images_settings)) {
            $this->images_settings = array_merge( array(
                'webp_option' => '1',
                'lazyload_option' => '1'
            ), $this->images_settings );
        }
        
        if (!isset($this->css_settings)) {
            $this->css_settings = array_merge( array(
                'minify_option' => '1',
                'unused_css_option' => '1',
                'fonts_option' => '1'
            ), $this->css_settings );
        }

        if (!isset($this->js_settings)) {
            $this->js_settings = array_merge( array(
                'delayjs_option' => '1'
            ), $this->js_settings );
        }

        if (!isset($this->cache_settings)) {
            $this->cache_settings = array_merge( array(
                'cache_option' => '1'
            ), $this->cache_settings );
        }
        if (isset($this->images_settings['webp_option']) && $this->images_settings['webp_option'] == "1") {
           require_once CWVPSB_PLUGIN_DIR."includes/images/convert-webp.php";
        }
        if (isset($this->images_settings['lazyload_option']) && $this->images_settings['lazyload_option'] == "1") {
           require_once CWVPSB_PLUGIN_DIR."includes/images/lazy-loading.php";
        }
        if (isset($this->css_settings['minify_option']) && $this->css_settings['minify_option'] == "1") {
           require_once CWVPSB_PLUGIN_DIR."includes/css/minify.php";
        }
        if (isset($this->css_settings['unused_css_option']) && $this->css_settings['unused_css_option'] == "1") {
           require_once CWVPSB_PLUGIN_DIR."includes/css/unused-css.php";
        }
        if (isset($this->css_settings['fonts_option']) && $this->css_settings['fonts_option'] == "1") {
           require_once CWVPSB_PLUGIN_DIR."includes/css/google-fonts.php";
        }
        if (isset($this->js_settings['delayjs_option']) && $this->js_settings['delayjs_option'] == "1") {
           require_once CWVPSB_PLUGIN_DIR."includes/javascript/delay-js.php";
        }

    }

    function register_images_settings() {
        $this->plugin_settings_tabs[$this->images] = 'images';

        register_setting( $this->images, $this->images );
        add_settings_section('section_images', __return_false(), '__return_false', $this->images);
        if (function_exists('imagewebp')) {
            add_settings_field( 'webp_option', esc_html__('Webp images', 'cwvpsb'), array( &$this, 'field_webp_option' ), $this->images, 'section_images' );
        }
        add_settings_field( 'lazyload_option', esc_html__('Lazy Load', 'cwvpsb'), array( &$this, 'field_lazyload_option' ), $this->images, 'section_images' );
    }

    function register_css_settings() {
        $this->plugin_settings_tabs[$this->css] = 'CSS';
        register_setting( $this->css, $this->css );
        add_settings_section('section_css', __return_false(), '__return_false', $this->css);
        add_settings_field( 'minify_option', esc_html__('Minification', 'cwvpsb'), array( &$this, 'field_minify_option' ), $this->css, 'section_css' );
        add_settings_field( 'unused_css_option', esc_html__('Remove Unused CSS', 'cwvpsb'), array( &$this, 'field_unused_css_option' ), $this->css, 'section_css' );
        add_settings_field( 'fonts_option', esc_html__('Google Fonts Optimizations', 'cwvpsb'), array( &$this, 'field_fonts_option' ), $this->css, 'section_css' );
    }

    function register_js_settings() {
        $this->plugin_settings_tabs[$this->js] = 'Javascript';
        register_setting( $this->js, $this->js );
        add_settings_section('section_js', __return_false(), '__return_false', $this->js);
        add_settings_field( 'delayjs_option', esc_html__('Delay JavaScript Execution', 'cwvpsb'), array( &$this, 'field_delayjs_option' ), $this->js, 'section_js' );
    }

    function register_cache_settings() {
        $this->plugin_settings_tabs[$this->cache] = 'Cache';
        register_setting( $this->cache, $this->cache );
        add_settings_section('section_cache', __return_false(), '__return_false', $this->cache);
        add_settings_field( 'cache_option', esc_html__('Cache', 'cwvpsb'), array( &$this, 'field_cache_option' ), $this->cache, 'section_cache' );
    }

    function field_webp_option() {
        $this->images_settings['webp_option'] = isset($this->images_settings['webp_option']) ? $this->images_settings['webp_option'] : '';
        ?>
        <label class="switch">
        <input type='checkbox' name="<?php echo esc_attr($this->images); ?>[webp_option]" <?php checked( $this->images_settings['webp_option'], "on" ); ?> >
        <span class="slider round"></span></label>
        <p class="description"><?php echo esc_html__("Images are converted to WebP on the fly if the browser supports it. You don't have to do anything", 'cwvpsb');?></p>
        <?php
    }

    function field_lazyload_option() {
        $this->images_settings['lazyload_option'] = isset($this->images_settings['lazyload_option']) ? $this->images_settings['lazyload_option'] : '';
        ?>
        <label class="switch">
        <input type='checkbox' name="<?php echo esc_attr($this->images); ?>[lazyload_option]" <?php checked( $this->images_settings['lazyload_option'], "on" ); ?> >
        <span class="slider round"></span></label>
        <p class="description"><?php echo esc_html__("Lazy Load delays loading of images and iframes in long web pages. which are outside of viewport and will not be loaded before user scrolls to them", 'cwvpsb');?></p>
        <?php
    }

    function field_minify_option() {
        $this->css_settings['minify_option'] = isset($this->css_settings['minify_option']) ? $this->css_settings['minify_option'] : '';
        ?>
        <label class="switch">
        <input type='checkbox' name="<?php echo esc_attr($this->css); ?>[minify_option]" <?php checked( $this->css_settings['minify_option'], "on" ); ?> >
        <span class="slider round"></span></label>
        <p class="description"><?php echo esc_html__("You will see the source of your HTML, CSS and JavaScript are now compressed and the size will be smaller which will be helpful to improve your page load speed", 'cwvpsb');?></p>
        <?php
    }
     
    function field_unused_css_option() {
        $webp_nonce = wp_create_nonce('cwv-security-nonce');
        $this->css_settings['unused_css_option'] = isset($this->css_settings['unused_css_option']) ? $this->css_settings['unused_css_option'] : '';
        ?>
        <label class="switch">
        <input type='checkbox' name="<?php echo esc_attr($this->css); ?>[unused_css_option]" <?php checked( $this->css_settings['unused_css_option'], "on" ); ?> >
        <span class="slider round"></span></label>
        <p class="description"><?php echo esc_html__("Makes your site even faster and lighter by automatically removing unused CSS from your website", 'cwvpsb');?></p>
        <?php if ($this->css_settings['unused_css_option'] == '1') {?>
        <br/><textarea rows='5' cols='70' name="<?php echo esc_attr($this->css); ?>[whitelist_css]" id='cwvpsb_add_whitelist_css'><?php echo esc_html($this->css_settings['whitelist_css']) ?></textarea>
            <p class="description"><?php echo esc_html__("Add the CSS selectors line by line which you don't want to remove", 'cwvpsb');?></p><br/>
        <?php } if ($this->css_settings['unused_css_option'] == '1') { ?>
            <div style='display:inline-block;'><span class='button button-secondry' id='clear-css-cache' data-cleaningtype='css' data-nonce='<?php echo $webp_nonce;?>' >Clear Cached CSS</span><span class='clear-cache-msg'></span></div>
        <?php }
    }  

    function field_fonts_option() {
        $this->css_settings['fonts_option'] = isset($this->css_settings['fonts_option']) ? $this->css_settings['fonts_option'] : '';
        ?>
        <label class="switch">
        <input type='checkbox' name="<?php echo esc_attr($this->css); ?>[fonts_option]" <?php checked( $this->css_settings['fonts_option'], "on" ); ?> >
        <span class="slider round"></span></label>
        <p class="description"><?php echo esc_html__("Locally hosting Google fonts for Pagespeed Insights or GT Metrix improvements", 'cwvpsb');?></p>
        <?php
    } 
     
    function field_delayjs_option() {
        $this->js_settings['delayjs_option'] = isset($this->js_settings['delayjs_option']) ? $this->js_settings['delayjs_option'] : '';
        ?>
        <label class="switch">
        <input type='checkbox' name="<?php echo esc_attr($this->js); ?>[delayjs_option]" <?php checked( $this->js_settings['delayjs_option'], "on" ); ?> >
        <span class="slider round"></span></label>
        <p class="description"><?php echo esc_html__("Delays the loading of JavaScript files until the user interacts like scroll, click etc, which improves performance", 'cwvpsb');?></p>
        <?php
    } 

    function field_cache_option() {
        $this->cache_settings['cache_option'] = isset($this->cache_settings['cache_option']) ? $this->cache_settings['cache_option'] : '';
        ?>
        <label class="switch">
        <input type='checkbox' name="<?php echo esc_attr($this->cache); ?>[cache_option]" <?php checked( $this->cache_settings['cache_option'], "on" ); ?> >
        <span class="slider round"></span></label>
        <button class="cache-btn" name="cache-btn"><i class="cache-trash"></i>&emsp;<?php echo esc_html__("Clear Site Cache", 'cwvpsb');?></button>
        <p class="description"><?php echo esc_html__("Caching pages will reduce the response time of your site and your web pages load much faster, directly from cache", 'cwvpsb');?></p>
        <?php
    } 

    function add_admin_menus() {
        add_menu_page( esc_html__('Core Web Vitals', 'cwvpsb'), esc_html__('Core Web Vitals', 'cwvpsb'), 'manage_options', $this->plugin_options_key, array( &$this, 'plugin_options_page' ) ,'dashicons-superhero');
    }

    function plugin_options_page() {
        // Authentication
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }              
    $tab = cwvpsb_get_tab('images', array('images', 'css','js','cache')); 
    ?>
    <h1><?php echo esc_html__('Core Web Vitals & PageSpeed Booster Settings', 'cwvpsb'); ?></h1>
        <h2 class="nav-tab-wrapper cwvpsb-tabs">
            <?php   
            echo '<a href="' . esc_url(cwvpsb_admin_link('images')) . '" class="nav-tab ' . esc_attr( $tab == 'images' ? 'nav-tab-active' : '') . '">' . esc_html__('images','cwvpsb') . '</a>';
                        
            echo '<a href="' . esc_url(cwvpsb_admin_link('css')) . '" class="nav-tab ' . esc_attr( $tab == 'css' ? 'nav-tab-active' : '') . '">' . esc_html__('CSS','cwvpsb') . '</a>';

            echo '<a href="' . esc_url(cwvpsb_admin_link('js')) . '" class="nav-tab ' . esc_attr( $tab == 'js' ? 'nav-tab-active' : '') . '">' . esc_html__('JS','cwvpsb') . '</a>';

            echo '<a href="' . esc_url(cwvpsb_admin_link('cache')) . '" class="nav-tab ' . esc_attr( $tab == 'cache' ? 'nav-tab-active' : '') . '">' . esc_html__('Cache','cwvpsb') . '</a>';
                                                
            ?>
        </h2>
            <form action="options.php" method="post" enctype="multipart/form-data" class="cwvpsb-settings-form">      
            <div class="cwvpsb-form-wrap">
            <?php
            settings_fields( $tab );

            echo "<div class='cwvpsb-images' ".( $tab != 'images' ? 'style="display:none;"' : '').">";
            do_settings_sections( $this->images );
            echo "</div>";

            echo "<div class='cwvpsb-css' ".( $tab != 'css' ? 'style="display:none;"' : '').">";
            do_settings_sections( $this->css );
            echo "</div>";

            echo "<div class='cwvpsb-js' ".( $tab != 'js' ? 'style="display:none;"' : '').">";
            do_settings_sections( $this->js ); 
            echo "</div>";

            echo "<div class='cwvpsb-cache' ".( $tab != 'cache' ? 'style="display:none;"' : '').">";
            do_settings_sections( $this->cache );
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
    <?php }

    function plugin_options_tabs() {?>
        <h2 class="nav-tab-wrapper cwvpsb-tabs">
            <?php   
            $tab = cwvpsb_get_tab('images', array('images', 'css'));

            echo '<a href="' . esc_url(cwvpsb_admin_link('images')) . '" class="nav-tab ' . esc_attr( $tab == 'images' ? 'nav-tab-active' : '') . '">' . esc_html__('images','cwvpsb') . '</a>';
                        
            echo '<a href="' . esc_url(cwvpsb_admin_link('css')) . '" class="nav-tab ' . esc_attr( $tab == 'css' ? 'nav-tab-active' : '') . '">' . esc_html__('CSS','cwvpsb') . '</a>';

            echo '<a href="' . esc_url(cwvpsb_admin_link('js')) . '" class="nav-tab ' . esc_attr( $tab == 'js' ? 'nav-tab-active' : '') . '">' . esc_html__('JS','cwvpsb') . '</a>';

            echo '<a href="' . esc_url(cwvpsb_admin_link('cache')) . '" class="nav-tab ' . esc_attr( $tab == 'cache' ? 'nav-tab-active' : '') . '">' . esc_html__('Cache','cwvpsb') . '</a>';                            
            ?>
        </h2>
    <?php }
};

$cwvpsb_settings = new cwvpsb_settings;