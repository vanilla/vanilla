<?php if (!defined('APPLICATION')) exit();
$ViewLocation = $this->FetchViewLocation('discussions', 'discussions');
?>
<div class="Categories">
   <?php foreach ($this->CategoryData->Result() as $Category) {
      $this->Category = $Category;
      $this->DiscussionData = $this->CategoryDiscussionData[$Category->CategoryID];
      if ($this->DiscussionData->NumRows() > 0) {
   ?>
   <div class="Tabs CategoryTabs">
      <ul>
         <li class="Active"><?php echo Anchor($Category->Name, '/categories/'.$Category->UrlCode); ?></li>
      </ul>
   </div>
   <ul class="DataList Discussions">
      <?php include($this->FetchViewLocation('discussions', 'discussions')); ?>
   </ul>
   <?php if ($this->DiscussionData->NumRows() == $this->DiscussionsPerCategory) { ?>
   <div class="More"><?php echo Anchor('More Discussions', '/categories/'.$Category->UrlCode); ?></div>
   <?php } ?>
   <?php }
   }
   ?>
</div>