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
 * Vanilla 1 to Vanilla 2 Upgrader.
 */
class UpgradeController extends DashboardController {
   
   public $Uses = array('Form');
   
   /**
    * A message describing the current state of the import.
    */
   public $Message;
   
   public function Index($Step = 0) {
      $this->Permission('Garden.Data.Import'); // This permission doesn't exist, so only users with Admin == '1' will succeed.
      
      $this->AddJsFile('upgrade.js', 'vanilla');
      
      $Step = is_numeric($Step) && $Step >= 0 && $Step < 20 ? $Step : '';
      $Database = Gdn::Database();
      $PDO = $Database->Connection();
      $Construct = $Database->Structure();
      $SQL = $Database->SQL();
      $SourcePrefix = Gdn::Config('Import.SourcePrefix', 'LUM_');
      $DestPrefix = Gdn::Config('Database.DatabasePrefix', '');
      if ($Step == 0) {
         $this->View = 'import';
         if ($this->Form->AuthenticatedPostBack()) {
            // Make sure that all of the destination tables exist (assuming that
            // columns are there if tables are there since they were likely just
            // installed moments ago).
            $DbTables = $Database->SQL()->FetchTables();
            $DestTables = explode(',', 'Role,User,UserRole,Conversation,ConversationMessage,UserConversation,Category,Discussion,Comment,UserDiscussion');
            for ($i = 0; $i < count($DestTables); ++$i) {
               $Table = $DestPrefix.$DestTables[$i];
               if (!InArrayI($Table, $DbTables)) {
                  $this->Form->AddError('The "'.$Table.'" table is required for import.');
                  break;
               }
            }
            
            if ($this->Form->ErrorCount() == 0) {
               // Make sure that all of the source tables & columns exist.
               $SourcePrefix = $this->Form->GetFormValue('SourcePrefix');
               $SourceTables = explode(',', 'Role,User,UserRoleHistory,UserDiscussionWatch,UserBookmark,Category,Discussion,Comment');
               for ($i = 0; $i < count($SourceTables); ++$i) {
                  $Table = $SourcePrefix.$SourceTables[$i];
                  if (!InArrayI($Table, $DbTables)) {
                     $this->Form->AddError('The "'.$Table.'" source table was not found. Are you sure "'.$SourcePrefix.'" is the correct table prefix for your Vanilla 1 tables?');
                     break;
                  }
                  $Columns = $Database->SQL()->FetchColumns($Table);
                  switch ($SourceTables[$i]) {
                     case 'Role':
                        $RequiredColumns = explode(',', 'RoleID,Name,Description');
                        break;
                     case 'User':
                        $RequiredColumns = explode(',', 'UserID,RoleID,Name,Email,UtilizeEmail,CountVisit,Discovery,DateFirstVisit,DateLastActive,DateFirstVisit,DateLastActive,CountDiscussions,CountComments');
                        break;
                     case 'UserRoleHistory':
                        $RequiredColumns = explode(',', 'UserID,RoleID,AdminUserID,Notes,Date');
                        break;
                     case 'UserDiscussionWatch':
                        $RequiredColumns = explode(',', 'UserID,DiscussionID,CountComments,LastViewed');
                        break;
                     case 'UserBookmark':
                        $RequiredColumns = explode(',', 'UserID,DiscussionID');
                        break;
                     case 'Category':
                        $RequiredColumns = explode(',', 'CategoryID,Name,Description,Priority');
                        break;
                     case 'Discussion':
                        $RequiredColumns = explode(',', 'DiscussionID,CategoryID,AuthUserID,LastUserID,WhisperUserID,Active,Name,CountComments,Closed,Sticky,Sink,DateCreated,DateLastActive');
                        break;
                     case 'Comment':
                        $RequiredColumns = explode(',', 'CommentID,DiscussionID,AuthUserID,EditUserID,WhisperUserID,Deleted,Body,FormatType,DateCreated,DateEdited');
                        break;
                     default:
                        $RequiredColumns = array();
                        break;                     
                  }
                  if (is_array($RequiredColumns)) {
                     for ($j = 0; $j < count($RequiredColumns); ++ $j) {
                        if (!InArrayI($RequiredColumns[$j], $Columns)) {
                           $this->Form->AddError('The "'.$Table.'" source table does not have the "'.$RequiredColumns[$j].'" column.');
                           break;
                        }
                     }
                  }
               }
            }
            // If there were no errors...
            if ($this->Form->ErrorCount() == 0) {
               // Save the sourceprefix
               SaveToConfig('Garden.Import.SourcePrefix', $SourcePrefix);
               
               // Proceed with the next step
               $this->Message = T('<strong>1/19</strong> Checking source & destination tables.');
               $this->View = 'index';
               $this->RedirectUrl = Url('/upgrade/1');
               if ($this->DeliveryType() == DELIVERY_TYPE_ALL)
                  Redirect('/upgrade/1');
            }
         } else {
            $this->Form->SetFormValue('SourcePrefix', $SourcePrefix);
         }
      } else if ($Step == 1) {
         // 1. Add Import IDs to various tables where necessary 
         $Construct->Table('Role')->Column('ImportID', 'int', 11, FALSE, NULL, 'key')->Set();
         $Construct->DatabasePrefix($SourcePrefix);
         $Construct->Table('Comment')->Column('ConversationID', 'int', 11, FALSE, NULL, 'key')->Set();
         $Construct->DatabasePrefix($DestPrefix);
         
         $this->Message = T('<strong>2/19</strong> Importing roles.');
         $this->RedirectUrl = Url('/upgrade/2');
      } else if ($Step == 2) {
         // 2. Move roles from old database into new one.
         $RoleModel = new RoleModel();
         // Get the old roles
         $OldRoles = $Database->Query('select * from '.$SourcePrefix.'Role');
         // Loop through each, inserting if it doesn't exist and updating ImportID if it does
         foreach ($OldRoles->Result() as $OldRole) {
            $RoleData = $Database->Query("select * from ".$DestPrefix."Role where Name = ".$PDO->quote($OldRole->Name));
            if ($RoleData->NumRows() == 0) {
               $Role = array();
               $Role['ImportID'] = $OldRole->RoleID;
               $Role['Name'] = $OldRole->Name;
               $Role['Description'] = $OldRole->Description;
               $RoleModel->Save($Role);
            } else {
               $Database->Query("update ".$DestPrefix."Role set ImportID = '".$OldRole->RoleID."' where RoleID = ".$RoleData->FirstRow()->RoleID);
            }
         }
         
         $this->Message = T('<strong>3/19</strong> Importing users.');
         $this->RedirectUrl = Url('/upgrade/3');
      } else if ($Step == 3) {
         // 3. Import users
         
         // Grab the current admin user.
         $AdminUser = $SQL->GetWhere('User', array('Admin' => 1))->FirstRow(DATASET_TYPE_ARRAY);
         // Delete the users.
         $SQL->Delete('User', array('UserID <>' => 0)); // where kludge
         
         $Database->Query("insert into ".$DestPrefix."User
         (UserID, Name, Password, Email, ShowEmail,    Gender, CountVisits, CountInvitations, InviteUserID, DiscoveryText, Preferences, Permissions, Attributes, DateSetInvitations, DateOfBirth, DateFirstVisit, DateLastActive, DateInserted,   DateUpdated,    HourOffset, About, CountNotifications, CountUnreadConversations, CountDiscussions, CountUnreadDiscussions, CountComments, CountDrafts, CountBookmarks) select
          UserID, Name, Password, Email, UtilizeEmail, 'm',    CountVisit,  0,                null,         Discovery,     null,        null,        null,       null,               null,        DateFirstVisit, DateLastActive, DateFirstVisit, DateLastActive, 0,          null,  0,                  0,                        CountDiscussions, 0,                      CountComments, 0,           0
         from ".$SourcePrefix."User");
         
         // Check to see if there is an existing user in the database that should now be root.
         $User = $SQL->GetWhere('User', array('Name' => $AdminUser['Name']))->FirstRow(DATASET_TYPE_ARRAY);
         if(is_array($User)) {
            $NewUserID = $User['UserID'];
            $SQL->Put(
               'User',
               array(
                  'Password' => $AdminUser['Password'],
                  'Admin' => 1),
               array('UserID' => $User['UserID']));
         } else {
            unset($AdminUser['UserID']);
            $NewUserID = $SQL->Insert('User', $AdminUser);
         }
         
         Gdn::Session()->UserID = $NewUserID;
         Gdn::Session()->User->UserID = $NewUserID;
         Gdn::Authenticator()->SetIdentity($NewUserID);

         $this->Message = T('<strong>4/19</strong> Importing role histories.');
         $this->RedirectUrl = Url('/upgrade/4');
      } else if ($Step == 4) {
         // 4. Import user role relationships
         $SQL->Delete('UserRole', array('UserID <>' => 0));
         $Database->Query("insert into ".$DestPrefix."UserRole
         (UserID, RoleID)
         select u.UserID, r.RoleID
         from ".$SourcePrefix."User u
         inner join ".$DestPrefix."Role r
            on u.RoleID = r.ImportID");

         $this->Message = T('<strong>5/19</strong> Importing user/role relationships.');
         $this->RedirectUrl = Url('/upgrade/5');
      } else if ($Step == 5) {
         // 5. Import user role history into activity table
         $Database->Query("insert into ".$DestPrefix."Activity
         (ActivityTypeID, ActivityUserID, RegardingUserID, Story, InsertUserID, DateInserted)
         select 8, rh.AdminUserID, rh.UserID, concat('Assigned to ', r.Name, ' Role <blockquote>', rh.Notes, '</blockquote>'), rh.AdminUserID, rh.Date
         from ".$SourcePrefix."UserRoleHistory rh
         inner join ".$DestPrefix."Role r
            on rh.RoleID = r.ImportID
         order by rh.Date asc");

         $this->Message = T('<strong>6/19</strong> Preparing whispers.');
         $this->RedirectUrl = Url('/upgrade/6');
      } else if ($Step == 6) {
         // 6. Update the WhisperUserID on all comments that are within whispered discussions
         $Database->Query("update ".$SourcePrefix."Comment c
         join ".$SourcePrefix."Discussion d
           on c.DiscussionID = d.DiscussionID
         set c.WhisperUserID = d.WhisperUserID
         where d.WhisperUserID > 0
           and c.AuthUserID <> d.WhisperUserID");
         
         $Database->Query("update ".$SourcePrefix."Comment c
         join ".$SourcePrefix."Discussion d
           on c.DiscussionID = d.DiscussionID
         set c.WhisperUserID = d.AuthUserID
         where d.WhisperUserID > 0
           and c.AuthUserID <> d.AuthUserID");
         
         $this->Message = T('<strong>7/19</strong> Creating conversations.');
         $this->RedirectUrl = Url('/upgrade/7');
      } else if ($Step == 7) {
         // 7. Create conversations
         $Database->Query("insert into ".$DestPrefix."Conversation
         (InsertUserID, DateInserted, UpdateUserID, DateUpdated, Contributors)
         select AuthUserID, now(), WhisperUserID, now(), ''
         from ".$SourcePrefix."Comment
         where WhisperUserID > 0
         group by AuthUserID, WhisperUserID");

         // 7b. Remove duplicate combinations
         $Database->Query("delete ".$DestPrefix."Conversation c
         from ".$DestPrefix."Conversation c
         join ".$DestPrefix."Conversation c2
           on c.InsertUserID = c2.UpdateUserID
           and c.UpdateUserID = c2.InsertUserID
         where c.ConversationID > c2.ConversationID");
         
         $this->Message = T('<strong>8/19</strong> Preparing conversations messages.');
         $this->RedirectUrl = Url('/upgrade/8');
      } else if ($Step == 8) {
         // 8. Update old comment table with conversation ids
         $Database->Query("update ".$SourcePrefix."Comment cm
         inner join ".$DestPrefix."Conversation cn
           on cm.AuthUserID = cn.InsertUserID
           and cm.WhisperUserID = cn.UpdateUserID
         set cm.ConversationID = cn.ConversationID");

         $Database->Query("update ".$SourcePrefix."Comment cm
         inner join ".$DestPrefix."Conversation cn
           on cm.WhisperUserID = cn.InsertUserID
           and cm.AuthUserID = cn.UpdateUserID
         set cm.ConversationID = cn.ConversationID");

         $this->Message = T('<strong>9/19</strong> Transforming whispers into conversations.');
         $this->RedirectUrl = Url('/upgrade/9');
      } else if ($Step == 9) {
         // 9. Insert whispers as conversation messages
         $Database->Query("insert into ".$DestPrefix."ConversationMessage
         (ConversationID, Body, InsertUserID, DateInserted)
         select ConversationID, Body, AuthUserID, DateCreated
         from ".$SourcePrefix."Comment
         where ConversationID > 0");

         $this->Message = T('<strong>10/19</strong> Finalizing conversations.');
         $this->RedirectUrl = Url('/upgrade/10');
      } else if ($Step == 10) {
         // 10. Insert the userconversation records so that messages are linked to conversations
         $Database->Query("insert into ".$DestPrefix."UserConversation
         (UserID, ConversationID, CountNewMessages, CountMessages, LastMessageID, DateLastViewed)
         select InsertUserID, ConversationID, 0, 0, max(MessageID), null
         from ".$DestPrefix."ConversationMessage
         group by InsertUserID, ConversationID");

         $this->Message = T('<strong>11/19</strong> Finalizing whisper messages.');
         $this->RedirectUrl = Url('/upgrade/11');
      } else if ($Step == 11) {
         // 11. Update the conversation record fields
         $Database->Query("update ".$DestPrefix."Conversation c
         join (
           select ConversationID, min(MessageID) as FirstMessageID, min(DateInserted) as DateInserted
           from ".$DestPrefix."ConversationMessage
           group by ConversationID
         ) cm
           on c.ConversationID = cm.ConversationID
         set c.FirstMessageID = cm.FirstMessageID,
           c.DateInserted = cm.DateInserted");
         
         $Database->Query("update ".$DestPrefix."Conversation c
         join (
           select ConversationID, max(MessageID) as LastMessageID
           from ".$DestPrefix."ConversationMessage
           group by ConversationID
         ) cm
           on c.ConversationID = cm.ConversationID
         join ".$DestPrefix."ConversationMessage lm
           on cm.LastMessageID = lm.MessageID
         set c.UpdateUserID = lm.InsertUserID,
           c.DateUpdated = lm.DateInserted");

         // Fudge your way back from the messages
         $Database->Query("update ".$DestPrefix."Conversation c
         join ".$DestPrefix."ConversationMessage m
           on c.FirstMessageID = m.MessageID
         set c.InsertUserID = m.InsertUserID");

         // Update the UserConversation.LastMessageID records
         // (ie. the last message in a conversation by someone other than the userconversation.userid person)
         $Database->Query("update ".$DestPrefix."UserConversation uc
         join (
           select ConversationID, InsertUserID, max(MessageID) as LastMessageID
           from ".$DestPrefix."ConversationMessage
           group by ConversationID, InsertUserID
         ) m
           on uc.ConversationId = m.ConversationID
           and uc.UserID <> m.InsertUserID
         set uc.LastMessageID = m.LastMessageID");

         // Update the message count for all users and all conversations
         $Database->Query("update ".$DestPrefix."UserConversation uc
         join (
           select ConversationID, count(MessageID) as CountMessages
           from ".$DestPrefix."ConversationMessage
           group by ConversationID
         ) m
           on uc.ConversationID = m.ConversationID
         set uc.CountMessages = m.CountMessages");

         $this->Message = T('<strong>12/19</strong> Importing discussion categories.');
         $this->RedirectUrl = Url('/upgrade/12');
      } else if ($Step == 12) {
         // Delete old categories.
         $SQL->Delete('Category', array('CategoryID <>' => 0));
         
         // 12. Import Categories
         $Database->Query("insert into ".$DestPrefix."Category
         (CategoryID, Name, Description, Sort, InsertUserID, UpdateUserID, DateInserted, DateUpdated)
         select CategoryID, left(Name,30), Description, Priority, 1, 1, now(), now()
         from ".$SourcePrefix."Category");

         $this->Message = T('<strong>13/19</strong> Importing discussions.');
         $this->RedirectUrl = Url('/upgrade/13');
      } else if ($Step == 13) {
         // 13. Import Discussions
         //$Database->Query('alter table '.$SourcePrefix.'Discussion CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci');
         //$Database->Query('alter table '.$SourcePrefix.'Comment CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci');
         
         $Database->Query("insert into ".$DestPrefix."Discussion
         (DiscussionID, CategoryID, InsertUserID, UpdateUserID, Name, CountComments, Closed, Announce, Sink, DateInserted, DateUpdated, DateLastComment)
         select DiscussionID, CategoryID, AuthUserID, LastUserID, Name, CountComments, Closed, Sticky, Sink, DateCreated, DateLastActive, DateLastActive
         from ".$SourcePrefix."Discussion
         where WhisperUserID = 0
            and Active = '1'");

         $this->Message = T('<strong>14/19</strong> Importing comments.');
         $this->RedirectUrl = Url('/upgrade/14');
      } else if ($Step == 14) {
         // 14. Import Comments
         $Database->Query("insert into ".$DestPrefix."Comment
         (CommentID, DiscussionID, InsertUserID, UpdateUserID, Body, Format, DateInserted, DateUpdated)
         select CommentID, DiscussionID, AuthUserID, EditUserID, Body, case FormatType when 'Text' then 'Display' else FormatType end, DateCreated, DateEdited
         from ".$SourcePrefix."Comment
         where (WhisperUserID is null or WhisperUserID = 0)
            and Deleted = '0'");

         $this->Message = T('<strong>15/19</strong> Finalizing discussions.');
         $this->RedirectUrl = Url('/upgrade/15');
      } else if ($Step == 15) {
         // 15. Update Discussions with first & last comment ids
         $Database->Query("update ".$DestPrefix."Discussion d
         join (
           select DiscussionID, min(CommentID) as FirstCommentID
           from ".$DestPrefix."Comment
           group by DiscussionID
         ) c
           on d.DiscussionID = c.DiscussionID
         set d.FirstCommentID = c.FirstCommentID");
         
         $Database->Query("update ".$DestPrefix."Discussion d
         join (
           select DiscussionID, max(CommentID) as LastCommentID
           from ".$DestPrefix."Comment
           group by DiscussionID
         ) c
           on d.DiscussionID = c.DiscussionID
         set d.LastCommentID = c.LastCommentID");
         
         // Update the CountDiscussions column on the category table
         $Database->Query("update ".$DestPrefix."Category c
         join (
           select CategoryID, count(DiscussionID) as CountDiscussions
           from ".$DestPrefix."Discussion
           group by CategoryID
         ) cc
           on c.CategoryID = cc.CategoryID
         set c.CountDiscussions = cc.CountDiscussions");

         $this->Message = T('<strong>16/19</strong> Importing bookmarks & watch data.');
         $this->RedirectUrl = Url('/upgrade/16');
      } else if ($Step == 16) {
         // 16. Import UserDiscussion (watch & bookmark data)
         $Database->Query("insert into ".$DestPrefix."UserDiscussion
         (UserID, DiscussionID, CountComments, DateLastViewed, Bookmarked)
         select ow.UserID, ow.DiscussionID, ow.CountComments, ow.LastViewed, if(isnull(ob.DiscussionID), '0', '1') as Bookmarked
         from ".$SourcePrefix."UserDiscussionWatch ow
         left join ".$SourcePrefix."UserBookmark ob
            on ow.DiscussionID = ob.DiscussionID
            and ow.UserID = ob.UserID
         left join ".$SourcePrefix."Discussion od
            on od.DiscussionID = ow.DiscussionID
         where od.Active = '1'");

         $this->Message = T('<strong>17/19</strong> Removing import structure.');
         $this->RedirectUrl = Url('/upgrade/17');
      } else if ($Step == 17) {
         // 17. Remove temp columns
         $Construct->Table('Role')->DropColumn('ImportID');
         $Construct->DatabasePrefix($SourcePrefix);
         $Construct->Table('Comment')->DropColumn('ConversationID');
         $Construct->DatabasePrefix($DestPrefix);

         $this->Message = T('<strong>18/19</strong> Restoring original comment structure.');
         $this->RedirectUrl = Url('/upgrade/18');
      } else if ($Step == 18) {
         // 18. remove whisperuserids from old comment table where the entire discussion is whispered
         $Database->Query("update ".$SourcePrefix."Comment c
         inner join ".$SourcePrefix."Discussion d
            on c.DiscussionID = d.DiscussionID
         set c.WhisperUserID = null
         where d.WhisperUserID > 0");

         $this->Message = T('<strong>19/19</strong> Finished.');
         $this->RedirectUrl = Url('/upgrade/19');
      } else if ($Step == 19) {
         // Finished!
         $this->RedirectUrl = 'Finished';
         $this->View = 'finished';
      }
      
      $this->SetJson('NextUrl', $this->RedirectUrl);
      $this->RedirectUrl = '';
      
      $this->MasterView = 'setup';
      $this->Render();
   }   
   
   public function OldIndex($Step = 0) {
      $this->Permission('Garden.Data.Import'); // This permission doesn't exist, so only users with Admin == '1' will succeed.
      
      $this->AddJsFile('upgrade.js', 'vanilla');
      
      $Step = is_numeric($Step) && $Step >= 0 && $Step < 20 ? $Step : '';
      $Database = Gdn::Database();
      $Construct = $Database->Structure();
      $PDO = $Database->Connection();
      $SourcePrefix = Gdn::Config('Garden.Import.SourcePrefix', 'LUM_');
      $DestPrefix = Gdn::Config('Database.DatabasePrefix', '');
      if ($Step == 0) {
         $this->View = 'import';
         if ($this->Form->AuthenticatedPostBack()) {
            // Make sure that all of the destination tables exist (assuming that
            // columns are there if tables are there since they were likely just
            // installed moments ago).
            $DbTables = $Database->SQL()->FetchTables();
            $DestTables = explode(',', 'Role,User,UserRole,Conversation,ConversationMessage,UserConversation,Category,Discussion,Comment,UserDiscussion');
            for ($i = 0; $i < count($DestTables); ++$i) {
               $Table = $DestPrefix.$DestTables[$i];
               if (!InArrayI($Table, $DbTables)) {
                  $this->Form->AddError('The "'.$Table.'" table is required for import.');
                  break;
               }
            }
            
            if ($this->Form->ErrorCount() == 0) {
               // Make sure that all of the source tables & columns exist.
               $SourcePrefix = $this->Form->GetFormValue('SourcePrefix');
               $SourceTables = explode(',', 'Role,User,UserRoleHistory,UserDiscussionWatch,UserBookmark,Category,Discussion,Comment');
               for ($i = 0; $i < count($SourceTables); ++$i) {
                  $Table = $SourcePrefix.$SourceTables[$i];
                  if (!InArrayI($Table, $DbTables)) {
                     $this->Form->AddError('The "'.$Table.'" source table was not found. Are you sure "'.$SourcePrefix.'" is the correct table prefix for your Vanilla 1 tables?');
                     break;
                  }
                  $Columns = $Database->SQL()->FetchColumns($Table);
                  switch ($SourceTables[$i]) {
                     case 'Role':
                        $RequiredColumns = explode(',', 'RoleID,Name,Description');
                        break;
                     case 'User':
                        $RequiredColumns = explode(',', 'UserID,RoleID,Name,Email,UtilizeEmail,CountVisit,Discovery,DateFirstVisit,DateLastActive,DateFirstVisit,DateLastActive,CountDiscussions,CountComments');
                        break;
                     case 'UserRoleHistory':
                        $RequiredColumns = explode(',', 'UserID,RoleID,AdminUserID,Notes,Date');
                        break;
                     case 'UserDiscussionWatch':
                        $RequiredColumns = explode(',', 'UserID,DiscussionID,CountComments,LastViewed');
                        break;
                     case 'UserBookmark':
                        $RequiredColumns = explode(',', 'UserID,DiscussionID');
                        break;
                     case 'Category':
                        $RequiredColumns = explode(',', 'CategoryID,Name,Description,Priority');
                        break;
                     case 'Discussion':
                        $RequiredColumns = explode(',', 'DiscussionID,CategoryID,AuthUserID,LastUserID,WhisperUserID,Active,Name,CountComments,Closed,Sticky,Sink,DateCreated,DateLastActive');
                        break;
                     case 'Comment':
                        $RequiredColumns = explode(',', 'CommentID,DiscussionID,AuthUserID,EditUserID,WhisperUserID,Deleted,Body,FormatType,DateCreated,DateEdited');
                        break;
                     default:
                        $RequiredColumns = array();
                        break;                     
                  }
                  if (is_array($RequiredColumns)) {
                     for ($j = 0; $j < count($RequiredColumns); ++ $j) {
                        if (!InArrayI($RequiredColumns[$j], $Columns)) {
                           $this->Form->AddError('The "'.$Table.'" source table does not have the "'.$RequiredColumns[$j].'" column.');
                           break;
                        }
                     }
                  }
               }
            }
            // If there were no errors...
            if ($this->Form->ErrorCount() == 0) {
               // Save the sourceprefix
               SaveToConfig('Garden.Import.SourcePrefix', $SourcePrefix);
               
               // Proceed with the next step
               $this->Message = T('<strong>1/19</strong> Checking source & destination tables.');
               $this->View = 'index';
               $this->RedirectUrl = Url('/upgrade/1');
               if ($this->DeliveryType() == DELIVERY_TYPE_ALL)
                  Redirect('/upgrade/1');
            }
         }
      } else if ($Step == 1) {
         // 1. Add Import IDs to various tables where necessary 
         $Construct->Table('Role')->Column('ImportID', 'int', TRUE, 'key')->Set();
         $Construct->Table('User')->Column('ImportID', 'int', TRUE, 'key')->Set();
         $Construct->Table('Category')->Column('ImportID', 'int', TRUE, 'key')->Set();
         $Construct->Table('Discussion')->Column('ImportID', 'int', TRUE, 'key')->Set();
         $Construct->DatabasePrefix($SourcePrefix);
         $Construct->Table('Comment')->Column('ConversationID', 'int', TRUE, 'key')->Set();
         $Construct->DatabasePrefix($DestPrefix);
         
         $this->Message = T('<strong>2/19</strong> Preparing tables for import.');
         $this->RedirectUrl = Url('/upgrade/2');
      } else if ($Step == 2) {
         // 2. Move roles from old database into new one.
         $RoleModel = new RoleModel();
         // Get the old roles
         $OldRoles = $Database->Query('select * from '.$SourcePrefix.'Role');
         // Loop through each, inserting if it doesn't exist and updating ImportID if it does
         foreach ($OldRoles->Result() as $OldRole) {
            $RoleData = $Database->Query("select * from ".$DestPrefix."Role where Name = ".$PDO->quote($OldRole->Name));
            if ($RoleData->NumRows() == 0) {
               $Role = array();
               $Role['ImportID'] = $OldRole->RoleID;
               $Role['Name'] = $OldRole->Name;
               $Role['Description'] = $OldRole->Description;
               $RoleModel->Save($Role);
            } else {
               $Database->Query("update ".$DestPrefix."Role set ImportID = '".$OldRole->RoleID."' where RoleID = ".$RoleData->FirstRow()->RoleID);
            }
         }
         
         $this->Message = T('<strong>3/19</strong> Importing roles.');
         $this->RedirectUrl = Url('/upgrade/3');
      } else if ($Step == 3) {
         // 3. Import users
         $Database->Query("insert into ".$DestPrefix."User
         (Name, Password, Email, ShowEmail,    Gender, CountVisits, CountInvitations, InviteUserID, DiscoveryText, Preferences, Permissions, Attributes, DateSetInvitations, DateOfBirth, DateFirstVisit, DateLastActive, DateInserted,   DateUpdated,    HourOffset, About, CountNotifications, CountUnreadConversations, CountDiscussions, CountUnreadDiscussions, CountComments, CountDrafts, CountBookmarks, ImportID) select
          Name, Password, Email, UtilizeEmail, 'm',    CountVisit,  0,                null,         Discovery,     null,        null,        null,       null,               null,        DateFirstVisit, DateLastActive, DateFirstVisit, DateLastActive, 0,          null,  0,                  0,                        CountDiscussions, 0,                      CountComments, 0,           0,              UserID
         from ".$SourcePrefix."User");

         $this->Message = T('<strong>4/19</strong> Importing users.');
         $this->RedirectUrl = Url('/upgrade/4');
      } else if ($Step == 4) {
         // 4. Import user role relationships
         $Database->Query("insert into ".$DestPrefix."UserRole
         (UserID, RoleID)
         select u.UserID, r.RoleID
         from ".$DestPrefix."User u
         inner join ".$SourcePrefix."User ou
            on u.ImportID = ou.UserID
         inner join ".$DestPrefix."Role r
            on ou.RoleID = r.ImportID");

         $this->Message = T('<strong>5/19</strong> Importing user/role relationships.');
         $this->RedirectUrl = Url('/upgrade/5');
      } else if ($Step == 5) {
         // 5. Import user role history into activity table
         $Database->Query("insert into ".$DestPrefix."Activity
         (ActivityTypeID, ActivityUserID, RegardingUserID, Story, InsertUserID, DateInserted)
         select 9, au.UserID, nu.UserID, concat('Assigned to ', r.Name, ' Role <blockquote>', rh.Notes, '</blockquote>'), au.UserID, rh.Date
         from ".$SourcePrefix."UserRoleHistory rh
         inner join ".$SourcePrefix."Role r
            on rh.RoleID = r.RoleID
         inner join ".$SourcePrefix."User u
            on rh.UserID = u.UserID
         inner join ".$DestPrefix."User nu
            on u.UserID = nu.ImportID
         inner join ".$DestPrefix."User au
            on rh.AdminUserID = au.ImportID
         order by rh.Date asc");

         $this->Message = T('<strong>6/19</strong> Importing role histories.');
         $this->RedirectUrl = Url('/upgrade/6');
      } else if ($Step == 6) {
         // 6. Update the WhisperUserID on all comments that are within whispered discussions
         $Database->Query("update ".$SourcePrefix."Comment c
         join ".$SourcePrefix."Discussion d
           on c.DiscussionID = d.DiscussionID
         set c.WhisperUserID = d.WhisperUserID
         where d.WhisperUserID > 0
           and c.AuthUserID <> d.WhisperUserID");
         
         $Database->Query("update ".$SourcePrefix."Comment c
         join ".$SourcePrefix."Discussion d
           on c.DiscussionID = d.DiscussionID
         set c.WhisperUserID = d.AuthUserID
         where d.WhisperUserID > 0
           and c.AuthUserID <> d.AuthUserID");
         
         $this->Message = T('<strong>7/19</strong> Preparing whispers.');
         $this->RedirectUrl = Url('/upgrade/7');
      } else if ($Step == 7) {
         // 7. Create conversations
         $Database->Query("insert into ".$DestPrefix."Conversation
         (InsertUserID, DateInserted, UpdateUserID, DateUpdated, Contributors)
         select AuthUserID, now(), WhisperUserID, now(), ''
         from ".$SourcePrefix."Comment
         where WhisperUserID > 0
         group by AuthUserID, WhisperUserID");

         // 7b. Remove duplicate combinations
         $Database->Query("delete ".$DestPrefix."Conversation c
         from ".$DestPrefix."Conversation c
         join ".$DestPrefix."Conversation c2
           on c.InsertUserID = c2.UpdateUserID
           and c.UpdateUserID = c2.InsertUserID
         where c.ConversationID > c2.ConversationID");
         
         $this->Message = T('<strong>8/19</strong> Creating conversations.');
         $this->RedirectUrl = Url('/upgrade/8');
      } else if ($Step == 8) {
         // 8. Update old comment table with conversation ids
         $Database->Query("update ".$SourcePrefix."Comment cm
         inner join ".$DestPrefix."Conversation cn
           on cm.AuthUserID = cn.InsertUserID
           and cm.WhisperUserID = cn.UpdateUserID
         set cm.ConversationID = cn.ConversationID");

         $Database->Query("update ".$SourcePrefix."Comment cm
         inner join ".$DestPrefix."Conversation cn
           on cm.WhisperUserID = cn.InsertUserID
           and cm.AuthUserID = cn.UpdateUserID
         set cm.ConversationID = cn.ConversationID");

         $this->Message = T('<strong>9/19</strong> Preparing conversations messages.');
         $this->RedirectUrl = Url('/upgrade/9');
      } else if ($Step == 9) {
         // 9. Insert whispers as conversation messages
         $Database->Query("insert into ".$DestPrefix."ConversationMessage
         (ConversationID, Body, InsertUserID, DateInserted)
         select cm.ConversationID, cm.Body, nu.UserID, cm.DateCreated
         from ".$SourcePrefix."Comment cm
         join ".$SourcePrefix."User ou
           on cm.AuthUserID = ou.UserID
         inner join ".$DestPrefix."User nu
           on nu.ImportID = ou.UserID
         where cm.ConversationID > 0");

         $this->Message = T('<strong>10/19</strong> Transforming whispers into conversations.');
         $this->RedirectUrl = Url('/upgrade/10');
      } else if ($Step == 10) {
         // 10. Insert the userconversation records so that messages are linked to conversations
         $Database->Query("insert into ".$DestPrefix."UserConversation
         (UserID, ConversationID, CountNewMessages, CountMessages, LastMessageID, DateLastViewed)
         select InsertUserID, ConversationID, 0, 0, max(MessageID), null
         from ".$DestPrefix."ConversationMessage
         group by InsertUserID, ConversationID");

         $this->Message = T('<strong>11/19</strong> Finalizing whisper messages.');
         $this->RedirectUrl = Url('/upgrade/11');
      } else if ($Step == 11) {
         // 11. Update the conversation record fields
         $Database->Query("update ".$DestPrefix."Conversation c
         join (
           select ConversationID, min(MessageID) as FirstMessageID, min(DateInserted) as DateInserted
           from ".$DestPrefix."ConversationMessage
           group by ConversationID
         ) cm
           on c.ConversationID = cm.ConversationID
         set c.FirstMessageID = cm.FirstMessageID,
           c.DateInserted = cm.DateInserted");
         
         $Database->Query("update ".$DestPrefix."Conversation c
         join (
           select ConversationID, max(MessageID) as LastMessageID
           from ".$DestPrefix."ConversationMessage
           group by ConversationID
         ) cm
           on c.ConversationID = cm.ConversationID
         join ".$DestPrefix."ConversationMessage lm
           on cm.LastMessageID = lm.MessageID
         set c.UpdateUserID = lm.InsertUserID,
           c.DateUpdated = lm.DateInserted");

         // Fudge your way back from the messages
         $Database->Query("update ".$DestPrefix."Conversation c
         join ".$DestPrefix."ConversationMessage m
           on c.FirstMessageID = m.MessageID
         set c.InsertUserID = m.InsertUserID");

         // Update the UserConversation.LastMessageID records
         // (ie. the last message in a conversation by someone other than the userconversation.userid person)
         $Database->Query("update ".$DestPrefix."UserConversation uc
         join (
           select ConversationID, InsertUserID, max(MessageID) as LastMessageID
           from ".$DestPrefix."ConversationMessage
           group by ConversationID, InsertUserID
         ) m
           on uc.ConversationId = m.ConversationID
           and uc.UserID <> m.InsertUserID
         set uc.LastMessageID = m.LastMessageID");

         // Update the message count for all users and all conversations
         $Database->Query("update ".$DestPrefix."UserConversation uc
         join (
           select ConversationID, count(MessageID) as CountMessages
           from ".$DestPrefix."ConversationMessage
           group by ConversationID
         ) m
           on uc.ConversationID = m.ConversationID
         set uc.CountMessages = m.CountMessages");

         $this->Message = T('<strong>12/19</strong> Finalizing conversations.');
         $this->RedirectUrl = Url('/upgrade/12');
      } else if ($Step == 12) {
         // 12. Import Categories
         $Database->Query("insert into ".$DestPrefix."Category
         (Name, Description, Sort, InsertUserID, UpdateUserID, DateInserted, DateUpdated, ImportID)
         select left(Name,30), Description, Priority, 1, 1, now(), now(), CategoryID
         from ".$SourcePrefix."Category");

         $this->Message = T('<strong>13/19</strong> Importing discussion categories.');
         $this->RedirectUrl = Url('/upgrade/13');
      } else if ($Step == 13) {
         // 13. Import Discussions
         $Database->Query("insert into ".$DestPrefix."Discussion
         (ImportID, CategoryID, InsertUserID, UpdateUserID, Name, CountComments, Closed, Announce, Sink, DateInserted, DateUpdated, DateLastComment)
         select od.DiscussionID, nc.CategoryID, niu.UserID, nuu.UserID, od.Name, od.CountComments, od.Closed, od.Sticky, od.Sink, od.DateCreated, od.DateLastActive, od.DateLastActive
         from ".$SourcePrefix."Discussion od
         join ".$DestPrefix."Category nc
            on od.CategoryID = nc.ImportID
         join ".$DestPrefix."User niu
            on od.AuthUserID = niu.ImportID
         join ".$DestPrefix."User nuu
            on od.LastUserID = nuu.ImportID
         where od.WhisperUserID = 0
            and od.Active = '1'");

         $this->Message = T('<strong>14/19</strong> Importing discussions.');
         $this->RedirectUrl = Url('/upgrade/14');
      } else if ($Step == 14) {
         // 14. Import Comments
         $Database->Query("insert into ".$DestPrefix."Comment
         (DiscussionID, InsertUserID, UpdateUserID, Body, Format, DateInserted, DateUpdated)
         select nd.DiscussionID, niu.UserID, nuu.UserID, Body, case FormatType when 'Text' then 'Display' else FormatType end, oc.DateCreated, oc.DateEdited
         from ".$SourcePrefix."Comment oc
         join ".$DestPrefix."Discussion nd
            on oc.DiscussionID = nd.ImportID
         join ".$DestPrefix."User niu
            on oc.AuthUserID = niu.ImportID
         left join ".$DestPrefix."User nuu
            on oc.EditUserID = nuu.ImportID
         where (oc.WhisperUserID is null or oc.WhisperUserID = 0)
            and oc.Deleted = '0'");

         $this->Message = T('<strong>15/19</strong> Importing comments.');
         $this->RedirectUrl = Url('/upgrade/15');
      } else if ($Step == 15) {
         // 15. Update Discussions with first & last comment ids
         $Database->Query("update ".$DestPrefix."Discussion d
         join (
           select DiscussionID, min(CommentID) as FirstCommentID
           from ".$DestPrefix."Comment
           group by DiscussionID
         ) c
           on d.DiscussionID = c.DiscussionID
         set d.FirstCommentID = c.FirstCommentID");
         
         $Database->Query("update ".$DestPrefix."Discussion d
         join (
           select DiscussionID, max(CommentID) as LastCommentID
           from ".$DestPrefix."Comment
           group by DiscussionID
         ) c
           on d.DiscussionID = c.DiscussionID
         set d.LastCommentID = c.LastCommentID");
         
         // Update the CountDiscussions column on the category table
         $Database->Query("update ".$DestPrefix."Category c
         join (
           select CategoryID, count(DiscussionID) as CountDiscussions
           from ".$DestPrefix."Discussion
           group by CategoryID
         ) cc
           on c.CategoryID = cc.CategoryID
         set c.CountDiscussions = cc.CountDiscussions");

         $this->Message = T('<strong>16/19</strong> Finalizing discussions.');
         $this->RedirectUrl = Url('/upgrade/16');
      } else if ($Step == 16) {
         // 16. Import UserDiscussion (watch & bookmark data)
         $Database->Query("insert into ".$DestPrefix."UserDiscussion
         (UserID, DiscussionID, CountComments, DateLastViewed, Bookmarked)
         select nu.UserID, nd.DiscussionID, ow.CountComments, ow.LastViewed, if(isnull(ob.DiscussionID), '0', '1') as Bookmarked
         from ".$SourcePrefix."UserDiscussionWatch ow
         join ".$SourcePrefix."Discussion od
            on ow.DiscussionID = od.DiscussionID
         join ".$DestPrefix."Discussion nd
            on od.DiscussionID = nd.ImportID
         join ".$DestPrefix."User nu
            on ow.UserID = nu.ImportID
         left join ".$SourcePrefix."UserBookmark ob
            on ow.DiscussionID = ob.DiscussionID
            and ow.UserID = ob.UserID
         where od.Active = '1'");

         $this->Message = T('<strong>17/19</strong> Importing bookmarks & watch data.');
         $this->RedirectUrl = Url('/upgrade/17');
      } else if ($Step == 17) {
         // 17. Remove temp columns
         $Construct->Table('Role')->DropColumn('ImportID');
         $Construct->Table('User')->DropColumn('ImportID');
         $Construct->Table('Category')->DropColumn('ImportID');
         $Construct->Table('Discussion')->DropColumn('ImportID');
         $Construct->DatabasePrefix($SourcePrefix);
         $Construct->Table('Comment')->DropColumn('ConversationID');
         $Construct->DatabasePrefix($DestPrefix);

         $this->Message = T('<strong>18/19</strong> Removing import structure.');
         $this->RedirectUrl = Url('/upgrade/18');
      } else if ($Step == 18) {
         // 18. remove whisperuserids from old comment table where the entire discussion is whispered
         $Database->Query("update ".$SourcePrefix."Comment c
         inner join ".$SourcePrefix."Discussion d
            on c.DiscussionID = d.DiscussionID
         set c.WhisperUserID = null
         where d.WhisperUserID > 0");

         $this->Message = T('<strong>19/19</strong> Restoring original comment structure.');
         $this->RedirectUrl = Url('/upgrade/19');
      } else if ($Step == 19) {
         // Finished!
         $this->RedirectUrl = 'Finished';
         $this->View = 'finished';
      }
      
      $this->SetJson('NextUrl', $this->RedirectUrl);
      $this->RedirectUrl = '';
      
      $this->MasterView = 'setup';
      $this->Render();
   }
   
   public function Initialize() {
      $this->Head = new HeadModule($this);
      $this->AddJsFile('jquery.js');
      $this->AddJsFile('jquery.livequery.js');
      $this->AddJsFile('jquery.form.js');
      $this->AddJsFile('jquery.popup.js');
      $this->AddJsFile('jquery.gardenhandleajaxform.js');
      $this->AddJsFile('global.js');

      $this->AddCssFile('setup.css');
      Gdn_Controller::Initialize();
   }   
}