<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

if (function_exists('imagewebp')) {
    add_action('wp','cwvpsb_convert_webp');
    add_filter('cwvpsb_complete_html_after_dom_loaded','cwvpsb_display_webp');
}

function cwvpsb_convert_webp(){
    $post = get_post( get_the_ID() );
    if(!$post){return;}
    $content = $post->post_content;
    if ( is_dynamic_sidebar() ) {
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
            $check_svg = strpos(file_get_contents($new_dir),'</svg>');
            if(!$check_svg && $check_svg!=0)
            {
                $image = imagecreatefromstring(file_get_contents($new_dir));
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
    $decodedHtml = html_entity_decode(htmlentities($content, ENT_QUOTES, 'UTF-8', false));
    if(!$decodedHtml){
        return $content;
    }
    $comp_dom->loadHTML( $decodedHtml );
    libxml_clear_errors();

    $xpath = new DOMXPath($comp_dom);
    $nodes = $xpath->query('//img[@src]');

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
                    $node->setAttribute('data-large_image', $img_src_large);
                    $node->setAttribute('data-src', $img_src_large);
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
    }

    $content = $comp_dom->saveHTML();
    return $content;
}

function cwvpsb_convert_to_webp($source_path, $destination_path) {
    $source_info = getimagesize($source_path);

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
