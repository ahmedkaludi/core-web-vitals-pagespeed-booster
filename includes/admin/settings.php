<?php

add_action("admin_menu", "cwvpsb_add_admin_menu");
function cwvpsb_add_admin_menu(){
    add_menu_page( 'Speed Booster', 'Speed Booster', 'manage_options', 'cwvpsb', 'cwvpsb_options_page' ,'dashicons-superhero');
}

function cwvpsb_options_page(){?>
            <div class="wrap">
            <h1>Core Web Vitals & PageSpeed Booster Settings</h1>
            <?php
                $active_tab = "cwvpsb";
                if(isset($_GET["tab"]))
                {
                    if($_GET["tab"] == "cwvpsb")
                    {
                        $active_tab = "cwvpsb";
                    }
                    else if($_GET["tab"] == "css-options")
                    {
                        $active_tab = "css-options";
                    }
                    else if($_GET["tab"] == "js-options")
                    {
                        $active_tab = "js-options";
                    }
                    else if($_GET["tab"] == "cache-options")
                    {
                        $active_tab = "cache-options";
                    }
                }
            ?>

            <h2 class="nav-tab-wrapper">
                <a href="?page=cwvpsb&tab=cwvpsb" class="nav-tab <?php if($active_tab == 'cwvpsb'){echo 'nav-tab-active';} ?> "><?php _e('Images', 'cwvpsb'); ?></a>
                <a href="?page=cwvpsb&tab=css-options" class="nav-tab <?php if($active_tab == 'css-options'){echo 'nav-tab-active';} ?>"><?php _e('CSS', 'cwvpsb'); ?></a>
                <a href="?page=cwvpsb&tab=js-options" class="nav-tab <?php if($active_tab == 'js-options'){echo 'nav-tab-active';} ?>"><?php _e('JS', 'cwvpsb'); ?></a>
                <a href="?page=cwvpsb&tab=cache-options" class="nav-tab <?php if($active_tab == 'cache-options'){echo 'nav-tab-active';} ?>"><?php _e('Cache', 'cwvpsb'); ?></a>
            </h2>

            <form method="post" action="options.php">
                <?php
               
                    settings_fields("cwvpsb_section");
                   
                    do_settings_sections("cwvpsb");
               
                    submit_button();
                   
                ?>          
            </form>
        </div>
        <?php
    }

add_action("admin_init", "cwvpsb_display_options");
function cwvpsb_display_options(){

        add_settings_section('cwvpsb_section', __return_false(), '__return_false', 'cwvpsb');

        //here we display the sections and options in the settings page based on the active tab
        if(isset($_GET["tab"]))
        {
            if($_GET["tab"] == "cwvpsb")
            {
            
                add_settings_field(
                    'cwvpsb_checkbox_webp', 
                    __( 'Webp images', 'cwvpsb' ), 
                    'cwvpsb_checkbox_webp_render', 
                    'cwvpsb', 
                    'cwvpsb_section' 
                );
                register_setting("cwvpsb_section", "cwvpsb_checkbox_webp");

                add_settings_field(
                    'cwvpsb_checkbox_lazyload', 
                    __( 'Lazy Load', 'cwvpsb' ), 
                    'cwvpsb_checkbox_lazyload_render', 
                    'cwvpsb', 
                    'cwvpsb_section' 
                );
                register_setting("cwvpsb_section", "cwvpsb_checkbox_lazyload");
            }
            else if($_GET["tab"] == "css-options")
                    {
                         add_settings_field(
        'cwvpsb_checkbox_minify', 
        __( 'Minification', 'cwvpsb' ), 
        'cwvpsb_checkbox_minify_render', 
        'cwvpsb', 
        'cwvpsb_section' 
    );      
                register_setting("cwvpsb_section", "cwvpsb_checkbox_minify");

              add_settings_field(
        'cwvpsb_checkbox_unused_css', 
        __( 'Remove Unused CSS', 'cwvpsb' ), 
        'cwvpsb_checkbox_unused_css_render', 
        'cwvpsb', 
        'cwvpsb_section' 
    );   
              register_setting("cwvpsb_section", "cwvpsb_checkbox_unused_css");
              add_settings_field(
        'cwvpsb_checkbox_fonts', 
        __( 'Google Fonts Optimizations', 'cwvpsb' ), 
        'cwvpsb_checkbox_fonts_render', 
        'cwvpsb', 
        'cwvpsb_section' 
    );
              register_setting("cwvpsb_section", "cwvpsb_checkbox_fonts");
                    }
                    else if($_GET["tab"] == "js-options")
                    {
                         add_settings_field(
        'cwvpsb_checkbox_delayjs', 
        __( 'Delay JavaScript Execution', 'cwvpsb' ), 
        'cwvpsb_checkbox_delayjs_render', 
        'cwvpsb', 
        'cwvpsb_section' 
    );
                         register_setting("cwvpsb_section", "cwvpsb_checkbox_delayjs");
                    }
                    else if($_GET["tab"] == "cache-options")
                    {
                        add_settings_field(
        'cwvpsb_checkbox_cache', 
        __( 'Cache', 'cwvpsb' ), 
        'cwvpsb_checkbox_cache_render', 
        'cwvpsb', 
        'cwvpsb_section' );
                         register_setting("cwvpsb_section", "cwvpsb_checkbox_cache");
                    }
        }
       
    }

