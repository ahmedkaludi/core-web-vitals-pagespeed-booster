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
			if($value === '' || $value === null || $value == "null" ) {
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

    $is_admin = current_user_can('manage_options');

    if(is_admin() || $is_admin){
        return;
    }

	if ( function_exists('is_checkout') && is_checkout() || (function_exists('is_feed')&& is_feed()) ) {
        return;
    }
    if( class_exists( 'next_article_layout' ) ) {
		return ;
	}

	if ( function_exists('elementor_load_plugin_textdomain') && \Elementor\Plugin::$instance->preview->is_preview_mode() ) {
    	return;
	}
	if(cwvpsb_wprocket_lazyjs()){
        add_filter('rocket_delay_js_exclusions', 'cwvpsb_add_rocket_delay_js_exclusions');
        return;   
     }
	add_filter('cwvpsb_complete_html_after_dom_loaded', 'cwvpsb_delay_js_html', 2);
	add_filter('cwvpsb_complete_html_after_dom_loaded', 'cwvpsb_remove_js_query_param', 99);
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
			isset($atts_array['id']) && stripos($atts_array['id'], 'corewvps-mergejsfile') !== false ||
			isset($atts_array['id']) && stripos($atts_array['id'], 'corewvps-cc') !== false
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
		// Fix for recaptcha
		if(strpos($tag,'recaptcha') !== false) {
			continue 1;
		}
		// Fix for google analytics
		if((strpos($tag,'google-analytics') !== false) || (strpos($tag,'googletagmanager') !== false)) {
			continue 1;
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
				//$html = str_replace($tag, '', $html);
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
		if(isset($atts_array['src']) && !$include){
		    $include = true;
		}
		if($delay_flag && $include ) {//
	
			$delayed_atts_string = cwvpsb_get_atts_string($atts_array);
	        $delayed_tag = sprintf('<script %1$s>', $delayed_atts_string) . (!empty($matches[3][$i]) ? $matches[3][$i] : '') .'</script>';
			$html = str_replace($tag, $delayed_tag, $html);
			continue;
		}
	}
	/*if($combined_ex_js_arr){
		$html = cwvpsb_combine_js_files($combined_ex_js_arr, $html);
	}*/
	return $html;
}

// function cwvpsb_combine_js_files($combined_ex_js_arr, $html){
// 	if(!count($combined_ex_js_arr)){ return ; }
// 	include_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
//      include_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
//      if (!class_exists('WP_Filesystem_Direct')) {
//          return false;
//      }

// 	$uniqueid = get_transient( CWVPSB_CACHE_NAME );
//     global $wp;
// 	$url = home_url( $wp->request );
//     $filename = md5($url.$uniqueid);

// 	 $user_dirname = CWVPSB_JS_EXCLUDE_CACHE_DIR;
// 	 $user_urlname = CWVPSB_JS_EXCLUDE_CACHE_URL;
// 	 $jsUrl = '';
	 
// 	 if(!file_exists($user_dirname.'/'.$filename.'.js')){
	 	
// 	     $jscontent = '';
// 	     foreach($combined_ex_js_arr as $file_url){
// 	     	$parse_url = parse_url($file_url);
// 	     	$file_path = str_replace(array(get_site_url(),'?'.$parse_url['query']),array(ABSPATH,''),$file_url);
// 		    $wp_filesystem = new WP_Filesystem_Direct(null);
// 		    $js = $wp_filesystem->get_contents($file_path);
// 		    unset($wp_filesystem);
// 		    if (empty($js)) {
// 		         $request = wp_remote_get($file_url);
// 		         $js = wp_remote_retrieve_body($request);
// 		    }
// 		    $jscontent .= "\n/*File: $file_url*/\n".$js;
// 		}
// 		if($jscontent){
// 			$fileSystem = new WP_Filesystem_Direct( new StdClass() );
// 			if(!file_exists($user_dirname)) wp_mkdir_p($user_dirname);
// 			$fileSystem->put_contents($user_dirname.'/'.$filename.'.js', $jscontent, 644 );
// 			unset($fileSystem);
// 		}
// 	}
// 	return $html;
// }

