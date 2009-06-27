<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Mark O'Sullivan
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Mark O'Sullivan at mark [at] lussumo [dot] com
*/

class ActivityModel extends Model {
   /// <summary>
   /// Class constructor. Defines the related database table name.
   /// </summary>
   /// <param name="Name" type="string" required="false" default="get_class($this)">
   /// An optional parameter that allows you to explicitly define the name of
   /// the table that this model represents. You can also explicitly set this
   /// value with $this->Name.
   /// </param>
   public function __construct() {
      parent::__construct('Activity');
   }
   
   public function ActivityQuery() {
      $this->SQL
         ->Select('a.*')
         ->Select('t.FullHeadline, t.ProfileHeadline, t.AllowComments, t.ShowIcon, t.RouteCode')
         ->Select('t.Name', '', 'ActivityType')
         ->Select('au.Name', '', 'ActivityName')
         ->Select('au.Gender', '', 'ActivityGender')
         ->Select('aup.Name', '', 'ActivityPhoto')
         ->Select('ru.Name', '', 'RegardingName')
         ->From('Activity a')
         ->Join('ActivityType t', 'a.ActivityTypeID = t.ActivityTypeID')
         ->Join('User au', 'a.ActivityUserID = au.UserID')
         ->Join('Photo aup', 'au.PhotoID = aup.PhotoID', 'left')
         ->Join('User ru', 'a.RegardingUserID = ru.UserID', 'left');
   }
   
   public function GetWhere($Field, $Value = '') {
      $this->ActivityQuery();
      return $this->SQL
         ->Where($Field, $Value)
         ->OrderBy('a.DateInserted', 'desc')
         ->Get();
   }
   
   public function Get($UserID = '', $Offset = '0', $Limit = '50') {
      $this->ActivityQuery();
      $this->SQL->Where('a.CommentActivityID', NULL, FALSE, FALSE);
      if ($UserID != '') {
         $this->SQL
            ->BeginWhereGroup()
            ->Where('au.UserID', $UserID)
            ->OrWhere('ru.UserID', $UserID)
            ->EndWhereGroup();
      }
      
      $Session = Gdn::Session();
      if (!$Session->IsValid() || $Session->UserID != $UserID)
         $this->SQL->Where('t.Public', '1');

      return $this->SQL
         ->OrderBy('a.DateInserted', 'desc')
         ->Limit($Limit, $Offset)
         ->Get();
   }
   
   public function GetID($ActivityID) {
      $this->ActivityQuery();
      return $this->SQL
         ->Where('a.ActivityID', $ActivityID)
         ->Get()
         ->FirstRow();
   }
   
   public function GetNotifications($UserID, $Offset = '0', $Limit = '50') {
      $this->ActivityQuery();
      return $this->SQL
         ->Where('RegardingUserID', $UserID)
         ->Where('t.Notify', '1')
         ->Limit($Limit, $Offset)
         ->OrderBy('a.ActivityID', 'desc')
         ->Get();
   }
   
   public function GetComments($FirstActivityID, $LastActivityID, $ActivityUserID = '') {
      $this->ActivityQuery();
      if (is_numeric($ActivityUserID)) {
         $this->SQL
            ->Join('Activity ca', 'a.CommentActivityID = ca.ActivityID')
            ->Where('ca.ActivityUserID', $ActivityUserID);
      }
         
      return $this->SQL
         ->Where('a.CommentActivityID >=', $FirstActivityID)
         ->Where('a.CommentActivityID <=', $LastActivityID)
         ->OrderBy('a.CommentActivityID', 'desc')
         ->OrderBy('a.DateInserted', 'asc')
         ->Get();
   }
   
   public function Add($ActivityUserID, $ActivityType, $Story = '', $RegardingUserID = '', $CommentActivityID = '', $Route = '') {
      // Make sure the user is authenticated
      // Get the ActivityTypeID & see if this is a notification
      $ActivityType = $this->SQL
         ->Select('ActivityTypeID, Notify')
         ->From('ActivityType')
         ->Where('Name', $ActivityType)
         ->Get()
         ->FirstRow();
         
      if ($ActivityType !== FALSE) {
         $ActivityTypeID = $ActivityType->ActivityTypeID;
         $Notify = $ActivityType->Notify == '1';
      } else {
         trigger_error(ErrorMessage(sprintf('Activity type could not be found: %s', $ActivityType), 'ActivityModel', 'Add'), E_USER_ERROR);
      }
      
      // If this is a notification, increment the regardinguserid's count
      if ($Notify) {
         $this->SQL
            ->Update('User')
            ->Set('CountNotifications', 'CountNotifications + 1', FALSE)
            ->Where('UserID', $RegardingUserID)
            ->Put();
      }
      
      $Fields = array('ActivityTypeID' => $ActivityTypeID,
         'ActivityUserID' => $ActivityUserID
      );
      if ($Story != '')
         $Fields['Story'] = $Story;
         
      if ($Route != '')
         $Fields['Route'] = $Route;
         
      if (is_numeric($RegardingUserID))
         $Fields['RegardingUserID'] = $RegardingUserID;
         
      if (is_numeric($CommentActivityID))
         $Fields['CommentActivityID'] = $CommentActivityID;
         
      $this->AddInsertFields($Fields);
      $this->DefineSchema();
      return $this->Insert($Fields); // NOTICE! This will silently fail if there are errors. Developers can figure out what's wrong by dumping the results of $this->ValidationResults();
   }
   
   public function Delete($ActivityID) {
      // Get the activity first
      $Activity = $this->GetID($ActivityID);
      if (is_object($Activity)) {
         $Users = array();
         $Users[] = $Activity->ActivityUserID;
         if (is_numeric($Activity->RegardingUserID) && $Activity->RegardingUserID > 0)
            $Users[] = $Activity->RegardingUserID;
            
         // Update the user's dateupdated field so that profile pages will not
         // be cached and will reflect this deletion.
         $this->SQL->Update('User')
            ->Set('DateUpdated', Format::ToDateTime())
            ->WhereIn('UserID', $Users)
            ->Put();
         
         // Delete comments on the activity item
         parent::Delete(array('CommentActivityID' => $ActivityID), FALSE, TRUE);
         // Delete the activity item
         parent::Delete(array('ActivityID' => $ActivityID));
      }
   }
}