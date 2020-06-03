<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::session();
include_once $this->fetchViewLocation('helper_functions', 'discussions', 'vanilla');

echo '<h1 class="H HomepageTitle">'.
    adminCheck(NULL, ['', ' ']).
    $this->data('Title').
    '</h1>';

if ($Description = $this->description()) {
    echo wrap($Description, 'div', ['class' => 'P PageDescription']);
}
// echo Gdn_Theme::module('DiscussionFilterModule');

if ($this->DiscussionData->numRows() > 0 || (isset($this->AnnounceData) && is_object($this->AnnounceData) && $this->AnnounceData->numRows() > 0)) {
    ?>
    <h2 class="sr-only"><?php echo t('Discussion List'); ?></h2>
    <ul class="DataList Discussions">
        <?php include($this->fetchViewLocation('discussions')); ?>
    </ul>
    <?php
    $PagerOptions = ['RecordCount' => $this->data('CountDiscussions'), 'CurrentRecords' => $this->data('Discussions')->numRows()];
    if ($this->data('_PagerUrl'))
        $PagerOptions['Url'] = $this->data('_PagerUrl');

    PagerModule::write($PagerOptions);
} else {
    ?>
    <div class="Empty"><?php echo t('No discussions were found.'); ?></div>
<?php
}
