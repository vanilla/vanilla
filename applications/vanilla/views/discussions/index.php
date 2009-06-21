<?php if (!defined('APPLICATION')) exit();
$DiscussionData = $this->DiscussionData;
$this->DiscussionData = $this->AnnounceData;
if ($this->AnnounceData && $this->AnnounceData->NumRows() > 0) {
?>
<h1 id="AnnouncementsHeading"><?php echo Gdn::Translate('Announcements'); ?></h1>
<ul class="Announcements Discussions">
   <?php include($this->FetchViewLocation('discussions')); ?>
</ul>
<?php
}
$this->DiscussionData = $DiscussionData;
if ($this->DiscussionData->NumRows() > 0) {
?>
<h1><?php
if (is_object($this->Category))
   echo sprintf(Gdn::Translate('Discussions <span>&bull;</span> %s'), $this->Category->Name);
else
   echo Gdn::Translate('Discussions');

?></h1>
<?php echo $this->Pager->ToString('less'); ?>
<ul class="Discussions">
   <?php include($this->FetchViewLocation('discussions')); ?>
</ul>
<?php echo $this->Pager->ToString('more');
}
