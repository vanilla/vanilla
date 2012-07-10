<?php if (!defined('APPLICATION')) exit();

$Session = Gdn::Session();
$this->FireEvent('BeforeCommentsRender');
if (!function_exists('WriteComment'))
   include($this->FetchViewLocation('helper_functions', 'discussion'));

$CurrentOffset = $this->Offset;

$this->EventArguments['CurrentOffset'] = &$CurrentOffset;
$this->FireEvent('BeforeFirstComment');

// Only prints individual comment list items
$Comments = $this->Data('Comments')->Result();
foreach ($Comments as $Comment) {
   if (is_numeric($Comment->CommentID))
      $CurrentOffset++;
   $this->CurrentComment = $Comment;
   WriteComment($Comment, $this, $Session, $CurrentOffset);
}
