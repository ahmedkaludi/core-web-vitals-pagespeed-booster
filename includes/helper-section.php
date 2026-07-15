<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Core_Web_Vital_Helper_Section{
	static function convert_to_webp($filename){
		$source = $filename;
		$upload = wp_upload_dir();
		$destinationPath = $upload['basedir']."/cwv-webp-images";
		if(!is_dir($destinationPath)) { wp_mkdir_p($destinationPath); }
		$destination = str_replace($upload['basedir'], $destinationPath, $filename).".webp";
		try {
			require_once CWVPSB_PLUGIN_DIR."/includes/vendor/autoload.php";
			$convertOptions = [];
			\WebPConvert\WebPConvert::convert($source, $destination, $convertOptions);
		} catch (\WebpConvert\Exceptions\WebPConvertException $e) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'error_log' ) ) {
                error_log( $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- gated behind WP_DEBUG
            }
        } catch (\Exception $e) {
        	$message = 'An exception was thrown!';
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'error_log' ) ) {
                error_log( $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- gated behind WP_DEBUG
            }
        }
	}


	static function do_upload_with_webp($filearray, $overrides = false, $ignore = false){
		if (isset($filearray['file'])) {
            try {
                $filename = $filearray['file'];
                $allowedMimeTypes = [];
	            $allowedMimeTypes[] = 'image/jpeg';
	            $allowedMimeTypes[] = 'image/png';

	            if(isset($filearray['type']) && ($filearray['type'] == 'image/svg+xml' || $filearray['type'] == 'image/webp')){
          			return $filearray;
        		}
		        
		        if (!in_array(wp_get_image_mime($filename), $allowedMimeTypes)) {
		            return $filearray;
		        }
		        self::convert_to_webp($filename);
            } catch (Exception $e) {
            	if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'error_log' ) ) {
            		error_log( $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- gated behind WP_DEBUG
            	}
            }
        }
        return $filearray;
	}
}