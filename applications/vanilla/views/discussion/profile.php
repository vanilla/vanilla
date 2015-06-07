<?php if (!defined('APPLICATION')) exit();

echo '<div class="DataListWrap">';
echo '<h2 class="H">'.t('Comments').'</h2>';
echo '<ul class="DataList SearchResults">';

if (sizeof($this->data('Comments'))) {
    echo $this->fetchView('profilecomments', 'Discussion', 'Vanilla');
    echo $this->Pager->toString('more');
} else {
    echo '<li class="Item Empty">'.t('This user has not commented yet.').'</li>';
}
echo '</ul>';
echo '</div>';
