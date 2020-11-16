<?php if (!defined('APPLICATION')) { exit(); }
/**
 * Conversations stub content for a new site.
 *
 * Called by ConversationsHooks::setup() to insert stub content upon enabling app.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Conversations
 * @since 2.2
 */

// Only do this once, ever.
if (empty($Drop)) {
    return;
}

$sql = Gdn::database()->sql();

// Prep default content
$conversationBody = "Pssst. Hey. A conversation is a private chat between two or more members. No one can see it ".
    "except the members added. You can delete this one since I&rsquo;m just a bot and know better than to talk back.";
$systemUserID = Gdn::userModel()->getSystemUserID();
$targetUserID = Gdn::session()->UserID;
$now = Gdn_Format::toDateTime();
$contributors = dbencode([$systemUserID, $targetUserID]);

// Insert stub conversation
$conversationID = $sql->insert('Conversation', [
    'InsertUserID' => $systemUserID,
    'DateInserted' => $now,
    'Contributors' => $contributors,
    'CountMessages' => 1
]);

$messageID = $sql->insert('ConversationMessage', [
    'ConversationID' => $conversationID,
    'Body' => t('StubConversationBody', $conversationBody),
    'Format' => 'Html',
    'InsertUserID' => $systemUserID,
    'DateInserted' => $now
]);

$sql->update('Conversation')
    ->set('LastMessageID', $messageID)
    ->where('ConversationID', $conversationID)
    ->put();

$sql->insert('UserConversation', [
    'ConversationID' => $conversationID,
    'UserID' => $targetUserID,
    'CountReadMessages' => 0,
    'LastMessageID' => $messageID,
    'DateConversationUpdated' => $now
]);
