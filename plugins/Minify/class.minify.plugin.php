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
$PluginInfo['Minify'] = array(
   'Name' => 'Minify',
   'Description' => 'Analyzes each page request for Javascript and CSS files, merging and minifying them where applicable.',
   'Version' => '1.0.3b',
   'Author' => "Mark O'Sullivan",
   'AuthorEmail' => 'mark@vanillaforums.com',
   'AuthorUrl' => 'http://markosullivan.ca'
);

class MinifyPlugin extends Gdn_Plugin {
   
   /** @var string Subfolder that Vanilla lives in */
   protected $BasePath = "";

   /**
    * Remove all CSS and JS files and add minified versions.
    *
    * @param HeadModule $Head
    */
   public function HeadModule_BeforeToString_Handler($Head) {
      // Set BasePath for the plugin
      $this->BasePath = Gdn::Request()->WebRoot();
      
      // Get current tags
      $Tags = $Head->Tags();

      // Grab all of the CSS
      $CssToCache = array();
      $JsToCache = array(); // Add the global js files
      $GlobalJS = array(
         'jquery.js',
         'jquery.livequery.js',
         'jquery.form.js',
         'jquery.popup.js',
         'jquery.gardenhandleajaxform.js',
         'global.js'
      );
      
      // Process all tags, finding JS & CSS files
      foreach ($Tags as $Index => $Tag) {
         $IsJs = GetValue(HeadModule::TAG_KEY, $Tag) == 'script';
         $IsCss = (GetValue(HeadModule::TAG_KEY, $Tag) == 'link' && GetValue('rel', $Tag) == 'stylesheet');
         if (!$IsJs && !$IsCss)
            continue;

         if ($IsCss)
            $Href = GetValue('href', $Tag, '!');
         else
            $Href = GetValue('src', $Tag, '!');
         
         // Skip the rest if path doesn't start with a slash
         if ($Href[0] != '/')
            continue;

         // Strip any querystring off the href.
         $Href = preg_replace('`\?.*`', '', $Href);
         
         // Strip BasePath & extra slash from Href (Minify adds an extra slash when substituting basepath)
         if($this->BasePath != '')
            $Href = preg_replace("`^/{$this->BasePath}/`U", '', $Href);
            
         // Skip the rest if the file doesn't exist
         $FixPath = ($Href[0] != '/') ? '/' : ''; // Put that slash back to test for it in file structure
         $Path = PATH_ROOT . $FixPath . $Href;
         if (!file_exists($Path))
            continue;

         // Remove the css from the tag because minifier is taking care of it.
         unset($Tags[$Index]);

         // Add the reference to the appropriate cache collection.
         if ($IsCss) {
            $CssToCache[] = $Href;
         } elseif ($IsJs) {
            // Don't include the file if it's in the global js.
            $Filename = basename($Path);
            if (in_array($Filename, $GlobalJS)) {
               continue;
            }
            $JsToCache[] = $Href;
         }
      }
      
      // Add minified css & js directly to the head module.
      $Url = 'plugins/Minify/min/?' . ($this->BasePath != '' ? "b={$this->BasePath}&" : '');
      
      // Update HeadModule's $Tags
      $Head->Tags($Tags);
      
      // Add minified CSS to HeadModule
      $Head->AddCss($Url . 'token=' . $this->_PrepareToken($CssToCache), 'screen');
      
      // Add global minified JS separately (and first)
      $Head->AddScript($Url . 'g=globaljs', 'text/javascript', -100);
      
      // Add other minified JS to HeadModule
      $Head->AddScript($Url . 'token=' . $this->_PrepareToken($JsToCache));
   }
   
   /**
    * Build unique, repeatable identifier for cache files.
    *
    * @param array $Files List of filenames
    * @return string $Token Unique identifier for file collection
    */
   protected function _PrepareToken($Files) {
      // Build token
      $Query = array('f' => implode(',', array_unique($Files)));
      if ($this->BasePath != '')
         $Query['b'] = $this->BasePath;
      $Query = serialize($Query);
      $Token = md5($Query);
      
      // Save file name with token
      $CacheFile = PATH_CACHE . DS . 'Minify' . DS . 'query_' . $Token;
      if (!file_exists($CacheFile)) {
         file_put_contents($CacheFile, $Query);
      }
      
      return $Token;
   }
   
   /**
    * Create 'Minify' cache folder.
    */
   public function Setup() {
      $Folder = PATH_CACHE . DS . 'Minify';
      if (!file_exists($Folder))
         @mkdir($Folder);
   }
   
   /**
    * Empty cache when disabling this plugin.
    */ 
   public function OnDisable() { $this->_EmptyCache(); }
   
   /** 
    * Empty cache when enabling or disabling any other plugin, application, or theme.
    */
   public function SettingsController_AfterEnablePlugin_Handler() { $this->_EmptyCache(); }
   public function SettingsController_AfterDisablePlugin_Handler() { $this->_EmptyCache(); }
   public function SettingsController_AfterEnableApplication_Handler() { $this->_EmptyCache(); }
   public function SettingsController_AfterDisableApplication_Handler() { $this->_EmptyCache(); }
   public function SettingsController_AfterEnableTheme_Handler() { $this->_EmptyCache(); }
   
   /**
    * Empty Minify's cache.
    */
   private function _EmptyCache() {
      $Files = glob(PATH_CACHE.'/Minify/*', GLOB_MARK);
      foreach ($Files as $File) {
         if (substr($File, -1) != '/')
            unlink($File);
      }
   }
}