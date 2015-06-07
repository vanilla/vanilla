<?php
/**
 * Gdn_Upload
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
 */

/**
 * Handles file uploads.
 */
class Gdn_Upload extends Gdn_Pluggable {

    /** @var array */
    protected $_AllowedFileExtensions;

    /** @var int */
    protected $_MaxFileSize;

    /** @var string */
    protected $_UploadedFile;

    /**
     * Class constructor.
     */
    public function __construct() {
        $this->clear();
        parent::__construct();
        $this->ClassName = 'Gdn_Upload';
    }

    /**
     * Adds an extension (or array of extensions) to the array of allowed file extensions.
     *
     * @param mixed The name (or array of names) of the extension to allow.
     */
    public function allowFileExtension($Extension) {
        if ($Extension === null) {
            $this->_AllowedFileExtensions = array();
        } elseif (is_array($Extension))
            $this->_AllowedFileExtensions = array_merge($this->_AllowedFileExtensions, $Extension);
        else {
            $this->_AllowedFileExtensions[] = $Extension;
        }
    }

    /**
     *
     *
     * @param null $UploadPath
     * @return bool
     */
    public static function canUpload($UploadPath = null) {
        if (is_null($UploadPath)) {
            $UploadPath = PATH_UPLOADS;
        }

        if (ini_get('file_uploads') != 1) {
            return false;
        }

        if (!is_dir($UploadPath)) {
            @mkdir($UploadPath);
        }
        if (!is_dir($UploadPath)) {
            return false;
        }

        if (!isWritable($UploadPath) || !is_readable($UploadPath)) {
            return false;
        }

        return true;
    }

    /**
     *
     */
    public function clear() {
        $this->_MaxFileSize = self::unformatFileSize(Gdn::config('Garden.Upload.MaxFileSize', ''));
        $this->_AllowedFileExtensions = Gdn::config('Garden.Upload.AllowedFileExtensions', array());
    }

