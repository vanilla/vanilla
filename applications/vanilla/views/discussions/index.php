<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::session();
include_once $this->fetchViewLocation('helper_functions', 'discussions', 'vanilla');
include_once $this->fetchViewLocation('helper_functions', 'categories', 'vanilla');

echo '<h1 class="H HomepageTitle">'.
    adminCheck(NULL, ['', ' ']).
    $this->data('Title').
    followButton($this->data('Category.CategoryID')).
    '</h1>';

$Description = $this->data('Category.Description', $this->description());
echo wrapIf(Gdn_Format::htmlFilter($Description), 'div', ['class' => 'P PageDescription']);

$this->fireEvent('AfterPageTitle');

$subtreeView = $this->fetchViewLocation('subtree', 'categories', 'vanilla', false);
if ($subtreeView) {
    // This use of subtree is deprecated.
    include $subtreeView;
} elseif (isset($this->CategoryModel) && $this->CategoryModel instanceof CategoryModel) {
    $childCategories = $this->data('CategoryTree', []);
    $this->CategoryModel->joinRecent($childCategories);
    if ($childCategories) {
        include($this->fetchViewLocation('helper_functions', 'categories', 'vanilla'));
        if (c('Vanilla.Categories.Layout') === 'table') {
            writeCategoryTable($childCategories);
        } else {
            writeCategoryList($childCategories);
        }
    }
}

$this->fireEvent('AfterCategorySubtree');

$PagerOptions = ['Wrapper' => '<span class="PagerNub">&#160;</span><div %1$s>%2$s</div>', 'RecordCount' => $this->data('CountDiscussions'), 'CurrentRecords' => $this->data('Discussions')->numRows()];
if ($this->data('_PagerUrl'))
    $PagerOptions['Url'] = $this->data('_PagerUrl');

echo '<div class="PageControls Top">';
PagerModule::write($PagerOptions);
echo Gdn_Theme::module('NewDiscussionModule', $this->data('_NewDiscussionProperties', ['CssClass' => 'Button Action Primary']));
// Avoid displaying in a category's list of discussions.
if ($this->data('EnableFollowingFilter')) {
    echo discussionFilters();
}
$this->fireEvent('PageControls');
echo '</div>';

if ($this->DiscussionData->numRows() > 0 || (isset($this->AnnounceData) && is_object($this->AnnounceData) && $this->AnnounceData->numRows() > 0)) {
    ?>
    <h2 class="sr-only"><?php echo t('Discussion List'); ?></h2>
    <ul class="DataList Discussions">
        <?php include($this->fetchViewLocation('discussions', 'Discussions', 'Vanilla')); ?>
    </ul>
    <?php

    echo '<div class="PageControls Bottom">';
    PagerModule::write($PagerOptions);
    echo Gdn_Theme::module('NewDiscussionModule', $this->data('_NewDiscussionProperties', ['CssClass' => 'Button Action Primary']));
    echo '</div>';

} else {
    ?>
    <div class="Empty"><?php echo t('No discussions were found.'); ?></div>
<?php
}