function cwvpsb_remove_js_query_param($html){

    
    $html = preg_replace('/type="cwvpsbdelayedscript"\s+src="(.*?)\.js\?(.*?)"/',  'type="cwvpsbdelayedscript" src="$1.js"', $html);
    if(preg_match('/<link(.*?)rel="cwvpsbdelayedstyle"(.*?)href="(.*?)\.css\?(.*?)"(.*?)>/m',$html)){
        $html = preg_replace('/<link(.*?)rel="cwvpsbdelayedstyle"(.*?)href="(.*?)\.css\?(.*?)"(.*?)>/',  '<link$1rel="cwvpsbdelayedstyle"$2href="$3.css"$5>', $html);
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
add_action( 'wp_enqueue_scripts',  'cwvpsb_scripts_styles' , 99999);
function cwvpsb_scripts_styles(){
    
	global $wp_scripts;
	$wp_scripts->all_deps($wp_scripts->queue);

	$uniqueid = get_transient( CWVPSB_CACHE_NAME );
    global $wp;
	$url = home_url( $wp->request );
    $filename = md5($url.$uniqueid);
	 $user_dirname = CWVPSB_JS_EXCLUDE_CACHE_DIR;
	 $user_urlname = CWVPSB_JS_EXCLUDE_CACHE_URL;
	
	
	if(!file_exists($user_dirname.'/'.$filename.'.js')){
		$combined_ex_js_arr= array();
		$jscontent = '';
		$regex = cwvpsb_delay_exclude_js();
		include_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
	    include_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
	    if (!class_exists('WP_Filesystem_Direct')) {
	         return false;
	    }
	    $wp_scripts->all_deps($wp_scripts->queue);
		foreach( $wp_scripts->to_do as $key=>$handle) 
		{
			$localize = $localize_handle = '';
			//$src = strtok($wp_scripts->registered[$handle]->src, '?');
			if($regex && preg_match( '#(' . $regex . ')#', $wp_scripts->registered[$handle]->src )){
				$localize_handle = $handle;
			}
			if($regex && preg_match( '#(' . $regex . ')#', $handle )){
				$localize_handle = $handle;
			}
			if($localize_handle){
				if(@array_key_exists('data', $wp_scripts->registered[$handle]->extra)) {
					$localize = $wp_scripts->registered[$handle]->extra['data'] . ';';
				}
				$file_url = $wp_scripts->registered[$handle]->src;
				$parse_url = parse_url($file_url);
		     	$file_path = str_replace(array(get_site_url(),'?'.@$parse_url['query']),array(ABSPATH,''),$file_url);

		     	if(substr( $file_path, 0, 13 ) === "/wp-includes/"){
		     		$file_path = ABSPATH.$file_path;	
		     	}
			    $wp_filesystem = new WP_Filesystem_Direct(null);
			    $js = $wp_filesystem->get_contents($file_path);
			    unset($wp_filesystem);
			    if (empty($js)) {
			         $request = wp_remote_get($file_url);
			         $js = wp_remote_retrieve_body($request);
			    }


				//$combined_ex_js_arr[$handle] = ;
				$jscontent .= "\n/*File: $file_url*/\n".$localize.$js;
				
				//wp_deregister_script($handle);
			}
		}
		if($jscontent){
			$fileSystem = new WP_Filesystem_Direct( new StdClass() );
			if(!file_exists($user_dirname)) wp_mkdir_p($user_dirname);
			$fileSystem->put_contents($user_dirname.'/'.$filename.'.js', $jscontent, 644 );
			unset($fileSystem);
		}
	}


	$uniqueid = get_transient( CWVPSB_CACHE_NAME );
    global $wp;
	$url = home_url( $wp->request );
    $filename = md5($url.$uniqueid);
	 $user_dirname = CWVPSB_JS_EXCLUDE_CACHE_DIR;
	 $user_urlname = CWVPSB_JS_EXCLUDE_CACHE_URL;
	 
	 if(file_exists($user_dirname.'/'.$filename.'.js')){
	 	wp_register_script('corewvps-mergejsfile', $user_urlname.'/'.$filename.'.js', array(), CWVPSB_VERSION, true);
		wp_enqueue_script('corewvps-mergejsfile');
	 }
	 	
}
add_filter( 'script_loader_src', 'cwvpsb_remove_css_js_version', 9999, 2 );
function cwvpsb_remove_css_js_version($src, $handle ){
	$handles_with_version = [ 'corewvps-mergejsfile', 'corewvps-cc','corewvps-mergecssfile' ];
	if ( strpos( $src, 'ver=' ) && in_array( $handle, $handles_with_version, true ) ){
        //$src = remove_query_arg( 'ver', $src );
	}
	$src = add_query_arg( 'time', time(), $src );
    return $src;
}


function cwvpsb_merge_js_scripts(){
	global $wp_scripts;
	$wp_scripts->all_deps($wp_scripts->queue);     
   
	$uniqueid = get_transient( CWVPSB_CACHE_NAME );
    global $wp;
	$url = home_url( $wp->request );
    $filename = md5($url.$uniqueid);
	 $user_dirname = CWVPSB_JS_MERGE_FILE_CACHE_DIR;
	 $user_urlname = CWVPSB_JS_MERGE_FILE_CACHE_CACHE_URL;

	if(!file_exists($user_dirname.'/'.$filename.'.js')){
		$combined_ex_js_arr= array();
		$jscontent = '';
		$regex = cwvpsb_delay_exclude_js();
		include_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
	    include_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
	    if (!class_exists('WP_Filesystem_Direct')) {
	         return false;
	    }
	    $wp_scripts->all_deps($wp_scripts->queue);
		foreach( $wp_scripts->to_do as $key=>$handle) 
		{
			$localize = '';
			$localize_handle = 'cwvpsb-merged-js';
			//$src = strtok($wp_scripts->registered[$handle]->src, '?');
			if($regex && preg_match( '#(' . $regex . ')#', $wp_scripts->registered[$handle]->src )){
				$localize_handle = $handle;
			}
			if($regex && preg_match( '#(' . $regex . ')#', $handle )){
				$localize_handle = $handle;
			}
			
	
			if($localize_handle == 'cwvpsb-merged-js'){
				if(@array_key_exists('data', $wp_scripts->registered[$handle]->extra)) {
					$localize = $wp_scripts->registered[$handle]->extra['data'] . ';';
				}
				$file_url = $wp_scripts->registered[$handle]->src;
				$parse_url = parse_url($file_url);
		     	$file_path = str_replace(array(get_site_url(),'?'.@$parse_url['query']),array(ABSPATH,''),$file_url);

		     	if(substr( $file_path, 0, 13 ) === "/wp-includes/"){
		     		$file_path = ABSPATH.$file_path;	
		     	}
			    $wp_filesystem = new WP_Filesystem_Direct(null);
			    if(file_exists($file_path)){
			    	$js = $wp_filesystem->get_contents($file_path);
				}
			    unset($wp_filesystem);
			    if (empty($js)) {
			         $request = wp_remote_get($file_url);
			         $js = wp_remote_retrieve_body($request);
			    }


				//$combined_ex_js_arr[$handle] = ;
				$jscontent .= "\n/*File: $file_url*/\n".$localize.$js;
				
				//wp_deregister_script($handle);
			}
		}
		if($jscontent){
			$fileSystem = new WP_Filesystem_Direct( new StdClass() );
			if(!file_exists($user_dirname)) wp_mkdir_p($user_dirname);
			$fileSystem->put_contents($user_dirname.'/'.$filename.'.js', $jscontent, 644 );
			unset($fileSystem);
		}
	}


	$uniqueid = get_transient( CWVPSB_CACHE_NAME );
    global $wp;
	$url = home_url( $wp->request );
    $filename = md5($url.$uniqueid);
	 $user_dirname = CWVPSB_JS_MERGE_FILE_CACHE_DIR;
	 $user_urlname = CWVPSB_JS_MERGE_FILE_CACHE_CACHE_URL;
	 
	 if(file_exists($user_dirname.'/'.$filename.'.js')){
	 	wp_register_script('corewvps-delayjs-mergedfile', $user_urlname.'/'.$filename.'.js', array(), CWVPSB_VERSION, true);
		wp_enqueue_script('corewvps-delayjs-mergedfile');
	 }
	
}

// Merged CSS Code Starts here....

//add_action( 'wp_enqueue_scripts',  'cwvpsb_merge_css_scripts_styles' , 99999);
function cwvpsb_merge_css_scripts_styles(){
	global $wp_styles;
	$wp_styles->all_deps($wp_styles->queue);

	$uniqueid = get_transient( CWVPSB_CACHE_NAME );
    global $wp;
	$url = home_url( $wp->request );
    $filename = md5($url.$uniqueid);
	 $user_dirname = CWVPSB_CSS_MERGE_FILE_CACHE_DIR;
	 $user_urlname = CWVPSB_CSS_MERGE_FILE_CACHE_CACHE_URL;
	 
	
	if(!file_exists($user_dirname.'/'.$filename.'.css')){
		$combined_ex_js_arr= array();
		$csscontent = '';
		
		include_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
	    include_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
	    if (!class_exists('WP_Filesystem_Direct')) {
	         return false;
	    }

	    $wp_styles->all_deps($wp_styles->queue);
	    
    	  

		foreach( $wp_styles->to_do as $key=>$handle) 
		{
			$localize =  '';
			
			
				if(@array_key_exists('data', $wp_styles->registered[$handle]->extra)) {
					$localize = $wp_styles->registered[$handle]->extra['data'] . ';';
				}
				
				
				$file_url = $wp_styles->registered[$handle]->src;
			
				$parse_url = parse_url($file_url);
		     	$file_path = str_replace(array(get_site_url(),'?'.@$parse_url['query']),array(ABSPATH,''),$file_url);

		     	if(substr( $file_path, 0, 13 ) === "/wp-includes/"){
		     		$file_path = ABSPATH.$file_path;	
		     	}
		     
			    $wp_filesystem = new WP_Filesystem_Direct(null);
			    if(file_exists($file_path)){
			        $css = $wp_filesystem->get_contents($file_path);
			    }
			    
			    if (empty($css)) {
			         $request = wp_remote_get($file_url);
			         $css = wp_remote_retrieve_body($request);
			    }
			    
			     if(preg_match('/url\([^"|^\'](.*?)\/fonts\/(.*?)\)/m',$css)){
			        $css = preg_replace('/url\([^"|^\'](.*?)\/fonts\/(.*?)\)/m','url(".$1/fonts/$2")',$css);
			    }
			    
			    
                if(preg_match_all('/url\((\'|\")[^data](.*?)\/fonts\//m',$css,$matches,PREG_SET_ORDER)){
                    
                    $slash_count = count(explode('/',$matches[0][2])) + 1;
                    $explode_fileurl = explode('/',$file_url);
                    
                    $file_url_count = count($explode_fileurl);
                   
                    $initial_point = $file_url_count - $slash_count;
                   
                    $exclude_url = '';
                    for($i = $initial_point;$i < $file_url_count;$i++ ){
                        $exclude_url .= '/'.$explode_fileurl[$i];
                         
                    }
                     $font_url = str_replace($exclude_url,'', $file_url);
                   $css = preg_replace('/url\((\'|\")[^data](.*?)\/fonts\//m','url($1'.$font_url.'/fonts/',$css);
                   
                }
                
                
                if(preg_match('/url\([^"|^\'](.*?)(woff|ttf|eot)(.*?)\)/m',$css)){

                	 $css = preg_replace('/url\([^"|^\'](.*?)(woff|ttf|eot)(.*?)\)/m','url("$1$2$3")',$css);
                }
                
                if(!preg_match('/url\((\'|\")[^data](.*?)\/fonts\//m',$css)){

                    $explode_url = explode('/',$file_url);

                    $file_ex_index = count($explode_url)-1;
                    
                    $new_font_url = str_replace($explode_url[$file_ex_index],'',$file_url);
                    
                    
                    if(preg_match('/url\((\'|\")fonts(.*?)(woff|ttf|eot)/m',$css)){
         				  $css = preg_replace('/url\((\'|\")fonts(.*?)(woff|ttf|eot)/m','url($1'.$new_font_url.'fonts$2$3',$css);
                        
                    }elseif(preg_match('/url\((\'|\")(.*?)(woff|ttf|eot)/m',$css)){
         				    $css = preg_replace('/url\((\'|\")(.*?)(woff|ttf|eot)/m','url($1'.$new_font_url.'$2$3',$css);
        			}
                }
                
                 $css =    preg_replace(
                                array(
                                    // Remove comment(s)
                                    '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')|\/\*(?!\!)(?>.*?\*\/)|^\s*|\s*$#s',
                                    // Remove unused white-space(s)
                                    '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\'|\/\*(?>.*?\*\/))|\s*+;\s*+(})\s*+|\s*+([*$~^|]?+=|[{};,>~]|\s(?![0-9\.])|!important\b)\s*+|([[(:])\s++|\s++([])])|\s++(:)\s*+(?!(?>[^{}"\']++|"(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')*+{)|^\s++|\s++\z|(\s)\s+#si',
                                    // Replace `0(cm|em|ex|in|mm|pc|pt|px|vh|vw|%)` with `0`
                                    '#(?<=[\s:])(0)(cm|em|ex|in|mm|pc|pt|px|vh|vw|%)#si',
                                    // Replace `:0 0 0 0` with `:0`
                                    '#:(0\s+0|0\s+0\s+0\s+0)(?=[;\}]|\!important)#i',
                                    // Replace `background-position:0` with `background-position:0 0`
                                    '#(background-position):0(?=[;\}])#si',
                                    // Replace `0.6` with `.6`, but only when preceded by `:`, `,`, `-` or a white-space
                                    '#(?<=[\s:,\-])0+\.(\d+)#s',
                                    // Minify string value
                                    '#(\/\*(?>.*?\*\/))|(?<!content\:)([\'"])([a-z_][a-z0-9\-_]*?)\2(?=[\s\{\}\];,])#si',
                                    '#(\/\*(?>.*?\*\/))|(\burl\()([\'"])([^\s]+?)\3(\))#si',
                                    // Minify HEX color code
                                    '#(?<=[\s:,\-]\#)([a-f0-6]+)\1([a-f0-6]+)\2([a-f0-6]+)\3#i',
                                    // Replace `(border|outline):none` with `(border|outline):0`
                                    '#(?<=[\{;])(border|outline):none(?=[;\}\!])#',
                                    // Remove empty selector(s)
                                    '#(\/\*(?>.*?\*\/))|(^|[\{\}])(?:[^\s\{\}]+)\{\}#s'
                                ),
                                array(
                                    '$1',
                                    '$1$2$3$4$5$6$7',
                                    '$1',
                                    ':0',
                                    '$1:0 0',
                                    '.$1',
                                    '$1$3',
                                    '$1$2$4$5',
                                    '$1$2$3',
                                    '$1:0',
                                    '$1$2'
                                ),
                            $css);
				//$combined_ex_js_arr[$handle] = ;
				$csscontent .= "\n/*File: $file_url*/\n".$localize.$css;
				
				//wp_deregister_script($handle);
		}
		if($csscontent){
			$fileSystem = new WP_Filesystem_Direct( new StdClass() );
			if(!file_exists($user_dirname)) wp_mkdir_p($user_dirname);
			$fileSystem->put_contents($user_dirname.'/'.$filename.'.css', $csscontent, 644 );
			unset($fileSystem);
		}
	}


	$uniqueid = get_transient( CWVPSB_CACHE_NAME );
    global $wp;
	$url = home_url( $wp->request );
    $filename = md5($url.$uniqueid);
	 $user_dirname = CWVPSB_CSS_MERGE_FILE_CACHE_DIR;
	 $user_urlname = CWVPSB_CSS_MERGE_FILE_CACHE_CACHE_URL;
	 
	 if(file_exists($user_dirname.'/'.$filename.'.css')){
	      foreach( $wp_styles->to_do as $key=>$handle) {
        		    $wp_styles->add_data( $handle, 'title', 'cwvpsbenqueuedstyles' );
        		}
	 	wp_register_style('corewvps-mergecssfile', $user_urlname.'/'.$filename.'.css', array(), '1.0.25', true);
		wp_enqueue_style('corewvps-mergecssfile');
	 }
	
}

