<?php if (!defined('APPLICATION')) exit();

echo '<h2 class="H">'.T('Discussions').'</h2>';

// Create some variables so that they aren't defined in every loop.
$ViewLocation = $this->FetchViewLocation('discussions', 'discussions', 'vanilla');

if (!is_object($this->DiscussionData) || $this->DiscussionData->NumRows() <= 0) {
   echo Wrap(T("This user has not made any discussions yet."), 'div', array('Class' => 'Empty'));
} else {
   echo '<ul class="DataList Discussions">';
   include($ViewLocation); 
   echo '</ul>';
   echo $this->Pager->ToString('more');
}