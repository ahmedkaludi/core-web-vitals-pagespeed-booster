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
	$html = $tmpDoc->saveHTML($tmpDoc->documentElement);
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
		$css = '.td-icon-search:before {content: "\e80a";}.td-icon-mobile:before {content: "\e83e";}.td-icon-menu-left:before {content: "\e80c";}.td-icon-menu-right:before {content: "\e80d";}.td-icon-star:before {content: "\e80f";}.td-icon-facebook:before {content: "\e818";}.td-icon-googleplus:before {content: "\e81b";}.td-icon-instagram:before {content: "\e81d";}.td-icon-pinterest:before {content: "\e825";}.td-icon-reddit:before {content: "\e827";}.td-icon-tumblr:before {content: "\e830";}.td-icon-twitter:before {content: "\e831";}.td-icon-youtube:before {content: "\e836";}.td-icon-comments:before {content: "\e83b";}.td-icon-mobile:before {content: "\e83e";}.td-icon-whatsapp:before {content: "\f232";}.td-icon-print:before {content: "\f02f";}.td-icon-telegram:before {content: "\f2c6";}.td-icon-line:before {content: "\e906";}.td-js-loaded .td-post-sharing {-webkit-transition: opacity 0.3s;transition: opacity 0.3s;opacity: 1;}ul.sf-js-enabled > li > a > i.td-icon-menu-down:before {content: "\e806";}.td-header-wrap .td-drop-down-search.td-drop-down-search-open {visibility: visible;opacity: 1;transform: translate3d(0, 0, 0);-webkit-transform: translate3d(0, 0, 0);pointer-events: auto;}.td-icon-share:before {content: "\e829";}#wp-admin-bar-ai-toolbar-settings .ab-icon:before {content: "\f111";}.td-icon-right:before {content: "\e803";}.td-icon-menu-down:before {content: "\e806";}.td-blog-architecture .td-header-style-5 .sf-menu > li:first-child {padding-right: 15px;}.td-js-loaded .td-category-siblings {opacity: 1;-webkit-transition: opacity 0.3s;transition: opacity 0.3s;}.td-menu-mob-open-menu .td-demo-multicolumn-2 .sub-menu {padding: 0;-moz-column-count: 1;-webkit-column-count: 1;column-count: 1;}.td-menu-mob-open-menu .td-demo-menuitem-hide {display: none;}.td-menu-mob-open-menu #td-outer-wrap {position: fixed;transform: scale3d(0.9, 0.9, 0.9);-webkit-transform: scale3d(0.9, 0.9, 0.9);-webkit-box-shadow: 0 0 46px #000000;box-shadow: 0 0 46px #000000;}.td-menu-mob-open-menu #td-mobile-nav {height: calc(100% + 1px);overflow: auto;transform: translate3d(0, 0, 0);-webkit-transform: translate3d(0, 0, 0);left: 0;}.td-menu-mob-open-menu #td-mobile-nav label {-webkit-transition: all 0.2s ease;transition: all 0.2s ease;}.td-menu-mob-open-menu #td-mobile-nav .td-login-animation {-webkit-transition: all 0.5s ease 0.5s;transition: all 0.5s ease 0.5s;}.td-menu-mob-open-menu .td-menu-background {transform: translate3d(0, 0, 0);-webkit-transform: translate3d(0, 0, 0);}.td-menu-mob-open-menu .td-mobile-container {-webkit-transition: all 0.5s ease 0.5s;transition: all 0.5s ease 0.5s;}.admin-bar #td-mobile-nav {padding-top: 32px;}@media (max-width: 767px) {.admin-bar #td-mobile-nav {padding-top: 46px;}}#td-mobile-nav {padding: 0;position: fixed;width: 100%;height: calc(100% + 1px);top: 0;z-index: 9999;visibility: hidden;transform: translate3d(-99%, 0, 0);-webkit-transform: translate3d(-99%, 0, 0);left: -1%;font-family: -apple-system, ".SFNSText-Regular", "San Francisco", "Roboto", "Segoe UI", "Helvetica Neue", "Lucida Grande", sans-serif;}#td-mobile-nav .td_display_err {text-align: center;color: #fff;border: none;-webkit-box-shadow: 0 0 8px rgba(0, 0, 0, 0.16);box-shadow: 0 0 8px rgba(0, 0, 0, 0.16);margin: -9px -30px 24px;font-size: 14px;border-radius: 0;padding: 12px;position: relative;background-color: rgba(255, 255, 255, 0.06);display: none;}#td-mobile-nav input:invalid {box-shadow: none !important;}.td-js-loaded .td-menu-background,.td-js-loaded #td-mobile-nav {visibility: visible;-webkit-transition: transform 0.5s cubic-bezier(0.79, 0.14, 0.15, 0.86);transition: transform 0.5s cubic-bezier(0.79, 0.14, 0.15, 0.86);}#td-mobile-nav {height: 1px;overflow: hidden;}#td-mobile-nav .td-menu-socials {padding: 0 65px 0 20px;overflow: hidden;height: 60px;}#td-mobile-nav .td-social-icon-wrap {margin: 20px 5px 0 0;display: inline-block;}#td-mobile-nav .td-social-icon-wrap i {border: none;background-color: transparent;font-size: 14px;width: 40px;height: 40px;line-height: 38px;color: #fff;vertical-align: middle;}#td-mobile-nav .td-social-icon-wrap .td-icon-instagram {font-size: 16px;}.td-menu-mob-open-menu #td-mobile-nav {height: calc(100% + 1px);overflow: auto;transform: translate3d(0, 0, 0);-webkit-transform: translate3d(0, 0, 0);left: 0;}.td-menu-mob-open-menu #td-mobile-nav label {-webkit-transition: all 0.2s ease;transition: all 0.2s ease;}.td-menu-mob-open-menu #td-mobile-nav .td-login-animation {-webkit-transition: all 0.5s ease 0.5s;transition: all 0.5s ease 0.5s;}#td-mobile-nav .td-login-animation {opacity: 0;position: absolute;top: 0;width: 100%;}#td-mobile-nav .td-login-animation .td-login-inputs {height: 76px;}#td-mobile-nav .td-login-hide {-webkit-transition: all 0.5s ease 0s;transition: all 0.5s ease 0s;visibility: hidden !important;}#td-mobile-nav .td-login-show {visibility: visible !important;opacity: 1;}#td-mobile-nav label {position: absolute;top: 26px;left: 10px;font-size: 17px;color: #fff;opacity: 0.6;pointer-events: none;}.tagdiv-small-theme .td-menu-background,.tagdiv-small-theme #td-mobile-nav {visibility: visible;transition: transform 0.5s cubic-bezier(0.79, 0.14, 0.15, 0.86);}.tagdiv-small-theme #td-mobile-nav .td-mobile-content {padding-top: 74px;}.tagdiv-small-theme #td-mobile-nav .menu-item {position: relative;}.td-icon-close-mobile:before {content: "\e900";}.td-search-opened #td-outer-wrap {position: fixed;transform: scale3d(0.9, 0.9, 0.9);-webkit-transform: scale3d(0.9, 0.9, 0.9);-webkit-box-shadow: 0 0 46px;box-shadow: 0 0 46px;}.td-search-opened .td-search-wrap-mob .td-drop-down-search {opacity: 1;visibility: visible;-webkit-transition: all 0.5s ease 0.3s;transition: all 0.5s ease 0.3s;}.td-search-opened .td-search-background {transform: translate3d(0, 0, 0);-webkit-transform: translate3d(0, 0, 0);visibility: visible;}.td-search-opened .td-search-input:after {transform: scaleX(1);-webkit-transform: scaleX(1);}.admin-bar .td-search-wrap-mob {padding-top: 32px;}@media (max-width: 767px) {.admin-bar .td-search-wrap-mob {padding-top: 46px;}}.tdi_40 .tdb-menu > li > a .tdb-sub-menu-icon {top: 18px;}.tdb_header_search .tdb-head-search-btn {margin-top: 15px;}.tdi_122 .tdm-social-item i {margin-top: 10px;}.td-social-but-icon {padding: 14px;}.td-icon-down:before {content: "\e801";}.tdb_header_search .tdb-drop-down-search-open {visibility: visible;opacity: 1;-webkit-transform: translate3d(0, 0, 0);transform: translate3d(0, 0, 0);}.switcher .selected a:after{content: "\02C5"!important;font-size: 17px;margin: 5px auto;}.td-icon-video-thumb-play:before {content: "\e9d4";}.tdm-header .header-search-wrap {top: 30px;}.page-nav .td-icon-menu-right {padding: 6px 0;}';
	}
	if ( function_exists( 'yasr_fs' ) ) {
		$css = '.yasr-star-rating {background-image: url(https://binary-options-brokers-reviews.com/wp-content/plugins/yet-another-stars-rating/includes/img/star_2.svg);}.yasr-star-rating .yasr-star-value {background: url(https://binary-options-brokers-reviews.com/wp-content/plugins/yet-another-stars-rating/includes/img/star_3.svg);}.yasr-star-rating .yasr-star-value {height: 100%;}';
	}
	if (function_exists('ribbon_lite_setup')) {
 		$css .= '.icon-plus:before { content: "\e800" }.icon-bookmark:before { content: "\e801" }.icon-comment:before { content: "\e802" }.icon-users:before { content: "\e803" }.icon-minus:before { content: "\e804" }.icon-mail:before { content: "\e805" }.icon-twitter:before { content: "\f099" }.icon-facebook:before { content: "\f09a" }.icon-rss:before { content: "\f09e" }.icon-menu:before { content: "\f0c9" }.icon-pinterest-circled:before { content: "\f0d2" }.icon-gplus:before { content: "\f0d5" }.icon-linkedin:before { content: "\f0e1" }.icon-angle-double-right:before { content: "\f101" }.icon-angle-left:before { content: "\f104" }.icon-angle-right:before { content: "\f105" }.icon-angle-up:before { content: "\f106" }.icon-angle-down:before { content: "\f107" }.icon-github:before { content: "\f113" }.icon-youtube:before { content: "\f167" }.icon-dropbox:before { content: "\f16b" }.icon-instagram:before { content: "\f16d" }.icon-flickr:before { content: "\f16e" }.icon-tumblr:before { content: "\f173" }.icon-up:before { content: "\f176" }.icon-dribbble:before { content: "\f17d" }.icon-skype:before { content: "\f17e" }.icon-foursquare:before { content: "\f180" }.icon-vimeo-squared:before { content: "\f194" }.icon-reddit:before { content: "\f1a1" }.icon-stumbleupon:before { content: "\f1a4" }.icon-behance:before { content: "\f1b4" }.icon-soundcloud:before { content: "\f1be" }.menu-item-has-children > a:after {content: "\f101";}.toggle-menu.active > .toggle-caret .ribbon-icon:before { content: "\e804" }  .toggle-menu .active > .toggle-caret .ribbon-icon:before { content: "\e804" } a#pull:after {content: "\f0c9";}li#menu-item-2994:hover ul,li#menu-item-2995:hover ul,li#menu-item-2988:hover ul { display: block; }';
	}
	if (function_exists('jnews_sanitize_output')) {
 		$css .= '.jeg_header .container,.jeg_block_container ,.jeg_navbar_mobile .container{height: initial;}.jeg_search_expanded .jeg_search_popup_expand .jeg_search_form {opacity: 1;visibility: visible;padding: 20px;height: auto;-webkit-transition: padding .2s,height .1s,opacity .15s;-o-transition: padding .2s,height .1s,opacity .15s;transition: padding .2s,height .1s,opacity .15s;}.fa.fa-close:before {content: "\f00d";}.jeg_navbar:not(.jeg_navbar_boxed):not(.jeg_navbar_menuborder) .jeg_search_popup_expand:last-child .jeg_search_form, .jeg_navbar:not(.jeg_navbar_boxed):not(.jeg_navbar_menuborder) .jeg_search_popup_expand:last-child .jeg_search_result {right: -17px;top: 33px;}body.jeg_show_menu {overflow: hidden;}body.admin-bar.jeg_show_menu .jeg_menu_close{top:44px}@media screen and (max-width:782px){.admin-bar .jeg_mobile_wrapper{padding-top:46px}body.admin-bar.jeg_show_menu .jeg_menu_close{top:65px}}body.jeg_show_menu{overflow:hidden;}.jeg_show_menu .jeg_bg_overlay{opacity:.85;visibility:visible;-webkit-transition:.4s cubic-bezier(.22,.61,.36,1) .1s;transition:.4s cubic-bezier(.22,.61,.36,1) .1s}.jeg_show_menu .jeg_mobile_wrapper{opacity:1;-webkit-transform:translate3d(0,0,0);transform:translate3d(0,0,0);-webkit-box-shadow:1px 0 5px rgba(0,0,0,.1),3px 0 25px rgba(0,0,0,.18);box-shadow:1px 0 5px rgba(0,0,0,.1),3px 0 25px rgba(0,0,0,.18)}.jeg_show_menu .jeg_menu_close{opacity:.75;visibility:visible;-webkit-transform:rotate(0);transform:rotate(0);-webkit-transition:.2s ease .3s;transition:.2s ease .3s}.jeg_block_nav .prev , .jeg_block_nav .next{padding: 5px;}';
	}
  return $css;
}