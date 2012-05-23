<?php if (!defined('APPLICATION')) exit();

/**
 * Handles image uploads
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @author Todd Burry <todd@vanillaforums.com> 
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
 */

class Gdn_UploadImage extends Gdn_Upload {

   public static function CanUploadImages() {
      // Check that we have the necessary tools to allow image uploading
      
      // Is the Uploads directory available and correctly permissioned?
      if (!Gdn_Upload::CanUpload())
         return FALSE;
      
      // Do we have GD?
      if (!function_exists('gd_info'))
         return FALSE;
      
      $GdInfo = gd_info();
      // Do we have a good version of GD?
      $GdVersion = preg_replace('/[a-z ()]+/i', '', $GdInfo['GD Version']);
      if ($GdVersion < 2)
         return FALSE;
      
      return TRUE;
   }
   
   public function Clear() {
      parent::Clear();
		$this->_AllowedFileExtensions = array('jpg','jpeg','gif','png','bmp','ico');
   }
   
   /**
    * Gets the image size of a file.
    * @param string $Path The path to the file.
    * @param string $Filename The name of the file.
    * @return array An array of [width, height, image type].
    * @since 2.1
    */
   public static function ImageSize($Path, $Filename = FALSE) {
      if (!$Filename)
         $Filename = $Path;
      
      if (in_array(strtolower(pathinfo($Filename, PATHINFO_EXTENSION)), array('gif', 'jpg', 'jpeg', 'png'))) {
         $ImageSize = @getimagesize($Path);
         if (!is_array($ImageSize) || !in_array($ImageSize[2], array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG)))
            return array(0, 0, FALSE);
         return $ImageSize;
      }
      return array(0, 0, FALSE);
   }

   /**
    * Validates the uploaded image. Returns the temporary name of the uploaded file.
    */
   public function ValidateUpload($InputName, $ThrowError = TRUE) {
   
      if (!function_exists('gd_info'))
         throw new Exception(T('The uploaded file could not be processed because GD is not installed.'));
   
      // Make sure that all standard file upload checks are performed.
      $TmpFileName = parent::ValidateUpload($InputName, $ThrowError);
      
      // Now perform image-specific checks.
      if ($TmpFileName) {
         $Size = getimagesize($TmpFileName);
         if ($Size === FALSE)
            throw new Exception(T('The uploaded file was not an image.'));
      }
      
      return $TmpFileName;
   }
   
   /**
    * Saves the specified image at $Target in the specified format with the
    * specified dimensions (or the existing dimensions if height/width are not
    * provided.
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
   public static function SaveImageAs($Source, $Target, $Height = '', $Width = '', $Options = array()) {
      $Crop = FALSE; $OutputType = ''; $ImageQuality = C('Garden.UploadImage.Quality', 75);
      
      // Make function work like it used to.
      $Args = func_get_args();
      $SaveGif = FALSE;
      if (count($Args) > 5) {
         $Crop = GetValue(4, $Args, $Crop);
         $OutputType = GetValue(5, $Args, $OutputType);
         $ImageQuality = GetValue(6, $Args, $ImageQuality);
      } elseif (is_bool($Options)) {
         $Crop = $Options;
      } else {
         $Crop = GetValue('Crop', $Options, $Crop);
         $OutputType = GetValue('OutputType', $Options, $OutputType);
         $ImageQuality = GetValue('ImageQuality', $Options, $ImageQuality);
         $SaveGif = GetValue('SaveGif', $Options);
      }

      // Make sure type, height & width are properly defined.
      
      if (!function_exists('gd_info'))
         throw new Exception(T('The uploaded file could not be processed because GD is not installed.'));
         
      $GdInfo = gd_info();      
      $Size = getimagesize($Source);
      list($WidthSource, $HeightSource, $Type) = $Size;
      $WidthSource = GetValue('SourceWidth', $Options, $WidthSource);
      $HeightSource = GetValue('SourceHeight', $Options, $HeightSource);
      
      if ($Height == '' || !is_numeric($Height))
         $Height = $HeightSource;
         
      if ($Width == '' || !is_numeric($Width))
         $Width = $WidthSource;

      if (!$OutputType) {      
         $OutputTypes = array(1 => 'gif', 2 => 'jpeg', 3 => 'png', 17 => 'ico');
         $OutputType = GetValue($Type, $OutputTypes, 'jpg');
      }
      elseif ($Type == 17 && $OutputType != 'ico') {
         // Icons cannot be converted
         throw new Exception(T('Upload cannot convert icons.'));
      }

      // Figure out the target path.
      $TargetParsed = Gdn_Upload::Parse($Target);
      $TargetPath = PATH_UPLOADS.'/'.ltrim($TargetParsed['Name'], '/');

      if (!file_exists(dirname($TargetPath)))
         mkdir(dirname($TargetPath), 0777, TRUE);
      
      // Don't resize if the source dimensions are smaller than the target dimensions or an icon
      $XCoord = GetValue('SourceX', $Options, 0);
      $YCoord = GetValue('SourceY', $Options, 0);
      if (($HeightSource > $Height || $WidthSource > $Width) && $Type != 17) {
         $AspectRatio = (float) $WidthSource / $HeightSource;
         if ($Crop === FALSE) {
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
               if (!isset($Options['SourceX']))
                  $XCoord = round(($WidthSource - $NewWidthSource) / 2);
               $WidthSource = $NewWidthSource;
            } else {
               // Crop the original height down
               $NewHeightSource = round(($Height * $WidthSource) / $Width);
               
               // And set the original y position to the cropped start point.
               if (!isset($Options['SourceY']))
                  $YCoord = 0; // crop to top because most portraits show the face at the top.
               $HeightSource = $NewHeightSource;
            }
         }
      } else {
         // Neither target dimension is larger than the original, so keep the original dimensions.
         $Height = $HeightSource;
         $Width = $WidthSource;
      }

      $Process = TRUE;
      if ($WidthSource <= $Width && $HeightSource <= $Height && $Type == 1 && $SaveGif) {
         $Process = FALSE;
      }
      
      // Never process icons
      if ($Type == 17) {
         $Process = FALSE;
      }

      if ($Process) {
         // Create GD image from the provided file, but first check if we have the necessary tools
         $SourceImage = FALSE;
         switch ($Type) {
            case 1:
               if (GetValue('GIF Read Support', $GdInfo) || GetValue('GIF Write Support', $GdInfo))
                  $SourceImage = imagecreatefromgif($Source);
               break;
            case 2:
               if (GetValue('JPG Support', $GdInfo) || GetValue('JPEG Support', $GdInfo))
                  $SourceImage = imagecreatefromjpeg($Source);
               break;
            case 3:
               if (GetValue('PNG Support', $GdInfo)) {
                  $SourceImage = imagecreatefrompng($Source);
                  imagealphablending($SourceImage, TRUE);
               }
               break;
         }

         if (!$SourceImage)
            throw new Exception(sprintf(T('You cannot save images of this type (%s).'), $Type));

         // Create a new image from the raw source
         if (function_exists('imagecreatetruecolor')) {
            $TargetImage = imagecreatetruecolor($Width, $Height);    // Only exists if GD2 is installed
         } else
            $TargetImage = imagecreate($Width, $Height);             // Always exists if any GD is installed

         if ($OutputType == 'png') {
            imagealphablending($TargetImage, FALSE);
            imagesavealpha($TargetImage, TRUE);
         }

         imagecopyresampled($TargetImage, $SourceImage, 0, 0, $XCoord, $YCoord, $Width, $Height, $WidthSource, $HeightSource);
         imagedestroy($SourceImage);

         // No need to check these, if we get here then whichever function we need will be available
         if ($OutputType == 'gif')
            imagegif($TargetImage, $TargetPath);
         elseif ($OutputType == 'png') {
            imagepng($TargetImage, $TargetPath, (int)($ImageQuality/10));
         } elseif ($OutputType == 'ico') {
            self::ImageIco($TargetImage, $TargetPath);
         } else
            imagejpeg($TargetImage, $TargetPath, $ImageQuality);
      } else {
         copy($Source, $TargetPath);
      }

      // Allow a plugin to move the file to a differnt location.
      $Sender = new stdClass();
      $Sender->EventArguments = array();
      $Sender->EventArguments['Path'] = $TargetPath;
      $Parsed = self::Parse($TargetPath);
      $Sender->EventArguments['Parsed'] =& $Parsed;
      $Sender->Returns = array();
      Gdn::PluginManager()->CallEventHandlers($Sender, 'Gdn_Upload', 'SaveAs');
      return $Sender->EventArguments['Parsed'];
   }
   
   public static function ImageIco($GD, $TargetPath) {
      require_once PATH_LIBRARY.'/vendors/phpThumb/phpthumb.ico.php';
      require_once PATH_LIBRARY.'/vendors/phpThumb/phpthumb.functions.php';
      $Ico = new phpthumb_ico();
      $Arr = array('ico' => $GD);
      $IcoString = $Ico->GD2ICOstring($Arr);
      file_put_contents($TargetPath, $IcoString);
   }
}