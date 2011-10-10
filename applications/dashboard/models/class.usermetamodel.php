<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class UserMetaModel extends Gdn_Model {
   
   /**
    * Store in-memory copies of everything retrieved from and set to the DB.
    * Reference this if available, instead of querying
    * @TODO
    * @var array
    */
   protected static $MemoryCache;
   
   /**
    * Class constructor. Defines the related database table name.
    */
   public function __construct() {
      
      self::$MemoryCache = array();
      
      // We don't need this yet
      //parent::__construct('UserMeta');
   }
   
   /**
    * Retrieves UserMeta information for a UserID / Key pair
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
   public function GetUserMeta($UserID, $Key, $Default = NULL) {
      $Sql = clone Gdn::SQL();
      $Sql->Reset();
      $UserMetaQuery = $Sql
         ->Select('*')
         ->From('UserMeta u');
         
      if (is_array($UserID))
         $UserMetaQuery->WhereIn('u.UserID', $UserID);
      else
         $UserMetaQuery->Where('u.UserID', $UserID);
      
      if (stristr($Key, '%'))
         $UserMetaQuery->Where('u.Name like', $Key);
      else
         $UserMetaQuery->Where('u.Name', $Key);
      
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
         self::$MemoryCache[$Key] = $Default;
      
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
   public function SetUserMeta($UserID, $Key, $Value = NULL) {
      if (is_null($Value)) {  // Delete
         $UserMetaQuery = Gdn::SQL();
            
         if (is_array($UserID))
            $UserMetaQuery->WhereIn('UserID', $UserID);
         else
            $UserMetaQuery->Where('UserID', $UserID);
         
         if (stristr($Key, '%'))
            $UserMetaQuery->Like('Name', $Key);
         else
            $UserMetaQuery->Where('Name', $Key);      
         
         $UserMetaQuery->Delete('UserMeta');
      } else {                // Set
         if (!is_array($UserID))
            $UserID = array($UserID);
         
         foreach ($UserID as $UID) {
            try {
               Gdn::SQL()->Insert('UserMeta',array(
                  'UserID'    => $UID,
                  'Name'      => $Key,
                  'Value'     => $Value
               ));
            } catch (Exception $e) {
               Gdn::SQL()->Update('UserMeta',array(
                  'Value'     => $Value
               ),array(
                  'UserID'    => $UID,
                  'Name'      => $Key
               ))->Put();
            }
         }
      }
      return;
   }
   
}