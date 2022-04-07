<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}
function cwvpsb_get_atts_array($atts_string) {

	if(!empty($atts_string)) {
		$atts_array = array_map(
			function(array $attribute) {
				return $attribute['value'];
			},
			wp_kses_hair($atts_string, wp_allowed_protocols())
		);
		return $atts_array;
	}
	return false;
}

function cwvpsb_get_atts_string($atts_array) {

	if(!empty($atts_array)) {
		$assigned_atts_array = array_map(
		function($name, $value) {
			if($value === '') {
				return $name;
			}
			return sprintf('%s="%s"', $name, esc_attr($value));
		},
			array_keys($atts_array),
			$atts_array
		);
		$atts_string = implode(' ', $assigned_atts_array);
		return $atts_string;
	}
	return false;
}

function cwvpsb_delay_js_main() {
	if ( function_exists('is_checkout') && is_checkout() || (function_exists('is_feed')&& is_feed()) ) {
        return;
    }
    if( class_exists( 'next_article_layout' ) ) {
		return ;
	}

	if ( function_exists('elementor_load_plugin_textdomain') && \Elementor\Plugin::$instance->preview->is_preview_mode() ) {
    	return;
	}
	add_filter('cwvpsb_complete_html_after_dom_loaded', 'cwvpsb_delay_js_html', 2);
	add_action('wp_footer', 'cwvpsb_delay_js_load', PHP_INT_MAX);
}
add_action('wp', 'cwvpsb_delay_js_main');

function cwvpsb_delay_js_html($html) {

	$html_no_comments = $html;//preg_replace('/<!--(.*)-->/Uis', '', $html);
	preg_match_all('#(<script\s?([^>]+)?\/?>)(.*?)<\/script>#is', $html_no_comments, $matches);
	if(!isset($matches[0])) {
		return $html;
	}
	$combined_ex_js_arr = array();
	foreach($matches[0] as $i => $tag) {
		$atts_array = !empty($matches[2][$i]) ? cwvpsb_get_atts_array($matches[2][$i]) : array();
		if(isset($atts_array['type']) && stripos($atts_array['type'], 'javascript') == false || 
			isset($atts_array['id']) && stripos($atts_array['id'], 'corewvps-mergejsfile') !== false
		) {
			continue;
		}
		$delay_flag = false;
		$excluded_scripts = array(
			'cwvpsb-delayed-scripts',
		);

		if(!empty($excluded_scripts)) {
			foreach($excluded_scripts as $excluded_script) {
				if(strpos($tag, $excluded_script) !== false) {
					continue 2;
				}
			}
		}

		$delay_flag = true;
		if(!empty($atts_array['type'])) {
			$atts_array['data-cwvpsb-type'] = $atts_array['type'];
		}

		$atts_array['type'] = 'cwvpsbdelayedscript';
		$atts_array['defer'] = 'defer';

		$include = true;
		if(isset($atts_array['src'])){
			$regex = cwvpsb_delay_exclude_js();
			if($regex && preg_match( '#(' . $regex . ')#', $atts_array['src'] )){
				$combined_ex_js_arr[] = $atts_array['src'];
				$html = str_replace($tag, '', $html);
				$include = false;		
			}
		}
		if($include && isset($atts_array['id'])){
			$regex = cwvpsb_delay_exclude_js();
			$file_path =  $atts_array['id'];
			if($regex && preg_match( '#(' . $regex . ')#',  $file_path)){
				$include = false;		
			}
		}
		if($include && isset($matches[3][$i])){
			$regex = cwvpsb_delay_exclude_js();
			$file_path =  $matches[3][$i];
			if($regex && preg_match( '#(' . $regex . ')#',  $file_path)){
				$include = false;		
			}
		}
		if($delay_flag && $include) {
	
			$delayed_atts_string = cwvpsb_get_atts_string($atts_array);
	        $delayed_tag = sprintf('<script %1$s>', $delayed_atts_string) . (!empty($matches[3][$i]) ? $matches[3][$i] : '') .'</script>';
			$html = str_replace($tag, $delayed_tag, $html);
			continue;
		}
	}
	if($combined_ex_js_arr){
		$html = cwvpsb_combine_js_files($combined_ex_js_arr, $html);
	}
	return $html;
}

