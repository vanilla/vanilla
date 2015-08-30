<?php
/**
 * Gdn_FileSystem.
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @author Todd Burry <todd@vanillaforums.com>
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
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

    /**
     * Searches the provided file path(s). Returns the first one it finds in the
     * filesystem.
     *
     * @param mixed $Files The path (or array of paths) to files which should be checked for
     * existence.
     */
    public static function exists($Files) {
        if (!is_array($Files)) {
            $Files = array($Files);
        }

        $Return = false;
        $Count = count($Files);
        for ($i = 0; $i < $Count; ++$i) {
            if (file_exists($Files[$i])) {
                $Return = $Files[$i];
                break;
            }
        }

        return $Return;
    }

    /**
     * Returns an array of all folder names within the source folder or FALSE
     * if SourceFolder does not exist.
     *
     * @param string $SourceFolder
     */
    public static function folders($SourceFolders) {
        if (!is_array($SourceFolders)) {
            $SourceFolders = array($SourceFolders);
        }

        $BlackList = Gdn::config('Garden.FolderBlacklist');
        if (!is_array($BlackList)) {
            $BlackList = array('.', '..');
        }

        $Result = array();

        foreach ($SourceFolders as $SourceFolder) {
            if ($DirectoryHandle = opendir($SourceFolder)) {
                while (($Item = readdir($DirectoryHandle)) !== false) {
                    $SubFolder = combinePaths(array($SourceFolder, $Item));
                    if (!in_array($Item, $BlackList) && is_dir($SubFolder)) {
                        $Result[] = $Item;
                    }
                }
                closedir($DirectoryHandle);
            }
        }
        if (count($Result) == 0) {
            return false;
        }
        return $Result;
    }

    /**
     * Searches in $SourceFolders for $FileName. Returns the path to the file or
     * FALSE if not found.
     *
     * @param mixed $SourceFolders A string (or array of strings) representing the path to the root of the
     * search.
     * @param string $FileName The name of the file to search for.
     * @param mixed $WhiteList An option white-list of sub-folders within $SourceFolders in which the
     * search can be performed. If no white-list is provided, the search will
     * only be performed in $SourceFolder.
     */
    public static function find($SourceFolders, $FileName, $WhiteList = false) {
        $Return = self::_find($SourceFolders, $WhiteList, $FileName, true);
        if (is_array($Return)) {
            return count($Return) > 0 ? $Return[0] : false;
        } else {
            return $Return;
        }
    }

    /**
     * Searches in $SourceFolders (and $WhiteList of subfolders, if present) for
     * $FileName. Returns an array containing the full path of every occurrence
     * of $FileName or FALSE if not found.
     *
     * @param mixed $SourceFolders A string (or array of strings) representing the path to the root of the
     * search.
     * @param string $FileName The name of the file to search for.
     * @param mixed $WhiteList An option white-list of sub-folders within $SourceFolder in which the
     * search can be performed. If no white-list is provided, the search will
     * only be performed in $SourceFolder.
     */
    public static function findAll($SourceFolders, $FileName, $WhiteList = false) {
        return self::_find($SourceFolders, $WhiteList, $FileName, false);
    }

    /**
     * Searches in $SourceFolders (and $WhiteList of subfolders, if present) for
     * $FileName. Returns an array containing the full path of every occurrence
     * of $FileName or just the first occurrence of the path if $ReturnFirst is
     * TRUE. Returns FALSE if the file is not found.
     *
     * @param mixed $SourceFolders A string (or array of strings) representing the path to the root of the
     * search.
     * @param mixed $WhiteList An optional white-list array of sub-folders within $SourceFolder in which
     * the search can be performed. If FALSE is specified instead, the search
     * will only be performed in $SourceFolders.
     * @param string $FileName The name of the file to search for.
     * @param boolean $ReturnFirst Should the method return the path to the first occurrence of $FileName,
     * or should it return an array of every instance in which it is found?
     * Default is to return an array of every instance.
     */
    private static function _find($SourceFolders, $WhiteList, $FileName, $ReturnFirst = false) {
        $Return = array();

        if (!is_array($SourceFolders)) {
            $SourceFolders = array($SourceFolders);
        }

        foreach ($SourceFolders as $SourceFolder) {
            if ($WhiteList === false) {
                $Path = CombinePaths(array($SourceFolder, $FileName));
                if (file_exists($Path)) {
                    if ($ReturnFirst) {
                        return $Path;
                    } else {
                        $Return[] = array($Path);
                    }
                }
            } else {
                if ($DirectoryHandle = opendir($SourceFolder)) {
                    if ($DirectoryHandle === false) {
                        trigger_error(ErrorMessage('Failed to open folder when performing a filesystem search.', 'Gdn_FileSystem', '_Find', $SourceFolder), E_USER_ERROR);
                    }

                    // Search all subfolders
                    if ($WhiteList === true) {
                        $WhiteList = scandir($SourceFolder);
                    }

                    $SubFolders = array();
                    foreach ($WhiteList as $WhiteFolder) {
                        $SubFolder = combinePaths(array($SourceFolder, $WhiteFolder));
                        if (is_dir($SubFolder)) {
                            $SubFolders[] = $SubFolder;
                            $Path = combinePaths(array($SubFolder, $FileName));
                            // echo '<div style="color: red;">Looking For: '.$Path.'</div>';
                            if (file_exists($Path)) {
                                if ($ReturnFirst) {
                                    return array($Path);
                                } else {
                                    $Return[] = $Path;
                                }
                            }
                        }
                    }
                }
                closedir($DirectoryHandle);
            }
        }

        return count($Return) > 0 ? $Return : false;
    }

    /**
     * Searches in the specified mapping cache for $LibraryName. If a mapping is
     * found, it returns it. If not, it searches through the application
     * directories $Depth levels deep looking for $LibraryName. Returns FALSE
     * if not found.
     *
     * @param string $MappingsFileName The name of the mappings file to look in for library mappings. These
     * files are contained in the application's /cache folder.
     * @param string $SourceFolders The path to the folders that should be considered the "root" of this search.
     * @param mixed $FolderWhiteList A white-list array of sub-folders within $SourceFolder in which the
     * search can be performed. If FALSE is specified instead, the search will
     * only be performed in $SourceFolders.
     * @param string $LibraryName The name of the library to search for. This is a valid file name.
     * ie. "class.database.php"
     */
    public static function findByMapping($MappingCacheName, $SourceFolders, $FolderWhiteList, $LibraryName) {

        // If the application folder was provided, it will be the only entry in the whitelist, so prepend it.
        if (is_array($FolderWhiteList) && count($FolderWhiteList) == 1) {
            $LibraryName = combinePaths(array($FolderWhiteList[0], $LibraryName));
        }

        $LibraryKey = str_replace('.', '__', $LibraryName);
        Gdn_LibraryMap::prepareCache($MappingCacheName);
        $LibraryPath = Gdn_LibraryMap::getCache($MappingCacheName, $LibraryKey);
        if ($LibraryPath === null) {
            // $LibraryName wasn't contained in the mappings array.
            // I need to look through the folders in this application for the requested file.
            // Once I find it, I need to save the mapping so we don't have to search for it again.

            // Attempt to find the file directly off the root (if the app folder was provided in the querystring)
            /*if ($FolderWhiteList !== FALSE && count($FolderWhiteList) == 1) {
               $LibraryPath = self::Find($SourceFolders, $LibraryName);
            } else {
               $LibraryPath = self::Find($SourceFolders, $LibraryName, $FolderWhiteList);
            }*/
            $LibraryPath = self::find($SourceFolders, $LibraryName, $FolderWhiteList);

            // If the mapping was found
            if ($LibraryPath !== false) {
                Gdn_LibraryMap::cache($MappingCacheName, $LibraryKey, $LibraryPath);
            }
        }
        return $LibraryPath;
    }

    /**
     * Returns the contents of the specified file, or FALSE if it does not
     * exist.
     */
    public static function getContents() {
        $File = combinePaths(func_get_args());
        if (file_exists($File) && is_file($File)) {
            return file_get_contents($File);
        } else {
            return false;
        }
    }

    /**
     * Saves the specified file with the provided file contents.
     *
     * @param string $FileName The full path and name of the file to be saved.
     * @param string $FileContents The contents of the file being saved.
     */
    public static function saveFile($FileName, $FileContents, $Flags = VANILLA_FILE_PUT_FLAGS) {

        // Check that the folder exists and is writable
        $DirName = dirname($FileName);
        $FileBaseName = basename($FileName);
        if (!is_dir($DirName)) {
            throw new Exception(sprintf('Requested save operation [%1$s] could not be completed because target folder [%2$s] does not exist.', $FileBaseName, $DirName));
        }

        if (!IsWritable($DirName)) {
            throw new Exception(sprintf('Requested save operation [%1$s] could not be completed because target folder [%2$s] is not writable.', $FileBaseName, $DirName));
        }

        if (file_put_contents($FileName, $FileContents, $Flags) === false) {
            throw new Exception(sprintf('Requested save operation [%1$s] could not be completed!', $FileBaseName));
        }

        return true;
    }

    /**
     * Similar to the unix touch command, this method checks to see if $FileName
     * exists. If it does not, it creates the file with nothing inside it.
     *
     * @param string $FileName The full path to the file being touched.
     */
    public static function touch($FileName) {
        if (!file_exists($FileName)) {
            file_put_contents($FileName, '', LOCK_EX);
        }
    }

    /**
     * Serves a file to the browser.
     *
     * @param string $File Full path to the file being served.
     * @param string $Name Name to give the file being served. Including extension overrides $File extension. Uses $File filename if empty.
     * @param string $MimeType The mime type of the file.
     * @param string $ServeMode Whether to download the file as an attachment, or inline
     */
    public static function serveFile($File, $Name = '', $MimeType = '', $ServeMode = 'attachment') {

        $FileIsLocal = (substr($File, 0, 4) == 'http') ? false : true;
        $FileAvailable = ($FileIsLocal) ? is_readable($File) : true;

        if ($FileAvailable) {
            // Close the database connection
            Gdn::database()->closeConnection();

            // Determine if Path extension should be appended to Name
            $NameExtension = strtolower(pathinfo($Name, PATHINFO_EXTENSION));
            $FileExtension = strtolower(pathinfo($File, PATHINFO_EXTENSION));
            if ($NameExtension == '') {
                if ($Name == '') {
                    $Name = pathinfo($File, PATHINFO_FILENAME).'.'.$FileExtension;
                } elseif (!stringEndsWith($Name, '.'.$FileExtension)) {
                    $Name .= '.'.$FileExtension;
                }
            } else {
                $Extension = $NameExtension;
            }
            $Name = rawurldecode($Name);

            // Figure out the MIME type
            $MimeTypes = array(
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
            );

            if ($MimeType == '') {
                if (array_key_exists($FileExtension, $MimeTypes)) {
                    $MimeType = $MimeTypes[$FileExtension];
                } else {
                    $MimeType = 'application/force-download';
                };
            };

            @ob_end_clean();

            // required for IE, otherwise Content-Disposition may be ignored
            if (ini_get('zlib.output_compression')) {
                ini_set('zlib.output_compression', 'Off');
            }

            if ($ServeMode == 'inline') {
                safeHeader('Content-Disposition: inline; filename="'.$Name.'"');
            } else {
                safeHeader('Content-Disposition: attachment; filename="'.$Name.'"');
            }

            safeHeader('Content-Type: '.$MimeType);
            safeHeader("Content-Transfer-Encoding: binary");
            safeHeader('Accept-Ranges: bytes');
            safeHeader("Cache-control: private");
            safeHeader('Pragma: private');
            safeHeader("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
            readfile($File);
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
    public static function removeFolder($Path) {
        if (!file_exists($Path)) {
            return;
        }

        if (is_file($Path)) {
            unlink($Path);
            return;
        }

        $Path = rtrim($Path, '/').'/';

        // Get all of the files in the directory.
        if ($dh = opendir($Path)) {
            while (($File = readdir($dh)) !== false) {
                if (trim($File, '.') == '') {
                    continue;
                }

                $SubPath = $Path.$File;

                if (is_dir($SubPath)) {
                    self::removeFolder($SubPath);
                } else {
                    unlink($SubPath);
                }
            }
            closedir($dh);
        }
        rmdir($Path);
    }

    public static function checkFolderR($Path, $Flags = 0) {
        $TrimPath = ltrim($Path, '/');
        $PathParts = explode('/', $TrimPath);
        $Prepend = (strlen($Path) !== strlen($TrimPath)) ? DS : '';

        $CurrentPath = array();
        foreach ($PathParts as $FolderPart) {
            array_push($CurrentPath, $FolderPart);
            $TestFolder = $Prepend.implode(DS, $CurrentPath);

            if ($Flags & Gdn_FileSystem::O_CREATE) {
                if (!is_dir($TestFolder)) {
                    @mkdir($TestFolder);
                }
            }

            if (!is_dir($TestFolder)) {
                return false;
            }

        }

        if ($Flags & Gdn_FileSystem::O_READ) {
            if (!is_readable($Path)) {
                return false;
            }
        }

        if ($Flags & Gdn_FileSystem::O_WRITE) {
            if (!is_writable($Path)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Copy file or folder from source to destination
     *
     * It can do recursive copy as well and is very smart
     * It recursively creates the dest file or directory path if there weren't exists
     * Situtaions :
     * - Src:/home/test/file.txt ,Dst:/home/test/b ,Result:/home/test/b -> If source was file copy file.txt name with b as name to destination
     * - Src:/home/test/file.txt ,Dst:/home/test/b/ ,Result:/home/test/b/file.txt -> If source was file Creates b directory if does not exsits and copy file.txt into it
     * - Src:/home/test ,Dst:/home/ ,Result:/home/test/** -> If source was directory copy test directory and all of its content into dest
     * - Src:/home/test/ ,Dst:/home/ ,Result:/home/**-> if source was direcotry copy its content to dest
     * - Src:/home/test ,Dst:/home/test2 ,Result:/home/test2/** -> if source was directoy copy it and its content to dest with test2 as name
     * - Src:/home/test/ ,Dst:/home/test2 ,Result:->/home/test2/** if source was directoy copy it and its content to dest with test2 as name
     *
     * @author Sina Salek - http://sina.salek.ws/en/contact
     * @param $source //file or folder
     * @param $dest ///file or folder
     * @param $options //folderPermission,filePermission
     * @return boolean
     */
    public static function copy($source, $dest, $options = array('folderPermission' => 0755, 'filePermission' => 0755)) {
        $result = false;

        if (is_file($source)) {
            if ($dest[strlen($dest) - 1] == '/') {
                if (!file_exists($dest)) {
                    cmfcDirectory::makeAll($dest, $options['folderPermission'], true);
                }
                $__dest = $dest."/".basename($source);
            } else {
                $__dest = $dest;
            }
            $result = copy($source, $__dest);
            chmod($__dest, $options['filePermission']);

        } elseif (is_dir($source)) {
            if ($dest[strlen($dest) - 1] == '/') {
                if ($source[strlen($source) - 1] == '/') {
                    //Copy only contents
                } else {
                    //Change parent itself and its contents
                    $dest = $dest.basename($source);
                    @mkdir($dest);
                    chmod($dest, $options['filePermission']);
                }
            } else {
                if ($source[strlen($source) - 1] == '/') {
                    //Copy parent directory with new name and all its content
                    @mkdir($dest, $options['folderPermission']);
                    chmod($dest, $options['filePermission']);
                } else {
                    //Copy parent directory with new name and all its content
                    @mkdir($dest, $options['folderPermission']);
                    chmod($dest, $options['filePermission']);
                }
            }

            $dirHandle = opendir($source);
            while ($file = readdir($dirHandle)) {
                if ($file != "." && $file != "..") {
                    if (!is_dir($source."/".$file)) {
                        $__dest = $dest."/".$file;
                    } else {
                        $__dest = $dest."/".$file;
                    }
                    $result = Gdn_FileSystem::copy($source."/".$file, $__dest, $options);
                }
            }
            closedir($dirHandle);

        } else {
            $result = false;
        }
        return $result;
    }
}
