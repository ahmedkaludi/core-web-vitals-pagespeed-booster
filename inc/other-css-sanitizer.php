<?php
require_once WEBVITAL_PAGESPEED_BOOSTER_DIR."/inc/vendor/autoload.php";

use \Sabberworm\CSS\RuleSet\DeclarationBlock;
use \Sabberworm\CSS\CSSList\CSSList;
use \Sabberworm\CSS\Property\Selector;
use \Sabberworm\CSS\RuleSet\RuleSet;
use \Sabberworm\CSS\Property\AtRule;
use \Sabberworm\CSS\CSSList\KeyFrame;
use \Sabberworm\CSS\RuleSet\AtRuleSet;
use \Sabberworm\CSS\Property\Import;
use \Sabberworm\CSS\CSSList\AtRuleBlockList;
use \Sabberworm\CSS\Value\RuleValueList;
use \Sabberworm\CSS\Value\URL;
use \Sabberworm\CSS\CSSList\Document;





$allResult = array();
class webvital_Style_TreeShaking_Other{
	public $dom;
	public $xpath;
	function sanitized($dom){
		global $allResult;
		
		$parsedCss = web_vital_style_get_file_transient(md5(time()));
		if(!$parsedCss){
			$this->dom = $dom; 
			$xpath = new DOMXPath( $dom );
			$this->xpath = $xpath;

			$lower_case = 'translate( %s, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz" )'; // In XPath 2.0 this is lower-case().
			$predicates = array(
				sprintf( '( self::style and not( @amp-boilerplate ) and ( not( @type ) or %s = "text/css" ) )', sprintf( $lower_case, '@type' ) ),
				sprintf( '( self::link and @href and %s = "stylesheet" )', sprintf( $lower_case, '@rel' ) ),
				'( self::style and  @amp-custom )' ,
			);

			foreach ( $xpath->query( '//*[ ' . implode( ' or ', $predicates ) . ' ]' ) as $element ) {
				$elements[] = $element;
			}
			
			$rawStyleSheet = '';
			foreach ( $elements as $element ) {
				$node_name = strtolower( $element->nodeName );
				if ( 'style' === $node_name ) {
					$rawStyleSheet .= $this->process_style_element( $element );
				} elseif ( 'link' === $node_name ) {
					$rawStyleSheet .= $this->process_link_element( $element );
				}
			}
			$allResult = $this->parseStyleSheet($rawStyleSheet);
			web_vital_set_file_transient(md5(time()),$allResult);
		}else{
			$allResult = $parsedCss;
		}
		return $allResult;
	}
	
	private function process_style_element( DOMElement $element ) {
		$stylesheet   = trim( $element->textContent );
		$media = $element->getAttribute( 'media' );
		if ( $media && 'all' !== $media ) {
			$stylesheet = sprintf( '@media %s { %s }', $media, $stylesheet );
		}
		return $stylesheet;
	}

	function process_link_element( DOMElement $element ) {
			$href = $element->getAttribute( 'href' );
			$normalized_url = preg_replace( '#^(http:)?(?=//)#', 'https:', $href );
			$needs_preconnect_link = (
					'https://fonts.googleapis.com/' === substr( $normalized_url, 0, 29 )
					&&
					0 === $this->xpath->query( '//link[ @rel = "preconnect" and @crossorigin and starts-with( @href, "https://fonts.gstatic.com" ) ]', $this->dom )->length
				);
			if ( $needs_preconnect_link ) {
				return '';
			}
			$css_file_path = $href;//$this->get_validated_url_file_path( $href, array( 'css', 'less', 'scss', 'sass' ) );
			$response = wp_remote_get($css_file_path);
			if(wp_remote_retrieve_response_code($response)==200){
				$styleSheet = wp_remote_retrieve_body($response);
			}else{ $styleSheet = file_get_contents($css_file_path);}
			//$styleSheet = file_get_contents($css_file_path);
			return $styleSheet;
			
	}

	

