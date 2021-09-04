<?php

add_action( 'admin_menu', 'cwvpsb_add_admin_menu' );
add_action( 'admin_init', 'cwvpsb_settings_init' );

function cwvpsb_add_admin_menu() { 
    add_menu_page( 'Speed Booster', 'Speed Booster', 'manage_options', 'cwvpsb', 'cwvpsb_options_page' ,'dashicons-superhero');
}

function cwvpsb_settings_init() { 

    register_setting( 'plugin_page', 'cwvpsb_settings' );

    add_settings_section(
        'cwvpsb_plugin_page_section', 
        __( '', 'cwvpsb' ), 
        '', 
        'plugin_page'
    );

    add_settings_field(
        'cwvpsb_checkbox_webp', 
        __( 'Webp images', 'cwvpsb' ), 
        'cwvpsb_checkbox_webp_render', 
        'plugin_page', 
        'cwvpsb_plugin_page_section' 
    );
    add_settings_field(
        'cwvpsb_checkbox_lazyload', 
        __( 'Lazy Load', 'cwvpsb' ), 
        'cwvpsb_checkbox_lazyload_render', 
        'plugin_page', 
        'cwvpsb_plugin_page_section' 
    );
    add_settings_field(
        'cwvpsb_checkbox_minify', 
        __( 'Minification', 'cwvpsb' ), 
        'cwvpsb_checkbox_minify_render', 
        'plugin_page', 
        'cwvpsb_plugin_page_section' 
    );
    add_settings_field(
        'cwvpsb_checkbox_unused_css', 
        __( 'Remove Unused CSS', 'cwvpsb' ), 
        'cwvpsb_checkbox_unused_css_render', 
        'plugin_page', 
        'cwvpsb_plugin_page_section' 
    );
    add_settings_field(
        'cwvpsb_checkbox_fonts', 
        __( 'Google Fonts Optimizations', 'cwvpsb' ), 
        'cwvpsb_checkbox_fonts_render', 
        'plugin_page', 
        'cwvpsb_plugin_page_section' 
    );
    add_settings_field(
        'cwvpsb_checkbox_delayjs', 
        __( 'Delay JavaScript Execution', 'cwvpsb' ), 
        'cwvpsb_checkbox_delayjs_render', 
        'plugin_page', 
        'cwvpsb_plugin_page_section' 
    );
    add_settings_field(
        'cwvpsb_checkbox_cache', 
        __( 'Cache', 'cwvpsb' ), 
        'cwvpsb_checkbox_cache_render', 
        'plugin_page', 
        'cwvpsb_plugin_page_section' 
    );
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
    <p class="description">Caching pages will reduce the response time of your site and your web pages load much faster, directly from cache</p>
    <?php
}
function cwvpsb_options_page() {?>
        <form action='options.php' method='post'>
            <h2>Core Web Vitals & PageSpeed Booster Settings</h2>
            <?php
            settings_fields( 'plugin_page' );
            do_settings_sections( 'plugin_page' );
            submit_button();
            ?>
        </form>
        <?php
}