<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

add_filter('cwvpsb_complete_html_after_dom_loaded','cwvpsb_google_fonts');
function cwvpsb_google_fonts( $html ) { 
		//create our fonts cache directory
        if(!is_dir(CWVPSB_CACHE_FONTS_DIR . 'fonts/')) {
            @mkdir(CWVPSB_CACHE_FONTS_DIR . 'fonts/', 0755, true);
        }
	preg_match_all('#<link[^>]+?href=(["\'])([^>]*?fonts\.googleapis\.com\/css.*?)\1.*?>#i', $html, $google_fonts, PREG_SET_ORDER);
	if(!empty($google_fonts)) {
            foreach($google_fonts as $google_font) {
     
                //create unique file details
                $file_name = substr(md5($google_font[2]), 0, 12) . ".google-fonts.css";
                $file_path = CWVPSB_CACHE_FONTS_DIR . 'fonts/' . $file_name;
                $file_url = CWVPSB_CACHE_FONTS_URL . 'fonts/' . $file_name;

                //download file if it doesn't exist
                if(!file_exists($file_path)) {
                    if(!cwvpsb_download_google_font($google_font[2], $file_path)) {
                        continue;
                    }
                }

                //create font tag with new url
                $new_google_font = str_replace($google_font[2], $file_url, $google_font[0]);
                //replace original font tag
                $html = str_replace($google_font[0], $new_google_font, $html);
            }
        }
	return $html;
}

function cwvpsb_download_google_font($url, $file_path)
    {
        //add https if using relative scheme
        if(substr($url, 0, 2) === '//') {
            $url = 'https:' . $url;
        }

        //download css file
        $css_response = wp_remote_get(esc_url_raw($url), array('user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.122 Safari/537.36'));

        //check valid response
        if(wp_remote_retrieve_response_code($css_response) !== 200) {
            return false;
        }

        //css content
        $css = $css_response['body'];

        //find font files inside the css
        $regex = '/url\((https:\/\/fonts\.gstatic\.com\/.*?)\)/';
        preg_match_all($regex, $css, $matches);
        $font_urls = array_unique($matches[1]);
        $font_requests = array();
        if(!empty($font_urls)){
            foreach($font_urls as $font_url) {

                if(!file_exists(CWVPSB_CACHE_FONTS_DIR . 'fonts/' . basename($font_url))) {
                    $font_requests[] = array('url' => $font_url, 'type' => 'GET');
                }
    
                $cached_font_url = CWVPSB_CACHE_FONTS_URL . 'fonts/' . basename($font_url);
                $css = str_replace($font_url, $cached_font_url, $css);
            }
        }

        //download new font files to cache directory
        if(method_exists(Requests::class, 'request_multiple')) {
            $font_responses = Requests::request_multiple($font_requests);
            if(!empty($font_responses)){
                foreach($font_responses as $font_response) { 

                    if(isset($font_response->url) && isset($font_response->body)) {

                        $font_path = CWVPSB_CACHE_FONTS_DIR . 'fonts/' . basename($font_response->url);
                        
                        //save font file
                        file_put_contents($font_path, $font_response->body);
                    }
                }
            }
        }

        //save final css file
        file_put_contents($file_path, $css);

        return true;
    }