<?php if (!defined('APPLICATION')) exit();
echo '<h2 class="H">'.T('Comments').'</h2>';
if (is_object($this->CommentData) && $this->CommentData->NumRows() > 0) {
   echo '<ul class="DataList SearchResults">';
   echo $this->FetchView('profilecomments', 'Discussion', 'Vanilla');
   echo '</ul>';
   echo $this->Pager->ToString('more');
} else {
   echo '<div class="Empty">'.T('This user has not commented yet.').'</div>';
}
