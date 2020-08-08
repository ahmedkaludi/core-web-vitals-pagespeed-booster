<?php 
//add_action('shutdown', function(){ ob_start('web_vital_changes'); }, 990);
add_action('wp', function(){ ob_start('web_vital_changes'); }, 990);
function web_vital_changes($html){
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

	if(isset($settings['remove_unused_css']) && $settings['remove_unused_css']==1 && !empty($html)){
		//now filter
		try{
			require_once WEBVITAL_PAGESPEED_BOOSTER_DIR."/inc/style-sanitizer.php";
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
	$html .= json_encode($settings);

	if(empty($html)){
		$html = $bkpHtml."<!-- vital not work -->";
	} 
	return $html;
}