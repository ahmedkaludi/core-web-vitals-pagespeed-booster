<?php
class webVitalHelperSection{
	static function converttoWebp($filename){
		$source = $filename;
		$upload = wp_upload_dir();
		$destinationPath = $upload['basedir']."/web-vital-webp";
		if(!is_dir($destinationPath)) { wp_mkdir_p($destinationPath); }
		$destination = str_replace($upload['basedir'], $destinationPath, $filename).".webp";
		try {
			require_once WEBVITAL_PAGESPEED_BOOSTER_DIR."/inc/vendor/autoload.php";
			$convertOptions = [];
			\WebPConvert\WebPConvert::convert($source, $destination, $convertOptions);
		} catch (\WebpConvert\Exceptions\WebPConvertException $e) {
            if(function_exists('error_log')){ error_log($e->getMessage()); }
        } catch (\Exception $e) {
        	$message = 'An exception was thrown!';
            if(function_exists('error_log')){ error_log($e->getMessage()); }
        }
	}


	static function doUploadWithWebp($filearray, $overrides = false, $ignore = false){
		if (isset($filearray['file'])) {
            try {
                $filename = $filearray['file'];
                $allowedMimeTypes = [];
	            $allowedMimeTypes[] = 'image/jpeg';
	            $allowedMimeTypes[] = 'image/png';
		        
		        if (!in_array(@wp_get_image_mime($filename), $allowedMimeTypes)) {
		            return false;
		        }
		        self::converttoWebp($filename);
            } catch (Exception $e) {
            	if(function_exists('error_log')){ error_log($e->getMessage()); }
            }
        }
        return $filearray;
	}
}