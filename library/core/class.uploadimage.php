<?php
/**
 * Gdn_UploadImage
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

/**
 * Handles image uploads
 */
class Gdn_UploadImage extends Gdn_Upload {

    /**
     * Compression level (0-9) for PNGs.
     */
    const PNG_COMPRESSION = 9;

    /**
     * Check that we have the necessary tools to allow image uploading.
     *
     * @return bool
     */
    public static function canUploadImages() {
        // Is the Uploads directory available and correctly permissioned?
        if (!Gdn_Upload::canUpload()) {
            return false;
        }

        // Do we have GD?
        if (!function_exists('gd_info')) {
            return false;
        }

        $gdInfo = gd_info();
        // Do we have a good version of GD?
        $gdVersion = preg_replace('/[a-z ()]+/i', '', $gdInfo['GD Version']);
        if ($gdVersion < 2) {
            return false;
        }

        return true;
    }

    /**
     *
     */
    public function clear() {
        parent::clear();
        $this->_AllowedFileExtensions = ['jpg', 'jpeg', 'gif', 'png', 'bmp', 'ico'];
    }

    /**
     * Gets the image size of a file.
     *
     * @param string $path The path to the file.
     * @param string $filename The name of the file.
     * @return array An array of [width, height, image type].
     * @since 2.1
     */
    public static function imageSize($path, $filename = false) {
        if (!$filename) {
            $filename = $path;
        }

        if (in_array(strtolower(pathinfo($filename, PATHINFO_EXTENSION)), ['gif', 'jpg', 'jpeg', 'png'])) {
            $imageSize = @getimagesize($path);
            if (!is_array($imageSize) || !in_array($imageSize[2], [IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG])) {
                return [0, 0, false];
            }
            return $imageSize;
        }
        return [0, 0, false];
    }

    /**
     * Validates the uploaded image. Returns the temporary name of the uploaded file.
     */
    public function validateUpload($inputName, $throwError = true) {
        if (!function_exists('gd_info')) {
            throw new Exception(t('The uploaded file could not be processed because GD is not installed.'));
        }

        // Make sure that all standard file upload checks are performed.
        $tmpFileName = parent::validateUpload($inputName, $throwError);

        // Now perform image-specific checks.
        if ($tmpFileName) {
            $size = getimagesize($tmpFileName);
            if ($size === false) {
                throw new Exception(t('The uploaded file was not an image.'));
            }
        }

        return $tmpFileName;
    }

