<?php if (!defined('APPLICATION')) exit();
include($this->FetchViewLocation('helper_functions', 'discussions', 'vanilla'));
?>
<div class="TaggedHeading"><?php printf("Questions tagged with '%s'", htmlspecialchars($this->Tag)); ?></div>
<?php if ($this->DiscussionData->NumRows() > 0) { ?>
<ul class="DataList Discussions">
   <?php include($this->FetchViewLocation('discussions')); ?>
</ul>
<?php
   echo $this->Pager->ToString('more');
} else {
   ?>
   <div class="Empty"><?php printf(T('No items tagged with %s.'), htmlspecialchars($this->Tag)); ?></div>
   <?php
}
