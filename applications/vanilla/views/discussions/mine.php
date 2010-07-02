<?php if (!defined('APPLICATION')) exit();
$this->Title(T('My Discussions'));
include($this->FetchViewLocation('helper_functions', 'discussions', 'vanilla'));
$ViewLocation = $this->FetchViewLocation('discussions');
WriteFilterTabs($this);
if ($this->DiscussionData->NumRows() > 0) {
echo $this->Pager->ToString('less');
?>
<ul class="DataList Discussions Mine">
   <?php include($ViewLocation); ?>
</ul>
<?php
echo $this->Pager->ToString('more');
} else {
   echo '<div class="Empty">'.T('You have not started any discussions.').'</div>';
}
