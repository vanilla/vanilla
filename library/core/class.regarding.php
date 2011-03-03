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
 * Handles install-side statistics gathering and sending.
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Mark O'Sullivan
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @version @@GARDEN-VERSION@@
 * @namespace Garden.Core
 */

class Gdn_Regarding {

   private $ForeignType;
   private $ForeignID;
   
   private $
   
   public function __construct() {

   }
   
   /* Data sources */
   
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
         $VerifyModel = new 
      } else {
         $Verified = NULL;
      }
   
      if ($Verified || is_null($Verified)) {
         $this->ForeignType = $ThingType;
         $this->ForeignID = $ThingID;
      }
      
      return $this;
   }
   
   /* Actions */
   
   public function PraiseIt() {
      return $this->ActionId('Praise');
   }
   
   public function FlagIt() {
      $this-
      return $this->ActionIt('Flag');
   }
   
   public function ActionIt($ActionType) {
      $this->Type = $ActionType;
      return $this;
   }
   
   /* Meta data */
   
   public function Located($URL) {
      return $this;
   }
   
   public function From($UserID) {
      return $this;
   }
   
   public function Because($Reason) {
      return $this;
   }
   
   /* Finally... */
   
   public function Commit() {
      $RegardingModel = new RegardingModel();
      return FALSE;
   }
   
}