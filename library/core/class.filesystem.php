<?php
/**
 * Gdn_FileSystem.
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @author Todd Burry <todd@vanillaforums.com>
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Core
 * @since 2.0
 */

if (!defined('VANILLA_FILE_PUT_FLAGS')) {
    define('VANILLA_FILE_PUT_FLAGS', LOCK_EX);
}

/**
 * Framework filesystem layer
 *
 * Abstract many common filesystem tasks such as deleting and creating folders,
 * reading files, and creating blank files.
 */
class Gdn_FileSystem {

    /** Op. */
    const O_CREATE = 1;

    /** Op. */
    const O_WRITE = 2;

    /** Op. */
    const O_READ = 4;

    const TRUSTED_FOLDERS = [
        PATH_UPLOADS,
    ];

    /**
     * Searches the provided file path(s). Returns the first one it finds in the
     * filesystem.
     *
     * @param mixed $files The path (or array of paths) to files which should be checked for
     * existence.
     */
    public static function exists($files) {
        if (!is_array($files)) {
            $files = [$files];
        }

        $return = false;
        $count = count($files);
        for ($i = 0; $i < $count; ++$i) {
            if (file_exists($files[$i])) {
                $return = $files[$i];
                break;
            }
        }

        return $return;
    }

    /**
     * Returns an array of all folder names within the source folder or FALSE
     * if SourceFolder does not exist.
     *
     * @param string $sourceFolder
     */
    public static function folders($sourceFolders) {
        if (!is_array($sourceFolders)) {
            $sourceFolders = [$sourceFolders];
        }

        $blackList = Gdn::config('Garden.FolderBlacklist');
        if (!is_array($blackList)) {
            $blackList = ['.', '..'];
        }

        $result = [];

        foreach ($sourceFolders as $sourceFolder) {
            if ($directoryHandle = opendir($sourceFolder)) {
                while (($item = readdir($directoryHandle)) !== false) {
                    $subFolder = combinePaths([$sourceFolder, $item]);
                    if (!in_array($item, $blackList) && is_dir($subFolder)) {
                        $result[] = $item;
                    }
                }
                closedir($directoryHandle);
            }
        }
        if (count($result) == 0) {
            return false;
        }
        return $result;
    }

    /**
     * Searches in $sourceFolders for $fileName. Returns the path to the file or
     * FALSE if not found.
     *
     * @param mixed $sourceFolders A string (or array of strings) representing the path to the root of the
     * search.
     * @param string $fileName The name of the file to search for.
     * @param mixed $whiteList An option white-list of sub-folders within $sourceFolders in which the
     * search can be performed. If no white-list is provided, the search will
     * only be performed in $SourceFolder.
     */
    public static function find($sourceFolders, $fileName, $whiteList = false) {
        $return = self::_find($sourceFolders, $whiteList, $fileName, true);
        if (is_array($return)) {
            return count($return) > 0 ? $return[0] : false;
        } else {
            return $return;
        }
    }

    /**
     * Searches in $sourceFolders (and $whiteList of subfolders, if present) for
     * $fileName. Returns an array containing the full path of every occurrence
     * of $fileName or FALSE if not found.
     *
     * @param mixed $sourceFolders A string (or array of strings) representing the path to the root of the
     * search.
     * @param string $fileName The name of the file to search for.
     * @param mixed $whiteList An option white-list of sub-folders within $SourceFolder in which the
     * search can be performed. If no white-list is provided, the search will
     * only be performed in $SourceFolder.
     */
    public static function findAll($sourceFolders, $fileName, $whiteList = false) {
        return self::_find($sourceFolders, $whiteList, $fileName, false);
    }

    /**
     * Searches in $sourceFolders (and $whiteList of subfolders, if present) for
     * $fileName. Returns an array containing the full path of every occurrence
     * of $fileName or just the first occurrence of the path if $returnFirst is
     * TRUE. Returns FALSE if the file is not found.
     *
     * @param mixed $sourceFolders A string (or array of strings) representing the path to the root of the
     * search.
     * @param mixed $whiteList An optional white-list array of sub-folders within $sourceFolder in which
     * the search can be performed. If FALSE is specified instead, the search
     * will only be performed in $sourceFolders.
     * @param string $fileName The name of the file to search for.
     * @param boolean $returnFirst Should the method return the path to the first occurrence of $fileName,
     * or should it return an array of every instance in which it is found?
     * Default is to return an array of every instance.
     */
    private static function _find($sourceFolders, $whiteList, $fileName, $returnFirst = false) {
        $return = [];

        if (!is_array($sourceFolders)) {
            $sourceFolders = [$sourceFolders];
        }

        foreach ($sourceFolders as $sourceFolder) {
            if ($whiteList === false) {
                $path = combinePaths([$sourceFolder, $fileName]);
                if (file_exists($path)) {
                    if ($returnFirst) {
                        return $path;
                    } else {
                        $return[] = [$path];
                    }
                }
            } else {
                if ($directoryHandle = opendir($sourceFolder)) {
                    if ($directoryHandle === false) {
                        trigger_error(errorMessage('Failed to open folder when performing a filesystem search.', 'Gdn_FileSystem', '_Find', $sourceFolder), E_USER_ERROR);
                    }

                    // Search all subfolders
                    if ($whiteList === true) {
                        $whiteList = scandir($sourceFolder);
                    }

                    $subFolders = [];
                    foreach ($whiteList as $whiteFolder) {
                        $subFolder = combinePaths([$sourceFolder, $whiteFolder]);
                        if (is_dir($subFolder)) {
                            $subFolders[] = $subFolder;
                            $path = combinePaths([$subFolder, $fileName]);
                            // echo '<div style="color: red;">Looking For: '.$Path.'</div>';
                            if (file_exists($path)) {
                                if ($returnFirst) {
                                    return [$path];
                                } else {
                                    $return[] = $path;
                                }
                            }
                        }
                    }
                }
                closedir($directoryHandle);
            }
        }

        return count($return) > 0 ? $return : false;
    }

