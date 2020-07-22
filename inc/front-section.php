<?php 
add_action('wp', function(){ ob_start('web_vital_changes'); }, 990);

function web_vital_changes($html){
	$settings = web_vital_defaultSettings();
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
	return $html;
}