function cwvpsb_combine_js_files($combined_ex_js_arr, $html){
	if(!count($combined_ex_js_arr)){ return ; }
	include_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
     include_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
     if (!class_exists('WP_Filesystem_Direct')) {
         return false;
     }

     $filename = md5('cc-js-files');
     $upload_dir = wp_upload_dir(); 
	 $user_dirname = $upload_dir['basedir'] . '/' . 'cc-cwvpb-js';
	 $user_urlname = $upload_dir['baseurl'] . '/' . 'cc-cwvpb-js';
	 $jsUrl = '';
	 
	 if(!file_exists($user_dirname.'/'.$filename.'.js')){
	 	
	     $jscontent = '';
	     foreach($combined_ex_js_arr as $file_url){
	     	$parse_url = parse_url($file_url);
	     	$file_path = str_replace(array(get_site_url(),'?'.$parse_url['query']),array(ABSPATH,''),$file_url);
		    $wp_filesystem = new WP_Filesystem_Direct(null);
		    $js = $wp_filesystem->get_contents($file_path);
		    unset($wp_filesystem);
		    if (empty($js)) {
		         $request = wp_remote_get($file_url);
		         $js = wp_remote_retrieve_body($request);
		    }
		    $jscontent .= "\n/*File: $file_url*/\n".$js;
		}
		if($jscontent){
			$fileSystem = new WP_Filesystem_Direct( new StdClass() );
			if(!file_exists($user_dirname)) wp_mkdir_p($user_dirname);
			$fileSystem->put_contents($user_dirname.'/'.$filename.'.js', $jscontent, 644 );
			unset($fileSystem);
		}
	}
	return $html;
}


function cwvpsb_delay_exclude_js(){
	$settings = cwvpsb_defaults();
	$inputs['exclude_js'] = $settings['exclude_delay_js'];
	if ( ! empty( $inputs['exclude_js'] ) ) {
		if ( ! is_array( $inputs['exclude_js'] ) ) {
			$inputs['exclude_js'] = explode( "\n", $inputs['exclude_js'] );
		}
		$inputs['exclude_js'] = array_map( 'trim', $inputs['exclude_js'] );
		//$inputs['exclude_js'] = array_map( 'cwvpsb_sanitize_js', $inputs['exclude_js'] );
		$inputs['exclude_js'] = (array) array_filter( $inputs['exclude_js'] );
		$inputs['exclude_js'] = array_unique( $inputs['exclude_js'] );
	} else {
		$inputs['exclude_js'] = array();
	}
	$excluded_files = array();
	if($inputs['exclude_js']){
		foreach ( $inputs['exclude_js'] as $i => $excluded_file ) {
			// Escape characters for future use in regex pattern.
			$excluded_files[ $i ] = str_replace( '#', '\#', $excluded_file );
		}
	}
	if(is_array($excluded_files)){
		return implode( '|', $excluded_files );
	}else{
		return '';
	}
}
add_action( 'wp_enqueue_scripts',  'cwvpsb_scripts_styles' , 9);
function cwvpsb_scripts_styles(){
	$filename = md5('cc-js-files');
     $upload_dir = wp_upload_dir(); 
	 $user_dirname = $upload_dir['basedir'] . '/' . 'cc-cwvpb-js';
	 $user_urlname = $upload_dir['baseurl'] . '/' . 'cc-cwvpb-js';
	 
	 if(file_exists($user_dirname.'/'.$filename.'.js')){
	 	wp_register_script('corewvps-mergejsfile', $user_urlname.'/'.$filename.'.js', array(), CWVPSB_VERSION, true);
		wp_enqueue_script('corewvps-mergejsfile');
	 }
	
}