    /**
     * Saves the specified image at $target in the specified format with the
     * specified dimensions (or the existing dimensions if height/width are not provided.
     *
     * @param string The path to the source image. Typically this is the tmp file name returned by $this->validateUpload();
     * @param string The full path to where the image should be saved, including image name.
     * @param int An integer value indicating the maximum allowed height of the image (in pixels).
     * @param int An integer value indicating the maximum allowed width of the image (in pixels).
     * @param array Options additional options for saving the image.
     *  - <b>Crop</b>: Image proportions will always remain constrained. The Crop parameter is a boolean value indicating if the image should be cropped when one dimension (height or width) goes beyond the constrained proportions.
     *  - <b>OutputType</b>: The format in which the output image should be saved. Options are: jpg, png, and gif. Default is jpg.
     *  - <b>ImageQuality</b>: An integer value representing the qualityof the saved image. Ranging from 0 (worst quality, smaller file) to 100 (best quality, biggest file).
     *  - <b>SourceX, SourceY</b>: If you want to create a thumbnail that is a crop of the image these are the coordinates of the thumbnail.
     *  - <b>SourceHeight. SourceWidth</b>: If you want to create a thumbnail that is a crop of the image these are it's dimensions.
     */
    public static function saveImageAs($source, $target, $height = '', $width = '', $options = []) {
        $crop = false;
        $outputType = '';
        $imageQuality = c('Garden.UploadImage.Quality', 100);

        // Make function work like it used to.
        $args = func_get_args();
        $saveGif = false;
        if (count($args) > 5) {
            $crop = val(4, $args, $crop);
            $outputType = val(5, $args, $outputType);
            $imageQuality = val(6, $args, $imageQuality);
        } elseif (is_bool($options)) {
            $crop = $options;
        } else {
            $crop = val('Crop', $options, $crop);
            $outputType = val('OutputType', $options, $outputType);
            $imageQuality = val('ImageQuality', $options, $imageQuality);
            $saveGif = val('SaveGif', $options);
        }

        // Set some boundaries for $ImageQuality
        if ($imageQuality < 10) {
            $imageQuality = 10;
        }
        if ($imageQuality > 100 || !is_numeric($imageQuality)) {
            $imageQuality = 100;
        }

        // Make sure type, height & width are properly defined.

        if (!function_exists('gd_info')) {
            throw new Exception(t('The uploaded file could not be processed because GD is not installed.'));
        }

        $gdInfo = gd_info();
        $size = getimagesize($source);
        list($widthSource, $heightSource, $type) = $size;
        $widthSource = val('SourceWidth', $options, $widthSource);
        $heightSource = val('SourceHeight', $options, $heightSource);

        if ($height == '' || !is_numeric($height)) {
            $height = $heightSource;
        }

        if ($width == '' || !is_numeric($width)) {
            $width = $widthSource;
        }

        if (!$outputType) {
            $outputTypes = [1 => 'gif', 2 => 'jpeg', 3 => 'png', 17 => 'ico'];
            $outputType = val($type, $outputTypes, 'jpg');
        } elseif ($type == 17 && $outputType != 'ico') {
            // Icons cannot be converted
            throw new Exception(t('Upload cannot convert icons.'));
        }

        // Figure out the target path.
        $targetParsed = Gdn_Upload::parse($target);
        $targetPath = PATH_UPLOADS.'/'.ltrim($targetParsed['Name'], '/');

        if (!file_exists(dirname($targetPath))) {
            mkdir(dirname($targetPath), 0777, true);
        }

        // Don't resize if the source dimensions are smaller than the target dimensions or an icon
        $xCoord = val('SourceX', $options, 0);
        $yCoord = val('SourceY', $options, 0);
        if (($heightSource > $height || $widthSource > $width) && $type != 17) {
            $aspectRatio = (float)$widthSource / $heightSource;
            if ($crop === false) {
                if (round($width / $aspectRatio) > $height) {
                    $width = round($height * $aspectRatio);
                } else {
                    $height = round($width / $aspectRatio);
                }
            } else {
                $heightDiff = $heightSource - $height;
                $widthDiff = $widthSource - $width;
                if ($widthDiff > $heightDiff) {
                    // Crop the original width down
                    $newWidthSource = round(($width * $heightSource) / $height);

                    // And set the original x position to the cropped start point.
                    if (!isset($options['SourceX'])) {
                        $xCoord = round(($widthSource - $newWidthSource) / 2);
                    }
                    $widthSource = $newWidthSource;
                } else {
                    // Crop the original height down
                    $newHeightSource = round(($height * $widthSource) / $width);

                    // And set the original y position to the cropped start point.
                    if (!isset($options['SourceY'])) {
                        $yCoord = 0; // crop to top because most portraits show the face at the top.
                    }                    $heightSource = $newHeightSource;
                }
            }
        } else {
            // Neither target dimension is larger than the original, so keep the original dimensions.
            $height = $heightSource;
            $width = $widthSource;
        }

        $process = true;
        if ($widthSource <= $width && $heightSource <= $height && $type == 1 && $saveGif) {
            $process = false;
        }

        // Never process icons
        if ($type == 17) {
            $process = false;
        }

        if ($process) {
            // Create GD image from the provided file, but first check if we have the necessary tools
            $sourceImage = false;
            switch ($type) {
                case 1:
                    if (val('GIF Read Support', $gdInfo) || val('GIF Write Support', $gdInfo)) {
                        $sourceImage = imagecreatefromgif($source);
                    }
                    break;
                case 2:
                    if (val('JPG Support', $gdInfo) || val('JPEG Support', $gdInfo)) {
                        $sourceImage = imagecreatefromjpeg($source);
                    }
                    break;
                case 3:
                    if (val('PNG Support', $gdInfo)) {
                        $sourceImage = imagecreatefrompng($source);
                        imagealphablending($sourceImage, true);
                    }
                    break;
            }

            if (!$sourceImage) {
                throw new Exception(sprintf(t('You cannot save images of this type (%s).'), $type));
            }

            // Create a new image from the raw source
            if (function_exists('imagecreatetruecolor')) {
                $targetImage = imagecreatetruecolor($width, $height);    // Only exists if GD2 is installed
            } else {
                $targetImage = imagecreate($width, $height);             // Always exists if any GD is installed
            }
            if (in_array($outputType, ['png', 'ico'])) {
                imagealphablending($targetImage, false);
                imagesavealpha($targetImage, true);
            }

            imagecopyresampled($targetImage, $sourceImage, 0, 0, $xCoord, $yCoord, $width, $height, $widthSource, $heightSource);
            imagedestroy($sourceImage);

            // Check for EXIF rotation tag, and rotate the image if present
            if (function_exists('exif_read_data') &&
                (($type == IMAGETYPE_JPEG) || ($type == IMAGETYPE_TIFF_II) || ($type == IMAGETYPE_TIFF_MM))
            ) {
                $imageExif = exif_read_data($source);
                if (!empty($imageExif['Orientation'])) {
                    switch ($imageExif['Orientation']) {
                        case 3:
                            $targetImage = imagerotate($targetImage, 180, 0);
                            break;
                        case 6:
                            $targetImage = imagerotate($targetImage, -90, 0);
                            list($width, $height) = [$height, $width];
                            break;
                        case 8:
                            $targetImage = imagerotate($targetImage, 90, 0);
                            list($width, $height) = [$height, $width];
                            break;
                    }
                }
            }

            // No need to check these, if we get here then whichever function we need will be available
            if ($outputType == 'gif') {
                imagegif($targetImage, $targetPath);
            } elseif ($outputType == 'png') {
                imagepng($targetImage, $targetPath, Gdn_UploadImage::PNG_COMPRESSION);
            } elseif ($outputType == 'ico') {
                self::imageIco($targetImage, $targetPath);
            } else {
                imagejpeg($targetImage, $targetPath, $imageQuality);
            }
        } else {
            copy($source, $targetPath);
        }

        // Allow a plugin to move the file to a different location.
        $sender = new stdClass();
        $sender->EventArguments = [];
        $sender->EventArguments['Path'] = $targetPath;
        $parsed = self::parse($targetPath);
        $parsed['Width'] = $width;
        $parsed['Height'] = $height;
        $sender->EventArguments['Parsed'] =& $parsed;
        $sender->EventArguments['Options'] = $options;
        $sender->EventArguments['OriginalFilename'] = val('OriginalFilename', $options);
        $sender->Returns = [];
        Gdn::pluginManager()->callEventHandlers($sender, 'Gdn_Upload', 'SaveAs');
        return $sender->EventArguments['Parsed'];
    }

    /**
     *
     *
     * @param $GD
     * @param $TargetPath
     */
    public static function imageIco($gd, $targetPath) {
        $imagePath = tempnam(sys_get_temp_dir(), 'iconify');
        imagepng($gd, $imagePath);

        $icoLib = new PHP_ICO($imagePath, [[16,16]]);
        $icoLib->save_ico($targetPath);
        unlink($imagePath);
    }
}
