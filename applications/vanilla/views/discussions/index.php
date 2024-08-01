<?php use Vanilla\Forum\Modules\FoundationDiscussionsShim;
use Vanilla\Theme\BoxThemeShim;if (!defined('APPLICATION')) exit();
$Session = Gdn::session();
$isDataDrivenTheme = Gdn::themeFeatures()->useDataDrivenTheme();
include_once $this->fetchViewLocation('helper_functions', 'discussions', 'vanilla');
include_once $this->fetchViewLocation('helper_functions', 'categories', 'vanilla');

$checkMark = !$isDataDrivenTheme ? adminCheck(NULL, ['', ' ']) : '';
echo '<section class="headerBoxLayout">';
BoxThemeShim::startHeading();
echo '<h1 class="H HomepageTitle">'.
    $checkMark.
    $this->data('Title').
    '</h1>';
/** @var $htmlSanitizer */
$htmlSanitizer = Gdn::getContainer()->get(\Vanilla\Formatting\Html\HtmlSanitizer::class);
$Description = $htmlSanitizer->filter($this->data('Category.Description', $this->description()));
echo wrapIf($Description, 'div', ['class' => 'P PageDescription']);
$this->fireEvent('AfterPageTitle');
BoxThemeShim::endHeading();
echo followButton($this->data('Category.CategoryID')).'</section>';



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
    <?php
    if (!FoundationDiscussionsShim::isEnabled()) {
        echo '<ul class="DataList Discussions pageBox">';
    }
    $isMainContent = true;
    include($this->fetchViewLocation('discussions', 'Discussions', 'Vanilla'));
    if (!FoundationDiscussionsShim::isEnabled()) {
        echo '</ul>';

    }
    ?>
    <?php $this->fireEvent('AfterDiscussionsList'); ?>
    <?php

    echo '<div class="PageControls Bottom">';
    PagerModule::write($PagerOptions);
    echo Gdn_Theme::module('NewDiscussionModule', $this->data('_NewDiscussionProperties', ['CssClass' => 'Button Action Primary']));
    echo '</div>';

} else {
    ?>
    <?php BoxThemeShim::startBox(); ?>
    <div class="Empty"><?php echo t('No discussions were found.'); ?></div>
    <?php BoxThemeShim::endBox(); ?>
    <?php $this->fireEvent('AfterDiscussionsList'); ?>
<?php
}
