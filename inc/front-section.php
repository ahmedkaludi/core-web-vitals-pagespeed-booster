<?php 
//add_action('shutdown', function(){ ob_start('web_vital_changes'); }, 990);
add_action('wp', 'web_vitals_initialize', 990);
function web_vitals_initialize(){
	if (!is_admin() || (function_exists('wp_doing_ajax') && wp_doing_ajax()) || (defined( 'DOING_AJAX' ) && DOING_AJAX)) {
        ob_start('web_vitals_changes');
    }
  }
function web_vitals_changes($html){
	// Don't do anything with the RSS feed, Preview mode, customization, elementor preview
    if (is_feed() || 
		is_preview() || 
		(function_exists('is_customize_preview') && is_customize_preview()) ||
		( class_exists('\Elementor\Plugin') && \Elementor\Plugin::$instance->preview->is_preview_mode() )
		) {
        return $html;
    }
	$bkpHtml = $html;
	$settings = web_vital_defaultSettings();
	
	$replaceJs ='';
	if(isset($settings['lazy_load']) && $settings['lazy_load']==1){
		$re = '/<script(\s|\n)*async(\s|\n)*src="(https:|https|)\/\/pagead2\.googlesyndication\.com\/pagead\/js\/adsbygoogle\.js"><\/script>/';
		$html = preg_replace($re, "", $html);
		$replace = '<script type="text/javascript">
					function showDownloadJSAtOnload() {
					var element = document.createElement("script");
					element.src = "https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js";
					document.body.appendChild(element);
					}
					if (window.addEventListener)
					window.addEventListener("load", showDownloadJSAtOnload, false);
					else if (window.attachEvent)
					window.attachEvent("onload", showDownloadJSAtOnload);
					else window.onload = showDownloadJSAtOnload;
					</script></body>';
					$html = preg_replace("/<\/body>/", $replace, $html);
	}
	if(isset($settings['load_on_scroll']) && $settings['load_on_scroll']==1){
		$re = '/<script(\s|\n)*async(=""|)(\s|\n)*src="(https:|https|)\/\/pagead2\.googlesyndication\.com\/pagead\/js\/adsbygoogle\.js"><\/script>/';
		$html = preg_replace($re, "", $html);
		 
		$replaceJs .= 'var e=document.createElement("script");e.type="text/javascript",e.async=!0,e.src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js";var a=document.getElementsByTagName("script")[0];a.parentNode.insertBefore(e,a);';
			//$html = preg_replace("/<\/body>/", $replace, $html);
	}
	
	//Second option 
	if(isset($settings['list_of_urls']) && count(array_filter($settings['list_of_urls']))>0){
		foreach ($settings['list_of_urls'] as $key => $value) {
			$url = str_replace(array('/', '.', ' '), array('\\/', '\.', ''), $value);
			$regex = '/<script[a-z=\"\',\[\]\s\-\.0-9]*src="'.$url.'"><\/script>/';
			preg_match($regex, $html, $matches);
	
			if(isset($matches[0])){
				$str = $matches[0];
				$replaceClass = 'webvital-'.($key+1);
				//grab all attributs
				$grabAttrreg = '/(\S+)=["\']?((?:.(?!["\']?\s+(?:\S+)=|[>"\']))+.)["\']?/si';
				
				$html = preg_replace($regex, "<div class='".$replaceClass."'></div>", $html);
				preg_match_all($grabAttrreg, $str, $allattrs);
				$attrsjs = '';
				foreach($allattrs[0] as $key=>$attrs){
					$attrsjs .= 'e.setAttribute("'.$allattrs[1][$key].'","'.$allattrs[2][$key].'");';
				}

				$replaceJs .= 'var e=document.createElement("script");'.$attrsjs.'var a=document.getElementsByClassName("'.$replaceClass.'")[0];a.parentNode.insertBefore(e,a);';
				
				//replace
			}
			//return $html;
		}
	}
	if($replaceJs){
		$replaceAdd = '<script type="text/javascript">
			//<![CDATA[
			var la=!1;window.addEventListener("scroll",function(){(0!=document.documentElement.scrollTop&&!1===la||0!=document.body.scrollTop&&!1===la)&&(!function(){'.$replaceJs.'}(),la=!0)},!0);
			//]]>
			</script></body>';
	
		$html = preg_replace("/<\/body>/", $replaceAdd, $html);
	}

	if(isset($settings['image_convert_webp']) && $settings['image_convert_webp']==1 && (strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') !== false)){
		$guessurl = site_url();
		if ( ! $guessurl ) {
			$guessurl = wp_guess_url();
		}
		$base_url    = untrailingslashit( $guessurl );
		$upload = wp_upload_dir();

		$tmpDoc = new DOMDocument();
		libxml_use_internal_errors(true);
		$tmpDoc->loadHTML($html);

		$xpath = new DOMXPath( $tmpDoc );
		$domImg = $xpath->query( '//img');
		foreach ($domImg as $key => $element) {
			$srcupdate = $element->getAttribute("src");
			if(strpos($srcupdate, $base_url)!==false){
				//test page exists or not
				$srcupdatePath = str_replace($upload['baseurl'], $upload['basedir'].'/web-vital-webp', $srcupdate);
				$srcupdatePath = "$srcupdatePath.webp";
				if(file_exists($srcupdatePath)){
					$srcupdate = str_replace($upload['baseurl'], $upload['baseurl'].'/web-vital-webp', $srcupdate);
					$srcupdate .= '.webp';
					$element->setAttribute("src", $srcupdate);	
				}
				
			}
			if($element->hasAttribute('srcset')){
				$attrValue = $element->getAttribute("srcset");
				
				$srcsetArr = explode(',', $attrValue);
				foreach ($srcsetArr as $i => $srcSetEntry) {
					// $srcSetEntry is ie "image.jpg 520w", but can also lack width, ie just "image.jpg"
					// it can also be ie "image.jpg 2x"
					$srcSetEntry = trim($srcSetEntry);
					$entryParts = preg_split('/\s+/', $srcSetEntry, 2);
					if (count($entryParts) == 2) {
						list($src, $descriptors) = $entryParts;
					} else {
						$src = $srcSetEntry;
						$descriptors = null;
					}

					//$webpUrl = $this->replaceUrlOr($src, false);
					if(strpos($src, $base_url)!==false){
						//test page exists or not
						$srcupdatePath = str_replace($upload['baseurl'], $upload['basedir'].'/web-vital-webp', $src);
						$srcupdatePath = "$srcupdatePath.webp";
						if(file_exists($srcupdatePath)){
							$webpUrl = str_replace($upload['baseurl'], $upload['baseurl'].'/web-vital-webp', $src);
							$webpUrl .= '.webp';
						}else{ $webpUrl = $src; }
					}else{ $webpUrl = $src; }
					if ($webpUrl !== false) {
						$srcsetArr[$i] = $webpUrl . (isset($descriptors) ? ' ' . $descriptors : '');
					}
				}
				$newSrcsetArr = implode(', ', $srcsetArr);
				$attrValue = $element->setAttribute("srcset", $newSrcsetArr);
			}
		}
		$html = $tmpDoc->saveHTML();
	}
	//lazyload for images
	if(isset($settings['native_lazyload_image']) && $settings['native_lazyload_image']==1 && !empty($html)){
		$guessurl = site_url();
		if ( ! $guessurl ) {
			$guessurl = wp_guess_url();
		}
		$base_url    = untrailingslashit( $guessurl );

		$tmpDoc = new DOMDocument();
		libxml_use_internal_errors(true);
		$tmpDoc->loadHTML($html);

		$xpath = new DOMXPath( $tmpDoc );
		$domImg = $xpath->query( '//img');
		$domIframe = $xpath->query( '//iframe');
		
		foreach($domIframe as $iframe){
			$iframe->setAttribute("loading", "lazy");
			$iframesrc = $iframe->getAttribute("src");
			$iframe->setAttribute("data-src", $iframesrc);
			$iframe->setAttribute("data-iframetest", "1");
			$iframe->removeAttribute("src");
		}
		
		$appendlazyScript = false;
		foreach ($domImg as $key => $element) {
			$element->setAttribute("loading", "lazy");
			
			$srcupdate = $actualImage = $element->getAttribute("src");
			if(strpos($srcupdate, $base_url)!==false){
				if(!$appendlazyScript){ $appendlazyScript = true; }
				$classupdate = $element->getAttribute("class");
				$element->setAttribute("class", $classupdate." wvp-lazy");
				
				//check queryUrl
				$queryUrl = '';
				if(strpos($srcupdate, "?")!==false){
					$srcbreak = explode("?", $srcupdate);
					$srcupdate = $srcbreak[0];
					$queryUrl = $srcbreak[1];
				}
				$srcbreak = explode("/", $srcupdate);
				
				

				$fileExt = explode(".", $srcbreak[count($srcbreak)-1]);
				$fileSizePosition = 2;
				//if convert .webp is changed then there is proper size name will moved to -3 position
				if(end($fileExt)=='webp'){
					$fileSizePosition = 3;
				}
				//remove previous size
				$fileName = $fileExt[count($fileExt)-$fileSizePosition];
				if(strpos($fileName, "-")!==false){
					$filesize = explode("-", $fileName);
					$fileExt[count($fileExt)-$fileSizePosition] = str_replace("-".end($filesize), "", $fileName);
				}

				$fileExt[count($fileExt)-$fileSizePosition] = $fileExt[count($fileExt)-$fileSizePosition]."-150x150";
				$fileExt = implode(".", $fileExt);

				$srcbreak[count($srcbreak)-1] = $fileExt;
				$srcbreak = implode("/", $srcbreak);
				
				//check Image Available or not 
				$upload = wp_upload_dir();
				$srcupdatePath = str_replace($upload['baseurl'], $upload['basedir'], $srcbreak);
				if(file_exists($srcupdatePath)){
					$srcset = $element->getAttribute("srcset");
					if($srcset){
						$element->setAttribute("data-presrcset", $srcset);
						$element->removeAttribute("srcset");
					}
					$element->setAttribute("src",$srcbreak);
					$element->setAttribute("data-presrc", $actualImage);
				}
				
				
			}
		}
		$html = $tmpDoc->saveHTML();
		if($appendlazyScript){
			$lazyScript = "<script>".web_vitals_lazy_loader_script()."</script>";
			$html = str_replace("</body>", $lazyScript."</body>", $html);
		}
	}
	
	if(isset($settings['remove_unused_css']) && $settings['remove_unused_css']==1 && !empty($html)){
		//now filter
		try{
			require_once WEB_VITALS_PAGESPEED_BOOSTER_DIR."/inc/style-sanitizer.php";
			$tmpDoc = new DOMDocument();
			libxml_use_internal_errors(true);
			$tmpDoc->loadHTML($html);
			$error_codes = [];
			$args        = [
				'validation_error_callback' => static function( $error ) use ( &$error_codes ) {
					$error_codes[] = $error['code'];
				},
				'should_locate_sources'=>true,
				'use_document_element'=>false,
				'include_manifest_comment'=>false,
			];
			$parser = new webvital_Style_TreeShaking($tmpDoc,$args);
			$sanitize = $parser->sanitize();
			$sheet = $parser->get_stylesheets();
			$sheetData = '';
			$sheetData .= implode( '', $sheet );
			
			$html = $tmpDoc->saveHTML();
			$html = str_replace("</head>", "<style>".$sheetData."</style></head>", $html);
		}catch(Throwable $e){
			$html .= json_encode($e);
		}
	
		

	}

	if(empty($html)){
		$html = $bkpHtml."<!-- vital not work -->";
	} 
	return $html;
}

/**
 * WP rocket compatibility
 */
if(class_exists("WP_Rocket_Requirements_Check")){

	function rocket_deactivate_lazyload_on_style_opt( $run_filter ) {
			$settings = web_vital_defaultSettings();
			if(isset($settings['remove_unused_css']) && $settings['remove_unused_css']==1 ){
				$run_filter = false;
			}
		return $run_filter;
	}
	add_filter( 'do_rocket_lazyload', 'rocket_deactivate_lazyload_on_style_opt' );
	add_filter( 'do_rocket_lazyload_iframes', 'rocket_deactivate_lazyload_on_style_opt' );
}



function web_vitals_lazy_loader_script(){
	$lazyscript = '!function(window){
			  var $q = function(q, res){
			        if (document.querySelectorAll) {
			          res = document.querySelectorAll(q);
			        } else {
			          var d=document
			            , a=d.styleSheets[0] || d.createStyleSheet();
			          a.addRule(q,\'f:b\');
			          for(var l=d.all,b=0,c=[],f=l.length;b<f;b++)
			            l[b].currentStyle.f && c.push(l[b]);

			          a.removeRule(0);
			          res = c;
			        }
			        return res;
			      }
			    , addEventListener = function(evt, fn){
			        window.addEventListener
			          ? this.addEventListener(evt, fn, false)
			          : (window.attachEvent)
			            ? this.attachEvent(\'on\' + evt, fn)
			            : this[\'on\' + evt] = fn;
			      }
			    , _has = function(obj, key) {
			        return Object.prototype.hasOwnProperty.call(obj, key);
			      };
			  function loadImage (el, fn) {
				var img = new Image()
				  , src = el.getAttribute(\'data-presrc\')
				  , srcset = el.getAttribute(\'data-presrcset\');
				if(src){
					img.onload = function() {
					  if (!! el.parent){
						el.parent.replaceChild(img, el)
					  }else{
						el.src = src;if(srcset){ el.srcset = srcset; }
					  }
					  fn? fn() : null;
					}
					img.src = src;
					if(srcset){ img.srcset = srcset; }
				}
			  }
			  function loadIframe (el, fn) {
				var src = el.getAttribute(\'data-src\');
			    el.setAttribute("src", src);
			  }
			  function elementInViewport(el) {
			    var rect = el.getBoundingClientRect()
			    return (
			       rect.top    >= 0
			    && rect.left   >= 0
			    && rect.top <= (window.innerHeight || document.documentElement.clientHeight)
			    )
			  }
			    var images = new Array(), query = $q(\'img.wvp-lazy\'),
				iframe = new Array(), iframequery = $q(\'iframe\'),
				webvitalprocessScroll = function(){
			          for (var i = 0; i < images.length; i++) {
			            if (elementInViewport(images[i])) {
			              loadImage(images[i], function () {
			                images.splice(i, i);
			              });
			            }
			          };
					  for (var i = 0; i < iframe.length; i++) {
			            if (elementInViewport(iframe[i])) {
			              loadIframe(iframe[i], function () {
			                iframe.splice(i, i);
			              });
			            }
			          }; 
			        };
			    for (var i = 0; i < query.length; i++) {
			      images.push(query[i]);
			    };
				for (var i = 0; i < iframequery.length; i++) {
			      iframe.push(iframequery[i]);
			    };
			    webvitalprocessScroll();
			    addEventListener(\'scroll\',webvitalprocessScroll);
			}(this);';
return $lazyscript;
}