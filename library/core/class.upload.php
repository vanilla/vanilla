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
 * Handles uploading files.
 */
class Gdn_Upload {
   
   protected $_MaxFileSize;
   protected $_AllowedFileExtensions;
   protected $_UploadedFile;

   /**
    * Class constructor
    */
   public function __construct() {
      $this->Clear();
   }

   public function Clear() {
      $this->_MaxFileSize = Gdn::Config('Garden.Upload.MaxFileSize', '1024000');
      $this->_AllowedFileExtensions = Gdn::Config('Garden.Upload.AllowedFileExtensions', array());
   }
   
   public static function CanUpload($UploadPath=NULL) {
      if (is_null($UploadPath))
         $UploadPath = PATH_UPLOADS;
      
      if (ini_get('file_uploads') != 1)
         return FALSE;
      
      if (!is_dir($UploadPath)) 
         @mkdir($UploadPath);
         if (!is_dir($UploadPath))
            return FALSE;
      
      if (!is_writable($UploadPath) || !is_readable($UploadPath)) 
         return FALSE;
         
      return TRUE;
   }
   
   /**
    * Adds an extension (or array of extensions) to the array of allowed file
    * extensions.
    *
    * @param mixed The name (or array of names) of the extension to allow.
    */
   public function AllowFileExtension($Extension) {
      if (is_array($Extension))
         array_merge($this->_AllowedFileExtensions, $Extension);
      else 
         $this->_AllowedFileExtensions[] = $Extension;
   }

   /**
    * Validates the uploaded file. Returns the temporary name of the uploaded file.
    */
   public function ValidateUpload($InputName, $ThrowException = TRUE) {
      $Ex = FALSE;
		if(!array_key_exists($InputName, $_FILES) || !is_uploaded_file($_FILES[$InputName]['tmp_name']))
			$Ex = T('The file failed to upload.');
      else {
			switch ($_FILES[$InputName]['error']) {
			   case 1:
			   case 2:
			      $Ex = T('The file is too large to be uploaded to this application.');
			      break;
				case 3:
				case 4:
				  $Ex = T('The file failed to upload.');
				  break;
				case 6:
					$Ex = T('The temporary upload folder has not been configured.');
					break;
				case 7:
					$Ex = T('Failed to write the file to disk.');
					break;
				case 8:
					$Ex = T('The upload was stopped by extension.');
					break;
		  }
		}
      
      // Check the maxfilesize again just in case the value was spoofed in the form.
      if (!$Ex && filesize($_FILES[$InputName]['tmp_name']) > $this->_MaxFileSize)
         $Ex = T('The file is too large to be uploaded to this application.');
      elseif(!$Ex) {
			// Make sure that the file extension is allowed
			$Extension = pathinfo($_FILES[$InputName]['name'], PATHINFO_EXTENSION);
			if (!InArrayI($Extension, $this->_AllowedFileExtensions))
			   $Ex = sprintf(T('You cannot upload files with this extension (%s).'), $Extension);
		}

		if($Ex) {
			if($ThrowException) {
				throw new Exception($Ex);
			} else {
				$this->Exception = $Ex;
				return FALSE;
			}
		} else {
			// If all validations were successful, return the tmp name/location of the file.
			$this->_UploadedFile = $_FILES[$InputName];
			return $this->_UploadedFile['tmp_name'];
		}
   }
   
   public function GetUploadedFileName() {
      return $this->_UploadedFile['name'];
   }

   public function GenerateTargetName($TargetFolder, $Extension) {
      $Name = RandomString(12);
      while (file_exists($TargetFolder . DS . $Name . '.' . $Extension)) {
         $Name = RandomString(12);
      }
      return $TargetFolder . DS . $Name . '.' . $Extension;
   }
   
   public function SaveAs($Source, $Target) {
      if (!move_uploaded_file($Source, $Target))
         throw new Exception(sprintf(T('Failed to move uploaded file to target destination (%s).'), $Target));
   }
   
}