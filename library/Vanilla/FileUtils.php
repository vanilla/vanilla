<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla;

use Symfony\Component\Yaml\Yaml;

/**
 * Utility functions for working with file data.
 */
class FileUtils
{
    /**
     * Check if a file was uploaded in the current request.
     *
     * @param string $filename
     * @return bool
     */
    public function isUploadedFile($filename)
    {
        $result = is_uploaded_file($filename);
        return $result;
    }

    /**
     * Move an upload to a new location.
     *
     * @param string $filename
     * @param string $destination
     * @return bool
     */
    public function moveUploadedFile($filename, $destination)
    {
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
        string $name = "",
        string $targetDirectory = PATH_UPLOADS
    ) {
        do {
            $subdir = "";
            if (!$name) {
                $name = randomString(12);
            }
            if ($chunk) {
                $subdir = randomString(12) . "/";
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
    public static function deleteRecursively(string $root)
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            $deleteFunction = $fileinfo->isDir() ? "rmdir" : "unlink";
            $deleteFunction($fileinfo->getRealPath());
        }

        // Final directory delete.
        rmdir($root);
    }

    /**
     * A version of file_put_contents() that is multi-thread safe.
     *
     * @param string $filename Path to the file where to write the data.
     * @param mixed $data The data to write. Can be either a string, an array or a stream resource.
     * @param int $mode The permissions to set on a new file.
     * @return boolean
     * @category Filesystem Functions
     * @see http://php.net/file_put_contents
     */
    public static function putContents($filename, $data, $mode = 0644)
    {
        $temp = @tempnam(dirname($filename), "atomic");

        if (!($fp = @fopen($temp, "wb"))) {
            $temp = dirname($filename) . DIRECTORY_SEPARATOR . uniqid("atomic");
            if (!($fp = @fopen($temp, "wb"))) {
                trigger_error(
                    __CLASS__ . "::" . __FUNCTION__ . "(): error writing temporary file '$temp'",
                    E_USER_WARNING
                );
                return false;
            }
        }

        fwrite($fp, $data);
        fclose($fp);

        if (!@rename($temp, $filename)) {
            $r = @unlink($filename);
            $r &= @rename($temp, $filename);
            if (!$r) {
                trigger_error(__CLASS__ . "::" . __FUNCTION__ . "(): : error writing file '$filename'", E_USER_WARNING);
                return false;
            }
        }
        if (function_exists("apc_delete_file")) {
            // This fixes a bug with some configurations of apc.
            apc_delete_file($filename);
        } elseif (function_exists("opcache_invalidate")) {
            opcache_invalidate($filename);
        }

        @chmod($filename, $mode);
        return true;
    }

    /**
     * Get the contents of a file previously created using putExport.
     *
     * @param string $filename Path to the file where to read the data.
     * @return mixed Returns the data from the file.
     * @see FileUtils::putExport()
     */
    public static function getExport(string $filename)
    {
        $result = require $filename;
        return $result;
    }

    /**
     * Save a value to a file as a var_export.
     *
     * @param string $filename Path to the file where to write the data.
     * @param mixed $value The value to write.
     * @return bool
     */
    public static function putExport(string $filename, $value): bool
    {
        $data = "<?php return " . var_export($value, true) . ";\n";
        return self::putContents($filename, $data);
    }

    /**
     * Load and parse a file into an array based on its file extension.
     *
     * @param string $path The path to the file. The path must exist.
     * @return array Returns the data from the file after parsing.
     */
    public static function getArray(string $path): array
    {
        switch (pathinfo($path, PATHINFO_EXTENSION)) {
            case "json":
                $result = json_decode(file_get_contents($path), true);
                break;
            case "yml":
            case "yaml":
                try {
                    $result = Yaml::parseFile($path);
                } catch (\Throwable $ex) {
                    throw new \Exception("Error parsing $path: " . $ex->getMessage(), 500, $ex);
                }
                break;
            default:
                throw new \InvalidArgumentException("Unrecognized file extension for $path", 500);
        }
        if (!is_array($result)) {
            throw new \Exception("Error parsing $path.", 500);
        }

        return $result;
    }

    /**
     * Get the data from a file or hydrate the file.
     *
     * This is a convenience method for file caching simple PHP structures using `var_export()`. It handles the logic
     * of whether or not to hydrate the file, but the caller otherwise must handle the hydration.
     *
     * @param string $cachePath The path to the cache file.
     * @param callable $hydrate The function used to hydrate the cache.
     * @return mixed Returns the data from the cache or the hydration function.
     */
    public static function getCached(string $cachePath, callable $hydrate)
    {
        if (!file_exists($cachePath)) {
            $data = $hydrate();
            self::putExport($cachePath, $data);
            return $data;
        } else {
            return self::getExport($cachePath);
        }
    }
}
