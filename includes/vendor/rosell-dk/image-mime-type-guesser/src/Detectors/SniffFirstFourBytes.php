<?php

namespace ImageMimeTypeGuesser\Detectors;

use \ImageMimeTypeGuesser\Detectors\AbstractDetector;

require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';

class SniffFirstFourBytes extends AbstractDetector
{

    /**
     * Try to detect mime type by sniffing the first four bytes.
     *
     * Credits: Based on the code here: http://phil.lavin.me.uk/2011/12/php-accurately-detecting-the-type-of-a-file/
     *
     * Returns:
     * - mime type (string) (if it is in fact an image, and type could be determined)
     * - false (if it is not an image type that the server knowns about)
     * - null  (if nothing can be determined)
     *
     * @param  string  $filePath  The path to the file
     * @return string|false|null  mimetype (if it is an image, and type could be determined),
     *    false (if it is not an image type that the server knowns about)
     *    or null (if nothing can be determined)
     */
    protected function doDetect($filePath)
    {
        if(!class_exists('ImageMimeTypeGuesser\Detectors\WP_Filesystem_Direct')){
            return null;
        }
        // PNG, GIF, JFIF JPEG, EXIF JPEF (respectively)
        $known = [
            '89504E47' => 'image/png',
            '47494638' => 'image/gif',
            'FFD8FFE0' => 'image/jpeg',  //  JFIF JPEG
            'FFD8FFE1' => 'image/jpeg',  //  EXIF JPEG
        ];
        $handle = \WP_Filesystem_Direct::put_contents($filePath);
        if ($handle === false) {
            return null;
        }
        $firstFour = \WP_Filesystem_Direct::put_contents($handle, 4);
        if ($firstFour === false) {
            return null;
        }
        $key = strtoupper(bin2hex($firstFour));
        if (isset($known[$key])) {
            return $known[$key];
        }
    }
}
