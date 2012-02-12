<?php if (!defined('APPLICATION')) exit();
$this->Title(T('My Bookmarks'));
include($this->FetchViewLocation('helper_functions', 'discussions', 'vanilla'));

// WriteFilterTabs($this);
if ($this->DiscussionData->NumRows() > 0 || (is_object($this->AnnounceData) && $this->AnnounceData->NumRows() > 0)) {
?>
<ul class="DataList Discussions Bookmarks">
   <?php include($this->FetchViewLocation('discussions')); ?>
</ul>
<?php
   $PagerOptions = array('RecordCount' => $this->Data('CountDiscussions'), 'CurrentRecords' => $this->Data('Discussions')->NumRows());
   if ($this->Data('_PagerUrl')) {
      $PagerOptions['Url'] = $this->Data('_PagerUrl');
   }
   echo PagerModule::Write($PagerOptions);
} else {
   ?>
   <div class="Empty"><?php echo T('No discussions were found.'); ?></div>
   <?php
}