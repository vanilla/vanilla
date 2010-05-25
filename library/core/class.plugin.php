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
 * A simple framework that all plugins should extend. Aside from the implementation of
 * Gdn_IPlugin, this class provides some convenience methods to make plugin development
 * easier and faster.
 *
 * @author Tim Gunter
 * @package Garden
 * @version @@GARDEN-VERSION@@
 * @namespace Garden.Core
 */
abstract class Gdn_Plugin implements Gdn_IPlugin {

   public function GetView($ViewName) {
      $PluginName = substr(get_class($this),0,-6);
      $PluginDirectory = PATH_PLUGINS.DS.$PluginName.DS.'views';
      return $PluginDirectory.DS.$ViewName;
   }
   
   // Get the path to a file within the plugin's folder (and optionally include it)
   public function GetResource($Filename, $IncludeFile = FALSE, $AbsolutePath = TRUE) {
      $PluginName = substr(get_class($this),0,-6);
      $PathParts = array(
         ($AbsolutePath) ? PATH_PLUGINS : 'plugins',
         $PluginName,
         $Filename
      );
      $RequiredFilename = implode(DS, $PathParts);
      if ($IncludeFile && file_exists($RequiredFilename))
         require_once($RequiredFilename);
            
      return $RequiredFilename;
   }

}