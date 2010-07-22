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

   public function GetPluginName() {
      return substr(get_class($this),0,-6);
   }
   
   /**
    * Gets the path to a file within the plugin's folder (and optionally include it)
    *
    * @param $Filename string relative path to a file within the plugin's folder
    * @param $IncludeFile boolean whether or not to immediately include() the file if it exists
    * @param $AbsolutePath boolean whether or not to prepend the full document root to the path
    * @return string path to the file
    */
   public function GetResource($Filepath, $Include = FALSE, $Absolute = TRUE) {
      $PluginName = $this->GetPluginName();
      
      $PathParts = array(
         $PluginName,
         $Filepath
      );
         
      array_unshift($PathParts, ($Absolute) ? PATH_PLUGINS : 'plugins');
      
      $RequiredFilename = implode(DS, $PathParts);
      if ($Include && file_exists($RequiredFilename))
         include($RequiredFilename);
            
      return $RequiredFilename;
   }
   
   /**
    * Converts view files to Render() paths
    * 
    * This method takes a simple filename and, assuming it is located inside <yourplugin>/views/, 
    * converts it into a path that is suitable for $Sender->Render().
    * 
    * @param $ViewName string name of the view file, including extension
    * @return string path to the view file, relative to the document root.
    */
   public function GetView($ViewName) {
      $PluginName = $this->GetPluginName();
      $PluginDirectory = PATH_PLUGINS.DS.$PluginName.DS.'views';
      return $PluginDirectory.DS.$ViewName;
   }
   
   public function GetWebResource($Filepath) {
      $WebResource = $this->GetResource($Filepath, FALSE, FALSE);
      
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
      } else {
         throw new Exception(sprintf("Call to invalid plugin controller method '%s' on %sPlugin",$MethodName, $this->GetPluginName()));
      }
   }
   
   /**
    * Retries UserMeta information for a UserID / Key pair
    * 
    * This method takes a $UserID or array of $UserIDs, and a $Key. It converts the
    * $Key to fully qualified format and then queries for the associated value(s). $Key
    * can contain SQL wildcards, in which case multiple results can be returned.
    * 
    * If $UserID is an array, the return value will be a multi dimensional array with the first
    * axis containing UserIDs and the second containing fully qualified UserMetaKeys, associated with 
    * their values.
    * 
    * If $UserID is a scalar, the return value will be a single dimensional array of $UserMetaKey => $Value 
    * pairs.
    *
    * @param $UserID integer UserID or array of UserIDs
    * @param $Key string relative user meta key
    * @param $Default optional default return value if key is not found
    * @return array results or $Default
    */
   protected function GetUserMeta($UserID, $Key, $Default = NULL) {
      $MetaKey = $this->MakeMetaKey($Key);
      
      $UserMetaQuery = Gdn::SQL()
         ->Select('*')
         ->From('UserMeta u');
         
      if (is_array($UserID))
         $UserMetaQuery->WhereIn('u.UserID', $UserID);
      else
         $UserMetaQuery->Where('u.UserID', $UserID);
      
      if (stristr($Key, '%'))
         $UserMetaQuery->Like('u.Name', $MetaKey);
      else
         $UserMetaQuery->Where('u.Name', $MetaKey);
      
      $UserMetaData = $UserMetaQuery->Get();
      
      $UserMeta = array();
      if ($UserMetaData->NumRows())
         if (is_array($UserID)) {
            while ($MetaRow = $UserMetaData->NextRow())
               $UserMeta[$MetaRow->UserID][$MetaRow->Name] = $MetaRow->Value;
         } else {
            while ($MetaRow = $UserMetaData->NextRow())
               $UserMeta[$MetaRow->Name] = $MetaRow->Value;
         }
      else
         return $Default;
      unset($UserMetaData);
      return $UserMeta;
   }
   
   /**
    * Sets UserMeta data to the UserMeta table
    * 
    * This method takes a UserID, Key, and Value, and attempts to set $Key = $Value for $UserID.
    * $Key can be an SQL wildcard, thereby allowing multiple variations of a $Key to be set. $UserID 
    * can be an array, thereby allowing multiple users' $Keys to be set to the same $Value.
    *
    * ++ Before any queries are run, $Key is converted to its fully qualified format (Plugin.<PluginName> prepended)
    * ++ to prevent collisions in the meta table when multiple plugins have similar key names.
    *
    * If $Value == NULL, the matching row(s) are deleted instead of updated.
    * 
    * @param $UserID int UserID or array of UserIDs
    * @param $Key string relative user key
    * @param $Value mixed optional value to set, null to delete
    * @return void
    */
   protected function SetUserMeta($UserID, $Key, $Value = NULL) {
      $MetaKey = $this->MakeMetaKey($Key);
      
      if (is_null($Value)) {  // Delete
         $UserMetaQuery = Gdn::SQL()
            ->From('UserMeta u');
            
         if (is_array($UserID))
            $UserMetaQuery->WhereIn('UserID', $UserID);
         else
            $UserMetaQuery->Where('UserID', $UserID);
         
         if (stristr($Key, '%'))
            $UserMetaQuery->Like('Name', $MetaKey);
         else
            $UserMetaQuery->Where('Name', $MetaKey);      
         
         $UserMetaQuery->Delete();
      } else {                // Set
         if (!is_array($UserID))
            $UserID = array($UserID);
         
         foreach ($UserID as $UID) {
            try {
               Gdn::SQL()->Insert('UserMeta',array(
                  'UserID'    => $UID,
                  'Name'      => $MetaKey,
                  'Value'     => $Value
               ));
            } catch (Exception $e) {
               Gdn::SQL()->Update('UserMeta',array(
                  'Value'     => $Value
               ),array(
                  'UserID'    => $UID,
                  'Name'      => $MetaKey
               ))->Put();
            }
         }
      }
      return;
   }
   
   /**
    * This method trims the plugin prefix from a fully qualified MetaKey.
    *
    * For example, Plugin.Signatures.Sig would become 'Sig'.
    *
    * @param $UserMetaKey string fully qualified meta key
    * @return string relative meta key
    */
   protected function TrimMetaKey($FullyQualifiedUserKey) {
      $Key = explode('.', $FullyQualifiedUserKey);
      if ($Key[0] == 'Plugin' && sizeof($Key) >= 3) {
         return implode('.',array_slice($Key, 2));
      }
         
      return $FullyQualifiedUserKey;
   }
   
   /**
    * This method takes a UserKey (short relative form) and prepends the plugin prefix.
    * 
    * For example, 'Sig' becomes 'Plugin.Signatures.Sig'
    * 
    * @param $UserKey string relative user meta key
    * @return string fully qualified meta key
    */
   protected function MakeMetaKey($RelativeUserKey) {
      return implode('.',array('Plugin',$this->GetPluginName(),$this->TrimMetaKey($RelativeUserKey)));
   }

}