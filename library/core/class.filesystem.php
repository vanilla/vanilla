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
 * Load files and either return their contents or send them to the browser.
 *
 * @author Mark O'Sullivan
 * @copyright 2003 Mark O'Sullivan
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @version @@GARDEN-VERSION@@
 * @namespace Garden.Core
 * @todo Make this object deliver content with a save as dialogue.
 */

class Gdn_FileSystem {

   const O_CREATE = 1;
   const O_WRITE = 2;
   const O_READ = 4;

   /**
    * Searches the provided file path(s). Returns the first one it finds in the
    * filesystem.
    *
    * @param mixed $Files The path (or array of paths) to files which should be checked for
    * existence.
    */
   public static function Exists($Files) {
      if (!is_array($Files))
         $Files = array($Files);

      $Return = FALSE;
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
    * @todo Documentation and variable type is needed for $SourceFolder.
    */
   public static function Folders($SourceFolders) {
      if(!is_array($SourceFolders))
         $SourceFolders = array($SourceFolders);
   
      $BlackList = Gdn::Config('Garden.FolderBlacklist');
      if (!is_array($BlackList))
         $BlackList = array('.', '..');
         
      $Result = array();
      
      foreach($SourceFolders as $SourceFolder) {
         if ($DirectoryHandle = opendir($SourceFolder)) {
            while (($Item = readdir($DirectoryHandle)) !== FALSE) {
               $SubFolder = CombinePaths(array($SourceFolder, $Item));
               if (!in_array($Item, $BlackList) && is_dir($SubFolder))
                  $Result[] = $Item;
            }
            closedir($DirectoryHandle);
         }
      }
      if(count($Result) == 0)
         return FALSE;
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
   public static function Find($SourceFolders, $FileName, $WhiteList = FALSE) {
      $Return = self::_Find($SourceFolders, $WhiteList, $FileName, TRUE);
      if (is_array($Return))
         return count($Return) > 0 ? $Return[0] : FALSE;
      else
         return $Return;
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
   public static function FindAll($SourceFolders, $FileName, $WhiteList = FALSE) {
      return self::_Find($SourceFolders, $WhiteList, $FileName, FALSE);
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
   private static function _Find($SourceFolders, $WhiteList, $FileName, $ReturnFirst = FALSE) {
      $Return = array();

      if (!is_array($SourceFolders))
         $SourceFolders = array($SourceFolders);

      foreach ($SourceFolders as $SourceFolder) {
         if (!is_array($WhiteList)) {
            $Path = CombinePaths(array($SourceFolder, $FileName));
            if (file_exists($Path)) {
               if ($ReturnFirst)
                  return $Path;
               else
                  $Return[] = array($Path);
            }
         } else {
            if ($DirectoryHandle = opendir($SourceFolder)) {
               if ($DirectoryHandle === FALSE)
                  trigger_error(ErrorMessage('Failed to open folder when performing a filesystem search.', 'Gdn_FileSystem', '_Find', $SourceFolder), E_USER_ERROR);

               $SubFolders = array();
               foreach ($WhiteList as $WhiteFolder) {
                  $SubFolder = CombinePaths(array($SourceFolder, $WhiteFolder));
                  if (is_dir($SubFolder)) {
                     $SubFolders[] = $SubFolder;
                     $Path = CombinePaths(array($SubFolder, $FileName));
                     // echo '<div style="color: red;">Looking For: '.$Path.'</div>';
                     if (file_exists($Path)) {
                        if ($ReturnFirst)
                           return array($Path);
                        else
                           $Return[] = $Path;
                     }
                  }
               }
            }
            closedir($DirectoryHandle);
         }
      }

      return count($Return) > 0 ? $Return : FALSE;
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
   public static function FindByMapping($MappingCacheName, $SourceFolders, $FolderWhiteList, $LibraryName) {

      // If the application folder was provided, it will be the only entry in the whitelist, so prepend it.
      if (is_array($FolderWhiteList) && count($FolderWhiteList) == 1)
         $LibraryName = CombinePaths(array($FolderWhiteList[0], $LibraryName));
         
      $LibraryKey = str_replace('.', '__', $LibraryName);
      Gdn_LibraryMap::PrepareCache($MappingCacheName);
      $LibraryPath = Gdn_LibraryMap::GetCache($MappingCacheName, $LibraryKey);
      if ($LibraryPath === NULL) {
         // $LibraryName wasn't contained in the mappings array.
         // I need to look through the folders in this application for the requested file.
         // Once I find it, I need to save the mapping so we don't have to search for it again.

         // Attempt to find the file directly off the root (if the app folder was provided in the querystring)
         if ($FolderWhiteList !== FALSE && count($FolderWhiteList) == 1) {
            $LibraryPath = self::Find($SourceFolders, $LibraryName);
         } else {
            $LibraryPath = self::Find($SourceFolders, $LibraryName, $FolderWhiteList);
         }

         // If the controller was found
         if($LibraryPath !== FALSE) {
            Gdn_LibraryMap::Cache($MappingCacheName, $LibraryKey, $LibraryPath);
         }
      }
      return $LibraryPath;
   }

   /**
    * Returns the contents of the specified file, or FALSE if it does not
    * exist. Note that this is only useful for static content since any php
    * code will be parsed as if it were within this method of this object.
    */
   public static function GetContents() {
      $File = CombinePaths(func_get_args());
      if (file_exists($File) && is_file($File))
         return self::_GetContents($File);
      else
         return FALSE;
   }

   /**
    * Returns the contents of the specified file. Does not check for existence
    * of the file first. Use the public self::GetContents() for the extra
    * security.
    *
    * @param string $File The full path and name of the file being examined.
    */
   private static function _GetContents($File) {
      ob_start();
      include($File);
      $Contents = ob_get_contents();
      ob_end_clean();
      return $Contents;
   }

   /**
    * Saves the specified file with the provided file contents.
    *
    * @param string $FileName The full path and name of the file to be saved.
    * @param string $FileContents The contents of the file being saved.
    */
   public static function SaveFile($FileName, $FileContents, $Flags = LOCK_EX) {
   
      // Check that the folder exists and is writable
      $DirName = dirname($FileName);
      $FileBaseName = basename($FileName);
      if (!is_dir($DirName))
         throw new Exception(sprintf('Requested save operation [%1$s] could not be completed because target folder [%2$s] does not exist.',$FileBaseName,$DirName));
         
      if (!IsWritable($DirName))
         throw new Exception(sprintf('Requested save operation [%1$s] could not be completed because target folder [%2$s] is not writable.',$FileBaseName,$DirName));
         
      file_put_contents($FileName, $FileContents, $Flags);
      return TRUE;
   }
   
   /**
    * Similar to the unix touch command, this method checks to see if $FileName
    * exists. If it does not, it creates the file with nothing inside it.
    *
    * @param string $FileName The full path to the file being touched.
    */
   public static function Touch($FileName) {
      if (!file_exists($FileName))
         file_put_contents($FileName, '', LOCK_EX);
   }
   
   /**
    * Serves a file to the browser.
    *
    * @param string $File The full path to the file being served.
    * @param string $Name The name to give the file being served (don't include file extension, it will be added automatically). Will use file's name on disk if ignored.
    * @param string $MimeType The mime type of the file.
    */
   public static function ServeFile($File, $Name = '', $MimeType = '') {
      if (is_readable($File)) {
         // Get the db connection and make sure it is closed
         $Database = Gdn::Database();
         $Database->CloseConnection();
         
         $Size = filesize($File);
         $Extension = strtolower(pathinfo($File, PATHINFO_EXTENSION));
         if ($Name == '') {
            $Name = pathinfo($File, PATHINFO_FILENAME) . '.' . $Extension;
         } else {
            $Name .= '.'.$Extension;
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
           "jpeg"=> "image/jpg",
           "jpg" =>  "image/jpg",
           "php" => "text/plain"
         );
         
         if ($MimeType == '') {
            if (array_key_exists($Extension, $MimeTypes)){
              $MimeType = $MimeTypes[$Extension];
            } else {
              $MimeType = 'application/force-download';
            };
         };
         
         @ob_end_clean();
         
         // required for IE, otherwise Content-Disposition may be ignored
         if(ini_get('zlib.output_compression'))
            ini_set('zlib.output_compression', 'Off');
         
         header('Content-Type: ' . $MimeType);
         header('Content-Disposition: attachment; filename="'.$Name.'"');
         header("Content-Transfer-Encoding: binary");
         header('Accept-Ranges: bytes');
         header("Cache-control: private");
         header('Pragma: private');
         header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
         readfile($File);
         exit();
      }
   }
   
   /**
    * Remove a folder (and all the sub-folders and files).
    * Taken from http://php.net/manual/en/function.rmdir.php
    * 
    * @param string $Dir 
    * @return void
    */
   public static function RemoveFolder($Dir) {
      // Make sure the directory is properly denoted (otherwise this function
      // will also delete directories prefixed with $Dir).
      if (substr($Dir, -1, 1) != '/')
         $Dir  .= '/';
         
      $Files = glob($Dir . '*', GLOB_MARK);
      
      foreach ($Files as $File) {
         if (substr($File, -1) == '/')
            self::RemoveFolder($File);
         else
            unlink($File);
      }

      if (is_dir($Dir)) rmdir($Dir);
   }
   
   public static function CheckFolderR($Path, $Flags = 0) {
      $TrimPath = ltrim($Path, '/');
      $PathParts = explode('/', $TrimPath);
      $Prepend = (strlen($Path) !== strlen($TrimPath)) ? DS : '';
      
      $CurrentPath = array();
      foreach ($PathParts as $FolderPart) {
         array_push($CurrentPath, $FolderPart);
         $TestFolder = $Prepend.implode(DS, $CurrentPath);
         
         if ($Flags & Gdn_FileSystem::O_CREATE) {
            if (!is_dir($TestFolder))
               @mkdir($TestFolder);
         }
         
         if (!is_dir($TestFolder))
            return FALSE;

      }
      
      if ($Flags & Gdn_FileSystem::O_READ) {
         if (!is_readable($Path))
            return FALSE;
      }
         
      if ($Flags & Gdn_FileSystem::O_WRITE) {
         if (!is_writable($Path))
            return FALSE;
      }
      return TRUE;
   }
   
}