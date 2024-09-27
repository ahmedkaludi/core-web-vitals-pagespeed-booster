<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

if (function_exists('imagewebp')) {
    add_action('wp','cwvpsb_convert_webp');
    $settings = cwvpsb_defaults();
    if(isset($settings['image_optimization_alt']) && $settings['image_optimization_alt'] == 1 ){
        add_filter('cwvpsb_complete_html_after_dom_loaded','cwvpsb_display_webp_regex');
      }else{
        add_filter('cwvpsb_complete_html_after_dom_loaded','cwvpsb_display_webp');
      }
    
}

function cwvpsb_convert_webp(){
    $post = get_post( get_the_ID() );
    if(!$post){return;}
    $content = $post->post_content;
    $product_archive_page_id = function_exists('get_product_listing_id') ? get_product_listing_id() : false ;
    if ( is_dynamic_sidebar() && ( ! $product_archive_page_id || ( $product_archive_page_id && ! is_page( ! $product_archive_page_id ) ) ) ) {
        ob_start();
        dynamic_sidebar();
        $sidebar_html = ob_get_contents();
        ob_end_clean();
        $content .=  $sidebar_html; 
    }
    if (class_exists('Mfn_Builder_Front')) {
        $content .= get_post_field( 'mfn-page-items-seo', get_the_ID());
    }
    $get_src_regex = '/src="([^"]*)"/';
    preg_match_all( $get_src_regex, $content, $matches );
    $matches = array_reverse($matches);
    $img_src = $matches[0];

    if ( has_post_thumbnail() ) {  
        $featured_img = get_the_post_thumbnail_url(get_the_ID(),'full');
        array_push($img_src , $featured_img);
    }
    if (function_exists('et_setup_theme')) { 
        $logo = et_get_option( 'divi_logo' );
        array_push($img_src , $logo);
    }
    if ( has_custom_logo() ) {
        $logo = wp_get_attachment_url( get_theme_mod( 'custom_logo' ));
        array_push($img_src , $logo);
    }

    foreach ($img_src as $key => $img_dir) {
        $img_dir = explode('/wp-content', $img_dir);
        if(count($img_dir)==1)
        {
         continue;
        }
        $upload_dir = wp_upload_dir();
        $upload_dir = $upload_dir['path'];
        $upload_dir = explode('/uploads', $upload_dir);
        $new_dir = $upload_dir[0] . $img_dir[1];
        $img_dir = explode('/', $img_dir[1]);
        $img_dir = end($img_dir);
        $wp_upload_dir = wp_upload_dir();
        $upload_dir_base = $wp_upload_dir['basedir'] . '/' . 'cwv-webp-images';
        if(!file_exists($upload_dir_base)) wp_mkdir_p($upload_dir_base);
        $upload_dir_base .= '/'.$img_dir;
        $check_dir = $upload_dir_base . '.webp';
        if(!file_exists($check_dir) && file_exists($new_dir)){
            $check_svg = strpos(cwvpsb_read_file_contents($new_dir),'</svg>');
            if(!$check_svg && $check_svg!=0)
            {
                $image = imagecreatefromstring(cwvpsb_read_file_contents($new_dir));
                ob_start();
                imagejpeg($image,NULL,100);
                $get_contents = ob_get_contents();
                ob_end_clean();
                imagedestroy($image);
                $content = imagecreatefromstring($get_contents);
                $output = $upload_dir_base . '.webp';
                imagewebp($content,$output);
                imagedestroy($content);
            }
        }
    }
}

