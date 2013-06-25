<?php if (!defined('APPLICATION')) exit();

echo '<div class="DataListWrap">';
echo '<h2 class="H">'.T('Discussions').'</h2>';
echo '<ul class="DataList Discussions">';

// Create some variables so that they aren't defined in every loop.
$ViewLocation = $this->FetchViewLocation('discussions', 'discussions', 'vanilla');

if (!is_object($this->DiscussionData) || $this->DiscussionData->NumRows() <= 0) {
   echo Wrap(T("This user has not made any discussions yet."), 'li', array('Class' => 'Item Empty'));
} else {
   include($ViewLocation); 
   echo $this->Pager->ToString('more');
}
echo '</ul>';
echo '</div>';