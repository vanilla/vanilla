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
		$this->_MaxFileSize = self::UnformatFileSize(Gdn::Config('Garden.Upload.MaxFileSize', ''));
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

      if (!IsWritable($UploadPath) || !is_readable($UploadPath)) 
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
      if ($Extension === NULL)
         $this->_AllowedFileExtensions = array();
		elseif (is_array($Extension))
			array_merge($this->_AllowedFileExtensions, $Extension);
		else
			$this->_AllowedFileExtensions[] = $Extension;
	}

	/** Format a number of bytes with the largest unit.
	 * @param int $Bytes The number of bytes.
	 * @param int $Precision The number of decimal places in the formatted number.
	 * @return string the formatted filesize.
	 */
	public static function FormatFileSize($Bytes, $Precision = 1) {
		$Units = array('B', 'K', 'M', 'G', 'T');

		$Bytes = max((int)$Bytes, 0);
		$Pow = floor(($Bytes ? log($Bytes) : 0) / log(1024));
		$Pow = min($Pow, count($Units) - 1);

		$Bytes /= pow(1024, $Pow);

		$Result = round($Bytes, $Precision).$Units[$Pow];
		return $Result;
	}

	/**
	 * Take a string formatted filesize and return the number of bytes.
	 * @param string $Formatted The formatted filesize.
	 * @return int The number of bytes in the string.
	 */
	public static function UnformatFileSize($Formatted) {
		$Units = array('B' => 1, 'K' => 1024, 'M' => 1024 * 1024, 'G' => 1024 * 1024 * 1024, 'T' => 1024 * 1024 * 1024 * 1024);

		if(preg_match('/([0-9.]+)\s*([A-Z]*)/i', $Formatted, $Matches)) {
			$Number = floatval($Matches[1]);
			$Unit = strtoupper(substr($Matches[2], 0, 1));
			$Mult = GetValue($Unit, $Units, 1);

			$Result = round($Number * $Mult, 0);
			return $Result;
		} else {
			return FALSE;
		}
	}

	/**
	 * Validates the uploaded file. Returns the temporary name of the uploaded file.
	 */
	public function ValidateUpload($InputName, $ThrowException = TRUE) {
		$Ex = FALSE;

		if (!array_key_exists($InputName, $_FILES) || (!is_uploaded_file($_FILES[$InputName]['tmp_name']) && GetValue('error', $_FILES[$InputName], 0) == 0)) {
			// Check the content length to see if we exceeded the max post size.
			$ContentLength = Gdn::Request()->GetValueFrom('server', 'CONTENT_LENGTH');
			$MaxPostSize = self::UnformatFileSize(ini_get('post_max_size'));
			if($ContentLength > $MaxPostSize) {
				$Ex = sprintf(T('Gdn_Upload.Error.MaxPostSize', 'The file is larger than the maximum post size. (%s)'), self::FormatFileSize($MaxPostSize));
			} else {
            $Ex = T('The file failed to upload.');
         }
		} else {
			switch ($_FILES[$InputName]['error']) {
				case 1:
				case 2:
					$MaxFileSize = self::UnformatFileSize(ini_get('upload_max_filesize'));
					$Ex = sprintf(T('Gdn_Upload.Error.PhpMaxFileSize', 'The file is larger than the server\'s maximum file size. (%s)'), self::FormatFileSize($MaxFileSize));
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

		$Foo = self::FormatFileSize($this->_MaxFileSize);

		// Check the maxfilesize again just in case the value was spoofed in the form.
		if (!$Ex && $this->_MaxFileSize > 0 && filesize($_FILES[$InputName]['tmp_name']) > $this->_MaxFileSize) {
			$Ex = sprintf(T('Gdn_Upload.Error.MaxFileSize', 'The file is larger than the maximum file size. (%s)'), self::FormatFileSize($this->_MaxFileSize));
		} elseif(!$Ex) {
			// Make sure that the file extension is allowed.
			$Extension = pathinfo($_FILES[$InputName]['name'], PATHINFO_EXTENSION);
			if (!InArrayI($Extension, $this->_AllowedFileExtensions))
				$Ex = sprintf(T('You cannot upload files with this extension (%s). Allowed extension(s) are %s.'), $Extension, implode(', ', $this->_AllowedFileExtensions));
		}

		if($Ex) {
			if($ThrowException) {
				throw new Gdn_UserException($Ex);
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
		return GetValue('name', $this->_UploadedFile);
	}

	public function GetUploadedFileExtension() {
		$Name = $this->_UploadedFile['name'];
		$Info = pathinfo($Name);
		return GetValue('extension', $Info, '');
	}

	public function GenerateTargetName($TargetFolder, $Extension = '') {
		if ($Extension == '')
			$Extension = $this->GetUploadedFileExtension();

		$Name = RandomString(12);
		while (file_exists($TargetFolder . DS . $Name . '.' . $Extension)) {
			$Name = RandomString(12);
		}
		return $TargetFolder . DS . $Name . '.' . $Extension;
	}

	public function SaveAs($Source, $Target) {
      if (!file_exists(dirname($Target)))
         mkdir(dirname($Target));

		if (!move_uploaded_file($Source, $Target))
			throw new Exception(sprintf(T('Failed to move uploaded file to target destination (%s).'), $Target));
	}

}