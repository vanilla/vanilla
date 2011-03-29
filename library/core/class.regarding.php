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
 * Handles relating external actions to comments and discussions. Flagging, Praising, Reporting, etc
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Mark O'Sullivan
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @version @@GARDEN-VERSION@@
 * @namespace Garden.Core
 */

class Gdn_Regarding extends Gdn_Pluggable implements Gdn_IPlugin {

   public function __construct() {
      parent::__construct();
   }

   /* With regard to... */

   public function Comment($CommentID, $Verify = TRUE) {
      return $this->Regarding('Comment', $CommentID, $Verify);
   }

   public function Discussion($DiscussionID, $Verify = TRUE) {
      return $this->Regarding('Discussion', $DiscussionID, $Verify);
   }

   public function Regarding($ThingType, $ThingID, $Verify = TRUE) {
      $Verified = FALSE;
      if ($Verify) {
         $ModelName = ucfirst($ThingType).'Model';
         $VerifyModel = new $ModelName;
         $Verified = TRUE; // fake it for now
      } else {
         $Verified = NULL;
      }

      if ($Verified || is_null($Verified))
         return new Gdn_RegardingEntity($ThingType, $ThingID);

      throw new Exception(sprintf(T("Could not verify entity relationship '%s(%d)' for Regarding call"), $ModelName, $ThingID));
   }

   public function That() {
      return call_user_func_array(array($this,'Regarding'), func_get_args());
   }

   /*
    * Event system: Provide information for external hooks
    */

   public function GetEvent(&$EventArguments) {
      /**
      * 1) Entity
      * 2) Regarding Data
      * 3) [optional] Options
      */
      $Response = array(
         'EventSender'     => NULL,
         'Entity'          => NULL,
         'RegardingData'   => NULL,
         'Options'         => NULL
      );

      if (sizeof($EventArguments) >= 1)
         $Response['EventSender'] = $EventArguments[0];

      if (sizeof($EventArguments) >= 2)
         $Response['Entity'] = $EventArguments[1];

      if (sizeof($EventArguments) >= 3)
         $Response['RegardingData'] = $EventArguments[2];

      if (sizeof($EventArguments) >= 4)
         $Response['Options'] = $EventArguments[3];

      return $Response;
   }

   public function MatchEvent($RegardingType, $ForeignType, $ForeignID = NULL) {
      $EventOptions = $Sender->GetEvent($this->EventArguments);

      return $EventOptions;
   }

   /*
    * Event system: Hook into core events
    */

   // Cache regarding data for displayed comments
   public function DiscussionController_BeforeDiscussionRender_Handler($Sender) {
      if (GetValue('RegardingCache', $Sender, NULL) != NULL) return;

      $Comments = $Sender->Data('CommentData');
      $CommentIDList = array();
      if ($Comments && $Comments instanceof Gdn_DataSet) {
         $Comments->DataSeek(-1);
         while ($Comment = $Comments->NextRow())
            $CommentIDList[] = $Comment->CommentID;
      }

      $this->CacheRegarding($Sender, 'discussion', $Sender->Discussion->DiscussionID, 'comment', $CommentIDList);
   }

   protected function CacheRegarding($Sender, $ParentType, $ParentID, $ForeignType, $ForeignIDs) {

      $Sender->RegardingCache = array();

      $ChildRegardingData = $this->RegardingModel()->GetAll($ForeignType, $ForeignIDs);
      $ParentRegardingData = $this->RegardingModel()->Get($ParentType, $ParentID);

/*
      $MediaArray = array();
      if ($MediaData !== FALSE) {
         $MediaData->DataSeek(-1);
         while ($Media = $MediaData->NextRow()) {
            $MediaArray[$Media->ForeignTable.'/'.$Media->ForeignID][] = $Media;
            $this->MediaCacheById[GetValue('MediaID',$Media)] = $Media;
         }
      }
*/

      $this->RegardingCache = array();
   }

   public function DiscusssionController_BeforeCommentBody_Handler($Sender) {
      $Context = strtolower($Sender->EventArguments['Type']);
      if ($Context != 'discussion') return;

      $RegardingID = GetValue('RegardingID', $Sender->EventArguments['Object'], NULL);
      if (is_null($RegardingID) || $RegardingID < 0) return;

      try {
         $RegardingData = $this->RegardingModel()->GetID($RegardingID);
         $this->EventArguments = array_merge($this->EventArguments,array(
            'EventSender'     => $Sender,
            'Entity'          => $Sender->EventArguments['Object'],
            'RegardingData'   => $RegardingData,
            'Options'         => NULL
         ));
         $this->FireEvent('RegardingDisplay');
      } catch (Exception $e) {}
   }

   public function RegardingModel() {
      static $RegardingModel = NULL;
      if (is_null($RegardingModel))
         $RegardingModel = new RegardingModel();
      return $RegardingModel;
   }

   public function Setup(){}

}