    /**
     * Searches in the specified mapping cache for $libraryName. If a mapping is
     * found, it returns it. If not, it searches through the application
     * directories $Depth levels deep looking for $libraryName. Returns FALSE
     * if not found.
     *
     * @param string $MappingsFileName The name of the mappings file to look in for library mappings. These
     * files are contained in the application's /cache folder.
     * @param string $sourceFolders The path to the folders that should be considered the "root" of this search.
     * @param mixed $folderWhiteList A white-list array of sub-folders within $SourceFolder in which the
     * search can be performed. If FALSE is specified instead, the search will
     * only be performed in $sourceFolders.
     * @param string $libraryName The name of the library to search for. This is a valid file name.
     * ie. "class.database.php"
     */
    public static function findByMapping($mappingCacheName, $sourceFolders, $folderWhiteList, $libraryName) {

        // If the application folder was provided, it will be the only entry in the whitelist, so prepend it.
        if (is_array($folderWhiteList) && count($folderWhiteList) == 1) {
            $libraryName = combinePaths([$folderWhiteList[0], $libraryName]);
        }

        $libraryKey = str_replace('.', '__', $libraryName);
        Gdn_LibraryMap::prepareCache($mappingCacheName);
        $libraryPath = Gdn_LibraryMap::getCache($mappingCacheName, $libraryKey);
        if ($libraryPath === null) {
            // $LibraryName wasn't contained in the mappings array.
            // I need to look through the folders in this application for the requested file.
            // Once I find it, I need to save the mapping so we don't have to search for it again.

            // Attempt to find the file directly off the root (if the app folder was provided in the querystring)
            /*if ($FolderWhiteList !== FALSE && count($FolderWhiteList) == 1) {
               $LibraryPath = self::find($SourceFolders, $LibraryName);
            } else {
               $LibraryPath = self::find($SourceFolders, $LibraryName, $FolderWhiteList);
            }*/
            $libraryPath = self::find($sourceFolders, $libraryName, $folderWhiteList);

            // If the mapping was found
            if ($libraryPath !== false) {
                Gdn_LibraryMap::cache($mappingCacheName, $libraryKey, $libraryPath);
            }
        }
        return $libraryPath;
    }

    /**
     * Returns the contents of the specified file, or FALSE if it does not
     * exist.
     */
    public static function getContents() {
        $file = combinePaths(func_get_args());
        if (file_exists($file) && is_file($file)) {
            return file_get_contents($file);
        } else {
            return false;
        }
    }

    /**
     * Saves the specified file with the provided file contents.
     *
     * @param string $fileName The full path and name of the file to be saved.
     * @param string $fileContents The contents of the file being saved.
     */
    public static function saveFile($fileName, $fileContents, $flags = VANILLA_FILE_PUT_FLAGS) {

        // Check that the folder exists and is writable
        $dirName = dirname($fileName);
        $fileBaseName = basename($fileName);
        if (!is_dir($dirName)) {
            throw new Exception(sprintf('Requested save operation [%1$s] could not be completed because target folder [%2$s] does not exist.', $fileBaseName, $dirName));
        }

        if (!isWritable($dirName)) {
            throw new Exception(sprintf('Requested save operation [%1$s] could not be completed because target folder [%2$s] is not writable.', $fileBaseName, $dirName));
        }

        if (file_put_contents($fileName, $fileContents, $flags) === false) {
            throw new Exception(sprintf('Requested save operation [%1$s] could not be completed!', $fileBaseName));
        }

        return true;
    }

    /**
     * Similar to the unix touch command, this method checks to see if $fileName
     * exists. If it does not, it creates the file with nothing inside it.
     *
     * @param string $fileName The full path to the file being touched.
     */
    public static function touch($fileName) {
        if (!file_exists($fileName)) {
            file_put_contents($fileName, '', LOCK_EX);
        }
    }

