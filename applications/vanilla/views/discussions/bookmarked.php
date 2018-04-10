<?php if (!defined('APPLICATION')) exit();
$this->title(t('My Bookmarks'));
include($this->fetchViewLocation('helper_functions', 'discussions', 'vanilla'));

// writeFilterTabs($this);
if ($this->DiscussionData->numRows() > 0 || (is_object($this->AnnounceData) && $this->AnnounceData->numRows() > 0)) {
    echo '<h2 class="sr-only">'.t('Discussion List').'</h2>';
    ?>
    <ul class="DataList Discussions Bookmarks">
        <?php include($this->fetchViewLocation('discussions')); ?>
    </ul>
    <?php
    $PagerOptions = ['RecordCount' => $this->data('CountDiscussions'), 'CurrentRecords' => $this->data('Discussions')->numRows()];
    if ($this->data('_PagerUrl')) {
        $PagerOptions['Url'] = $this->data('_PagerUrl');
    }
    echo PagerModule::write($PagerOptions);
} else {
    ?>
    <div class="Empty"><?php echo t('No discussions were found.'); ?></div>
<?php
}
