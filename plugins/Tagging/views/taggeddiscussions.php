<?php if (!defined('APPLICATION')) exit();
include($this->FetchViewLocation('helper_functions', 'discussions', 'vanilla'));
?>
<h1 class="H TaggedHeading"><?php printf(T('Tagged with "%s"'), htmlspecialchars($this->Tag)); ?></h1>
<?php if ($this->DiscussionData->NumRows() > 0) { ?>
<ul class="DataList Discussions">
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
   <div class="Empty"><?php printf(T('No items tagged with %s.'), htmlspecialchars($this->Tag)); ?></div>
   <?php
}