	function get_validated_url_file_path( $url, $allowed_extensions = array() ) {
		$needs_base_url = (
			! is_bool( $url )
			&&
			! preg_match( '|^(https?:)?//|', $url )
			&&
			! ( $this->content_url && 0 === strpos( $url, $this->content_url ) )
		);
		if ( $needs_base_url ) {
			$url = $this->base_url . $url;
		}

		$remove_url_scheme = function( $schemed_url ) {
			return preg_replace( '#^\w+:(?=//)#', '', $schemed_url );
		};
		$url = $remove_url_scheme( preg_replace( ':[\?#].*$:', '', $url ) );

		$includes_url = $remove_url_scheme( includes_url( '/' ) );
		$content_url  = $remove_url_scheme( content_url( '/' ) );
		$admin_url    = $remove_url_scheme( get_admin_url( null, '/' ) );
		$site_url     = $remove_url_scheme( site_url( '/' ) );

		$allowed_hosts = array(
			wp_parse_url( $includes_url, PHP_URL_HOST ),
			wp_parse_url( $content_url, PHP_URL_HOST ),
			wp_parse_url( $admin_url, PHP_URL_HOST ),
		);

		$url_host = wp_parse_url( $url, PHP_URL_HOST );
		if ( ! empty( $allowed_extensions ) ) {
			$pattern = sprintf( '/\.(%s)$/i', implode( '|', $allowed_extensions ) );
			if ( ! preg_match( $pattern, $url ) ) {
				return new WP_Error( 'disallowed_file_extension', sprintf( __( 'File does not have an allowed file extension for filesystem access (%s).', 'amp' ), $url ) );
			}
		}

		if ( ! in_array( $url_host, $allowed_hosts, true ) ) {
			return new WP_Error( 'external_file_url', sprintf( __( 'URL is located on an external domain: %s.', 'amp' ), $url_host ) );
		}

		$base_path  = null;
		$file_path  = null;
		$wp_content = 'wp-content';
		if ( 0 === strpos( $url, $content_url ) ) {
			$base_path = WP_CONTENT_DIR;
			$file_path = substr( $url, strlen( $content_url ) - 1 );
		} elseif ( 0 === strpos( $url, $includes_url ) ) {
			$base_path = ABSPATH . WPINC;
			$file_path = substr( $url, strlen( $includes_url ) - 1 );
		} elseif ( 0 === strpos( $url, $admin_url ) ) {
			$base_path = ABSPATH . 'wp-admin';
			$file_path = substr( $url, strlen( $admin_url ) - 1 );
		} elseif ( 0 === strpos( $url, $site_url . trailingslashit( $wp_content ) ) ) {
			$base_path = ABSPATH . $wp_content;
			$file_path = substr( $url, strlen( $site_url ) + strlen( $wp_content ) );
		}

		if ( ! $file_path || false !== strpos( $file_path, '../' ) || false !== strpos( $file_path, '..\\' ) ) {
			return new WP_Error( 'file_path_not_allowed', sprintf( __( 'Disallowed URL filesystem path for %s.', 'amp' ), $url ) );
		}
		if ( ! file_exists( $base_path . $file_path ) ) {
			return new WP_Error( 'file_path_not_found', sprintf( __( 'Unable to locate filesystem path for %s.', 'amp' ), $url ) );
		}

		return $base_path . $file_path;
	}
	
	function parseStyleSheet($styleSheet){
			$parser_settings = Sabberworm\CSS\Settings::create();
			$css_parser      = new Sabberworm\CSS\Parser( $styleSheet, $parser_settings );
			$css_document    = $css_parser->parse();
			
																								
			
			$style = $css_document->render(Sabberworm\CSS\OutputFormat::createCompact());
			return $style;
	}
}








function web_vital_is_blog(){
	return ( is_archive() || is_author() || is_category() || is_home() || is_single() || is_tag()) && 'post' == get_post_type();
}
function web_vital_get_proper_transient_name($transient){
	global $post;
	if( function_exists('is_home') && is_home()){
		$transient = "home";
	}elseif(function_exists('web_vital_is_blog') && web_vital_is_blog()){
		$transient = "blog";
	}elseif( function_exists('is_front_page') && is_front_page()){
		$transient = "post-".get_option( 'page_on_front' );
	}elseif(!empty($post) && is_object($post)){
		$transient = "post-".$post->ID;
	}
	return $transient;
}
function web_vital_set_file_transient( $transient, $value, $expiration = 0 ) {
	$transient = web_vital_get_proper_transient_name($transient);
	$expiration = (int) $expiration;

	$value = apply_filters( "web_vital_pre_set_transient_{$transient}", $value, $expiration, $transient );

	
	$expiration = apply_filters( "web_vital_expiration_of_transient_{$transient}", $expiration, $value, $transient );

		$transient_timeout = '_transient_timeout_' . $transient;
		$transient_option = '_transient_' . $transient;
		$result = null;
		if($value){
			$wperror = new WP_Error();
			$wperror->add('error', "man\n");
			$upload_dir = wp_upload_dir(); 
			$user_dirname = $upload_dir['basedir'] . '/' . 'web_vital';
			if(!file_exists($user_dirname)) wp_mkdir_p($user_dirname);
			$new_file = $user_dirname."/".$transient_option.".css";
			$ifp = @fopen( $new_file, 'w+' );
			if ( ! $ifp ) {
	          return ( array( 'error' => sprintf( __( 'Could not write file %s' ), $new_file ) ));
	        }
	        $result = @fwrite( $ifp, json_encode($value) );
		    fclose( $ifp );
		}
	return $result;
}


function web_vital_style_get_file_transient( $transient ) {

	$transient = web_vital_get_proper_transient_name($transient);
	$pre = apply_filters( "pre_transient_{$transient}", false, $transient );
	if ( false !== $pre )
		return $pre;

	if ( wp_using_ext_object_cache() ) {
		$value = wp_cache_get( $transient, 'transient' );
	} else {
		$transient_option = '_transient_' . $transient;
		if ( ! isset( $value ) ){
			$value = '';
			$upload_dir = wp_upload_dir(); 
			$user_dirname = $upload_dir['basedir'] . '/' . 'web_vital';
			if(!file_exists($user_dirname)) wp_mkdir_p($user_dirname);
			
			$new_file = $user_dirname."/".$transient_option.".css";

			if(file_exists($new_file) && filesize($new_file)>0){
				$ifp = @fopen( $new_file, 'r' );
				$value = fread($ifp, filesize($new_file)); 
				fclose($ifp);
			}
		}
	}

	
	return '';//apply_filters( "transient_{$transient}", json_decode($value, true), $transient );
}