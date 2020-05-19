<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla;

class FileUtils {

    /**
     * Check if a file was uploaded in the current request.
     *
     * @param $filename
     * @return bool
     */
    function isUploadedFile($filename) {
        $result = is_uploaded_file($filename);
        return $result;
    }

    /**
     * Move an upload to a new location.
     *
     * @param $filename
     * @param $destination
     * @return bool
     */
    function moveUploadedFile($filename, $destination) {
        $result = move_uploaded_file($filename, $destination);
        return $result;
    }

    /**
     * Generate a unique path for an upload.
     *
     * @param string $extension
     * @param bool $chunk
     * @param string $name
     * @param string $targetDirectory
     * @return string
     */
    public static function generateUniqueUploadPath(
        string $extension,
        bool $chunk = true,
        string $name = '',
        string $targetDirectory = PATH_UPLOADS
    ) {
        do {
            $subdir = '';
            if (!$name) {
                $name = randomString(12);
            }
            if ($chunk) {
                $subdir = randomString(12).'/';
            }
            $path = "${targetDirectory}/{$subdir}${name}.${extension}";
        } while (file_exists($path));
        return $path;
    }

    /**
     * Recursively delete a directory.
     *
     * @param string $root
     */
    public static function deleteRecursively(string $root) {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            $deleteFunction = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $deleteFunction($fileinfo->getRealPath());
        }

        // Final directory delete.
        rmdir($root);
    }
}
