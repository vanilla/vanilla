<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

// Define the plugin:
$PluginInfo['AssetCache'] = array(
   'Name' => 'Asset Cache',
   'Description' => 'Analyzes each page request for external js & css files, merging and minifying them where applicable.',
   'Version' => '1.0',
   'Author' => "Mark O'Sullivan",
   'AuthorEmail' => 'mark@vanillaforums.com',
   'AuthorUrl' => 'http://markosullivan.ca'
);

class AssetCachePlugin extends Gdn_Plugin {

   public function Setup() {
      $Folder = PATH_CACHE . DS . 'AssetCache';
		if (!file_exists($Folder))
			@mkdir($Folder);
   }
   
   public function Base_BeforeAddCss_Handler($Sender) {
      $WebRoot = Gdn::Request()->WebRoot();
      // Find all css file paths
      $CssToCache = array();
      foreach ($Sender->CssFiles() as $CssInfo) {
         $CssFile = $CssInfo['FileName'];
         
         if(strpos($CssFile, '/') !== FALSE) {
            // A direct path to the file was given.
            $CssPaths = array(CombinePaths(array(PATH_ROOT, str_replace('/', DS, $CssFile))));
         } else {
            $CssGlob = preg_replace('/(.*)(\.css)/', '\1*\2', $CssFile);
            $AppFolder = $CssInfo['AppFolder'];
            if ($AppFolder == '')
               $AppFolder = $Sender->ApplicationFolder;

            // CSS comes from one of four places:
            $CssPaths = array();
            if ($Sender->Theme) {
               // 1. Application-specific css. eg. root/themes/theme_name/app_name/design/
               // $CssPaths[] = PATH_THEMES . DS . $Sender->Theme . DS . $AppFolder . DS . 'design' . DS . $CssGlob;
               // 2. Theme-wide theme view. eg. root/themes/theme_name/design/
               // a) Check to see if a customized version of the css is there.
               if ($Sender->ThemeOptions) {
                  $Filenames = GetValueR('Styles.Value', $Sender->ThemeOptions);
                  if (is_string($Filenames) && $Filenames != '%s')
                     $CssPaths[] = PATH_THEMES.DS.$Sender->Theme.DS.'design'.DS.ChangeBasename($CssFile, $Filenames);
               }
               // b) Use the default filename.
               $CssPaths[] = PATH_THEMES . DS . $Sender->Theme . DS . 'design' . DS . $CssFile;
            }
            // 3. Application default. eg. root/applications/app_name/design/
            $CssPaths[] = PATH_APPLICATIONS . DS . $AppFolder . DS . 'design' . DS . $CssFile;
            // 4. Garden default. eg. root/applications/dashboard/design/
            $CssPaths[] = PATH_APPLICATIONS . DS . 'dashboard' . DS . 'design' . DS . $CssFile;
         }

         // Find the first file that matches the path.
         $CssPath = FALSE;
         foreach($CssPaths as $Glob) {
            $Paths = SafeGlob($Glob);
            if(is_array($Paths) && count($Paths) > 0) {
               $CssPath = $Paths[0];
               break;
            }
         }
         
         if ($CssPath !== FALSE) {
            $CssPath = substr($CssPath, strlen(PATH_ROOT)+1);
            $CssPath = str_replace(DS, '/', $CssPath);
            $CssToCache[] = $WebRoot.'/'.$CssPath;
         }
      }
      $CssToCache = array_unique($CssToCache);
      
      // And now search for/add all JS files
      $JsToCache = array();
      foreach ($Sender->JsFiles() as $JsInfo) {
         $JsFile = $JsInfo['FileName'];
         
         if (strpos($JsFile, '/') !== FALSE) {
            // A direct path to the file was given.
            $JsPaths = array(CombinePaths(array(PATH_ROOT, str_replace('/', DS, $JsFile)), DS));
         } else {
            $AppFolder = $JsInfo['AppFolder'];
            if ($AppFolder == '')
               $AppFolder = $Sender->ApplicationFolder;

            // JS can come from a theme, an any of the application folder, or it can come from the global js folder:
            $JsPaths = array();
            if ($Sender->Theme) {
               // 1. Application-specific js. eg. root/themes/theme_name/app_name/design/
               $JsPaths[] = PATH_THEMES . DS . $Sender->Theme . DS . $AppFolder . DS . 'js' . DS . $JsFile;
               // 2. Garden-wide theme view. eg. root/themes/theme_name/design/
               $JsPaths[] = PATH_THEMES . DS . $Sender->Theme . DS . 'js' . DS . $JsFile;
            }
            // 3. This application folder
            $JsPaths[] = PATH_APPLICATIONS . DS . $AppFolder . DS . 'js' . DS . $JsFile;
            // 4. Global JS folder. eg. root/js/
            $JsPaths[] = PATH_ROOT . DS . 'js' . DS . $JsFile;
            // 5. Global JS library folder. eg. root/js/library/
            $JsPaths[] = PATH_ROOT . DS . 'js' . DS . 'library' . DS . $JsFile;
         }

         // Find the first file that matches the path.
         $JsPath = FALSE;
         foreach($JsPaths as $Glob) {
            $Paths = SafeGlob($Glob);
            if(is_array($Paths) && count($Paths) > 0) {
               $JsPath = $Paths[0];
               break;
            }
         }
         
         if ($JsPath !== FALSE) {
            $JsPath = str_replace(
               array(PATH_ROOT, DS),
               array('', '/'),
               $JsPath
            );
            $JsToCache[] = $WebRoot.$JsPath;
         }
      }
      $JsToCache = array_unique($JsToCache);
      
      // Remove all js & css from the controller
      $Sender->ClearCssFiles();
      $Sender->ClearJsFiles();
      
      // Add minified css & js directly to the head module
      $Url = Gdn::Request()->Url('plugins/AssetCache/min/?f=', TRUE);
      $Sender->Head->AddCss($Url.implode(',', $CssToCache), 'screen');
      $Sender->Head->AddScript($Url.implode(',', $JsToCache));
      
      /*
      http://localhost/vanilla/plugins/AssetCache/min/?f=
      vanilla/js/library/jquery.js,
      vanilla/js/library/jquery.livequery.js,
      vanilla/js/library/jquery.form.js,
      vanilla/js/library/jquery.popup.js,
      vanilla/js/library/jquery.gardenhandleajaxform.js,
      vanilla/js/global.js,
      vanilla/js/library/jquery.autogrow.js,
      vanilla/js/library/jquery.tablednd.js,
      vanilla/js/library/jquery.ui.packed.js       
      */
   }   
}