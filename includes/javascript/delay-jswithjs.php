<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

add_filter('cwvpsb_complete_html_after_dom_loaded','cwvpsb_load_js');
function cwvpsb_load_js($content) {
    $min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';	
    $content = preg_replace('/<script(.*?)<\/script>/is', '<script type="cwvlazyloadscript" $1</script>', $content);
    $pattern = '/<head[^>]*>/i';
    $lazyload_script = CWVPSB_PLUGIN_DIR."includes/javascript/lazyload{$min}.js";
    $lazyload_script = cwvpsb_read_file_contents($lazyload_script);
    $content = preg_replace( $pattern, "$0<script id='cwvpsb-delayed-script'>{$lazyload_script}</script>", $content, 1 );
    return $content;
}