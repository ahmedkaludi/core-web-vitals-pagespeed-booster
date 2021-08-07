<?php 

add_filter('cwvpsb_complete_html_after_dom_loaded','cwvpsb_lazy_loading');
function cwvpsb_lazy_loading( $html ) {
		$check_ll = get_option('cwvpsb_check_lazyload');
	    if (!$check_ll) {
	       return $html;
	    }
		$tmpDoc = new DOMDocument();
		libxml_use_internal_errors(true);
		$tmpDoc->loadHTML($html);
		$xpath = new DOMXPath( $tmpDoc );
		$domImg = $xpath->query( '//img');
		foreach ($domImg as $key => $element) {
				$classupdate = $element->getAttribute("class");
				$element->setAttribute("class", $classupdate." cwv-lazy-loading");
		}
		$domIframe = $xpath->query( '//iframe');
		foreach($domIframe as $iframe){
			$iframe->setAttribute("loading", "lazy");
		}
		$html = $tmpDoc->saveHTML();
		if($appendlazyScript){
			$lazyScript = "<script>".cwvpsb_lazy_loading_script()."</script>";
			$html = str_replace("</body>", $lazyScript."</body>", $html);
		}
	return $html;
}
function cwvpsb_lazy_loading_script(){

	$lazyscript = 'document.addEventListener("DOMContentLoaded", function() {
        let lazyloadImages;
        if("IntersectionObserver" in window) {
          lazyloadImages = document.querySelectorAll(".cwv-lazy-loading");
          let imageObserver = new IntersectionObserver(function(entries, observer) {
            entries.forEach(function(entry) {
              if(entry.isIntersecting) {
                let image = entry.target;
                image.src = image.dataset.src;
                image.classList.remove("cwv-lazy-loading");
                imageObserver.unobserve(image);
              }
            });
          });
          lazyloadImages.forEach(function(image) {
            imageObserver.observe(image);
          });
        } else {
          let lazyloadThrottleTimeout;
          lazyloadImages = document.querySelectorAll(".cwv-lazy-loading");

          function lazyload() {
            if(lazyloadThrottleTimeout) {
              clearTimeout(lazyloadThrottleTimeout);
            }
            lazyloadThrottleTimeout = setTimeout(function() {
              let scrollTop = window.pageYOffset;
              lazyloadImages.forEach(function(img) {
                if(img.offsetTop < (window.innerHeight + scrollTop)) {
                  img.src = img.dataset.src;
                  img.classList.remove("cwv-lazy-loading");
                }
              });
              if(lazyloadImages.length == 0) {
                document.removeEventListener("scroll", lazyload);
                window.removeEventListener("resize", lazyload);
                window.removeEventListener("orientationChange", lazyload);
              }
            }, 20);
          }
          document.addEventListener("scroll", lazyload);
          window.addEventListener("resize", lazyload);
          window.addEventListener("orientationChange", lazyload);
        }
      });';
return $lazyscript;
}