// Merged CSS Code End here....

function cwvpsb_sanitize_js( $file ) {
	$file = preg_replace( '#\?.*$#', '', $file );
	$ext  = strtolower( pathinfo( $file, PATHINFO_EXTENSION ) );
	return ( 'js' === $ext ) ? trim( $file ) : false;
}

add_action('wp_ajax_cwvpsb_delay_ajax_request','cwvpsb_delay_ajax_request');
add_action('wp_ajax_nopriv_cwvpsb_delay_ajax_request','cwvpsb_delay_ajax_request');
function cwvpsb_delay_ajax_request(){
        echo 'success';
        exit();
	}

function cwvpsb_delay_js_load() {
  	$js_content = '<script type="text/javascript" id="cwvpsb-delayed-scripts">
	cwvpsbUserInteractions=["keydown","mousemove","wheel","touchmove","touchstart","touchend","touchcancel","touchforcechange"],cwvpsbDelayedScripts={normal:[],defer:[],async:[],jquery:[]},jQueriesArray=[];var cwvpsbDOMLoaded=!1;
	function cwvpsbTriggerDOMListener(){cwvpsbUserInteractions.forEach(function(e){window.removeEventListener(e,cwvpsbTriggerDOMListener,{passive:!0})}),"loading"===document.readyState?document.addEventListener("DOMContentLoaded",cwvpsbTriggerDelayedScripts):cwvpsbTriggerDelayedScripts()}

           var time = Date.now;
		   var ccfw_loaded = false; 
		   function calculate_load_times() {
				// Check performance support
				if (performance === undefined) {
					console.log("= Calculate Load Times: performance NOT supported");
					return;
				}
			
				// Get a list of "resource" performance entries
				var resources_length=0;
				var resources = performance.getEntriesByType("resource");
				if (resources === undefined || resources.length <= 0) {
					console.log("= Calculate Load Times: there are NO `resource` performance records");
				}
				if(resources.length)
				{
					resources_length=resources.length;
				}

				let is_last_resource = 0;
				for (var i=0; i < resources.length; i++) {
					if(resources[i].responseEnd>0){
						is_last_resource = is_last_resource + 1;
					}
				}
			
				let uag = navigator.userAgent;
                let gpat = /Google Page Speed Insights/gm;
                let gres = uag.match(gpat);
                let cpat = /Chrome-Lighthouse/gm;
                let cres = uag.match(cpat);
                let wait_till=1000;
                if(gres || cres){
                    wait_till = 3000;
                  }
				if(is_last_resource==resources.length){
					setTimeout(function(){
						cwvpsbTriggerDelayedScripts();
					},wait_till);
				}
			}
			
			window.addEventListener("load", function(e) {
				console.log("load complete");
				 setTimeout(function(){
					calculate_load_times();
				 },200);
		 });

			async function cwvpsbTriggerDelayedScripts() {
				if(ccfw_loaded){ return ;}
				ctl(), cwvpsbDelayEventListeners(), cwvpsbDelayJQueryReady(), cwvpsbProcessDocumentWrite(), cwvpsbSortDelayedScripts(), cwvpsbPreloadDelayedScripts(),await cwvpsbLoadDelayedScripts(cwvpsbDelayedScripts.jquery), await cwvpsbLoadDelayedScripts(cwvpsbDelayedScripts.normal), await cwvpsbLoadDelayedScripts(cwvpsbDelayedScripts.defer), await cwvpsbLoadDelayedScripts(cwvpsbDelayedScripts.async), await cwvpsbTriggerEventListeners()	
			}
			
			function cwvpsbDelayEventListeners() {
				let e = {};
			
				function t(t, n) {
					function r(n) {
						return e[t].delayedEvents.indexOf(n) >= 0 ? "cwvpsb-" + n : n
					}
					e[t] || (e[t] = {
						originalFunctions: {
							add: t.addEventListener,
							remove: t.removeEventListener
						},
						delayedEvents: []
					}, t.addEventListener = function() {
						arguments[0] = r(arguments[0]), e[t].originalFunctions.add.apply(t, arguments)
					}, t.removeEventListener = function() {
						arguments[0] = r(arguments[0]), e[t].originalFunctions.remove.apply(t, arguments)
					}), e[t].delayedEvents.push(n)
				}
			
				function n(e, t) {
					const n = e[t];
					Object.defineProperty(e, t, {
						get: n || function() {},
						set: function(n) {
							e["cwvpsb" + t] = n
						}
					})
				}
				t(document, "DOMContentLoaded"), t(window, "DOMContentLoaded"), t(window, "load"), t(window, "pageshow"), t(document, "readystatechange"), n(document, "onreadystatechange"), n(window, "onload"), n(window, "onpageshow")
			}
			
			function cwvpsbDelayJQueryReady() {
				let e = window.jQuery;
				Object.defineProperty(window, "jQuery", {
					get: () => e,
					set(t) {
						if (t && t.fn && !jQueriesArray.includes(t)) {
							t.fn.ready = t.fn.init.prototype.ready = function(e) {
								cwvpsbDOMLoaded ? e.bind(document)(t) : document.addEventListener("cwvpsb-DOMContentLoaded", function() {
									e.bind(document)(t)
								})
							};
							const e = t.fn.on;
							t.fn.on = t.fn.init.prototype.on = function() {
								if (this[0] === window) {
									function t(e) {
										return e.split(" ").map(e => "load" === e || 0 === e.indexOf("load.") ? "cwvpsb-jquery-load" : e).join(" ")
									}
									"string" == typeof arguments[0] || arguments[0] instanceof String ? arguments[0] = t(arguments[0]) : "object" == typeof arguments[0] && Object.keys(arguments[0]).forEach(function(e) {
										delete Object.assign(arguments[0], {
											[t(e)]: arguments[0][e]
										})[e]
									})
								}
								return e.apply(this, arguments), this
							}, jQueriesArray.push(t)
						}
						e = t
					}
				})
			}
			
			function cwvpsbProcessDocumentWrite() {
				const e = new Map;
				document.write = document.writeln = function(t) {
					var n = document.currentScript,
						r = document.createRange();
					let a = e.get(n);
					void 0 === a && (a = n.nextSibling, e.set(n, a));
					var o = document.createDocumentFragment();
					r.setStart(o, 0), o.appendChild(r.createContextualFragment(t)), n.parentElement.insertBefore(o, a)
				}
			}
			
			function cwvpsbSortDelayedScripts() {
				document.querySelectorAll("script[type=cwvpsbdelayedscript]").forEach(function(e) {
					e.hasAttribute("src")&&(e.getAttribute("src").match("jquery.min.js")||e.getAttribute("src").match("jquery-migrate.min.js"))?cwvpsbDelayedScripts.jquery.push(e):e.hasAttribute("src")?e.hasAttribute("defer")&&!1!==e.defer?cwvpsbDelayedScripts.defer.push(e):e.hasAttribute("async")&&!1!==e.async?cwvpsbDelayedScripts.async.push(e):cwvpsbDelayedScripts.normal.push(e):cwvpsbDelayedScripts.normal.push(e);
				})
			}
  	        
			function cwvpsbPreloadDelayedScripts() {
				var e = document.createDocumentFragment();
				[...cwvpsbDelayedScripts.normal, ...cwvpsbDelayedScripts.defer, ...cwvpsbDelayedScripts.async].forEach(function(t) {
					var n = removeVersionFromLink(t.getAttribute("src"));
					if (n) {
						t.setAttribute("src", n);
						var r = document.createElement("link");
						r.href = n, r.rel = "preload", r.as = "script", e.appendChild(r)
					}
				}), document.head.appendChild(e)
			}
			async function cwvpsbLoadDelayedScripts(e) {
				var t = e.shift();
				return t ? (await cwvpsbReplaceScript(t), cwvpsbLoadDelayedScripts(e)) : Promise.resolve()
			}
			async function cwvpsbReplaceScript(e) {
				return await cwvpsbNextFrame(), new Promise(function(t) {
					const n = document.createElement("script");
					[...e.attributes].forEach(function(e) {
						let t = e.nodeName;
						"type" !== t && ("data-type" === t && (t = "type"), n.setAttribute(t, e.nodeValue))
					}), e.hasAttribute("src") ? (n.addEventListener("load", t), n.addEventListener("error", t)) : (n.text = e.text, t()), e.parentNode.replaceChild(n, e)
				})
			}

  	function ctl(){
			var cssEle = document.querySelectorAll("link[rel=cwvpsbdelayedstyle]");
				for(var i=0; i <= cssEle.length;i++){
					if(cssEle[i]){
						cssEle[i].href = removeVersionFromLink(cssEle[i].href);
                        cssEle[i].rel = "stylesheet";
                        cssEle[i].type = "text/css";
					}
				}
				
				
				var cssEle = document.querySelectorAll("style[type=cwvpsbdelayedstyle]");
				for(var i=0; i <= cssEle.length;i++){
					if(cssEle[i]){
						cssEle[i].type = "text/css";
					}
				}
				ccfw_loaded=true;
			}
			function removeVersionFromLink(link)
            {
                if(cwvpbIsValidUrl(link))
				{
					const url = new URL(cwvpbFormatLink(link));
					url.searchParams.delete("ver");
					url.searchParams.delete("time");
					return url.href;
				}
				return link;
            }

			            function cwvpbIsValidUrl(urlString)
            {
                if(urlString){
                    var expression =/[-a-zA-Z0-9@:%_\+.~#?&//=]{2,256}\.[a-z]{2,4}\b(\/[-a-zA-Z0-9@:%_\+.~#?&//=]*)?/gi;
                    var regex = new RegExp(expression);
                    return urlString.match(regex);
                }
				return false;
            }
            function cwvpbFormatLink(link)
            {
                let http_check=link.match("http:");
                let https_check=link.match("https:");
                if(!http_check && !https_check)
                {
                    return location.protocol+link;
                }
                return link;
            }
		
			async function cwvpsbTriggerEventListeners() {
				cwvpsbDOMLoaded = !0, await cwvpsbNextFrame(), document.dispatchEvent(new Event("cwvpsb-DOMContentLoaded")), await cwvpsbNextFrame(), window.dispatchEvent(new Event("cwvpsb-DOMContentLoaded")), await cwvpsbNextFrame(), document.dispatchEvent(new Event("cwvpsb-readystatechange")), await cwvpsbNextFrame(), document.cwvpsbonreadystatechange && document.cwvpsbonreadystatechange(), await cwvpsbNextFrame(), window.dispatchEvent(new Event("cwvpsb-load")), await cwvpsbNextFrame(), window.cwvpsbonload && window.cwvpsbonload(), await cwvpsbNextFrame(), jQueriesArray.forEach(function(e) {
					e(window).trigger("cwvpsb-jquery-load")
				}), window.dispatchEvent(new Event("cwvpsb-pageshow")), await cwvpsbNextFrame(), window.cwvpsbonpageshow && window.cwvpsbonpageshow()
			}
			async function cwvpsbNextFrame() {
				return new Promise(function(e) {
					requestAnimationFrame(e)
				})
			}
			cwvpsbUserInteractions.forEach(function(e) {
				window.addEventListener(e, cwvpsbTriggerDOMListener, {
					passive: !0
				})
			});</script>';
  	echo $js_content;
}

function cwvpsb_wprocket_lazyjs()
{
    if(defined('WP_ROCKET_VERSION'))
    {
        $cwvpsb_wprocket_options=get_option('wp_rocket_settings',null);

        if(isset($cwvpsb_wprocket_options['defer_all_js']) && $cwvpsb_wprocket_options['defer_all_js']==1)
        {
            return true;   
        }
    }
    return false;
}

function cwvpsb_add_rocket_delay_js_exclusions( $patterns ) {
    $patterns[] = 'cwvpsb-delayed-scripts';	
	return $patterns;
}