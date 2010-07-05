<?php if (!defined('APPLICATION')) exit();
$this->Title(T('My Bookmarks'));
include($this->FetchViewLocation('helper_functions', 'discussions', 'vanilla'));

WriteFilterTabs($this);
if ($this->DiscussionData->NumRows() > 0) {
?>
<ul class="DataList Discussions Bookmarks">
   <?php include($this->FetchViewLocation('discussions')); ?>
</ul>
<?php
   echo $this->Pager->ToString('more');
} else {
   ?>
   <div class="Empty"><?php echo T('No discussions were found.'); ?></div>
   <?php
}