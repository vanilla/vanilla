<?php if (!defined('APPLICATION')) exit();
/**
 * Conversations stub content for a new site.
 *
 * Called by ConversationsHooks::Setup() to insert stub content upon enabling app.
 * @package Conversations
 */

// Only do this once, ever.
if (!$Drop)
   return;
   
$SQL = Gdn::Database()->SQL();

// Prep default content
$ConversationBody = "Pssst. Hey. A conversation is a private chat between two or more members. No one can see it except the members added. You can delete this one since I&rsquo;m just a bot and know better than to talk back.";
$SystemUserID = Gdn::UserModel()->GetSystemUserID();
$TargetUserID = Gdn::Session()->UserID;
$Now = Gdn_Format::ToDateTime();
$Contributors = Gdn_Format::Serialize(array($SystemUserID, $TargetUserID));

// Insert stub conversation
$ConversationID = $SQL->Insert('Conversation', array(
   'InsertUserID' => $SystemUserID,
   'DateInserted' => $Now,
   'Contributors' => $Contributors,
   'CountMessages' => 1
));
$MessageID = $SQL->Insert('ConversationMessage', array(
   'ConversationID' => $ConversationID,
   'Body' => T('StubConversationBody', $ConversationBody),
   'Format' => 'Html',
   'InsertUserID' => $SystemUserID,
   'DateInserted' => $Now
));
$SQL->Update('Conversation')
   ->Set('LastMessageID', $MessageID)
   ->Where('ConversationID', $ConversationID)
   ->Put();
$SQL->Insert('UserConversation', array(
   'ConversationID' => $ConversationID,
   'UserID' => $TargetUserID,
   'CountReadMessages' => 0,
   'LastMessageID' => $MessageID,
   'DateConversationUpdated' => $Now
));
