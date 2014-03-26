<?php if (!defined('APPLICATION')) exit();

/**
 * Handles file uploads
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @author Todd Burry <todd@vanillaforums.com> 
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
 */

class Gdn_Upload extends Gdn_Pluggable {
   /// PROPERTIES ///

	protected $_AllowedFileExtensions;
	protected $_MaxFileSize;
	protected $_UploadedFile;

   /// METHODS ///

	/**
	 * Class constructor
	 */
	public function __construct() {
		$this->Clear();
      parent::__construct();
      $this->ClassName = 'Gdn_Upload';
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
			$this->_AllowedFileExtensions = array_merge($this->_AllowedFileExtensions, $Extension);
		else
			$this->_AllowedFileExtensions[] = $Extension;
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

	public function Clear() {
		$this->_MaxFileSize = self::UnformatFileSize(Gdn::Config('Garden.Upload.MaxFileSize', ''));
		$this->_AllowedFileExtensions = Gdn::Config('Garden.Upload.AllowedFileExtensions', array());
	}

   /**
    * Copy an upload locally so that it can be operated on.
    *
    * @param string $Name
    */
   public function CopyLocal($Name) {
      $Parsed = self::Parse($Name);

      $LocalPath = '';
      $this->EventArguments['Parsed'] = $Parsed;
      $this->EventArguments['Path'] =& $LocalPath;

      $this->FireAs('Gdn_Upload')->FireEvent('CopyLocal');
      if (!$LocalPath) {
         $LocalPath = PATH_UPLOADS.'/'.$Parsed['Name'];
      }
      return $LocalPath;
   }

   /**
    * Delete an uploaded file.
    *
    * @param string $Name The name of the upload as saved in the database.
    */
   public function Delete($Name) {
      $Parsed = $this->Parse($Name);

      // Throw an event so that plugins that have stored the file somewhere else can delete it.
      $this->EventArguments['Parsed'] =& $Parsed;
      $Handled = FALSE;
      $this->EventArguments['Handled'] =& $Handled;
      $this->FireAs('Gdn_Upload')->FireEvent('Delete');

      if (!$Handled) {
         $Path = PATH_UPLOADS.'/'.ltrim($Name, '/');
         @unlink($Path);
      }
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

   public static function Parse($Name) {
      $Result = FALSE;
      $Name = str_replace('\\', '/', $Name);

      if (preg_match('`^https?://`', $Name)) {
         $Result = array('Name' => $Name, 'Type' => 'external', 'SaveName' => $Name, 'SaveFormat' => '%s', 'Url' => $Name, );
         return $Result;
      } elseif (StringBeginsWith($Name, PATH_UPLOADS)) {
         $Name = ltrim(substr($Name, strlen(PATH_UPLOADS)), '/');
         // This is an upload.
         $Result = array('Name' => $Name, 'Type' => '', 'SaveName' => $Name, 'SaveFormat' => '%s');
      } elseif (preg_match ('`^~([^/]*)/(.*)$`', $Name, $Matches)) {
         // The first part of the name tells us the type.
         $Type = $Matches[1];
         $Name = $Matches[2];

         $Result = array('Name' => $Name, 'Type' => $Type, 'SaveName' => "~$Type/$Name", 'SaveFormat' => "~$Type/%s");
      } else {
         $Name = ltrim($Name, '/');
         // This is an upload in the uploads folder.
         $Result = array('Name' => $Name, 'Type' => '', 'SaveName' => $Name, 'SaveFormat' => '%s');
      }

      $UrlPrefix = self::Urls($Result['Type']);
      if ($UrlPrefix === FALSE)
         $Result['Url'] = FALSE;
      else
         $Result['Url'] = $UrlPrefix.'/'.$Result['Name'];

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

	public function GetUploadedFileName() {
		return GetValue('name', $this->_UploadedFile);
	}

	public function GetUploadedFileExtension() {
		$Name = $this->_UploadedFile['name'];
		$Info = pathinfo($Name);
		return GetValue('extension', $Info, '');
	}

   public function GenerateTargetName($TargetFolder, $Extension = 'jpg', $Chunk = FALSE) {
      if (!$Extension) {
         $Extension = trim(pathinfo($this->_UploadedFile['name'], PATHINFO_EXTENSION), '.');
      }

      do {
         if ($Chunk) {
            $Name = RandomString(12);
            $Subdir = sprintf('%03d', mt_rand(0, 999)).'/';
         } else {
            $Name = RandomString(12);
            $Subdir = '';
         }
         $Path = "$TargetFolder/{$Subdir}$Name.$Extension";
      } while(file_exists($Path));
      return $Path;
   }

	public function SaveAs($Source, $Target) {
      $this->EventArguments['Path'] = $Source;
      $Parsed = self::Parse($Target);
      $this->EventArguments['Parsed'] =& $Parsed;
      $Handled = FALSE;
      $this->EventArguments['Handled'] =& $Handled;
      $this->FireAs('Gdn_Upload')->FireEvent('SaveAs');

      // Check to see if the event handled the save.
      if (!$Handled) {
         $Target = PATH_UPLOADS.'/'.$Parsed['Name'];
         if (!file_exists(dirname($Target)))
            mkdir(dirname($Target));
         
         if (StringBeginsWith($Source, PATH_UPLOADS))
            rename($Source, $Target);
         elseif (!move_uploaded_file($Source, $Target))
            throw new Exception(sprintf(T('Failed to move uploaded file to target destination (%s).'), $Target));
      }
      return $Parsed;
	}

   public static function Url($Name) {
      $Parsed = self::Parse($Name);
      return $Parsed['Url'];
   }

   /**
    * Returns the url prefix for a given type.
    * If there is a plugin that wants to store uploads at a different location or in a different way then they register themselves by subscribing to the Gdn_Upload_GetUrls_Handler event.
    * After that they will be available here.
    *
    * @param string $Type The type of upload to get the prefix for.
    * @return string The url prefix.
    */
   public static function Urls($Type = NULL) {
      static $Urls = NULL;

      if ($Urls === NULL) {
         $Urls = array('' => Asset('/uploads', TRUE));
         
         $Sender = new stdClass();
         $Sender->Returns = array();
         $Sender->EventArguments = array();
         $Sender->EventArguments['Urls'] =& $Urls;

         Gdn::PluginManager()->CallEventHandlers($Sender, 'Gdn_Upload', 'GetUrls');
      }

      if ($Type === NULL)
         return $Urls;
      if (isset($Urls[$Type]))
         return $Urls[$Type];
      return FALSE;
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

}