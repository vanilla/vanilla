<?php if (!defined('APPLICATION')) exit();

echo '<div class="DataListWrap">';
echo '<h2 class="H">'.t('Discussions').'</h2>';
echo '<ul class="DataList Discussions">';

// Create some variables so that they aren't defined in every loop.
$ViewLocation = $this->fetchViewLocation('discussions', 'discussions', 'vanilla');

if (!is_object($this->DiscussionData) || $this->DiscussionData->numRows() <= 0) {
    echo wrap(t("This user has not made any discussions yet."), 'li', array('Class' => 'Item Empty'));
} else {
    include($ViewLocation);
    echo $this->Pager->toString('more');
}
echo '</ul>';
echo '</div>';
