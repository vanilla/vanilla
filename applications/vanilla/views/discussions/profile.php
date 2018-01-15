<?php if (!defined('APPLICATION')) exit();

echo '<div class="DataListWrap">';
echo '<h2 class="H">'.t('Discussions').'</h2>';
echo '<ul class="DataList Discussions">';

// Create some variables so that they aren't defined in every loop.
$ViewLocation = $this->fetchViewLocation('discussions', 'discussions', 'vanilla');

if (is_object($this->DiscussionData) && $this->DiscussionData->numRows() > 0) {
    include($ViewLocation);
    echo $this->Pager->toString('more');
} elseif ($this->data('UnfilteredDiscussionsCount', 0) > 0) {
    echo '<li class="Item Empty">'.t('Every discussions on this page have been filtered out because you do not have the permission to see them.').'</li>';
    echo $this->Pager->toString('more');
} else {
    echo wrap(t("This user has not made any discussions yet."), 'li', ['Class' => 'Item Empty']);
}
echo '</ul>';
echo '</div>';
