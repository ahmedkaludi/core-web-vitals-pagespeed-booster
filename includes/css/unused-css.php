<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}
add_filter('cwvpsb_complete_html_after_dom_loaded','cwvpsb_unused_css');
function cwvpsb_unused_css($html){
	$settings = cwvpsb_defaults();
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
	$custom_style_element = $tmpDoc->createElement( 'style' );
	$tmpDoc->head->appendChild( $custom_style_element );
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
  $settings = cwvpsb_defaults();
  $white_list_data = $settings['whitelist_css'];
  $white_list_data = apply_filters('cwvpsb_whitelist_css', $white_list_data );
  $white_list = preg_split('/\r\n|\r|\n|\s/', $white_list_data);
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

add_filter('cwvpsb_whitelist_css', 'cwvpsb_whitelist_css_global');
function cwvpsb_whitelist_css_global($whitelist){
	if (empty($whitelist)) {
		$whitelist = '\s';
	}
	$whitelist .= '.et-waypoint img.emoji';
	return $whitelist;
}

add_filter('cwvpsb_whitelist_css_code', 'cwvpsb_whitelist_css_code');
function cwvpsb_whitelist_css_code($css){
	$theme = wp_get_theme();
	if ( 'Newsmag Child theme' == $theme->name ) {
		$css = '.essb_topbar.essb_active_topbar {margin: 0;}.td-js-loaded .sf-menu ul {visibility: visible;}.td-drop-down-search.td-drop-down-search-open {display:block !important;z-index: 9999 !important;}th.poptip.sort_default_asc , .full_table .left:first-child ,tfoot .left:first-child, tr.over_header, th.poptip.center{background-color: #dadcde;border: 1px solid #747678;opacity: initial;}table{border-collapse:collapse;border-spacing:0}td,th{padding:0}table,tr,td{page-break-before:avoid}table{width:100%;font-size: .875em;}table th{text-align:left;border:1px solid #e6e6e6;padding:2px 8px}table td{border:1px solid #e6e6e6;padding:2px 8px}table .odd td{background-color:#fcfcfc}table th {border: inherit;}';
	}
	if ( 'Newspaper' == $theme->name ) {
		$css = '.td-icon-search:before {content: "\e80a";}.td-icon-mobile:before {content: "\e83e";}.td-icon-menu-left:before {content: "\e80c";}.td-icon-menu-right:before {content: "\e80d";}.td-icon-star:before {content: "\e80f";}.td-icon-facebook:before {content: "\e818";}.td-icon-googleplus:before {content: "\e81b";}.td-icon-instagram:before {content: "\e81d";}.td-icon-pinterest:before {content: "\e825";}.td-icon-reddit:before {content: "\e827";}.td-icon-tumblr:before {content: "\e830";}.td-icon-twitter:before {content: "\e831";}.td-icon-youtube:before {content: "\e836";}.td-icon-comments:before {content: "\e83b";}.td-icon-mobile:before {content: "\e83e";}.td-icon-whatsapp:before {content: "\f232";}.td-icon-print:before {content: "\f02f";}.td-icon-telegram:before {content: "\f2c6";}.td-icon-line:before {content: "\e906";}';
	}

  return $css;
}