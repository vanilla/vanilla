<?php if (!defined('APPLICATION')) { exit(); }
/**
 * Conversations stub content for a new site.
 *
 * Called by ConversationsHooks::setup() to insert stub content upon enabling app.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Conversations
 * @since 2.2
 */

// Only do this once, ever.
if (!$Drop) {
    return;
}

$SQL = Gdn::database()->sql();

// Prep default content
$ConversationBody = "Pssst. Hey. A conversation is a private chat between two or more members. No one can see it except the members added. You can delete this one since I&rsquo;m just a bot and know better than to talk back.";
$SystemUserID = Gdn::userModel()->getSystemUserID();
$TargetUserID = Gdn::session()->UserID;
$Now = Gdn_Format::toDateTime();
$Contributors = dbencode([$SystemUserID, $TargetUserID]);

// Insert stub conversation
$ConversationID = $SQL->insert('Conversation', [
    'InsertUserID' => $SystemUserID,
    'DateInserted' => $Now,
    'Contributors' => $Contributors,
    'CountMessages' => 1
]);

$MessageID = $SQL->insert('ConversationMessage', [
    'ConversationID' => $ConversationID,
    'Body' => t('StubConversationBody', $ConversationBody),
    'Format' => 'Html',
    'InsertUserID' => $SystemUserID,
    'DateInserted' => $Now
]);

$SQL->update('Conversation')
    ->set('LastMessageID', $MessageID)
    ->where('ConversationID', $ConversationID)
    ->put();

$SQL->insert('UserConversation', [
    'ConversationID' => $ConversationID,
    'UserID' => $TargetUserID,
    'CountReadMessages' => 0,
    'LastMessageID' => $MessageID,
    'DateConversationUpdated' => $Now
]);