    /**
     * Copy an upload locally so that it can be operated on.
     *
     * @param string $Name
     */
    public function copyLocal($Name) {
        $Parsed = self::parse($Name);

        $LocalPath = '';
        $this->EventArguments['Parsed'] = $Parsed;
        $this->EventArguments['Path'] =& $LocalPath;

        $this->fireAs('Gdn_Upload')->fireEvent('CopyLocal');
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
    public function delete($Name) {
        $Parsed = $this->parse($Name);

        // Throw an event so that plugins that have stored the file somewhere else can delete it.
        $this->EventArguments['Parsed'] =& $Parsed;
        $Handled = false;
        $this->EventArguments['Handled'] =& $Handled;
        $this->fireAs('Gdn_Upload')->fireEvent('Delete');

        if (!$Handled) {
            $Path = PATH_UPLOADS.'/'.ltrim($Name, '/');
            @unlink($Path);
        }
    }

    /**
     * Format a number of bytes with the largest unit.
     *
     * @param int $Bytes The number of bytes.
     * @param int $Precision The number of decimal places in the formatted number.
     * @return string the formatted filesize.
     */
    public static function formatFileSize($Bytes, $Precision = 1) {
        $Units = array('B', 'K', 'M', 'G', 'T');

        $Bytes = max((int)$Bytes, 0);
        $Pow = floor(($Bytes ? log($Bytes) : 0) / log(1024));
        $Pow = min($Pow, count($Units) - 1);

        $Bytes /= pow(1024, $Pow);

        $Result = round($Bytes, $Precision).$Units[$Pow];
        return $Result;
    }

    /**
     * Parse a virtual filename that had previously been saved to the database.
     *
     * There are various formats supported for the name, mostly due to legacy concerns.
     *
     * - http(s)://domain/path.ext: A fully qualified url.
     * - /path/from/uploads.ext: This is a locally uploaded file.
     * - /path/to/uploads/path.ext: A full path starting from the uploads directory (deprecated).
     * - ~type/path.ext: A specific type of upload provided by a plugin (deprecated).
     * - type://domain/path.ext: A specific type of upload provied by a plugin with additional domain information.
     *
     * @param string $Name The virtual name of the file.
     * @return array|bool Returns an array of parsed information or false if the parse failed.
     */
    public static function parse($Name) {
        $Result = false;
        $Name = str_replace('\\', '/', $Name);
        $PathUploads = str_replace('\\', '/', PATH_UPLOADS);

        if (preg_match('`^https?://`', $Name)) {
            $Result = array('Name' => $Name, 'Type' => 'external', 'SaveName' => $Name, 'SaveFormat' => '%s', 'Url' => $Name,);
            return $Result;
        } elseif (stringBeginsWith($Name, $PathUploads)) {
            $Name = ltrim(substr($Name, strlen($PathUploads)), '/');
            // This is an upload.
            $Result = array('Name' => $Name, 'Type' => '', 'SaveName' => $Name, 'SaveFormat' => '%s');
        } elseif (preg_match('`^~([^/]*)/(.*)$`', $Name, $Matches)) {
            // The first part of the name tells us the type.
            $Type = $Matches[1];
            $Name = $Matches[2];

            $Result = array('Name' => $Name, 'Type' => $Type, 'SaveName' => "~$Type/$Name", 'SaveFormat' => "~$Type/%s");
        } else {
            $Parts = parse_url($Name);
            if (empty($Parts['scheme'])) {
                $Name = ltrim($Name, '/');
                // This is an upload in the uploads folder.
                $Result = array('Name' => $Name, 'Type' => '', 'SaveName' => $Name, 'SaveFormat' => '%s');
            } else {
                // This is a url in the format type:://domain/path.
                $Result = array(
                    'Name' => ltrim(val('path', $Parts), '/'),
                    'Type' => $Parts['scheme'],
                    'Domain' => val('host', $Parts)
                );

                $SaveFormat = "{$Result['Type']}://{$Result['Domain']}/%s";
                $Result['SaveName'] = sprintf($SaveFormat, $Result['Name']);
                $Result['SaveFormat'] = $SaveFormat;
            }
        }

        if (!empty($Result['Domain'])) {
            $UrlPrefix = self::urls("{$Result['Type']}://{$Result['Domain']}");
        } else {
            $UrlPrefix = self::urls($Result['Type']);
        }
        if ($UrlPrefix === false) {
            $Result['Url'] = false;
        } else {
            $Result['Url'] = $UrlPrefix.'/'.$Result['Name'];
        }

        return $Result;
    }

    /**
     * Take a string formatted filesize and return the number of bytes.
     *
     * @param string $Formatted The formatted filesize.
     * @return int The number of bytes in the string.
     */
    public static function unformatFileSize($Formatted) {
        $Units = array('B' => 1, 'K' => 1024, 'M' => 1024 * 1024, 'G' => 1024 * 1024 * 1024, 'T' => 1024 * 1024 * 1024 * 1024);

        if (preg_match('/([0-9.]+)\s*([A-Z]*)/i', $Formatted, $Matches)) {
            $Number = floatval($Matches[1]);
            $Unit = strtoupper(substr($Matches[2], 0, 1));
            $Mult = val($Unit, $Units, 1);

            $Result = round($Number * $Mult, 0);
            return $Result;
        } else {
            return false;
        }
    }

    /**
     *
     *
     * @return mixed
     */
    public function getUploadedFileName() {
        return val('name', $this->_UploadedFile);
    }

    /**
     *
     *
     * @return mixed
     */
    public function getUploadedFileExtension() {
        $Name = $this->_UploadedFile['name'];
        $Info = pathinfo($Name);
        return val('extension', $Info, '');
    }

    /**
     *
     *
     * @param $TargetFolder
     * @param string $Extension
     * @param bool $Chunk
     * @return string
     */
    public function generateTargetName($TargetFolder, $Extension = 'jpg', $Chunk = false) {
        if (!$Extension) {
            $Extension = trim(pathinfo($this->_UploadedFile['name'], PATHINFO_EXTENSION), '.');
        }

        do {
            if ($Chunk) {
                $Name = randomString(12);
                $Subdir = sprintf('%03d', mt_rand(0, 999)).'/';
            } else {
                $Name = randomString(12);
                $Subdir = '';
            }
            $Path = "$TargetFolder/{$Subdir}$Name.$Extension";
        } while (file_exists($Path));
        return $Path;
    }

    /**
     *
     *
     * @param $Source
     * @param $Target
     * @param array $Options
     * @return array|bool
     * @throws Exception
     */
    public function saveAs($Source, $Target, $Options = array()) {
        $this->EventArguments['Path'] = $Source;
        $Parsed = self::parse($Target);
        $this->EventArguments['Parsed'] =& $Parsed;
        $this->EventArguments['Options'] = $Options;
        $Handled = false;
        $this->EventArguments['Handled'] =& $Handled;
        $this->fireAs('Gdn_Upload')->fireEvent('SaveAs');

        // Check to see if the event handled the save.
        if (!$Handled) {
            $Target = PATH_UPLOADS.'/'.$Parsed['Name'];
            if (!file_exists(dirname($Target))) {
                mkdir(dirname($Target));
            }

            if (stringBeginsWith($Source, PATH_UPLOADS)) {
                rename($Source, $Target);
            } elseif (!move_uploaded_file($Source, $Target))
                throw new Exception(sprintf(t('Failed to move uploaded file to target destination (%s).'), $Target));
        }
        return $Parsed;
    }

    /**
     *
     *
     * @param $Name
     * @return mixed
     */
    public static function url($Name) {
        $Parsed = self::parse($Name);
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
    public static function urls($Type = null) {
        static $Urls = null;

        if ($Urls === null) {
            $Urls = array('' => asset('/uploads', true));

            $Sender = new stdClass();
            $Sender->Returns = array();
            $Sender->EventArguments = array();
            $Sender->EventArguments['Urls'] =& $Urls;

            Gdn::pluginManager()->callEventHandlers($Sender, 'Gdn_Upload', 'GetUrls');
        }

        if ($Type === null) {
            return $Urls;
        }
        if (isset($Urls[$Type])) {
            return $Urls[$Type];
        }
        return false;
    }

    /**
     * Validates the uploaded file. Returns the temporary name of the uploaded file.
     */
    public function validateUpload($InputName, $ThrowException = true) {
        $Ex = false;

        if (!array_key_exists($InputName, $_FILES) || (!is_uploaded_file($_FILES[$InputName]['tmp_name']) && GetValue('error', $_FILES[$InputName], 0) == 0)) {
            // Check the content length to see if we exceeded the max post size.
            $ContentLength = Gdn::request()->getValueFrom('server', 'CONTENT_LENGTH');
            $MaxPostSize = self::unformatFileSize(ini_get('post_max_size'));
            if ($ContentLength > $MaxPostSize) {
                $Ex = sprintf(t('Gdn_Upload.Error.MaxPostSize', 'The file is larger than the maximum post size. (%s)'), self::formatFileSize($MaxPostSize));
            } else {
                $Ex = t('The file failed to upload.');
            }
        } else {
            switch ($_FILES[$InputName]['error']) {
                case 1:
                case 2:
                    $MaxFileSize = self::unformatFileSize(ini_get('upload_max_filesize'));
                    $Ex = sprintf(T('Gdn_Upload.Error.PhpMaxFileSize', 'The file is larger than the server\'s maximum file size. (%s)'), self::formatFileSize($MaxFileSize));
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

        $Foo = self::formatFileSize($this->_MaxFileSize);

        // Check the maxfilesize again just in case the value was spoofed in the form.
        if (!$Ex && $this->_MaxFileSize > 0 && filesize($_FILES[$InputName]['tmp_name']) > $this->_MaxFileSize) {
            $Ex = sprintf(T('Gdn_Upload.Error.MaxFileSize', 'The file is larger than the maximum file size. (%s)'), self::formatFileSize($this->_MaxFileSize));
        } elseif (!$Ex) {
            // Make sure that the file extension is allowed.
            $Extension = pathinfo($_FILES[$InputName]['name'], PATHINFO_EXTENSION);
            if (!InArrayI($Extension, $this->_AllowedFileExtensions)) {
                $Ex = sprintf(T('You cannot upload files with this extension (%s). Allowed extension(s) are %s.'), htmlspecialchars($Extension), implode(', ', $this->_AllowedFileExtensions));
            }
        }

        if ($Ex) {
            if ($ThrowException) {
                throw new Gdn_UserException($Ex);
            } else {
                $this->Exception = $Ex;
                return false;
            }
        } else {
            // If all validations were successful, return the tmp name/location of the file.
            $this->_UploadedFile = $_FILES[$InputName];
            return $this->_UploadedFile['tmp_name'];
        }
    }
}
