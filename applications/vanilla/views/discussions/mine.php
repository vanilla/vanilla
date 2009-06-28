<?php if (!defined('APPLICATION')) exit();
$ViewLocation = $this->FetchViewLocation('discussions');
if ($this->DiscussionData->NumRows() > 0) {
?>
<h1><?php echo Gdn::Translate('My Discussions'); ?></h1>
<?php echo $this->Pager->ToString('less'); ?>
<ul class="DataList Discussions Mine">
   <?php include($ViewLocation); ?>
</ul>
<?php
echo $this->Pager->ToString('more');
} else {
   echo '<p>'.Gdn::Translate('You have not started any discussions.').'</p>';
}