function cwvpsb_display_webp($content) {
    $comp_dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $decodedHtml = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    if(!$decodedHtml){
        return $content;
    }
    $comp_dom->loadHTML( $decodedHtml );
    libxml_clear_errors();

    $xpath = new DOMXPath($comp_dom);
    $nodes = $xpath->query('//img[@src]');
    $settings = cwvpsb_defaults();
    $force_alt_tags = isset($settings['images_add_alttags']) ? $settings['images_add_alttags'] : 1 ;
    foreach ($nodes as $node) {
        $url = $node->getAttribute('src');
        if(stripos($content, 'gravatars') !== false){
            continue;
        }
        $mod_url = explode('uploads',$url);
        $mod_url = count($mod_url)>1?$mod_url[1]:$mod_url[0];
        
        $wp_upload_dir = wp_upload_dir();
        $upload_baseurl = $wp_upload_dir['baseurl'] . '/' . 'cwv-webp-images';
        $upload_basedir = $wp_upload_dir['basedir'] . '/' . 'cwv-webp-images';
        if(!file_exists($upload_basedir)) wp_mkdir_p($upload_basedir);
        $img_webp = $upload_baseurl . $mod_url . ".webp";
        $img_webp_dir = $upload_basedir . $mod_url . ".webp";
        $img_src = str_replace($url, $img_webp, $url);
        $srcset = $node->getAttribute('srcset');
        $img_srcset = str_replace($srcset, $img_webp, $srcset);
        if (file_exists($img_webp_dir)) {
            $node->setAttribute('src', $img_src);
            $node->setAttribute('srcset', $img_srcset);
            // Fix for woocommerce zoom image to work properly
            $large_image = $node->getAttribute('data-large_image');
             if($large_image){
                    $mod_url_large = explode('uploads',$large_image);
                    $mod_url_large = count($mod_url_large)>1?$mod_url_large[1]:$mod_url_large[0];
                    $img_webp_large = $upload_baseurl . $mod_url_large . ".webp";
                    $img_src_large = str_replace($large_image, $img_webp_large, $large_image);
                    if (file_exists($img_src_large)) {
                        $node->setAttribute('data-large_image', $img_src_large);
                        $node->setAttribute('data-src', $img_src_large);
                    }
                   
                }
            
        } else {
            // Convert the image to WebP if it doesn't exist
            $image_path = $wp_upload_dir['basedir'] . str_replace($wp_upload_dir['baseurl'], '', $url);
            $img_webp_dir_ar =  explode('/',$img_webp_dir);
            array_pop($img_webp_dir_ar);
            $img_webp_dir_ar = implode('/',$img_webp_dir_ar);
            if(!is_dir($img_webp_dir_ar)) wp_mkdir_p($img_webp_dir_ar);
            if (cwvpsb_convert_to_webp($image_path, $img_webp_dir)) {
                $node->setAttribute('src', $img_src);
                $node->setAttribute('srcset', $img_srcset);
            }
        }
        if ($force_alt_tags) {
            //check if alt attribute empty , if empty set alt attribute with image name
            if (!$node->getAttribute('alt') || empty($node->getAttribute('alt'))) {
                $alt = pathinfo($url, PATHINFO_FILENAME);
                $alt = preg_replace('/[-_]\d+x\d+$/', '', $alt);
                $alt = str_replace(['_', '-'], ' ', $alt);
                $alt = ucwords($alt);
                $node->setAttribute('alt', $alt);

            }
        }
    }

    $content = $comp_dom->saveHTML();
    return $content;
}

