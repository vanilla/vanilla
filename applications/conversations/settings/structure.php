<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
 */

if (!isset($Drop))
   $Drop = FALSE;
   
if (!isset($Explicit))
   $Explicit = TRUE;
   
$SQL = $Database->SQL();
$Construct = $Database->Structure();
$Px = $Database->DatabasePrefix;

// Contains all conversations. A conversation takes place between X number of
// ppl. This table keeps track of the unique id of the conversation, the person
// who started the conversation (and when), and the last person to contribute to
// the conversation (and when).
// Column($Name, $Type, $Length = '', $Null = FALSE, $Default = NULL, $KeyType = FALSE, $AutoIncrement = FALSE)
$Construct->Table('Conversation');

$UpdateCountMessages = $Construct->TableExists() && !$Construct->ColumnExists('CountMessages');
$UpdateLastMessageID = $Construct->TableExists() && !$Construct->ColumnExists('LastMessageID');

$Construct
   ->PrimaryKey('ConversationID')
   ->Column('Subject', 'varchar(100)', NULL)
   ->Column('Contributors', 'varchar(255)')
   ->Column('FirstMessageID', 'int', TRUE, 'key')
   ->Column('InsertUserID', 'int', FALSE, 'key')
   ->Column('DateInserted', 'datetime', NULL, 'key')
   ->Column('InsertIPAddress', 'varchar(39)', TRUE)
   ->Column('UpdateUserID', 'int', NULL, 'key')
   ->Column('DateUpdated', 'datetime', NULL)
   ->Column('UpdateIPAddress', 'varchar(39)', TRUE)
   ->Column('CountMessages', 'int', 0)
   ->Column('LastMessageID', 'int', NULL)
   ->Column('RegardingID', 'int(11)', TRUE, 'index')
   ->Set($Explicit, $Drop);

// Contains the user/conversation relationship. Keeps track of all users who are
// taking part in the conversation. It also keeps DateCleared, which is a
// per-user date relating to when each users last cleared the conversation
// history, and 
$Construct->Table('UserConversation');

$UpdateCountReadMessages = $Construct->TableExists() && !$Construct->ColumnExists('CountReadMessages');
$DateConversationUpdatedExists = $Construct->ColumnExists('DateConversationUpdated');

$Construct
   ->Column('UserID', 'int', FALSE, array('primary', 'index.Inbox'))
   ->Column('ConversationID', 'int', FALSE, array('primary', 'key'))
   ->Column('CountReadMessages', 'int', 0) // # of read messages
   ->Column('LastMessageID', 'int', TRUE) // The last message posted by a user other than this one, unless this user is the only person who has added a message
   ->Column('DateLastViewed', 'datetime', TRUE)
   ->Column('DateCleared', 'datetime', TRUE)
   ->Column('Bookmarked', 'tinyint(1)', '0')
   ->Column('Deleted', 'tinyint(1)', '0', 'index.Inbox') // User deleted this conversation
   ->Column('DateConversationUpdated', 'datetime', TRUE, 'index.Inbox') // For speeding up queries.
   ->Set($Explicit, $Drop);

if (!$DateConversationUpdatedExists) {
   $SQL->Update('UserConversation uc')
      ->Join('Conversation c', 'uc.ConversationID = c.ConversationID')
      ->Set('DateConversationUpdated', 'c.DateUpdated', FALSE)
      ->Put();
}
   
// Contains messages for each conversation, as well as who inserted the message
// and when it was inserted. Users cannot edit or delete their messages once
// they have been sent.
$Construct->Table('ConversationMessage')
   ->PrimaryKey('MessageID')
   ->Column('ConversationID', 'int', FALSE, 'key')
   ->Column('Body', 'text')
   ->Column('Format', 'varchar(20)', NULL)
   ->Column('InsertUserID', 'int', NULL, 'key')
   ->Column('DateInserted', 'datetime', FALSE)
   ->Column('InsertIPAddress', 'varchar(39)', TRUE)
   ->Set($Explicit, $Drop);

if ($UpdateCountMessages) {
   // Calculate the count column.
   $UpSql = "update {$Px}Conversation c
set CountMessages = (
   select count(MessageID)
   from {$Px}ConversationMessage m
   where c.ConversationID = m.ConversationID)";
   $Construct->Query($UpSql);
}
if ($UpdateLastMessageID) {
   // Calculate the count column.
   $UpSql = "update {$Px}Conversation c
set LastMessageID = (
   select max(MessageID)
   from {$Px}ConversationMessage m
   where c.ConversationID = m.ConversationID)";
   $Construct->Query($UpSql);
}

if ($UpdateCountReadMessages) {
   $UpSql = "update {$Px}UserConversation uc
set CountReadMessages = (
  select count(cm.MessageID)
  from {$Px}ConversationMessage cm
  where cm.ConversationID = uc.ConversationID
    and cm.MessageID <= uc.LastMessageID)";

  $Construct->Query($UpSql);
}

// Add extra columns to user table for tracking discussions, comments & replies
$Construct->Table('User')
   ->Column('CountUnreadConversations', 'int', NULL)
   ->Set(FALSE, FALSE);
   
// Insert some activity types
///  %1 = ActivityName
///  %2 = ActivityName Possessive
///  %3 = RegardingName
///  %4 = RegardingName Possessive
///  %5 = Link to RegardingName's Wall
///  %6 = his/her
///  %7 = he/she
///  %8 = RouteCode & Route (will be changed to <a href="route">routecode</a>)

// X sent you a message
if ($SQL->GetWhere('ActivityType', array('Name' => 'ConversationMessage'))->NumRows() == 0)
   $SQL->Insert('ActivityType', array('AllowComments' => '0', 'Name' => 'ConversationMessage', 'FullHeadline' => '%1$s sent you a %8$s.', 'ProfileHeadline' => '%1$s sent you a %8$s.', 'RouteCode' => 'message', 'Notify' => '1', 'Public' => '0'));

// X added Y to a conversation   
if ($SQL->GetWhere('ActivityType', array('Name' => 'AddedToConversation'))->NumRows() == 0)
   $SQL->Insert('ActivityType', array('AllowComments' => '0', 'Name' => 'AddedToConversation', 'FullHeadline' => '%1$s added %3$s to a %8$s.', 'ProfileHeadline' => '%1$s added %3$s to a %8$s.', 'RouteCode' => 'conversation', 'Notify' => '1', 'Public' => '0'));

$PermissionModel = Gdn::PermissionModel();
$PermissionModel->Define(array(
   'Conversations.Moderation.Manage' => 0,
   'Conversations.Conversations.Add' => 'Garden.Profiles.Edit',
));