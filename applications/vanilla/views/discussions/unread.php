<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
include_once $this->FetchViewLocation('helper_functions', 'discussions', 'vanilla');

echo '<span class="page-title"><h1 class="H HomepageTitle page-title">'.
   AdminCheck(NULL, array('', ' ')).
   $this->Data('Title').
   '</h1></span>';

if ($Description = $this->Description()) {
   echo Wrap($Description, 'div', array('class' => 'P PageDescription'));
}
// echo Gdn_Theme::Module('DiscussionFilterModule');

if ($this->DiscussionData->NumRows() > 0 || (isset($this->AnnounceData) && is_object($this->AnnounceData) && $this->AnnounceData->NumRows() > 0)) {
?>
<ul class="DataList Discussions">
   <?php include($this->FetchViewLocation('discussions')); ?>
</ul>
<?php
   $PagerOptions = array('RecordCount' => $this->Data('CountDiscussions'), 'CurrentRecords' => $this->Data('Discussions')->NumRows());
   if ($this->Data('_PagerUrl'))
      $PagerOptions['Url'] = $this->Data('_PagerUrl');
   
   PagerModule::Write($PagerOptions);
} else {
   ?>
   <div class="Empty"><?php echo T('No discussions were found.'); ?></div>
   <?php
}
