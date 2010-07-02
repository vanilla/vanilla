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
abstract class Gdn_Plugin extends Gdn_SliceProvider implements Gdn_IPlugin {

   public function GetView($ViewName) {
      $PluginName = substr(get_class($this),0,-6);
      $PluginDirectory = PATH_PLUGINS.DS.$PluginName.DS.'views';
      return $PluginDirectory.DS.$ViewName;
   }
   
   // Get the path to a file within the plugin's folder (and optionally include it)
   public function GetResource($Filename, $IncludeFile = FALSE, $AbsolutePath = TRUE) {
      $PluginName = substr(get_class($this),0,-6);
      
      $PathParts = array(
         $PluginName,
         $Filename
      );
         
      array_unshift($PathParts, ($AbsolutePath) ? PATH_PLUGINS : 'plugins');
      
      $RequiredFilename = implode(DS, $PathParts);
      if ($IncludeFile && file_exists($RequiredFilename))
         include($RequiredFilename);
            
      return $RequiredFilename;
   }
   
   public function GetWebResource($Filename) {
      $WebResource = $this->GetResource($Filename, FALSE, FALSE);
      
      if (Gdn::Request()->WebRoot())
         $WebResource = Gdn::Request()->WebRoot().'/'.$WebResource;
      return '/'.$WebResource;
   }
   
   public function Dispatch(&$Sender, $RequestArgs = array()) {
      $ControllerMethod = 'Controller_Index';
      if (is_array($RequestArgs) && sizeof($Sender->RequestArgs)) {
         list($MethodName) = $Sender->RequestArgs;
         $TestControllerMethod = 'Controller_'.$MethodName;
         if (method_exists($this, $TestControllerMethod))
            $ControllerMethod = $TestControllerMethod;
      }
      
      if (method_exists($this, $ControllerMethod)) {
         $Sender->Plugin = $this;
         return call_user_func(array($this,$ControllerMethod),$Sender);
      }
   }
   
   protected function GetUserMeta($UserID, $Key) {
      $SQL = Gdn::SQL();
      $UserMetaQuery = $SQL
         ->Select('*')
         ->From('UserMeta')
         ->Where('UserID', $UserID);
      
      if (stristr($Key, '%'))
         $UserMetaQuery->Like('Name', $Key);
      else
         $UserMetaQuery->Where('Name', $Key);
         
      $UserMetaData = $UserMetaQuery->Get();
      
      $UserMeta = array();
      if ($UserMetaData->NumRows())
         while ($MetaRow = $UserMetaData->NextRow(DATASET_TYPE_ARRAY))
            $UserMeta[$MetaRow['Name']] = $MetaRow['Value'];
      unset($UserMetaData);
      return $UserMeta;
   }

}