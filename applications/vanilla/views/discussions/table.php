<?php if (!defined('APPLICATION')) exit();
/**
 * "Table" layout for discussions. Mimics more traditional forum discussion layout.
 */

$Session = Gdn::session();
include_once $this->fetchViewLocation('helper_functions', 'discussions', 'vanilla');
include_once $this->fetchViewLocation('table_functions', 'discussions', 'vanilla');

/**
 * Render the page.
 */

$PagerOptions = array('Wrapper' => '<div %1$s>%2$s</div>', 'RecordCount' => $this->data('CountDiscussions'), 'CurrentRecords' => $this->data('Discussions')->numRows());
if ($this->data('_PagerUrl')) {
    $PagerOptions['Url'] = $this->data('_PagerUrl');
}

echo '<h1 class="H HomepageTitle">'.$this->data('Title').'</h1>';

if ($Description = $this->data('_Description')) {
    echo '<div class="P PageDescription">';
    echo $this->data('_Description', '&#160;');
    echo '</div>';
}
$this->fireEvent('AfterDescription');

include $this->fetchViewLocation('Subtree', 'Categories', 'Vanilla');

echo '<div class="PageControls Top">';
PagerModule::write($PagerOptions);
echo Gdn_Theme::Module('NewDiscussionModule', $this->data('_NewDiscussionProperties', array('CssClass' => 'Button Action Primary')));
echo '</div>';

if ($this->DiscussionData->numRows() > 0 || (isset($this->AnnounceData) && is_object($this->AnnounceData) && $this->AnnounceData->numRows() > 0)) {
    ?>
    <div class="DataTableWrap">
        <table class="DataTable DiscussionsTable">
            <thead>
            <?php
            WriteDiscussionHeading();
            ?>
            </thead>
            <tbody>
            <?php
            $Alt = '';
            if (property_exists($this, 'AnnounceData') && is_object($this->AnnounceData)) {
                foreach ($this->AnnounceData->result() as $Discussion) {
                    $Alt = $Alt == ' Alt' ? '' : ' Alt';
                    WriteDiscussionRow($Discussion, $this, $Session, $Alt);
                }
            }

            $Alt = '';
            foreach ($this->DiscussionData->result() as $Discussion) {
                $Alt = $Alt == ' Alt' ? '' : ' Alt';
                WriteDiscussionRow($Discussion, $this, $Session, $Alt);
            }
            ?>
            </tbody>
        </table>
    </div>
    <?php

    echo '<div class="PageControls Bottom">';
    PagerModule::write($PagerOptions);
    echo Gdn_Theme::Module('NewDiscussionModule', $this->data('_NewDiscussionProperties', array('CssClass' => 'Button Action Primary')));
    echo '</div>';

} else {
    ?>
    <div class="Empty"><?php echo t('No discussions were found.'); ?></div>
<?php
}
