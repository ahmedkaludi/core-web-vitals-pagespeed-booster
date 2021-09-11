<?php

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
           require_once CWVPSB_PLUGIN_DIR."includes/images/convert_webp.php";
        }
        if (isset($this->images_settings['lazyload_option']) && $this->images_settings['lazyload_option'] == "1") {
           require_once CWVPSB_PLUGIN_DIR."includes/images/lazy-loading.php";
        }
        if (isset($this->css_settings['minify_option']) && $this->css_settings['minify_option'] == "1") {
           require_once CWVPSB_PLUGIN_DIR."includes/css/minify.php";
        }
        if (isset($this->css_settings['unused_css_option']) && $this->css_settings['unused_css_option'] == "1") {
           require_once CWVPSB_PLUGIN_DIR."includes/css/unused_css.php";
        }
        if (isset($this->css_settings['fonts_option']) && $this->css_settings['fonts_option'] == "1") {
           require_once CWVPSB_PLUGIN_DIR."includes/css/google-fonts.php";
        }
        if (isset($this->js_settings['delayjs_option']) && $this->js_settings['delayjs_option'] == "1") {
           require_once CWVPSB_PLUGIN_DIR."includes/javascript/delay_js.php";
        }

    }

    function register_images_settings() {
        $this->plugin_settings_tabs[$this->images] = 'images';

        register_setting( $this->images, $this->images );
        add_settings_section('section_images', __return_false(), '__return_false', $this->images);
        add_settings_field( 'webp_option', esc_html__('Webp images', 'cwvpsb'), array( &$this, 'field_webp_option' ), $this->images, 'section_images' );
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

    function field_webp_option() {?>
        <label class="switch">
        <input type='checkbox' name="<?php echo $this->images; ?>[webp_option]" <?php checked( $this->images_settings['webp_option'], 1 ); ?> value='1'>
        <span class="slider round"></span></label>
        <p class="description"><?php echo esc_html__("Images are converted to WebP on the fly if the browser supports it. You don't have to do anything", 'cwvpsb');?></p>
        <?php
    }

    function field_lazyload_option() {?>
        <label class="switch">
        <input type='checkbox' name="<?php echo $this->images; ?>[lazyload_option]" <?php checked( $this->images_settings['lazyload_option'], 1 ); ?> value='1'>
        <span class="slider round"></span></label>
        <p class="description"><?php echo esc_html__("Lazy Load delays loading of images and iframes in long web pages. which are outside of viewport and will not be loaded before user scrolls to them", 'cwvpsb');?></p>
        <?php
    }

    function field_minify_option() {?>
        <label class="switch">
        <input type='checkbox' name="<?php echo $this->css; ?>[minify_option]" <?php checked( $this->css_settings['minify_option'], 1 ); ?> value='1'>
        <span class="slider round"></span></label>
        <p class="description"><?php echo esc_html__("You will see the source of your HTML, CSS and JavaScript are now compressed and the size will be smaller which will be helpful to improve your page load speed", 'cwvpsb');?></p>
        <?php
    }
     
    function field_unused_css_option() {?>
        <label class="switch">
        <input type='checkbox' name="<?php echo $this->css; ?>[unused_css_option]" <?php checked( $this->css_settings['unused_css_option'], 1 ); ?> value='1'>
        <span class="slider round"></span></label>
        <p class="description"><?php echo esc_html__("Makes your site even faster and lighter by automatically removing unused CSS from your website", 'cwvpsb');?></p>
        <?php
    }  

    function field_fonts_option() {?>
        <label class="switch">
        <input type='checkbox' name="<?php echo $this->css; ?>[fonts_option]" <?php checked( $this->css_settings['fonts_option'], 1 ); ?> value='1'>
        <span class="slider round"></span></label>
        <p class="description"><?php echo esc_html__("Locally hosting Google fonts for Pagespeed Insights or GT Metrix improvements", 'cwvpsb');?></p>
        <?php
    } 
     
    function field_delayjs_option() {?>
        <label class="switch">
        <input type='checkbox' name="<?php echo $this->js; ?>[delayjs_option]" <?php checked( $this->js_settings['delayjs_option'], 1 ); ?> value='1'>
        <span class="slider round"></span></label>
        <p class="description"><?php echo esc_html__("Delays the loading of JavaScript files until the user interacts like scroll, click etc, which improves performance", 'cwvpsb');?></p>
        <?php
    } 

    function field_cache_option() {?>
        <label class="switch">
        <input type='checkbox' name="<?php echo $this->cache; ?>[cache_option]" <?php checked( $this->cache_settings['cache_option'], 1 ); ?> value='1'>
        <span class="slider round"></span></label>
        <button class="cache-btn" name="cache-btn"><i class="cache-trash"></i>&emsp;<?php echo esc_html__("Clear Site Cache", 'cwvpsb');?></button>
        <p class="description"><?php echo esc_html__("Caching pages will reduce the response time of your site and your web pages load much faster, directly from cache", 'cwvpsb');?></p>
        <?php
    } 

    function add_admin_menus() {
        add_menu_page( esc_html__('Speed Booster', 'cwvpsb'), esc_html__('Speed Booster', 'cwvpsb'), 'manage_options', $this->plugin_options_key, array( &$this, 'plugin_options_page' ) ,'dashicons-superhero');
    }

    function plugin_options_page() {
        $tab = isset( $_GET['tab'] ) ? $_GET['tab'] : $this->images;?>
        <div class="wrap">
            <?php $this->plugin_options_tabs(); ?>
            <form method="post" action="options.php">
                <?php wp_nonce_field( 'update-options' ); ?>
                <?php settings_fields( $tab ); ?>
                <?php do_settings_sections( $tab ); ?>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    function plugin_options_tabs() {
        $current_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : $this->images;

        screen_icon();
        echo '<h2 class="nav-tab-wrapper">';
        foreach ( $this->plugin_settings_tabs as $tab_key => $tab_caption ) {
            $active = $current_tab == $tab_key ? 'nav-tab-active' : '';
            echo '<a class="nav-tab ' . $active . '" href="?page=' . $this->plugin_options_key . '&tab=' . $tab_key . '">' . $tab_caption . '</a>';
        }
        echo '</h2>';
    }
};

$cwvpsb_settings = new cwvpsb_settings;