    /**
     * Serves a file to the browser.
     *
     * @param string $file Full path to the file being served.
     * @param string $name Name to give the file being served. Including extension overrides $file extension. Uses $file filename if empty.
     * @param string $mimeType The mime type of the file.
     * @param string $serveMode Whether to download the file as an attachment, or inline
     */
    public static function serveFile($file, $name = '', $mimeType = '', $serveMode = 'attachment') {
        $fileIsLocal = (substr($file, 0, 4) == 'http') ? false : true;
        if ($fileIsLocal) {
            $fileAvailable = is_readable($file);
            if (is_readable($file)) {
                $fileAvailable = false;
                $filePath = realpath($file);
                foreach (self::TRUSTED_FOLDERS as $trustedFolder) {
                    if (strncmp($filePath, $trustedFolder, strlen($trustedFolder)) === 0) {
                        $fileAvailable = true;
                        break;
                    }
                }
            }
        } else {
            $fileAvailable = true;
        }


        if ($fileAvailable) {
            // Close the database connection
            Gdn::database()->closeConnection();

            // Determine if Path extension should be appended to Name
            $nameExtension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $fileExtension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if ($nameExtension == '') {
                if ($name == '') {
                    $name = pathinfo($file, PATHINFO_FILENAME).'.'.$fileExtension;
                } elseif (!stringEndsWith($name, '.'.$fileExtension)) {
                    $name .= '.'.$fileExtension;
                }
            } else {
                $extension = $nameExtension;
            }
            $name = rawurldecode($name);

            // Figure out the MIME type
            $mimeTypes = [
                "pdf" => "application/pdf",
                "txt" => "text/plain",
                "html" => "text/html",
                "htm" => "text/html",
                "exe" => "application/octet-stream",
                "zip" => "application/zip",
                "doc" => "application/msword",
                "xls" => "application/vnd.ms-excel",
                "ppt" => "application/vnd.ms-powerpoint",
                "gif" => "image/gif",
                "png" => "image/png",
                "jpeg" => "image/jpg",
                "jpg" => "image/jpg",
                "php" => "text/plain",
                "ico" => "image/vnd.microsoft.icon"
            ];

            if ($mimeType == '') {
                if (array_key_exists($fileExtension, $mimeTypes)) {
                    $mimeType = $mimeTypes[$fileExtension];
                } else {
                    $mimeType = 'application/force-download';
                };
            };

            @ob_end_clean();

            // required for IE, otherwise Content-Disposition may be ignored
            if (ini_get('zlib.output_compression')) {
                ini_set('zlib.output_compression', 'Off');
            }

            if ($serveMode == 'inline') {
                safeHeader('Content-Disposition: inline; filename="'.$name.'"');
            } else {
                safeHeader('Content-Disposition: attachment; filename="'.$name.'"');
            }

            safeHeader('Content-Type: '.$mimeType);
            safeHeader("Content-Transfer-Encoding: binary");
            safeHeader('Accept-Ranges: bytes');
            safeHeader("Cache-control: private");
            safeHeader('Pragma: private');
            safeHeader("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
            readfile($file);
            exit();
        } else {
            die('not readable');
        }
    }

    /**
     * Remove a folder (and all the sub-folders and files).
     * Taken from http://php.net/manual/en/function.rmdir.php
     *
     * @param string $Dir
     * @return void
     */
    public static function removeFolder($path) {
        if (!file_exists($path)) {
            return;
        }

        if (is_file($path)) {
            unlink($path);
            return;
        }

        $path = rtrim($path, '/').'/';

        // Get all of the files in the directory.
        if ($dh = opendir($path)) {
            while (($file = readdir($dh)) !== false) {
                if (trim($file, '.') == '') {
                    continue;
                }

                $subPath = $path.$file;

                if (is_dir($subPath)) {
                    self::removeFolder($subPath);
                } else {
                    unlink($subPath);
                }
            }
            closedir($dh);
        }
        rmdir($path);
    }

    public static function checkFolderR($path, $flags = 0) {
        $trimPath = ltrim($path, '/');
        $pathParts = explode('/', $trimPath);
        $prepend = (strlen($path) !== strlen($trimPath)) ? DS : '';

        $currentPath = [];
        foreach ($pathParts as $folderPart) {
            array_push($currentPath, $folderPart);
            $testFolder = $prepend.implode(DS, $currentPath);

            if ($flags & Gdn_FileSystem::O_CREATE) {
                if (!is_dir($testFolder)) {
                    @mkdir($testFolder);
                }
            }

            if (!is_dir($testFolder)) {
                return false;
            }

        }

        if ($flags & Gdn_FileSystem::O_READ) {
            if (!is_readable($path)) {
                return false;
            }
        }

        if ($flags & Gdn_FileSystem::O_WRITE) {
            if (!is_writable($path)) {
                return false;
            }
        }
        return true;
    }
}