function cwvpsb_sanitize_js( $file ) {
	$file = preg_replace( '#\?.*$#', '', $file );
	$ext  = strtolower( pathinfo( $file, PATHINFO_EXTENSION ) );
	return ( 'js' === $ext ) ? trim( $file ) : false;
}

function cwvpsb_delay_js_load() {
  	echo '<script type="text/javascript" id="cwvpsb-delayed-scripts">' . 'cwvpsbUserInteractions=["keydown","mousemove","wheel","touchmove","touchstart","touchend","touchcancel","touchforcechange"],cwvpsbDelayedScripts={normal:[],defer:[],async:[]},jQueriesArray=[];var cwvpsbDOMLoaded=!1;function cwvpsbTriggerDOMListener(){' . 'cwvpsbUserInteractions.forEach(function(e){window.removeEventListener(e,cwvpsbTriggerDOMListener,{passive:!0})}),"loading"===document.readyState?document.addEventListener("DOMContentLoaded",cwvpsbTriggerDelayedScripts):cwvpsbTriggerDelayedScripts()}async function cwvpsbTriggerDelayedScripts(){cwvpsbDelayEventListeners(),cwvpsbDelayJQueryReady(),cwvpsbProcessDocumentWrite(),cwvpsbSortDelayedScripts(),cwvpsbPreloadDelayedScripts(),await cwvpsbLoadDelayedScripts(cwvpsbDelayedScripts.normal),await cwvpsbLoadDelayedScripts(cwvpsbDelayedScripts.defer),await cwvpsbLoadDelayedScripts(cwvpsbDelayedScripts.async),await cwvpsbTriggerEventListeners(),ctl()}function cwvpsbDelayEventListeners(){let e={};function t(t,n){function r(n){return e[t].delayedEvents.indexOf(n)>=0?"perfmatters-"+n:n}e[t]||(e[t]={originalFunctions:{add:t.addEventListener,remove:t.removeEventListener},delayedEvents:[]},t.addEventListener=function(){arguments[0]=r(arguments[0]),e[t].originalFunctions.add.apply(t,arguments)},t.removeEventListener=function(){arguments[0]=r(arguments[0]),e[t].originalFunctions.remove.apply(t,arguments)}),e[t].delayedEvents.push(n)}function n(e,t){const n=e[t];Object.defineProperty(e,t,{get:n||function(){},set:function(n){e["perfmatters"+t]=n}})}t(document,"DOMContentLoaded"),t(window,"DOMContentLoaded"),t(window,"load"),t(window,"pageshow"),t(document,"readystatechange"),n(document,"onreadystatechange"),n(window,"onload"),n(window,"onpageshow")}function cwvpsbDelayJQueryReady(){let e=window.jQuery;Object.defineProperty(window,"jQuery",{get:()=>e,set(t){if(t&&t.fn&&!jQueriesArray.includes(t)){t.fn.ready=t.fn.init.prototype.ready=function(e){cwvpsbDOMLoaded?e.bind(document)(t):document.addEventListener("perfmatters-DOMContentLoaded",function(){e.bind(document)(t)})};const e=t.fn.on;t.fn.on=t.fn.init.prototype.on=function(){if(this[0]===window){function t(e){return e.split(" ").map(e=>"load"===e||0===e.indexOf("load.")?"perfmatters-jquery-load":e).join(" ")}"string"==typeof arguments[0]||arguments[0]instanceof String?arguments[0]=t(arguments[0]):"object"==typeof arguments[0]&&Object.keys(arguments[0]).forEach(function(e){delete Object.assign(arguments[0],{[t(e)]:arguments[0][e]})[e]})}return e.apply(this,arguments),this},jQueriesArray.push(t)}e=t}})}function cwvpsbProcessDocumentWrite(){const e=new Map;document.write=document.writeln=function(t){var n=document.currentScript,r=document.createRange();let a=e.get(n);void 0===a&&(a=n.nextSibling,e.set(n,a));var o=document.createDocumentFragment();r.setStart(o,0),o.appendChild(r.createContextualFragment(t)),n.parentElement.insertBefore(o,a)}}function cwvpsbSortDelayedScripts(){document.querySelectorAll("script[type=cwvpsbdelayedscript]").forEach(function(e){e.hasAttribute("src")?e.hasAttribute("defer")&&!1!==e.defer?cwvpsbDelayedScripts.defer.push(e):e.hasAttribute("async")&&!1!==e.async?cwvpsbDelayedScripts.async.push(e):cwvpsbDelayedScripts.normal.push(e):cwvpsbDelayedScripts.normal.push(e)})}function cwvpsbPreloadDelayedScripts(){var e=document.createDocumentFragment();[...cwvpsbDelayedScripts.normal,...cwvpsbDelayedScripts.defer,...cwvpsbDelayedScripts.async].forEach(function(t){var n=t.getAttribute("src");if(n){var r=document.createElement("link");r.href=n,r.rel="preload",r.as="script",e.appendChild(r)}}),document.head.appendChild(e)}async function cwvpsbLoadDelayedScripts(e){var t=e.shift();return t?(await cwvpsbReplaceScript(t),cwvpsbLoadDelayedScripts(e)):Promise.resolve()}async function cwvpsbReplaceScript(e){return await cwvpsbNextFrame(),new Promise(function(t){const n=document.createElement("script");[...e.attributes].forEach(function(e){let t=e.nodeName;"type"!==t&&("data-type"===t&&(t="type"),n.setAttribute(t,e.nodeValue))}),e.hasAttribute("src")?(n.addEventListener("load",t),n.addEventListener("error",t)):(n.text=e.text,t()),e.parentNode.replaceChild(n,e)})}
  	function ctl(){
				var cssEle = document.querySelectorAll("link[rel=cwvpsbdelayedstyle]");
				console.log(cssEle.length);
				for(var i=0; i <= cssEle.length;i++){
					if(cssEle[i]){
						var cssMain = document.createElement("link");
						cssMain.href = cssEle[i].href;
						cssMain.rel = "stylesheet";
						cssMain.type = "text/css";
						document.getElementsByTagName("head")[0].appendChild(cssMain);
					}
				}
				var cssEle = document.querySelectorAll("style[type=cwvpsbdelayedstyle]");
				for(var i=0; i <= cssEle.length;i++){
					if(cssEle[i]){
						var cssMain = document.createElement("style");
						cssMain.type = "text/css";
						/*cssMain.rel = "stylesheet";*/
						/*cssMain.type = "text/css";*/
						cssMain.textContent = cssEle[i].textContent;
						document.getElementsByTagName("head")[0].appendChild(cssMain);
					}
				}
			}
  	async function cwvpsbTriggerEventListeners(){cwvpsbDOMLoaded=!0,await cwvpsbNextFrame(),document.dispatchEvent(new Event("perfmatters-DOMContentLoaded")),await cwvpsbNextFrame(),window.dispatchEvent(new Event("perfmatters-DOMContentLoaded")),await cwvpsbNextFrame(),document.dispatchEvent(new Event("perfmatters-readystatechange")),await cwvpsbNextFrame(),document.perfmattersonreadystatechange&&document.perfmattersonreadystatechange(),await cwvpsbNextFrame(),window.dispatchEvent(new Event("perfmatters-load")),await cwvpsbNextFrame(),window.perfmattersonload&&window.perfmattersonload(),await cwvpsbNextFrame(),jQueriesArray.forEach(function(e){e(window).trigger("perfmatters-jquery-load")}),window.dispatchEvent(new Event("perfmatters-pageshow")),await cwvpsbNextFrame(),window.perfmattersonpageshow&&window.perfmattersonpageshow()}async function cwvpsbNextFrame(){return new Promise(function(e){requestAnimationFrame(e)})}cwvpsbUserInteractions.forEach(function(e){window.addEventListener(e,cwvpsbTriggerDOMListener,{passive:!0})});</script>';
}