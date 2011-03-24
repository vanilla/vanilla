<?php if (!defined('APPLICATION')) exit();
$ViewLocation = $this->FetchViewLocation('discussions', 'discussions');
?>
<div class="Categories">
   <?php foreach ($this->CategoryData->Result() as $Category) {
      if ($Category->CategoryID <= 0)
         continue;

      $this->Category = $Category;
      $this->DiscussionData = $this->CategoryDiscussionData[$Category->CategoryID];
      if ($this->DiscussionData->NumRows() > 0) {
   ?>
   <div class="CategoryBox Category-<?php echo $Category->UrlCode; ?>">
      <div class="Tabs CategoryTabs">
         <ul>
            <li class="Active"><?php echo Anchor($Category->Name, '/categories/'.$Category->UrlCode); ?></li>
         </ul>
      </div>
      <ul class="DataList Discussions">
         <?php include($this->FetchViewLocation('discussions', 'discussions')); ?>
      </ul>
      <?php if ($this->DiscussionData->NumRows() == $this->DiscussionsPerCategory) { ?>
      <div class="Foot"><?php echo Anchor(T('More Discussions'), '/categories/'.$Category->UrlCode, 'TabLink'); ?></div>
      <?php
         }
      ?></div><?php
      }
   }
   ?>
</div>