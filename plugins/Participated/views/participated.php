<?php if (!defined('APPLICATION')) exit();
$this->Title(T('Participated Discussions'));
include($this->FetchViewLocation('helper_functions', 'discussions', 'vanilla'));
$ViewLocation = $this->FetchViewLocation('discussions');
?>
<span class="page-title">
<h1 class="HomepageTitle"><?php echo T('ParticipatedHomepageTitle', 'Participated Discussions'); ?></h1>
</span>
<?php
if ($this->DiscussionData->NumRows() > 0) {
?>
<ul class="DataList Discussions Participated">
   <?php include($ViewLocation); ?>
</ul>
<?php
echo $this->Pager->ToString('more');
} else {
   echo '<div class="Empty">'.T('You have not participated in any discussions.').'</div>';
}
