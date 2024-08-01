<?php use Vanilla\Theme\BoxThemeShim;

if (!defined('APPLICATION')) exit();

echo '<div class="DataListWrap">';
BoxThemeShim::startHeading();
echo '<h2 class="H">'.t('Comments').'</h2>';
BoxThemeShim::endHeading();
echo '<ul class="DataList Comments pageBox">';

if (sizeof($this->data('Comments'))) {
    echo $this->fetchView('profilecomments', 'Discussion', 'Vanilla');
    echo $this->Pager->toString('more');
} elseif ($this->data('UnfilteredCommentsCount', 0) > 0) {
    echo '<li class="Item Empty">'.t('You do not have access to any comments on this page.').'</li>';
    echo $this->Pager->toString('more');
} else {
    echo '<li class="Item Empty">'.t('This user has not commented yet.').'</li>';
}

echo '</ul>';
echo '</div>';
