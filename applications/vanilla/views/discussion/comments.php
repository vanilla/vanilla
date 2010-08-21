<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
$this->FireEvent('BeforeCommentsRender');
if (!function_exists('WriteComment'))
   include($this->FetchViewLocation('helper_functions', 'discussion'));
   
$CurrentOffset = $this->Offset;
if ($CurrentOffset == 0 && !$this->Data('NewComments', FALSE)) {
   echo WriteComment($this->Discussion, $this, $Session, $CurrentOffset);
}

// Only prints individual comment list items
$CommentData = $this->CommentData->Result();
foreach ($CommentData as $Comment) {
   ++$CurrentOffset;
   $this->CurrentComment = $Comment;
   WriteComment($Comment, $this, $Session, $CurrentOffset);
}
