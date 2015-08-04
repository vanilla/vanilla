<?php
/**
 * Gdn_UploadImage
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

/**
 * Handles image uploads
 */
class Gdn_UploadImage extends Gdn_Upload {

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

        $GdInfo = gd_info();
        // Do we have a good version of GD?
        $GdVersion = preg_replace('/[a-z ()]+/i', '', $GdInfo['GD Version']);
        if ($GdVersion < 2) {
            return false;
        }

        return true;
    }

    /**
     *
     */
    public function clear() {
        parent::clear();
        $this->_AllowedFileExtensions = array('jpg', 'jpeg', 'gif', 'png', 'bmp', 'ico');
    }

    /**
     * Gets the image size of a file.
     *
     * @param string $Path The path to the file.
     * @param string $Filename The name of the file.
     * @return array An array of [width, height, image type].
     * @since 2.1
     */
    public static function imageSize($Path, $Filename = false) {
        if (!$Filename) {
            $Filename = $Path;
        }

        if (in_array(strtolower(pathinfo($Filename, PATHINFO_EXTENSION)), array('gif', 'jpg', 'jpeg', 'png'))) {
            $ImageSize = @getimagesize($Path);
            if (!is_array($ImageSize) || !in_array($ImageSize[2], array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG))) {
                return array(0, 0, false);
            }
            return $ImageSize;
        }
        return array(0, 0, false);
    }

    /**
     * Validates the uploaded image. Returns the temporary name of the uploaded file.
     */
    public function validateUpload($InputName, $ThrowError = true) {
        if (!function_exists('gd_info')) {
            throw new Exception(T('The uploaded file could not be processed because GD is not installed.'));
        }

        // Make sure that all standard file upload checks are performed.
        $TmpFileName = parent::validateUpload($InputName, $ThrowError);

        // Now perform image-specific checks.
        if ($TmpFileName) {
            $Size = getimagesize($TmpFileName);
            if ($Size === false) {
                throw new Exception(T('The uploaded file was not an image.'));
            }
        }

        return $TmpFileName;
    }

    /**
     * Saves the specified image at $Target in the specified format with the
     * specified dimensions (or the existing dimensions if height/width are not provided.
     *
     * @param string The path to the source image. Typically this is the tmp file name returned by $this->ValidateUpload();
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
    public static function saveImageAs($Source, $Target, $Height = '', $Width = '', $Options = array()) {
        $Crop = false;
        $OutputType = '';
        $ImageQuality = C('Garden.UploadImage.Quality', 75);

        // Make function work like it used to.
        $Args = func_get_args();
        $SaveGif = false;
        if (count($Args) > 5) {
            $Crop = val(4, $Args, $Crop);
            $OutputType = val(5, $Args, $OutputType);
            $ImageQuality = val(6, $Args, $ImageQuality);
        } elseif (is_bool($Options)) {
            $Crop = $Options;
        } else {
            $Crop = val('Crop', $Options, $Crop);
            $OutputType = val('OutputType', $Options, $OutputType);
            $ImageQuality = val('ImageQuality', $Options, $ImageQuality);
            $SaveGif = val('SaveGif', $Options);
        }

        // Set some boundaries for $ImageQuality
        if ($ImageQuality < 10) {
            $ImageQuality = 10;
        }
        if ($ImageQuality > 100 || !is_numeric($ImageQuality)) {
            $ImageQuality = 100;
        }

        // Make sure type, height & width are properly defined.

        if (!function_exists('gd_info')) {
            throw new Exception(T('The uploaded file could not be processed because GD is not installed.'));
        }

        $GdInfo = gd_info();
        $Size = getimagesize($Source);
        list($WidthSource, $HeightSource, $Type) = $Size;
        $WidthSource = val('SourceWidth', $Options, $WidthSource);
        $HeightSource = val('SourceHeight', $Options, $HeightSource);

        if ($Height == '' || !is_numeric($Height)) {
            $Height = $HeightSource;
        }

        if ($Width == '' || !is_numeric($Width)) {
            $Width = $WidthSource;
        }

        if (!$OutputType) {
            $OutputTypes = array(1 => 'gif', 2 => 'jpeg', 3 => 'png', 17 => 'ico');
            $OutputType = val($Type, $OutputTypes, 'jpg');
        } elseif ($Type == 17 && $OutputType != 'ico') {
            // Icons cannot be converted
            throw new Exception(T('Upload cannot convert icons.'));
        }

        // Figure out the target path.
        $TargetParsed = Gdn_Upload::parse($Target);
        $TargetPath = PATH_UPLOADS.'/'.ltrim($TargetParsed['Name'], '/');

        if (!file_exists(dirname($TargetPath))) {
            mkdir(dirname($TargetPath), 0777, true);
        }

        // Don't resize if the source dimensions are smaller than the target dimensions or an icon
        $XCoord = val('SourceX', $Options, 0);
        $YCoord = val('SourceY', $Options, 0);
        if (($HeightSource > $Height || $WidthSource > $Width) && $Type != 17) {
            $AspectRatio = (float)$WidthSource / $HeightSource;
            if ($Crop === false) {
                if (round($Width / $AspectRatio) > $Height) {
                    $Width = round($Height * $AspectRatio);
                } else {
                    $Height = round($Width / $AspectRatio);
                }
            } else {
                $HeightDiff = $HeightSource - $Height;
                $WidthDiff = $WidthSource - $Width;
                if ($WidthDiff > $HeightDiff) {
                    // Crop the original width down
                    $NewWidthSource = round(($Width * $HeightSource) / $Height);

                    // And set the original x position to the cropped start point.
                    if (!isset($Options['SourceX'])) {
                        $XCoord = round(($WidthSource - $NewWidthSource) / 2);
                    }
                    $WidthSource = $NewWidthSource;
                } else {
                    // Crop the original height down
                    $NewHeightSource = round(($Height * $WidthSource) / $Width);

                    // And set the original y position to the cropped start point.
                    if (!isset($Options['SourceY'])) {
                        $YCoord = 0; // crop to top because most portraits show the face at the top.
                    }                    $HeightSource = $NewHeightSource;
                }
            }
        } else {
            // Neither target dimension is larger than the original, so keep the original dimensions.
            $Height = $HeightSource;
            $Width = $WidthSource;
        }

        $Process = true;
        if ($WidthSource <= $Width && $HeightSource <= $Height && $Type == 1 && $SaveGif) {
            $Process = false;
        }

        // Never process icons
        if ($Type == 17) {
            $Process = false;
        }

        if ($Process) {
            // Create GD image from the provided file, but first check if we have the necessary tools
            $SourceImage = false;
            switch ($Type) {
                case 1:
                    if (val('GIF Read Support', $GdInfo) || val('GIF Write Support', $GdInfo)) {
                        $SourceImage = imagecreatefromgif($Source);
                    }
                    break;
                case 2:
                    if (val('JPG Support', $GdInfo) || val('JPEG Support', $GdInfo)) {
                        $SourceImage = imagecreatefromjpeg($Source);
                    }
                    break;
                case 3:
                    if (val('PNG Support', $GdInfo)) {
                        $SourceImage = imagecreatefrompng($Source);
                        imagealphablending($SourceImage, true);
                    }
                    break;
            }

            if (!$SourceImage) {
                throw new Exception(sprintf(T('You cannot save images of this type (%s).'), $Type));
            }

            // Create a new image from the raw source
            if (function_exists('imagecreatetruecolor')) {
                $TargetImage = imagecreatetruecolor($Width, $Height);    // Only exists if GD2 is installed
            } else {
                $TargetImage = imagecreate($Width, $Height);             // Always exists if any GD is installed
            }
            if ($OutputType == 'png') {
                imagealphablending($TargetImage, false);
                imagesavealpha($TargetImage, true);
            }

            imagecopyresampled($TargetImage, $SourceImage, 0, 0, $XCoord, $YCoord, $Width, $Height, $WidthSource, $HeightSource);
            imagedestroy($SourceImage);

            // Check for EXIF rotation tag, and rotate the image if present
            if (function_exists('exif_read_data') &&
                (($Type == IMAGETYPE_JPEG) || ($Type == IMAGETYPE_TIFF_II) || ($Type == IMAGETYPE_TIFF_MM))
            ) {
                $ImageExif = exif_read_data($Source);
                if (!empty($ImageExif['Orientation'])) {
                    switch ($ImageExif['Orientation']) {
                        case 3:
                            $TargetImage = imagerotate($TargetImage, 180, 0);
                            break;
                        case 6:
                            $TargetImage = imagerotate($TargetImage, -90, 0);
                            list($Width, $Height) = array($Height, $Width);
                            break;
                        case 8:
                            $TargetImage = imagerotate($TargetImage, 90, 0);
                            list($Width, $Height) = array($Height, $Width);
                            break;
                    }
                }
            }

            // No need to check these, if we get here then whichever function we need will be available
            if ($OutputType == 'gif') {
                imagegif($TargetImage, $TargetPath);
            } elseif ($OutputType == 'png') {
                imagepng($TargetImage, $TargetPath, 10 - (int)($ImageQuality / 10));
            } elseif ($OutputType == 'ico') {
                self::imageIco($TargetImage, $TargetPath);
            } else {
                imagejpeg($TargetImage, $TargetPath, $ImageQuality);
            }
        } else {
            copy($Source, $TargetPath);
        }

        // Allow a plugin to move the file to a differnt location.
        $Sender = new stdClass();
        $Sender->EventArguments = array();
        $Sender->EventArguments['Path'] = $TargetPath;
        $Parsed = self::parse($TargetPath);
        $Parsed['Width'] = $Width;
        $Parsed['Height'] = $Height;
        $Sender->EventArguments['Parsed'] =& $Parsed;
        $Sender->Returns = array();
        Gdn::pluginManager()->callEventHandlers($Sender, 'Gdn_Upload', 'SaveAs');
        return $Sender->EventArguments['Parsed'];
    }

    /**
     *
     *
     * @param $GD
     * @param $TargetPath
     */
    public static function imageIco($GD, $TargetPath) {
        require_once PATH_LIBRARY.'/vendors/phpThumb/phpthumb.ico.php';
        require_once PATH_LIBRARY.'/vendors/phpThumb/phpthumb.functions.php';
        $Ico = new phpthumb_ico();
        $Arr = array('ico' => $GD);
        $IcoString = $Ico->GD2ICOstring($Arr);
        file_put_contents($TargetPath, $IcoString);
    }
}
