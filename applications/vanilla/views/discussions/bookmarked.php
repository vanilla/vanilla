<?php if (!defined('APPLICATION')) exit();
$this->title(t('My Bookmarks'));
include($this->fetchViewLocation('helper_functions', 'discussions', 'vanilla'));

// WriteFilterTabs($this);
if ($this->DiscussionData->numRows() > 0 || (is_object($this->AnnounceData) && $this->AnnounceData->numRows() > 0)) {
    ?>
    <ul class="DataList Discussions Bookmarks">
        <?php include($this->fetchViewLocation('discussions')); ?>
    </ul>
    <?php
    $PagerOptions = array('RecordCount' => $this->data('CountDiscussions'), 'CurrentRecords' => $this->data('Discussions')->numRows());
    if ($this->data('_PagerUrl')) {
        $PagerOptions['Url'] = $this->data('_PagerUrl');
    }
    echo PagerModule::write($PagerOptions);
} else {
    ?>
    <div class="Empty"><?php echo t('No discussions were found.'); ?></div>
<?php
}
