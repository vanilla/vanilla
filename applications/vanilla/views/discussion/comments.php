<?php if (!defined('APPLICATION')) exit();

$Session = Gdn::Session();
$this->FireEvent('BeforeCommentsRender');
if (!function_exists('WriteComment'))
   include($this->FetchViewLocation('helper_functions', 'discussion'));
   
$CurrentOffset = $this->Offset;

// Only prints individual comment list items
$CommentData = $this->CommentData->Result();
foreach ($CommentData as $Comment) {
   if (is_numeric($Comment->CommentID))
      $CurrentOffset++;
   $this->CurrentComment = $Comment;
   WriteComment($Comment, $this, $Session, $CurrentOffset);
}
