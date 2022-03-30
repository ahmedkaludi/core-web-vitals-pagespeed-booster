<?php

class criticalCss{
	public function __construct(){
		$this->init();
	}

	public function init(){
		if ( function_exists('is_checkout') && is_checkout()  || (function_exists('is_feed')&& is_feed())) {
        	return;
	    }
	    if ( function_exists('elementor_load_plugin_textdomain') && \Elementor\Plugin::$instance->preview->is_preview_mode() ) {
	    	return;
		}
		add_action('wp', array($this, 'delay_css_loadings'));
		add_action('wp_footer', array($this,'cwvpsb_delay_js_load'), PHP_INT_MAX);

		add_action('wp_head', array($this, 'print_style_cc'));
		if(!is_admin()){
			add_action( 'wp_enqueue_scripts', array($this, 'scripts_styles') );
		}
		add_action("wp_ajax_cc_call", array($this, 'grab_cc_css'));
		add_action("wp_ajax_nopriv_cc_call", array($this, 'grab_cc_css'));
	}

	function scripts_styles(){
		wp_register_script('corewvps-cc', CWVPSB_PLUGIN_DIR_URI.'/includes/javascript/cc.js', array('jquery'), CWVPSB_VERSION, true);

		wp_enqueue_script('corewvps-cc');
		global $wp;
		$upload_dir = wp_upload_dir(); 
		$user_dirname = $upload_dir['basedir'] . '/' . 'cc-cwvpb';

		$data = array('ajaxurl'=>admin_url( 'admin-ajax.php' ),
					'cc_nonce'   => wp_create_nonce('cc_ajax_check_nonce'),
					'current_url' => home_url( $wp->request ),
					'grab_cc_check'=> (file_exists($user_dirname."/".md5(home_url( $wp->request )).".css")? 1: 2), 
					'test'=>$user_dirname."/".md5(home_url( $wp->request )).".css"
					);
		wp_localize_script('corewvps-cc', 'cwvpb_ccdata', $data);
	}

	function grab_cc_css(){
		if ( ! isset( $_POST['security_nonce'] ) ){
	       echo json_encode( array("message"=>"security nonce not found") );die;  
	    }
	    if ( !wp_verify_nonce( $_POST['security_nonce'], 'cc_ajax_check_nonce' ) ){
	       echo json_encode( array("message"=>"security nonce wrong") );die;  
	    }
	    $targetUrl = $_POST['current_url'];
	    $URL = 'http://45.32.112.172/?url='.$targetUrl;
	    $response = wp_remote_get($URL, array());
	    $resStatuscode = wp_remote_retrieve_response_code( $response );
	    if($resStatuscode==200){
	    	$response = wp_remote_retrieve_body($response);
	    	$responseArr = json_decode($response, true);
	    	if($responseArr["status"] != 200){
	    		echo json_encode( array("status"=>$responseArr["status"]) );die;
	    	}
	    	$upload_dir = wp_upload_dir(); 
			$user_dirname = $upload_dir['basedir'] . '/' . 'cc-cwvpb';
			if(!file_exists($user_dirname)) wp_mkdir_p($user_dirname);

			$content = $responseArr['critical_css'];
			$new_file = $user_dirname."/".md5($targetUrl).".css";
			$ifp = @fopen( $new_file, 'w+' );
			if ( ! $ifp ) {
	          echo json_encode(  array( 'error' => sprintf( __( 'Could not write file %s' ), $new_file ) ));die;
	        }
	        $result = @fwrite( $ifp, $content );
		    fclose( $ifp );

	    	echo json_encode(array("status"=>200));die;
	    }else{
	    	echo json_encode(array("status"=>$resStatuscode, 'message'=> 'return from server'));die;
	    }





	}


	function print_style_cc(){
		$upload_dir = wp_upload_dir(); 
		$user_dirname = $upload_dir['basedir'] . '/' . 'cc-cwvpb';
		global $wp;
		$url = home_url( $wp->request );
		if(file_exists($user_dirname.'/'.md5($url).'.css')){
			$css =  file_get_contents($user_dirname.'/'.md5($url).'.css');
		 	echo "<style type='text/css' id='cc-styles'>$css</style>";
		}
	}
	
	public function delay_css_loadings(){
		if ( function_exists('is_checkout') && is_checkout()  || (function_exists('is_feed')&& is_feed())) {
        	return;
	    }
	    if ( function_exists('elementor_load_plugin_textdomain') && \Elementor\Plugin::$instance->preview->is_preview_mode() ) {
	    	return;
		}
		add_filter('cwvpsb_complete_html_after_dom_loaded', array($this, 'cwvpsb_delay_css_html'), 2,1);
	}

	public function cwvpsb_delay_css_html($html){
		$html_no_comments = preg_replace('/<!--(.*)-->/Uis', '', $html);
		preg_match_all('/<link\s?([^>]+)?>/is', $html_no_comments, $matches);

		if(!isset($matches[0])) {
			return $html;
		}
		
		foreach($matches[0] as $i => $tag) {
			$atts_array = !empty($matches[1][$i]) ? cwvpsb_get_atts_array($matches[1][$i]) : array();
			if(isset($atts_array['rel']) && stripos($atts_array['rel'], 'stylesheet') === false) {
				continue;
			}
			$delay_flag = false;
			$excluded_scripts = array(
				'cwvpsb-delayed-styles',
			);

			if(!empty($excluded_scripts)) {
				foreach($excluded_scripts as $excluded_script) {
					if(strpos($tag, $excluded_script) !== false) {
						continue 2;
					}
				}
			}

			$delay_flag = true;
			if(!empty($atts_array['rel'])) {
				$atts_array['data-cwvpsb-rel'] = $atts_array['rel'];
			}

			$atts_array['rel'] = 'cwvpsbdelayedstyle';
			$atts_array['defer'] = 'defer';
		
			if($delay_flag) {
				//$html = str_replace($tag, '<noscript id="cwvpsbdelayedstyle">'.$tag.'</noscript>', $html);
				 $delayed_atts_string = cwvpsb_get_atts_string($atts_array);
		        $delayed_tag = sprintf('<link %1$s', $delayed_atts_string) . (!empty($matches[3][$i]) ? $matches[3][$i] : '') .'/>';
				$html = str_replace($tag, $delayed_tag, $html); 
				continue;
			}
		}
		return $html;
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


	function cwvpsb_delay_js_load() {
		echo '<script type="text/javascript" id="cwvpsb-delayed-styles">
			(function() {
			cwvpsbUserInteractionsAll = ["keydown", "mousemove", "wheel", "touchmove", "touchstart", "touchend", "touchcancel", "touchforcechange"]
			cwvpsbUserInteractionsAll.forEach(function(e) {
					window.removeEventListener(e, cwvpsbTriggerDOMListener, {
					passive: !0
				})
			}), "loading" === document.readyState ? document.addEventListener("DOMContentLoaded", ctl) : ctl()
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
			}

		 

		})();
		</script>';
	}

}
$cwvpbCriticalCss = new criticalCss();