<?php 
add_action('wp', function(){ ob_start('web_vital_changes'); }, 990);

function web_vital_changes($html){
	$bkpHtml = $html;
	$settings = web_vital_defaultSettings();
	
	//Second option 
	if(isset($settings['list_of_urls']) && count(array_filter($settings['list_of_urls']))>0){
		foreach ($settings['list_of_urls'] as $key => $value) {
			$url = str_replace(array('/', '.', ' '), array('\\/', '\.', ''), $value);
			$regex = '/<script[a-z=\"\',\[\]\s\-\.0-9]*src="'.$url.'"><\/script>/';
			preg_match($regex, $html, $matches, PREG_UNMATCHED_AS_NULL);
	
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

				$replace = '<script type="text/javascript">
				//<![CDATA[
				var la=!1;window.addEventListener("scroll",function(){(0!=document.documentElement.scrollTop&&!1===la||0!=document.body.scrollTop&&!1===la)&&(!function(){var e=document.createElement("script");'.$attrsjs.'var a=document.getElementsByClassName("'.$replaceClass.'")[0];a.parentNode.insertBefore(e,a)}(),la=!0)},!0);
				//]]>
				</script></body>';
				$html = preg_replace("/<\/body>/", $replace, $html);
				//replace
			}
			//return $html;
		}
	}
	
	
	
	$re = '/<script(\s|\n)*async(\s|\n)*src="(https:|https|)\/\/pagead2\.googlesyndication\.com\/pagead\/js\/adsbygoogle\.js"><\/script>/';
	$html = preg_replace($re, "", $html);

	if(isset($settings['lazy_load']) && $settings['lazy_load']==1){
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
		$replace = '<script type="text/javascript">
			//<![CDATA[
			var la=!1;window.addEventListener("scroll",function(){(0!=document.documentElement.scrollTop&&!1===la||0!=document.body.scrollTop&&!1===la)&&(!function(){var e=document.createElement("script");e.type="text/javascript",e.async=!0,e.src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js";var a=document.getElementsByTagName("script")[0];a.parentNode.insertBefore(e,a)}(),la=!0)},!0);
			//]]>
			</script></body>';
			$html = preg_replace("/<\/body>/", $replace, $html);
	}


	if(empty($html)){
		$html = $bkpHtml."<!-- vital not work -->";
	}
	return $html;
}