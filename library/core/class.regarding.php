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

class Gdn_Regarding {

   private $Type = NULL;
   private $ForeignType = NULL;
   private $ForeignID = NULL;
   
   private $UserID = NULL;
   private $ForeignURL = NULL;
   private $Comment = NULL;
   
   public function __construct() {
      
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
      } else {
         $Verified = NULL;
      }
   
      if ($Verified || is_null($Verified)) {
         $this->ForeignType = $ThingType;
         $this->ForeignID = $ThingID;
      }
      
      return $this;
   }
   
   /* I'd like to... */
   
   public function PraiseIt() {
      return $this->ActionId('Praise');
   }
   
   public function FlagIt() {
      return $this->ActionIt('Flag');
   }
   
   public function ActionIt($ActionType) {
      $this->Type = $ActionType;
      return $this;
   }
   
   /* ... */
   
   public function ForDiscussion($InCategory) {
      $this->CollaborativeAction[] = 'discussion';
   }
   
   public function ForConversation($WithUsers) {
      $this->CollaborativeAction[] = 'conversation';
   }
   
   public function WithTitle($CollaborativeTitle) {
   
   }
   
   
   
   /* Meta data */
   
   public function Located($URL) {
      $this->ForeignURL = $URL;
      return $this;
   }
   
   public function From($UserID) {
      $this->UserID = $UserID;
      return $this;
   }
   
   public function Because($Reason) {
      $this->Comment = $Reason;
      return $this;
   }
   
   // Filler
   public function Plus() {
      return $this;
   }
   
   /* Finally... */
   
   public function Commit() {
      if (is_null($this->Type))
         throw new Exception(T("Adding a Regarding event requires a type."));
         
      if (is_null($this->ForeignType))
         throw new Exception(T("Adding a Regarding event requires a foreign association type."));
         
      if (is_null($this->ForeignID))
         throw new Exception(T("Adding a Regarding event requires a foreign association id."));
         
      if (is_null($this->UserID))
         $this->UserID = Gdn::Session()->UserID;
         
      $RegardingModel = new RegardingModel();
      $RegardingModel->Save(array(
         'ForeignType'     => $this->ForeignType,
         'ForeignID'       => $this->ForeignID,
         'InsertUserID'    => $this->UserID,
         'DateInserted'    => date('Y-m-d H:i:s')
         
         'ForeignURL'      => $this->ForeignURL,
         'Comment'         => $this->Comment
      ));
      return TRUE;
   }
   
}