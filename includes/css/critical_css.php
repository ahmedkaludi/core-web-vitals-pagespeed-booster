<?php
/**
 * Critical CSS functionality 
 * @since 1.3
 **/
class criticalCss{
	public function __construct(){
		$this->init();
	}

	public function cachepath(){
		if(defined(CWVPSB_CRITICAL_CSS_CACHE_DIR)){
			return CWVPSB_CRITICAL_CSS_CACHE_DIR;
		}else{
			return WP_CONTENT_DIR . "/cache/cwvpsb/critical_css/";
		}
	}

	public function init(){
		if ( function_exists('is_checkout') && is_checkout()  || (function_exists('is_feed')&& is_feed())) {
        	return;
	    }
	    if ( function_exists('elementor_load_plugin_textdomain') && \Elementor\Plugin::$instance->preview->is_preview_mode() ) {
	    	return;
		}
		add_action('wp_footer', array($this,'cwvpsb_delay_js_load'), PHP_INT_MAX);
		if(function_exists('is_user_logged_in') && !is_user_logged_in()){
		    add_action('wp', array($this, 'delay_css_loadings'), 999);
	    }
	    
	    add_action('wp_head', array($this, 'print_style_cc'),2);
		//if(!is_admin()){
		    add_action( 'wp_enqueue_scripts', array($this, 'scripts_styles') );
			
		//}
		add_action("wp_ajax_cc_call", array($this, 'grab_cc_css'));
		add_action("wp_ajax_nopriv_cc_call", array($this, 'grab_cc_css'));
	}

	function scripts_styles(){
		global $wp;
		wp_register_script('corewvps-cc', CWVPSB_PLUGIN_DIR_URI.'/includes/css/cc.js', array('jquery'), CWVPSB_VERSION, true);
		wp_enqueue_script('corewvps-cc');
		$user_dirname =  $this->cachepath();
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
	    $response = wp_remote_get($URL, array('timeout'     => 40));
	    $resStatuscode = wp_remote_retrieve_response_code( $response );
	    if($resStatuscode==200){
	    	$response = wp_remote_retrieve_body($response);
	    	$responseArr = json_decode($response, true);
	    	if($responseArr["status"] != 200){
	    		echo json_encode( array("status"=>$responseArr["status"]) );die;
	    	}
	    	
			$user_dirname = $this->cachepath();
			if(!file_exists($user_dirname)) wp_mkdir_p($user_dirname);

			$content = $responseArr['critical_css'];
			$content = str_replace("url('wp-content/", "url('https://buildingandinteriors.com/wp-content/", $content); 
			$content = str_replace('url("wp-content/', 'url("https://buildingandinteriors.com/wp-content/', $content); 
			
			if(true){//$content
				$new_file = $user_dirname."/".md5($targetUrl).".css";
				$ifp = @fopen( $new_file, 'w+' );
				if ( ! $ifp ) {
		          echo json_encode(  array( 'error' => sprintf( __( 'Could not write file %s' ), $new_file ) ));die;
		        }
		        $result = @fwrite( $ifp, $content );
			    fclose( $ifp );

		    	echo json_encode(array("status"=>200));die;
		    }else{
		    	echo json_encode(array("status"=>401, "message"=> "file content is blank"));die;
		    }
	    }else{
	    	echo json_encode(array("status"=>$resStatuscode, 'message'=> 'return from server', 'response'=>$response));die;
	    }
	}


	function print_style_cc(){
		$upload_dir = wp_upload_dir(); 
		$user_dirname = $upload_dir['basedir'] . '/' . $this->cachepath();
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
		add_filter('cwvpsb_complete_html_after_dom_loaded', array($this, 'cwvpsb_delay_css_html'), 1,1);
	}

	function check_critical_css(){
		$upload_dir = wp_upload_dir(); 
		$user_dirname = $upload_dir['basedir'] . '/' . $this->cachepath();
		global $wp;
		$url = home_url( $wp->request );
		return file_exists($user_dirname.'/'.md5($url).'.css')? true :  false; 
	}

