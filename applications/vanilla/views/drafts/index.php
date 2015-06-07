<?php if (!defined('APPLICATION')) exit();
echo '<h1 class="H HomepageTitle">'.$this->data('Title').'</h1>';
include($this->fetchViewLocation('helper_functions', 'discussions', 'vanilla'));
$Session = Gdn::session();
$ShowOptions = TRUE;
$Alt = '';
$ViewLocation = $this->fetchViewLocation('drafts', 'drafts');
// WriteFilterTabs($this);
echo Gdn_Theme::Module('DiscussionFilterModule');
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
    <div class="Empty"><?php echo t('You do not have any drafts.'); ?></div>
<?php
}
