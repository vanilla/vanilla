<?php use Vanilla\Theme\BoxThemeShim;

if (!defined('APPLICATION')) exit();
BoxThemeShim::startHeading();
echo '<h1 class="H HomepageTitle">'.$this->data('Title').'</h1>';
BoxThemeShim::endHeading();
include($this->fetchViewLocation('helper_functions', 'discussions', 'vanilla'));
$Session = Gdn::session();
$ShowOptions = TRUE;
$ViewLocation = $this->fetchViewLocation('drafts', 'drafts');
// writeFilterTabs($this);
if (!\Gdn::themeFeatures()->useNewQuickLinks()) {
    echo Gdn_Theme::module('DiscussionFilterModule');
}
if ($this->DraftData->numRows() > 0) {
    echo $this->Pager->toString('less');
    ?>
    <ul class="DataList Drafts pageBox">
        <?php
        include($ViewLocation);
        ?>
    </ul>
    <?php
    echo $this->Pager->toString('more');
} else {
    ?>
    <?php BoxThemeShim::startBox(); ?>
    <div class="Empty"><?php echo t('No drafts.', 'You do not have any drafts.'); ?></div>
    <?php BoxThemeShim::endBox(); ?>
    <?php
}
