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
		$css = '.td-icon-search:before {content: "\e80a";}.td-icon-mobile:before {content: "\e83e";}.td-icon-menu-left:before {content: "\e80c";}.td-icon-menu-right:before {content: "\e80d";}.td-icon-star:before {content: "\e80f";}.td-icon-facebook:before {content: "\e818";}.td-icon-googleplus:before {content: "\e81b";}.td-icon-instagram:before {content: "\e81d";}.td-icon-pinterest:before {content: "\e825";}.td-icon-reddit:before {content: "\e827";}.td-icon-tumblr:before {content: "\e830";}.td-icon-twitter:before {content: "\e831";}.td-icon-youtube:before {content: "\e836";}.td-icon-comments:before {content: "\e83b";}.td-icon-mobile:before {content: "\e83e";}.td-icon-whatsapp:before {content: "\f232";}.td-icon-print:before {content: "\f02f";}.td-icon-telegram:before {content: "\f2c6";}.td-icon-line:before {content: "\e906";}.td-js-loaded .td-post-sharing {-webkit-transition: opacity 0.3s;transition: opacity 0.3s;opacity: 1;}ul.sf-js-enabled > li > a > i.td-icon-menu-down:before {content: "\e806";}.td-header-wrap .td-drop-down-search.td-drop-down-search-open {visibility: visible;opacity: 1;transform: translate3d(0, 0, 0);-webkit-transform: translate3d(0, 0, 0);pointer-events: auto;}.td-icon-share:before {content: "\e829";}';
	}
	if ( function_exists( 'yasr_fs' ) ) {
		$css = '.yasr-star-rating {background-image: url(https://binary-options-brokers-reviews.com/wp-content/plugins/yet-another-stars-rating/includes/img/star_2.svg);}.yasr-star-rating .yasr-star-value {background: url(https://binary-options-brokers-reviews.com/wp-content/plugins/yet-another-stars-rating/includes/img/star_3.svg);}.yasr-star-rating .yasr-star-value {height: 100%;}';
	}
	if (function_exists('ribbon_lite_setup')) {
 		$css .= '.icon-plus:before { content: "\e800" }.icon-bookmark:before { content: "\e801" }.icon-comment:before { content: "\e802" }.icon-users:before { content: "\e803" }.icon-minus:before { content: "\e804" }.icon-mail:before { content: "\e805" }.icon-twitter:before { content: "\f099" }.icon-facebook:before { content: "\f09a" }.icon-rss:before { content: "\f09e" }.icon-menu:before { content: "\f0c9" }.icon-pinterest-circled:before { content: "\f0d2" }.icon-gplus:before { content: "\f0d5" }.icon-linkedin:before { content: "\f0e1" }.icon-angle-double-right:before { content: "\f101" }.icon-angle-left:before { content: "\f104" }.icon-angle-right:before { content: "\f105" }.icon-angle-up:before { content: "\f106" }.icon-angle-down:before { content: "\f107" }.icon-github:before { content: "\f113" }.icon-youtube:before { content: "\f167" }.icon-dropbox:before { content: "\f16b" }.icon-instagram:before { content: "\f16d" }.icon-flickr:before { content: "\f16e" }.icon-tumblr:before { content: "\f173" }.icon-up:before { content: "\f176" }.icon-dribbble:before { content: "\f17d" }.icon-skype:before { content: "\f17e" }.icon-foursquare:before { content: "\f180" }.icon-vimeo-squared:before { content: "\f194" }.icon-reddit:before { content: "\f1a1" }.icon-stumbleupon:before { content: "\f1a4" }.icon-behance:before { content: "\f1b4" }.icon-soundcloud:before { content: "\f1be" }.menu-item-has-children > a:after {content: "\f101";}.toggle-menu.active > .toggle-caret .ribbon-icon:before { content: "\e804" }  .toggle-menu .active > .toggle-caret .ribbon-icon:before { content: "\e804" } a#pull:after {content: "\f0c9";}';
	}
  return $css;
}