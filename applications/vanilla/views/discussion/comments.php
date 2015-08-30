<?php if (!defined('APPLICATION')) exit();

$Session = Gdn::session();
$this->fireEvent('BeforeCommentsRender');
if (!function_exists('WriteComment'))
    include($this->fetchViewLocation('helper_functions', 'discussion'));

$CurrentOffset = $this->Offset;

$this->EventArguments['CurrentOffset'] = &$CurrentOffset;
$this->fireEvent('BeforeFirstComment');

// Only prints individual comment list items
$Comments = $this->data('Comments')->result();
foreach ($Comments as $Comment) {
    if (is_numeric($Comment->CommentID))
        $CurrentOffset++;
    $this->CurrentComment = $Comment;
    WriteComment($Comment, $this, $Session, $CurrentOffset);
}