	public function cwvpsb_delay_css_html($html){
		if(!$this->check_critical_css()){ return $html; }
		$html_no_comments = preg_replace('/<!--(.*)-->/Uis', '', $html);
		preg_match_all('/<link\s?([^>]+)?>/is', $html_no_comments, $matches);

		if(!isset($matches[0])) {
			return $html;
		}
		
		foreach($matches[0] as $i => $tag) {
			$atts_array = !empty($matches[1][$i]) ? $this->cwvpsb_get_atts_array($matches[1][$i]) : array();
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
				$delayed_atts_string = $this->cwvpsb_get_atts_string($atts_array);
		        $delayed_tag = sprintf('<link %1$s', $delayed_atts_string) . (!empty($matches[3][$i]) ? $matches[3][$i] : '') .'/>';
				$html = str_replace($tag, $delayed_tag, $html); 
				continue;
			}
		}

		preg_match_all('#(<style\s?([^>]+)?\/?>)(.*?)<\/style>#is', $html_no_comments, $matches1);
		if(isset($matches1[0])){
			foreach($matches1[0] as $i => $tag) {
				$atts_array = !empty($matches1[2][$i]) ? $this->cwvpsb_get_atts_array($matches1[2][$i]) : array();
				if($atts_array['id'] == 'cc-styles'){ continue; }
				if(isset($atts_array['type'])){
					$atts_array['data-cwvpsb-cc-type'] = $atts_array['type'];
				}
				$atts_array['type'] = 'cwvpsbdelayedstyle';
				$delayed_atts_string = $this->cwvpsb_get_atts_string($atts_array);
		        $delayed_tag = sprintf('<style %1$s>', $delayed_atts_string) . (!empty($matches1[3][$i]) ? $matches1[3][$i] : '') .'</style>';
				$html = str_replace($tag, $delayed_tag, $html);
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
		if(!$this->check_critical_css()){ return ; }
		$settings = cwvpsb_defaults();
		if( $settings['delay_js'] == 'php'){ return; }
		echo '<script type="text/javascript" id="cwvpsb-delayed-styles">
			cwvpsbUserInteractions = ["keydown", "mousemove", "wheel", "touchmove", "touchstart", "touchend", "touchcancel", "touchforcechange"], cwvpsbDelayedScripts = {
    normal: [],
    defer: [],
    async: []
}, jQueriesArray = [];
var cwvpsbDOMLoaded = !1;

function cwvpsbTriggerDOMListener() {
    ' . '
    cwvpsbUserInteractions.forEach(function(e) {
        window.removeEventListener(e, cwvpsbTriggerDOMListener, {
            passive: !0
        })
    }), "loading" === document.readyState ? document.addEventListener("DOMContentLoaded", cwvpsbTriggerDelayedScripts) : cwvpsbTriggerDelayedScripts()
}
async function cwvpsbTriggerDelayedScripts() {
    cwvpsbDelayEventListeners(), cwvpsbDelayJQueryReady(), cwvpsbProcessDocumentWrite(), cwvpsbSortDelayedScripts(), cwvpsbPreloadDelayedScripts(), await cwvpsbLoadDelayedScripts(cwvpsbDelayedScripts.normal), await cwvpsbLoadDelayedScripts(cwvpsbDelayedScripts.defer), await cwvpsbLoadDelayedScripts(cwvpsbDelayedScripts.async), await cwvpsbTriggerEventListeners(), ctl()
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
        e.hasAttribute("src") ? e.hasAttribute("defer") && !1 !== e.defer ? cwvpsbDelayedScripts.defer.push(e) : e.hasAttribute("async") && !1 !== e.async ? cwvpsbDelayedScripts.async.push(e) : cwvpsbDelayedScripts.normal.push(e) : cwvpsbDelayedScripts.normal.push(e)
    })
}

function cwvpsbPreloadDelayedScripts() {
    var e = document.createDocumentFragment();
    [...cwvpsbDelayedScripts.normal, ...cwvpsbDelayedScripts.defer, ...cwvpsbDelayedScripts.async].forEach(function(t) {
        var n = t.getAttribute("src");
        if (n) {
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
});
























		</script>';
	}

}
$cwvpbCriticalCss = new criticalCss();