// function to display webp images where 
// DOMDocument is unable to parse html properly resulting in breaking of html
// mostly in case of HTML5 tags and invalid html due to builders
function cwvpsb_display_webp_regex($content) {
    // Match all <img> tags with a 'src' attribute
    $pattern = '/<img(?:\s+[^>]*?\s*src\s*=\s*["\']([^"\']*)["\'])?(?:\s+[^>]*?)*?>/i';
    $settings = cwvpsb_defaults();
    $force_alt_tags = isset($settings['images_add_alttags']) ? $settings['images_add_alttags'] : 1 ;
    // Perform the replacement using a callback function
    $content = preg_replace_callback($pattern, function ($matches) use ($force_alt_tags) {

        $img_srcset = '';
        if(empty($matches[1])){
            return $matches[0];
        }
        $url = $matches[1];
        $site_url = site_url();
        // Check if the 'src' attribute contains 'gravatars'
        if (stripos($url, 'gravatars') !== false) {
            return $matches[0]; // Return the original tag unchanged
        }

        if (stripos($url, $site_url) === false) {
            return $matches[0]; // Return the original tag if image is not hosted on the site
        } 
        if (stripos($url, '.webp') !== false) {
            return $matches[0]; // Return the original tag unchanged
        }
  
        $original_url = $url;
        $url = preg_replace('~^(?:f|ht)tps?://~i', '/', $url);
        $mod_url = explode('uploads', $url);
        $mod_url = count($mod_url) > 1 ? $mod_url[1] : $mod_url[0];
        $wp_upload_dir = wp_upload_dir();
        $upload_baseurl = $wp_upload_dir['baseurl'] . '/' . 'cwv-webp-images';
        $upload_basedir = $wp_upload_dir['basedir'] . '/' . 'cwv-webp-images';
        if (!file_exists($upload_basedir)) wp_mkdir_p($upload_basedir);

        $img_webp = $upload_baseurl . $mod_url . ".webp";
        $img_webp_dir = $upload_basedir . $mod_url . ".webp";

        $img_src = str_replace($original_url, $img_webp, $original_url);
        // Assuming 'srcset' attribute is present in the <img> tag
        $patternSrcset = '/srcset=["\'](.*?)["\']/i';
        preg_match($patternSrcset,$matches[0],$srcset_matches);
        $srcset_width=0;
        if (preg_match('/width=\"(\d+)\"/', $matches[0], $width_matche)) {
            $srcset_width = $width_matche[1];
        }
        if(isset($srcset_matches[1])){
            $sources = explode(',', $srcset_matches[1]);
            $srcset_arr = [];
            foreach ($sources as $source) {
                $parts = explode(' ', trim($source));
                $url = $parts[0];
                $widthDescriptor = isset($parts[1])?$parts[1]:'';;
                // Extract the width value from the descriptor
                $width = intval(preg_replace('/\D/', '', $widthDescriptor));
                $srcset_arr[$width] = $url;
                $curr_image_path = explode('uploads', $url);
                $curr_image_path = count($curr_image_path) > 1 ? $curr_image_path[1] : $curr_image_path[0];
                $source_path = $wp_upload_dir['basedir'].'/'.$curr_image_path;
                $destination_path = $upload_basedir . $curr_image_path . ".webp";
                $destination_url = $upload_baseurl . $curr_image_path . ".webp";
                $source_file_exists = file_exists($source_path);
                $dest_file_exists = file_exists($destination_path);
                if($source_file_exists && !$dest_file_exists){
                    if(cwvpsb_convert_to_webp($source_path, $destination_path)){
                        $matches[0] = str_replace($original_url, $destination_url, $matches[0]);
                    }
                }else if($dest_file_exists){
                    $matches[0] = str_replace($original_url, $destination_url, $matches[0]);
                }
            }
            if($srcset_width){
                $key = cwvpsb_find_best_match($srcset_width,array_keys($srcset_arr));
                if(isset($key) && isset($srcset_arr[$key])){
                    $img_webp = $destination_url;
                }
            }
        }

        $img_srcset = str_replace($patternSrcset, 'srcset="' . $img_webp . '"', $matches[0]);
        
        if (file_exists($img_webp_dir)) {
            $img_srcset ='';
            // WebP file exists, update attributes
            $matches[0] = str_replace($original_url, $img_src, $matches[0]);
            $matches[0] = str_replace($patternSrcset, 'srcset="' . $img_srcset . '"', $matches[0]);

            $large_image = '';
            if (preg_match('/data-large_image=["\'](.*?)["\']/i', $matches[0], $largeImageMatches)) {
                $large_image = $largeImageMatches[1];
            }

            if ($large_image) {
                $mod_url_large = explode('uploads', $large_image);
                $mod_url_large = count($mod_url_large) > 1 ? $mod_url_large[1] : $mod_url_large[0];
                $img_webp_large = $upload_baseurl . $mod_url_large . ".webp";
                $img_src_large = str_replace($large_image, $img_webp_large, $large_image);
                if(file_exists($img_src_large)){
                    $matches[0] = preg_replace('/data-large_image=["\'](.*?)["\']/i', 'data-large_image="' . $img_src_large . '"', $matches[0]);
                    $matches[0] = preg_replace('/data-src=["\'](.*?)["\']/i', 'data-src="' . $img_src_large . '"', $matches[0]);
                }
            }

        } else {
             $img_srcset ='';
            // WebP file doesn't exist, convert the image and update attributes
            
            $image_path = $wp_upload_dir['basedir'] . str_replace($wp_upload_dir['baseurl'], '', $url);
            $img_webp_dir_ar = explode('/', $img_webp_dir);
            array_pop($img_webp_dir_ar);
            $img_webp_dir_ar = implode('/', $img_webp_dir_ar);
            if (!is_dir($img_webp_dir_ar)) wp_mkdir_p($img_webp_dir_ar);

            if (cwvpsb_convert_to_webp($image_path, $img_webp_dir)) {
                $matches[0] = str_replace($original_url, $img_src, $matches[0]);
                $matches[0] = str_replace($patternSrcset, 'srcset="' . $img_srcset . '"', $matches[0]);
            }
        }
        if ($force_alt_tags) {
            //check if alt attribute empty , if empty set alt attribute with image name
            if (!preg_match('/alt=["\'].*?["\']/i', $matches[0]) || preg_match('/alt=["\']\s*["\']/i', $matches[0])) {
                $alt = pathinfo($original_url, PATHINFO_FILENAME);
                $alt = preg_replace('/[-_]\d+x\d+$/', '', $alt);
                $alt = str_replace(['_', '-'], ' ', $alt);
                $alt = ucwords($alt);
                // If `alt` attribute exists but is empty, replace the empty `alt`
                if (preg_match('/alt=["\']\s*["\']/i', $matches[0])) {
                    $matches[0] = preg_replace('/alt=["\']\s*["\']/i', 'alt="' . esc_attr($alt) . '"', $matches[0]);
                } else {
                    // If `alt` attribute is missing, add it
                    $matches[0] = str_replace('<img', '<img alt="' . esc_attr($alt) . '"', $matches[0]);
                }
            }
        }


        return $matches[0];
    }, $content);

    return $content;
}
function cwvpsb_convert_to_webp($source_path, $destination_path) {
    $source_info = array();
    if(file_exists($source_path)){
        $source_info = @getimagesize($source_path);
    }

    if (!$source_info) {
        return false; // Unable to get image information
    }

    list($source_width, $source_height, $source_type) = $source_info;

    if ($source_type !== IMAGETYPE_JPEG && $source_type !== IMAGETYPE_PNG) {
        return false; // Unsupported image type
    }


    if ($source_type === IMAGETYPE_JPEG) {
        $source_image = imagecreatefromjpeg($source_path);
    } elseif ($source_type === IMAGETYPE_PNG) {
        $source_image = imagecreatefrompng($source_path);
    }

    if (!$source_image) {
        return false; // Unable to create image resource
    }

 
    $webp_image = imagecreatetruecolor($source_width, $source_height);

    // Preserve transparency for PNG images
    if ($source_type === IMAGETYPE_PNG) {
        imagealphablending($webp_image, false);
        imagesavealpha($webp_image, true);
        $transparent = imagecolorallocatealpha($webp_image, 255, 255, 255, 127);
        imagefilledrectangle($webp_image, 0, 0, $source_width, $source_height, $transparent);
    }

    imagecopy($webp_image, $source_image, 0, 0, 0, 0, $source_width, $source_height);

    if (!imagewebp($webp_image, $destination_path, 80)) {
        return false; // Unable to save WebP image
    }

    // Free up resources
    imagedestroy($source_image);
    imagedestroy($webp_image);

    return true;
}

function cwvpsb_find_best_match($inputValue, $numbers) {
    $nearest = null;
    $minDifference = PHP_INT_MAX;
  
    foreach ($numbers as $number) {
        $difference = abs($inputValue - $number);
        if ($difference < $minDifference) {
            $minDifference = $difference;
            $nearest = $number;
        }
    }
  
    return $nearest;
  }

  function cwvpsb_wordpress_root_path() {
    // Check if ABSPATH constant is defined
    if (defined('ABSPATH')) {
        return ABSPATH;
    } else {
        // ABSPATH constant is not defined, try to calculate it
        $root_path = dirname(__FILE__); // Get the directory of the current file
        $root_path = str_replace('\\', '/', $root_path); // Convert backslashes to forward slashes
        $root_path = rtrim($root_path, '/'); // Remove trailing slash if exists
        $root_path = preg_replace('/\/wp-content.*$/', '', $root_path); // Remove everything after wp-content
        return $root_path;
    }
}