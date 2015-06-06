<?php if (!defined('APPLICATION')) {
    exit();
      }
/**
 * Conversations database structure.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Conversations
 * @since 2.0
 */

if (!isset($Drop)) {
    $Drop = false;
}

if (!isset($Explicit)) {
    $Explicit = true;
}

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
$CountParticipantsExists = $Construct->ColumnExists('CountParticipants');


$Construct
    ->PrimaryKey('ConversationID')
    ->Column('Type', 'varchar(10)', true, 'index')
    ->Column('ForeignID', 'varchar(40)', true)
    ->Column('Subject', 'varchar(255)', null)
    ->Column('Contributors', 'varchar(255)')
    ->Column('FirstMessageID', 'int', true, 'key')
    ->Column('InsertUserID', 'int', false, 'key')
    ->Column('DateInserted', 'datetime', null, 'key')
    ->Column('InsertIPAddress', 'varchar(15)', true)
    ->Column('UpdateUserID', 'int', false, 'key')
    ->Column('DateUpdated', 'datetime')
    ->Column('UpdateIPAddress', 'varchar(15)', true)
    ->Column('CountMessages', 'int', 0)
    ->Column('CountParticipants', 'int', 0)
    ->Column('LastMessageID', 'int', null)
    ->Column('RegardingID', 'int(11)', true, 'index')
    ->Set($Explicit, $Drop);

// Contains the user/conversation relationship. Keeps track of all users who are
// taking part in the conversation. It also keeps DateCleared, which is a
// per-user date relating to when each users last cleared the conversation
// history, and
$Construct->Table('UserConversation');

$UserConversationExists = $Construct->TableExists();
$UpdateCountReadMessages = $Construct->TableExists() && !$Construct->ColumnExists('CountReadMessages');
$DateConversationUpdatedExists = $Construct->ColumnExists('DateConversationUpdated');

$Construct
    ->Column('UserID', 'int', false, array('primary', 'index.Inbox'))
    ->Column('ConversationID', 'int', false, array('primary', 'key'))
    ->Column('CountReadMessages', 'int', 0)// # of read messages
    ->Column('LastMessageID', 'int', true)// The last message posted by a user other than this one, unless this user is the only person who has added a message
    ->Column('DateLastViewed', 'datetime', true)
    ->Column('DateCleared', 'datetime', true)
    ->Column('Bookmarked', 'tinyint(1)', '0')
    ->Column('Deleted', 'tinyint(1)', '0', 'index.Inbox')// User deleted this conversation
    ->Column('DateConversationUpdated', 'datetime', true, 'index.Inbox')// For speeding up queries.
    ->Set($Explicit, $Drop);

if (!$DateConversationUpdatedExists) {
    $SQL->Update('UserConversation uc')
        ->Join('Conversation c', 'uc.ConversationID = c.ConversationID')
        ->Set('DateConversationUpdated', 'c.DateUpdated', false)
        ->Put();
}

if (!$CountParticipantsExists && $UserConversationExists) {
    $SQL->Update('Conversation c')
        ->Set('c.CountParticipants', '(select count(uc.ConversationID) from GDN_UserConversation uc where uc.ConversationID = c.ConversationID and uc.Deleted = 0)', false, false)
        ->Put();
}

// Contains messages for each conversation, as well as who inserted the message
// and when it was inserted. Users cannot edit or delete their messages once
// they have been sent.
$Construct->Table('ConversationMessage')
    ->PrimaryKey('MessageID')
    ->Column('ConversationID', 'int', false, 'key')
    ->Column('Body', 'text')
    ->Column('Format', 'varchar(20)', null)
    ->Column('InsertUserID', 'int', null, 'key')
    ->Column('DateInserted', 'datetime', false)
    ->Column('InsertIPAddress', 'varchar(15)', true)
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
    ->Column('CountUnreadConversations', 'int', null)
    ->Set(false, false);

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
if ($SQL->GetWhere('ActivityType', array('Name' => 'ConversationMessage'))->NumRows() == 0) {
    $SQL->Insert('ActivityType', array('AllowComments' => '0', 'Name' => 'ConversationMessage', 'FullHeadline' => '%1$s sent you a %8$s.', 'ProfileHeadline' => '%1$s sent you a %8$s.', 'RouteCode' => 'message', 'Notify' => '1', 'Public' => '0'));
}

// X added Y to a conversation
if ($SQL->GetWhere('ActivityType', array('Name' => 'AddedToConversation'))->NumRows() == 0) {
    $SQL->Insert('ActivityType', array('AllowComments' => '0', 'Name' => 'AddedToConversation', 'FullHeadline' => '%1$s added %3$s to a %8$s.', 'ProfileHeadline' => '%1$s added %3$s to a %8$s.', 'RouteCode' => 'conversation', 'Notify' => '1', 'Public' => '0'));
}

$PermissionModel = Gdn::PermissionModel();
$PermissionModel->Define(array(
    'Conversations.Moderation.Manage' => 0,
    'Conversations.Conversations.Add' => 'Garden.Profiles.Edit',
));
