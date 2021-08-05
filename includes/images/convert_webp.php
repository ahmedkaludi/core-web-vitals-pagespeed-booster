<?php 

add_action('wp','cwvpsb_convert_webp');
function cwvpsb_convert_webp(){
    $check_webp = get_option('cwvpsb_check_webp');
    if (!$check_webp) {
       return;
    }
    $post = get_post( get_the_ID() );
    $content = $post->post_content;
    if ( is_dynamic_sidebar() ) {
        ob_start();
        dynamic_sidebar();
        $sidebar_html = ob_get_contents();
        ob_end_clean();
        $content .=  $sidebar_html; 
    }
    $get_src_regex = '/src="([^"]*)"/';
    preg_match_all( $get_src_regex, $content, $matches );
    $matches = array_reverse($matches);
    $img_src = $matches[0];

    if ( has_post_thumbnail() ) {  
        $featured_img = get_the_post_thumbnail_url(get_the_ID(),'full');
        array_push($img_src , $featured_img);
    }
    if ( has_custom_logo() ) {
        $logo = wp_get_attachment_url( get_theme_mod( 'custom_logo' ));
        array_push($img_src , $logo);
    }

    foreach ($img_src as $key => $img_dir) {
        $img_dir = explode('/wp-content', $img_dir);
        $upload_dir = wp_upload_dir();
        $upload_dir = $upload_dir['path'];
        $upload_dir = explode('/uploads', $upload_dir);
        $new_dir = $upload_dir[0] . $img_dir[1];
        $check_dir = $new_dir . '.webp';
        if(!file_exists($check_dir)){
            $image = imagecreatefromstring(file_get_contents($new_dir));
            ob_start();
            imagejpeg($image,NULL,100);
            $get_contents = ob_get_contents();
            ob_end_clean();
            imagedestroy($image);
            $content = imagecreatefromstring($get_contents);
            $output = $new_dir . '.webp';
            imagewebp($content,$output);
            imagedestroy($content);
        }
    }
}
add_filter('cwvpsb_complete_html_after_dom_loaded','cwvpsb_display_webp');
function cwvpsb_display_webp( $content ) {
    $check_webp = get_option('cwvpsb_check_webp');
    if (!$check_webp) {
        return $content;
    }
    $comp_dom = new DOMDocument();
    $comp_dom->loadHTML($content);
    $xpath = new DOMXPath( $comp_dom );
    $nodes = $xpath->query('//img[@src]');
    foreach ($nodes as $node) {
        $url = $node->getAttribute('src');
        $img_webp = $url.".webp";
        $img_src = str_replace($url, $img_webp, $url);
        $srcset = $node->getAttribute('srcset');
        $img_srcset = str_replace($srcset, $img_webp, $srcset);
        $node->setAttribute('src',$img_src);
        $node->setAttribute('srcset',$img_srcset);
    }
    $content = $comp_dom->saveHTML();
    return $content;
}