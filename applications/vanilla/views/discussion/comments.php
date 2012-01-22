<?php if (!defined('APPLICATION')) exit();

$Session = Gdn::Session();
$this->FireEvent('BeforeCommentsRender');
if (!function_exists('WriteComment'))
   include($this->FetchViewLocation('helper_functions', 'discussion'));

// Add 1 to offset to account for discussion (moved in 2.1)
$CurrentOffset = $this->Offset + 1;

$this->EventArguments['CurrentOffset'] = &$CurrentOffset;
$this->FireEvent('BeforeFirstComment');

// Only prints individual comment list items
$CommentData = $this->CommentData->Result();
foreach ($CommentData as $Comment) {
   if (is_numeric($Comment->CommentID))
      $CurrentOffset++;
   $this->CurrentComment = $Comment;
   WriteComment($Comment, $this, $Session, $CurrentOffset);
}
