<?php if (!defined('APPLICATION')) exit();
echo '<h1 class="H HomepageTitle">'.$this->data('Title').'</h1>';
include($this->fetchViewLocation('helper_functions', 'discussions', 'vanilla'));
$Session = Gdn::session();
$ShowOptions = TRUE;
$ViewLocation = $this->fetchViewLocation('drafts', 'drafts');
// writeFilterTabs($this);
echo Gdn_Theme::module('DiscussionFilterModule');
if ($this->DraftData->numRows() > 0) {
    echo $this->Pager->toString('less');
    ?>
    <ul class="DataList Drafts">
        <?php
        include($ViewLocation);
        ?>
    </ul>
    <?php
    echo $this->Pager->toString('more');
} else {
    ?>
    <div class="Empty"><?php echo t('No drafts.', 'You do not have any drafts.'); ?></div>
<?php
}
