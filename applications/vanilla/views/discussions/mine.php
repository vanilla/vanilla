<?php if (!defined('APPLICATION')) exit();
$ViewLocation = $this->FetchViewLocation('discussions');
if ($this->DiscussionData->NumRows() > 0) {
?>
<h1><?php echo T('My Discussions'); ?></h1>
<?php echo $this->Pager->ToString('less'); ?>
<ul class="DataList Discussions Mine">
   <?php include($ViewLocation); ?>
</ul>
<?php
echo $this->Pager->ToString('more');
} else {
   echo '<p>'.T('You have not started any discussions.').'</p>';
}
