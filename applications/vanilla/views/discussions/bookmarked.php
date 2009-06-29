<?php if (!defined('APPLICATION')) exit();
$ViewLocation = $this->FetchViewLocation('discussions');
echo $this->Pager->ToString('less');
if ($this->DiscussionData->NumRows() > 0) {
?>
<h1><?php echo Gdn::Translate('Bookmarked Discussions'); ?></h1>
<ul class="DataList Discussions Bookmarks">
   <?php include($ViewLocation); ?>
</ul>
<?php
} else {
   echo '<p>'.Gdn::Translate('You do not have any bookmarked discussions.').'</p>';
}
echo $this->Pager->ToString('more');