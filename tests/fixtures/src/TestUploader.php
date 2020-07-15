<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
namespace VanillaTests\Fixtures;

use Exception;
use Vanilla\UploadedFile;
use Gdn_Upload;

/**
 * Test utilities for uploads.
 */
class TestUploader {

    const TEST_UPLOAD_PATH = PATH_UPLOADS;

    /**
     * Verify the uploads directory exists. Attempt to create it, if not.
     *
     * @throws Exception If the directory can't be created.
     */
    protected static function ensureDirectory() {
        if (!file_exists(self::TEST_UPLOAD_PATH)) {
            $result = mkdir(self::TEST_UPLOAD_PATH, 0777, true);
            if (!$result) {
                throw new Exception('Unable to create uploads directory: '.self::TEST_UPLOAD_PATH);
            }
        }
    }

    /**
     * Generate a valid, random filename for an upload.
     *
     * @return string
     */
    protected static function generateFilename() {
        do {
            $name = randomString(12);
            $path = self::TEST_UPLOAD_PATH."/{$name}";
        } while (file_exists($path));
        return $path;
    }

    /**
     * Clear the uploads directory and reset the $_FILES global..
     */
    public static function resetUploads() {
        if (file_exists(self::TEST_UPLOAD_PATH)) {
            $files = glob(self::TEST_UPLOAD_PATH.'/*.*');
            foreach ($files as $file) {
                if (is_resource($file)) {
                    unlink($file);
                }
            }
        }

        $_FILES = [];
    }

    /**
     * Copy a file into the uploads directory and add its details to the current $_FILES superglobal.
     *
     * @param string $name A field name associated with this file upload.
     * @param string $file Path to the file.
     * @throws Exception If the file does not exist.
     * @throws Exception If the file is not actually a file (i.e. is a directory).
     * @throws Exception If the file is not readable.
     * @return UploadedFile
     */
    public static function uploadFile($name, $file) {
        if (!file_exists($file)) {
            throw new Exception("{$file} does not exist.");
        }
        if (!is_file($file)) {
            throw new Exception("{$file} is not a file.");
        }
        if (!is_readable($file)) {
            throw new Exception("{$file} could not be read.");
        }

        static::ensureDirectory();
        $destination = static::generateFilename();

        if (!copy($file, $destination)) {
            throw new Exception('Unable to copy file to destination: '.$destination);
        }

        $info = [
            'name' => basename($file),
            'type' => mime_content_type($file),
            'size' => filesize($file),
            'tmp_name' => $destination,
            'error' => UPLOAD_ERR_OK
        ];

        $_FILES[$name] = $info;

        $result = new UploadedFile(
            new Gdn_Upload(),
            $info['tmp_name'],
            $info['size'],
            $info['error'],
            $info['name'],
            $info['type']
        );
        return $result;
    }
}
