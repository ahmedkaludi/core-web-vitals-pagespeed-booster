<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}
add_filter('cwvpsb_complete_html_after_dom_loaded','cwvpsb_unused_css');
function cwvpsb_unused_css($html){
	require_once CWVPSB_PLUGIN_DIR."/includes/style-sanitizer.php";
	$tmpDoc = new DOMDocument();
	libxml_use_internal_errors(true);
	$tmpDoc->loadHTML($html);
	$error_codes = [];
	$args        = [
		'validation_error_callback' => static function( $error ) use ( &$error_codes ) {
					$error_codes[] = $error['code'];
		},
		'should_locate_sources'=>true,
		'use_document_element'=>true,
		'include_manifest_comment'=>false,
	];
	$parser = new cwvpsb_treeshaking($tmpDoc,$args);
	$sanitize = $parser->sanitize();
	$whitelist = cwvpsb_css_whitelist_selectors($html);
  	if(!empty($whitelist)){
	    $custom_style_element = $tmpDoc->createElement( 'style' );
	    $custom_style_element->appendChild($tmpDoc->createTextNode( $whitelist ));
	    $tmpDoc->head->appendChild( $custom_style_element );
  	}
	$html = $tmpDoc->saveHTML();
	return $html;
}
$whitelist_css = '';
function cwvpsb_css_whitelist_selectors($html){
    global $whitelist_css;
    return $whitelist_css;
}
add_action('cwvpsb_css_whitelist_data', 'cwvpsb_get_whitelist_css', 10, 1);
function cwvpsb_get_whitelist_css($html){
  $white_list = array();
  $cwvpsb_settings = new cwvpsb_settings;
  $cwvpsb_settings = get_option( $cwvpsb_settings->css );
  $white_list_data = $cwvpsb_settings['whitelist_css'];
  $white_list = preg_split('/\r\n|\r|\n/', $white_list_data);
  	global $whitelist_css;
  	for($i=0;$i<count($white_list);$i++){
        $whitelist_all = $white_list[$i];
        preg_match_all('/'.$whitelist_all.'(.*?){(.*?)}/s', $html, $matches);
    	if(isset($matches[0]) && !empty($matches[0])){
      		foreach($matches[0] as $match){
	        	if(!empty($match)){
	          		$whitelist_css .= $match;
	        	}
      		}
    	}
    }  
}