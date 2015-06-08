<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::session();
include_once $this->fetchViewLocation('helper_functions', 'discussions', 'vanilla');

echo '<h1 class="H HomepageTitle">'.
    AdminCheck(NULL, array('', ' ')).
    $this->data('Title').
    '</h1>';

if ($Description = $this->Description()) {
    echo wrap($Description, 'div', array('class' => 'P PageDescription'));
}
// echo Gdn_Theme::Module('DiscussionFilterModule');

if ($this->DiscussionData->numRows() > 0 || (isset($this->AnnounceData) && is_object($this->AnnounceData) && $this->AnnounceData->numRows() > 0)) {
    ?>
    <ul class="DataList Discussions">
        <?php include($this->fetchViewLocation('discussions')); ?>
    </ul>
    <?php
    $PagerOptions = array('RecordCount' => $this->data('CountDiscussions'), 'CurrentRecords' => $this->data('Discussions')->numRows());
    if ($this->data('_PagerUrl'))
        $PagerOptions['Url'] = $this->data('_PagerUrl');

    PagerModule::write($PagerOptions);
} else {
    ?>
    <div class="Empty"><?php echo t('No discussions were found.'); ?></div>
<?php
}
