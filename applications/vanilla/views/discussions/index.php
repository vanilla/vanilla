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


$PagerOptions = array('Wrapper' => '<span class="PagerNub">&#160;</span><div %1$s>%2$s</div>', 'RecordCount' => $this->Data('CountDiscussions'), 'CurrentRecords' => $this->Data('Discussions')->NumRows());
if ($this->Data('_PagerUrl'))
   $PagerOptions['Url'] = $this->Data('_PagerUrl');

echo '<div class="PageControls Top">';
   PagerModule::Write($PagerOptions);
   echo Gdn_Theme::Module('NewDiscussionModule', $this->Data('_NewDiscussionProperties', array('CssClass' => 'Button Action Primary')));
echo '</div>';

if ($this->DiscussionData->NumRows() > 0 || (isset($this->AnnounceData) && is_object($this->AnnounceData) && $this->AnnounceData->NumRows() > 0)) {
?>
<ul class="DataList Discussions">
   <?php include($this->FetchViewLocation('discussions')); ?>
</ul>
<?php
   
echo '<div class="PageControls Bottom">';
   PagerModule::Write($PagerOptions);
   echo Gdn_Theme::Module('NewDiscussionModule', $this->Data('_NewDiscussionProperties', array('CssClass' => 'Button Action Primary')));
echo '</div>';

} else {
   ?>
   <div class="Empty"><?php echo T('No discussions were found.'); ?></div>
   <?php
}
