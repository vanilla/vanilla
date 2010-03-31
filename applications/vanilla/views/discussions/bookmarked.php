<?php if (!defined('APPLICATION')) exit();
$ViewLocation = $this->FetchViewLocation('discussions');
echo $this->Pager->ToString('less');
?>
<h1><?php echo T('Bookmarked Discussions'); ?></h1>
<?php
if ($this->DiscussionData->NumRows() > 0) {
?>
<ul class="DataList Discussions Bookmarks">
   <?php include($ViewLocation); ?>
</ul>
<?php
} else {
?>
<div class="Info EmptyInfo"><?php echo T('You do not have any bookmarks.'); ?></div>
<?php
}
echo $this->Pager->ToString('more');