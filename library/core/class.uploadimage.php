<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

/**
 * Handles uploading image files.
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

   /**
    * Validates the uploaded image. Returns the temporary name of the uploaded file.
    */
   public function ValidateUpload($InputName) {
   
      if (!function_exists('gd_info'))
         throw new Exception(T('The uploaded file could not be processed because GD is not installed.'));
   
      // Make sure that all standard file upload checks are performed.
      $TmpFileName = parent::ValidateUpload($InputName);
      
      // Now perform image-specific checks
      $Size = getimagesize($TmpFileName);
      if ($Size === FALSE)
         throw new Exception(T('The uploaded file was not an image.'));
      
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
      $SaveGig = FALSE;
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
         $OutputTypes = array(1 => 'gif', 2 => 'jpeg', 3 => 'png');
         $OutputType = GetValue($Type, $OutputTypes, 'jpg');
      }

      // Figure out the target path.
      $TargetParsed = Gdn_Upload::Parse($Target);
      $TargetPath = PATH_LOCAL_UPLOADS.'/'.ltrim($TargetParsed['Name'], '/');

      if (!file_exists(dirname($TargetPath)))
         mkdir(dirname($TargetPath), 0777, TRUE);
      
      // Don't resize if the source dimensions are smaller than the target dimensions
      $XCoord = GetValue('SourceX', $Options, 0);
      $YCoord = GetValue('SourceY', $Options, 0);
      if ($HeightSource > $Height || $WidthSource > $Width) {
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
                  $YCoord = round(($HeightSource - $NewHeightSource) / 2);
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
         else if ($OutputType == 'png') {
            imagepng($TargetImage, $TargetPath, (int)($ImageQuality/10));
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
      Gdn::PluginManager()->CallEventHandlers($Sender, 'Gdn_UploadImage', 'SaveImageAs');
      return $Sender->EventArguments['Parsed'];
   }
   
   public function GenerateTargetName($TargetFolder, $Extension = 'jpg', $Chunk = FALSE) {
      if (!$Extension) {
         $Extension = trim(pathinfo($this->_UploadedFile['name'], PATHINFO_EXTENSION), '.');
      }

      do {
         if ($Chunk) {
            $Name = RandomString(12);
            $Subdir = sprintf('%03d', mt_rand(0, 999));
         } else {
            $Name = RandomString(12);
            $Subdir = '';
         }
         $Path = "$TargetFolder/$Subdir/$Name.$Extension";
      } while(file_exists($Path));
      return $Path;
   }
}