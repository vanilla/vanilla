<?php if (!defined('APPLICATION')) exit();
include($this->FetchViewLocation('helper_functions', 'discussions', 'vanilla'));
$DiscussionData = $this->DiscussionData;
$this->DiscussionData = $this->AnnounceData;
$HasAnnouncements = $this->AnnounceData && $this->AnnounceData->NumRows() > 0;
WriteFilterTabs($this);
if ($this->DiscussionData->NumRows() > 0) {
?>
<ul class="DataList Discussions">
   <?php
   if ($HasAnnouncements) {
      include($this->FetchViewLocation('discussions'));
      $this->DiscussionData = $DiscussionData;
   }

   include($this->FetchViewLocation('discussions'));
   ?>
</ul>
<?php
   echo $this->Pager->ToString('more');
} else if (!$HasAnnouncements) {
   ?>
   <div class="Empty"><?php echo T('No discussions were found.'); ?></div>
   <?php
}
