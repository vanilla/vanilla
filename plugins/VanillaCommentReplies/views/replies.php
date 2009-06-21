<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();

// Only prints individual replies
foreach ($this->ReplyData->Result() as $CurrentReply) {
   VanillaCommentRepliesPlugin::WriteReply($this, $Session);
}