function cwvpsb_checkbox_webp_render() {
    $options = get_option( 'cwvpsb_settings' );
    ?>
    <label class="switch">
    <input type='checkbox' name='cwvpsb_settings[cwvpsb_checkbox_webp]' <?php checked( $options['cwvpsb_checkbox_webp'], 1 ); ?> value='1'>
    <span class="slider round"></span></label>
    <p class="description">Images are converted to WebP on the fly if the browser supports it. You don't have to do anything</p>
    <?php
}

function cwvpsb_checkbox_lazyload_render() {
    $options = get_option( 'cwvpsb_settings' );
    ?>
    <label class="switch">
    <input type='checkbox' name='cwvpsb_settings[cwvpsb_checkbox_lazyload]' <?php checked( $options['cwvpsb_checkbox_lazyload'], 1 ); ?> value='1'>
    <span class="slider round"></span></label>
    <p class="description">Lazy Load delays loading of images and iframes in long web pages. which are outside of viewport and will not be loaded before user scrolls to them</p>
    <?php
}

function cwvpsb_checkbox_minify_render() {

    $options = get_option( 'cwvpsb_settings' );
    ?>
    <label class="switch">
    <input type='checkbox' name='cwvpsb_settings[cwvpsb_checkbox_minify]' <?php checked( $options['cwvpsb_checkbox_minify'], 1 ); ?> value='1'>
    <span class="slider round"></span></label>
    <p class="description">You will see the source of your HTML, CSS and JavaScript are now compressed and the size will be smaller which will be helpful to improve your page load speed</p>
    <?php
}

function cwvpsb_checkbox_unused_css_render() {

    $options = get_option( 'cwvpsb_settings' );
    ?>
    <label class="switch">
    <input type='checkbox' name='cwvpsb_settings[cwvpsb_checkbox_unused_css]' <?php checked( $options['cwvpsb_checkbox_unused_css'], 1 ); ?> value='1'>
    <span class="slider round"></span></label>
    <p class="description">Makes your site even faster and lighter by automatically removing unused CSS from your website</p>
    <?php
}

function cwvpsb_checkbox_fonts_render() {

    $options = get_option( 'cwvpsb_settings' );
    ?>
    <label class="switch">
    <input type='checkbox' name='cwvpsb_settings[cwvpsb_checkbox_fonts]' <?php checked( $options['cwvpsb_checkbox_fonts'], 1 ); ?> value='1'>
    <span class="slider round"></span></label>
    <p class="description">Locally hosting Google fonts for Pagespeed Insights or GT Metrix improvements</p>
    <?php
}

function cwvpsb_checkbox_delayjs_render() {

    $options = get_option( 'cwvpsb_settings' );
    ?>
    <label class="switch">
    <input type='checkbox' name='cwvpsb_settings[cwvpsb_checkbox_delayjs]' <?php checked( $options['cwvpsb_checkbox_delayjs'], 1 ); ?> value='1'>
    <span class="slider round"></span></label>
    <p class="description">Delays the loading of JavaScript files until the user interacts like scroll, click etc, which improves performance</p>
    <?php
}

function cwvpsb_checkbox_cache_render() {

    $options = get_option( 'cwvpsb_settings' );
    ?>
    <label class="switch">
    <input type='checkbox' name='cwvpsb_settings[cwvpsb_checkbox_cache]' <?php checked( $options['cwvpsb_checkbox_cache'], 1 ); ?> value='1'>
    <span class="slider round"></span></label>
    <button class="cache-btn" name="cache-btn"><i class="cache-trash"></i>&emsp;Clear Site Cache</button>
    <p class="description">Caching pages will reduce the response time of your site and your web pages load much faster, directly from cache</p>
    <?php
}