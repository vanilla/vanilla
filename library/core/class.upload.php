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

    /** @var \Vanilla\FileUtils */
    protected $fileUtils;

    /**
     * Class constructor.
     */
    public function __construct() {
        $this->clear();
        parent::__construct();
        $this->ClassName = 'Gdn_Upload';

        $this->fileUtils = Gdn::getContainer()->get(\Vanilla\FileUtils::class);
    }

    /**
     * Adds an extension (or array of extensions) to the array of allowed file extensions.
     *
     * @param mixed The name (or array of names) of the extension to allow.
     */
    public function allowFileExtension($extension) {
        if ($extension === null) {
            $this->_AllowedFileExtensions = [];
        } elseif (is_array($extension))
            $this->_AllowedFileExtensions = array_merge($this->_AllowedFileExtensions, $extension);
        else {
            $this->_AllowedFileExtensions[] = $extension;
        }
    }

    /**
     *
     *
     * @param null $uploadPath
     * @return bool
     */
    public static function canUpload($uploadPath = null) {
        if (is_null($uploadPath)) {
            $uploadPath = PATH_UPLOADS;
        }

        if (ini_get('file_uploads') != 1) {
            return false;
        }

        if (!is_dir($uploadPath)) {
            @mkdir($uploadPath);
        }
        if (!is_dir($uploadPath)) {
            return false;
        }

        if (!isWritable($uploadPath) || !is_readable($uploadPath)) {
            return false;
        }

        return true;
    }

    /**
     *
     */
    public function clear() {
        $this->_MaxFileSize = self::unformatFileSize(Gdn::config('Garden.Upload.MaxFileSize', ''));
        $this->_AllowedFileExtensions = Gdn::config('Garden.Upload.AllowedFileExtensions', []);
        $this->_UploadedFile = null;
    }

    /**
     * Copy an upload locally so that it can be operated on.
     *
     * @param string $name Filename to be copied.
     * @return string Local file path.
     */
    public function copyLocal($name) {
        $parsed = self::parse($name);

        $localPath = '';
        $this->EventArguments['Parsed'] = $parsed;
        $this->EventArguments['Path'] =& $localPath;

        $this->fireAs('Gdn_Upload')->fireEvent('CopyLocal');
        if (!$localPath) {
            $localPath = PATH_UPLOADS.'/'.$parsed['Name'];
        }
        return $localPath;
    }

    /**
     * Delete an uploaded file.
     *
     * @param string $name The name of the upload as saved in the database.
     * @return bool
     */
    public function delete($name) {
        $parsed = $this->parse($name);

        // Throw an event so that plugins that have stored the file somewhere else can delete it.
        $this->EventArguments['Parsed'] =& $parsed;
        $handled = false;
        $this->EventArguments['Handled'] =& $handled;
        $this->fireAs('Gdn_Upload')->fireEvent('Delete');

        if (!$handled) {
            $path = PATH_UPLOADS.'/'.ltrim($name, '/');
            if ($path === realpath($path) && file_exists($path)) {
                return safeUnlink($path);
            }
        }
        return true;
    }

    /**
     * Format a number of bytes with the largest unit.
     *
     * @param int $bytes The number of bytes.
     * @param int $precision The number of decimal places in the formatted number.
     * @return string the formatted filesize.
     */
    public static function formatFileSize($bytes, $precision = 1) {
        $units = ['B', 'K', 'M', 'G', 'T'];

        $bytes = max((int)$bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        $result = round($bytes, $precision).$units[$pow];
        return $result;
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
     * @param string $name The virtual name of the file.
     * @return array|bool Returns an array of parsed information or false if the parse failed.
     */
    public static function parse($name) {
        $result = false;
        $name = str_replace('\\', '/', $name);
        $pathUploads = str_replace('\\', '/', PATH_UPLOADS);

        if (preg_match('`^https?://`', $name)) {
            $result = ['Name' => $name, 'Type' => 'external', 'SaveName' => $name, 'SaveFormat' => '%s', 'Url' => $name,];
            return $result;
        } elseif (stringBeginsWith($name, $pathUploads)) {
            $name = ltrim(substr($name, strlen($pathUploads)), '/');
            // This is an upload.
            $result = ['Name' => $name, 'Type' => '', 'SaveName' => $name, 'SaveFormat' => '%s'];
        } elseif (preg_match('`^~([^/]*)/(.*)$`', $name, $matches)) {
            // The first part of the name tells us the type.
            $type = $matches[1];
            $name = $matches[2];

            $result = ['Name' => $name, 'Type' => $type, 'SaveName' => "~$type/$name", 'SaveFormat' => "~$type/%s"];
        } else {
            $parts = parse_url($name);
            if (empty($parts['scheme'])) {
                $name = ltrim($name, '/');
                // This is an upload in the uploads folder.
                $result = ['Name' => $name, 'Type' => '', 'SaveName' => $name, 'SaveFormat' => '%s'];
            } else {
                // This is a url in the format type:://domain/path.
                $result = [
                    'Name' => ltrim(val('path', $parts), '/'),
                    'Type' => $parts['scheme'],
                    'Domain' => val('host', $parts)
                ];

                $saveFormat = "{$result['Type']}://{$result['Domain']}/%s";
                $result['SaveName'] = sprintf($saveFormat, $result['Name']);
                $result['SaveFormat'] = $saveFormat;
            }
        }

        if (!empty($result['Domain'])) {
            $urlPrefix = self::urls("{$result['Type']}://{$result['Domain']}");
        } else {
            $urlPrefix = self::urls($result['Type']);
        }
        if ($urlPrefix === false) {
            $result['Url'] = false;
        } else {
            $result['Url'] = $urlPrefix.'/'.$result['Name'];
        }

        return $result;
    }

    /**
     * Take a string formatted filesize and return the number of bytes.
     *
     * @param string $formatted The formatted filesize.
     * @return int The number of bytes in the string.
     */
    public static function unformatFileSize($formatted) {
        $units = ['B' => 1, 'K' => 1024, 'M' => 1024 * 1024, 'G' => 1024 * 1024 * 1024, 'T' => 1024 * 1024 * 1024 * 1024];

        if (preg_match('/([0-9.]+)\s*([A-Z]*)/i', $formatted, $matches)) {
            $number = floatval($matches[1]);
            $unit = strtoupper(substr($matches[2], 0, 1));
            $mult = val($unit, $units, 1);

            $result = round($number * $mult, 0);
            return $result;
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
        $name = $this->_UploadedFile['name'];
        $info = pathinfo($name);
        return val('extension', $info, '');
    }

    /**
     *
     *
     * @param $targetFolder
     * @param string $extension
     * @param bool $chunk
     * @return string
     */
    public function generateTargetName($targetFolder, $extension = 'jpg', $chunk = false) {
        if (!$extension) {
            $extension = trim(pathinfo($this->_UploadedFile['name'], PATHINFO_EXTENSION), '.');
        }

        do {
            if ($chunk) {
                $name = randomString(12);
                $subdir = sprintf('%03d', mt_rand(0, 999)).'/';
            } else {
                $name = randomString(12);
                $subdir = '';
            }
            $path = "$targetFolder/{$subdir}$name.$extension";
        } while (file_exists($path));
        return $path;
    }

    /**
     * Determine if a URI matches the format of a valid type/domain upload.
     *
     * @param string $uri The URI to test. This would be the value saved in the database (ex. GDN_User.Photo).
     * @return bool Returns **true** if {@link uri} looks like an uploaded file or **false** otherwise.
     */
    public static function isUploadUri($uri) {
        $parsed = Gdn_Upload::parse($uri);

        return !empty($parsed['Url']) && val('Type', $parsed) !== 'external';
    }

    /**
     *
     *
     * @param $source
     * @param $target
     * @param array $options
     * @return array|bool
     * @throws Exception
     */
    public function saveAs($source, $target, $options = [], $copy = false) {
        $this->EventArguments['Path'] = $source;
        $parsed = self::parse($target);
        $this->EventArguments['Parsed'] =& $parsed;
        $this->EventArguments['Options'] = $options;
        $this->EventArguments['OriginalFilename'] = val('OriginalFilename', $options);
        $handled = false;
        $this->EventArguments['Handled'] =& $handled;
        $this->fireAs('Gdn_Upload')->fireEvent('SaveAs');

        // Check to see if the event handled the save.
        if (!$handled) {
            $target = PATH_UPLOADS.'/'.$parsed['Name'];
            if (!file_exists(dirname($target))) {
                mkdir(dirname($target), 0777, true);
            }

            if (stringBeginsWith($source, PATH_UPLOADS)) {
                rename($source, $target);
            } else {
                $isUpload = $this->fileUtils->isUploadedFile($source);
                $result = ($copy && $isUpload) ? copy($source, $target) : $this->fileUtils->moveUploadedFile($source, $target);
                if (!$result) {
                    throw new Exception(sprintf(t('Failed to save uploaded file to target destination (%s).'), $target));
                }
            }
        }

        return $parsed;
    }

    /**
     *
     *
     * @param $name
     * @return mixed
     */
    public static function url($name) {
        $parsed = self::parse($name);
        return $parsed['Url'];
    }

    /**
     * Returns the url prefix for a given type.
     *
     * If there is a plugin that wants to store uploads at a different location or in a different way then they register
     * themselves by subscribing to the Gdn_Upload_GetUrls_Handler event. After that they will be available here.
     *
     * @param string $type The type of upload to get the prefix for.
     * @return string The url prefix.
     */
    public static function urls($type = null) {
        static $urls = null;

        if ($urls === null) {
            $urls = [
                '' => asset('/uploads', true),
                'static://v' => rtrim(asset('/', true), '/')
            ];

            $sender = new stdClass();
            $sender->Returns = [];
            $sender->EventArguments = [];
            $sender->EventArguments['Urls'] =& $urls;

            Gdn::pluginManager()->callEventHandlers($sender, 'Gdn_Upload', 'GetUrls');
        }

        if ($type === null) {
            return $urls;
        }
        if (isset($urls[$type])) {
            return $urls[$type];
        }
        return false;
    }

    /**
     * Check to see whether the user has selected a file for uploading.
     * 
     * @param $inputName The input name of the file.
     * @return bool Whether a file has been selected for the fiels.
     */
    public function isUpload($inputName) {
        return val('name', val($inputName, $_FILES, '')) !== '';
    }

    /**
     * Validates the uploaded file. Returns the temporary name of the uploaded file.
     */
    public function validateUpload($inputName, $throwException = true) {
        $ex = false;

        if (!array_key_exists($inputName, $_FILES) || (!$this->fileUtils->isUploadedFile($_FILES[$inputName]['tmp_name']) && getValue('error', $_FILES[$inputName], 0) == 0)) {
            // Check the content length to see if we exceeded the max post size.
            $contentLength = Gdn::request()->getValueFrom('server', 'CONTENT_LENGTH');
            $maxPostSize = self::unformatFileSize(ini_get('post_max_size'));
            if ($contentLength > $maxPostSize) {
                $ex = sprintf(t('Gdn_Upload.Error.MaxPostSize', 'The file is larger than the maximum post size. (%s)'), self::formatFileSize($maxPostSize));
            } else {
                $ex = t('The file failed to upload.');
            }
        } else {
            switch ($_FILES[$inputName]['error']) {
                case 1:
                case 2:
                    $maxFileSize = self::unformatFileSize(ini_get('upload_max_filesize'));
                    $ex = sprintf(t('Gdn_Upload.Error.PhpMaxFileSize', 'The file is larger than the server\'s maximum file size. (%s)'), self::formatFileSize($maxFileSize));
                    break;
                case 3:
                case 4:
                    $ex = t('The file failed to upload.');
                    break;
                case 6:
                    $ex = t('The temporary upload folder has not been configured.');
                    break;
                case 7:
                    $ex = t('Failed to write the file to disk.');
                    break;
                case 8:
                    $ex = t('The upload was stopped by extension.');
                    break;
            }
        }

        $foo = self::formatFileSize($this->_MaxFileSize);

        // Check the maxfilesize again just in case the value was spoofed in the form.
        if (!$ex && $this->_MaxFileSize > 0 && filesize($_FILES[$inputName]['tmp_name']) > $this->_MaxFileSize) {
            $ex = sprintf(t('Gdn_Upload.Error.MaxFileSize', 'The file is larger than the maximum file size. (%s)'), self::formatFileSize($this->_MaxFileSize));
        } elseif (!$ex) {
            // Make sure that the file extension is allowed.
            $extension = pathinfo($_FILES[$inputName]['name'], PATHINFO_EXTENSION);
            if (!inArrayI($extension, $this->_AllowedFileExtensions)) {
                $ex = sprintf(t('You cannot upload files with this extension (%s). Allowed extension(s) are %s.'), htmlspecialchars($extension), implode(', ', $this->_AllowedFileExtensions));
            }
        }

        if ($ex) {
            if ($throwException) {
                throw new Gdn_UserException($ex);
            } else {
                $this->Exception = $ex;
                return false;
            }
        } else {
            // If all validations were successful, return the tmp name/location of the file.
            $this->_UploadedFile = $_FILES[$inputName];
            return $this->_UploadedFile['tmp_name'];
        }
    }
}
