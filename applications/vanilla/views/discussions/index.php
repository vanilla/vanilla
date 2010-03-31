<?php if (!defined('APPLICATION')) exit();
$DiscussionData = $this->DiscussionData;
$this->DiscussionData = $this->AnnounceData;
$HasAnnouncements = $this->AnnounceData && $this->AnnounceData->NumRows() > 0;
if ($HasAnnouncements) {
?>
<h1 id="AnnouncementsHeading"><?php echo T('Announcements'); ?></h1>
<ul class="DataList Announcements">
   <?php include($this->FetchViewLocation('discussions')); ?>
</ul>
<?php
}
$this->DiscussionData = $DiscussionData;
if ($this->DiscussionData->NumRows() > 0) {
?>
<h1><?php
if (is_object($this->Category))
   echo sprintf(T('Discussions <span>&bull;</span> %s'), $this->Category->Name);
else
   echo T('Discussions');

?></h1>
<ul class="DataList Discussions">
   <?php include($this->FetchViewLocation('discussions')); ?>
</ul>
<?php echo $this->Pager->ToString('more');
} else if (!$HasAnnouncements) {
   ?>
   <h1><?php echo T('Discussions'); ?></h1>
   <div class="Info EmptyInfo"><?php echo T('No discussions up in here...'); ?></div>
<?php
}
