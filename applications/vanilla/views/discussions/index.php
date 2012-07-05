<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
include_once $this->FetchViewLocation('helper_functions', 'discussions', 'vanilla');

echo '<h1 class="H HomepageTitle">'.
   AdminCheck(NULL, array('', ' ')).
   $this->Data('Title').
   '</h1>';

if ($Description = $this->Description()) {
   echo Wrap($Description, 'div', array('class' => 'P PageDescription'));
}

include $this->FetchViewLocation('Subtree', 'Categories', 'Vanilla');


$PagerOptions = array('RecordCount' => $this->Data('CountDiscussions'), 'CurrentRecords' => $this->Data('Discussions')->NumRows());
if ($this->Data('_PagerUrl'))
   $PagerOptions['Url'] = $this->Data('_PagerUrl');

echo '<div class="PageControls Top">';
   echo Gdn_Theme::Module('NewDiscussionModule', array('CssClass' => 'Button Action'));
   echo PagerModule::Write($PagerOptions);
echo '</div>';

if ($this->DiscussionData->NumRows() > 0 || (isset($this->AnnounceData) && is_object($this->AnnounceData) && $this->AnnounceData->NumRows() > 0)) {
?>
<ul class="DataList Discussions">
   <?php include($this->FetchViewLocation('discussions')); ?>
</ul>
<?php
   
echo '<div class="PageControls Bottom">';
   echo Gdn_Theme::Module('NewDiscussionModule', array('CssClass' => 'Button Action'));
   PagerModule::Write($PagerOptions);
echo '</div>';

} else {
   ?>
   <div class="Empty"><?php echo T('No discussions were found.'); ?></div>
   <?php
}
