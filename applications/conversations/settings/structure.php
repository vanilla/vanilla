<?php if (!defined('APPLICATION')) { exit(); }
/**
 * Conversations database structure.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Conversations
 * @since 2.0
 */

if (!isset($Drop)) {
    $Drop = false;
}

if (!isset($Explicit)) {
    $Explicit = false;
}

$SQL = $Database->sql();
$Construct = $Database->structure();
$Px = $Database->DatabasePrefix;

// Contains all conversations. A conversation takes place between X number of
// ppl. This table keeps track of the unique id of the conversation, the person
// who started the conversation (and when), and the last person to contribute to
// the conversation (and when).
// column($Name, $Type, $Length = '', $Null = FALSE, $Default = null, $KeyType = FALSE, $AutoIncrement = FALSE)
$Construct->table('Conversation');

$UpdateCountMessages = $Construct->tableExists() && !$Construct->columnExists('CountMessages');
$UpdateLastMessageID = $Construct->tableExists() && !$Construct->columnExists('LastMessageID');
$CountParticipantsExists = $Construct->columnExists('CountParticipants');


$Construct
    ->primaryKey('ConversationID')
    ->column('Type', 'varchar(10)', true, 'index')
    ->column('ForeignID', 'varchar(40)', true)
    ->column('Subject', 'varchar(255)', null)
    ->column('Contributors', 'varchar(255)', true)
    ->column('FirstMessageID', 'int', true, 'key')
    ->column('InsertUserID', 'int', false, 'key')
    ->column('DateInserted', 'datetime', null, 'key')
    ->column('InsertIPAddress', 'ipaddress', true)
    ->column('UpdateUserID', 'int', false, 'key')
    ->column('DateUpdated', 'datetime')
    ->column('UpdateIPAddress', 'ipaddress', true)
    ->column('CountMessages', 'int', 0)
    ->column('CountParticipants', 'int', 0)
    ->column('LastMessageID', 'int', null)
    ->column('RegardingID', 'int(11)', true, 'index')
    ->set($Explicit, $Drop);

// Contains the user/conversation relationship. Keeps track of all users who are
// taking part in the conversation. It also keeps DateCleared, which is a
// per-user date relating to when each users last cleared the conversation history.
$Construct->table('UserConversation');

$UserConversationExists = $Construct->tableExists();
$UpdateCountReadMessages = $Construct->tableExists() && !$Construct->columnExists('CountReadMessages');
$DateConversationUpdatedExists = $Construct->columnExists('DateConversationUpdated');

$Construct
    ->column('UserID', 'int', false, ['primary', 'index.Inbox'])
    ->column('ConversationID', 'int', false, ['primary', 'key'])
    ->column('CountReadMessages', 'int', 0)// # of read messages
    ->column('LastMessageID', 'int', true) // The last message posted by a user other than this one, unless this user is the only person who has added a message
    ->column('DateLastViewed', 'datetime', true)
    ->column('DateCleared', 'datetime', true)
    ->column('Bookmarked', 'tinyint(1)', '0')
    ->column('Deleted', 'tinyint(1)', '0', 'index.Inbox') // User deleted this conversation
    ->column('DateConversationUpdated', 'datetime', true, 'index.Inbox') // For speeding up queries.
    ->set($Explicit, $Drop);

if (!$DateConversationUpdatedExists) {
    $SQL->update('UserConversation uc')
        ->join('Conversation c', 'uc.ConversationID = c.ConversationID')
        ->set('DateConversationUpdated', 'c.DateUpdated', false)
        ->put();
}

if (!$CountParticipantsExists && $UserConversationExists) {
    $SQL->update('Conversation c')
        ->set('c.CountParticipants', '(select count(uc.ConversationID) from GDN_UserConversation uc where uc.ConversationID = c.ConversationID and uc.Deleted = 0)', false, false)
        ->put();
}

// Contains messages for each conversation, as well as who inserted the message
// and when it was inserted. Users cannot edit or delete their messages once they have been sent.
$Construct->table('ConversationMessage')
    ->primaryKey('MessageID')
    ->column('ConversationID', 'int', false, 'key')
    ->column('Body', 'text')
    ->column('Format', 'varchar(20)', null)
    ->column('InsertUserID', 'int', null, 'key')
    ->column('DateInserted', 'datetime', false)
    ->column('InsertIPAddress', 'ipaddress', true)
    ->set($Explicit, $Drop);

if ($UpdateCountMessages) {
    // Calculate the count column.
    $UpSql = "update {$Px}Conversation c
set CountMessages = (
   select count(MessageID)
   from {$Px}ConversationMessage m
   where c.ConversationID = m.ConversationID)";
    $SQL->query($UpSql);
}
if ($UpdateLastMessageID) {
    // Calculate the count column.
    $UpSql = "update {$Px}Conversation c
set LastMessageID = (
   select max(MessageID)
   from {$Px}ConversationMessage m
   where c.ConversationID = m.ConversationID)";
    $SQL->query($UpSql);
}

if ($UpdateCountReadMessages) {
    $UpSql = "update {$Px}UserConversation uc
set CountReadMessages = (
  select count(cm.MessageID)
  from {$Px}ConversationMessage cm
  where cm.ConversationID = uc.ConversationID
    and cm.MessageID <= uc.LastMessageID)";

    $SQL->query($UpSql);
}

// Add extra columns to user table for tracking discussions, comments & replies
$Construct->table('User')
    ->column('CountUnreadConversations', 'int', null)
    ->set(false, false);

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
if ($SQL->getWhere('ActivityType', ['Name' => 'ConversationMessage'])->numRows() == 0) {
    $SQL->insert('ActivityType', [
        'AllowComments' => '0',
        'Name' => 'ConversationMessage',
        'FullHeadline' => '%1$s sent you a %8$s.',
        'ProfileHeadline' => '%1$s sent you a %8$s.',
        'RouteCode' => 'message',
        'Notify' => '1',
        'Public' => '0'
    ]);
}

// X added Y to a conversation
if ($SQL->getWhere('ActivityType', ['Name' => 'AddedToConversation'])->numRows() == 0) {
    $SQL->insert('ActivityType', [
        'AllowComments' => '0',
        'Name' => 'AddedToConversation',
        'FullHeadline' => '%1$s added %3$s to a %8$s.',
        'ProfileHeadline' => '%1$s added %3$s to a %8$s.',
        'RouteCode' => 'conversation',
        'Notify' => '1',
        'Public' => '0'
    ]);
}

$PermissionModel = Gdn::permissionModel();
$PermissionModel->define([
    'Conversations.Moderation.Manage' => 0,
    'Conversations.Conversations.Add' => 'Garden.Profiles.Edit',
]);

// Set current Conversations.Version
$appInfo = json_decode(file_get_contents(PATH_APPLICATIONS.DS.'conversations'.DS.'addon.json'), true);
saveToConfig('Conversations.Version', val('version', $appInfo, 'Undefined'));
