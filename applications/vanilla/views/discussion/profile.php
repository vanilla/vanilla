<?php if (!defined('APPLICATION')) exit();

echo '<div class="DataListWrap">';
echo '<h2 class="H">'.T('Comments').'</h2>';
echo '<ul class="DataList SearchResults">';

if (sizeof($this->Data('Comments'))) {
   echo $this->FetchView('profilecomments', 'Discussion', 'Vanilla');
   echo $this->Pager->ToString('more');
} else {
   echo '<li class="Item Empty">'.T('This user has not commented yet.').'</li>';
}
echo '</ul>';
echo '</div>';