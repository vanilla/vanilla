<?php if (!defined('APPLICATION')) exit();
$ViewLocation = $this->FetchViewLocation('discussions', 'discussions');
?>
<ul class="Categories">
   <?php foreach ($this->CategoryData->Result() as $Category) {
      $this->Category = $Category;
      $this->DiscussionData = $this->CategoryDiscussionData[$Category->CategoryID];
      if ($this->DiscussionData->NumRows() > 0) {
   ?>
   <li>
      <h1><?php
         echo Anchor($Category->Name, '/discussions/0/'.$Category->CategoryID.'/'.Format::Url($Category->Name));
      ?></h1>
      <ul class="DataList Discussions">
         <?php include($this->FetchViewLocation('discussions', 'discussions')); ?>
      </ul>
      <?php
      if ($this->DiscussionData->NumRows() == $this->DiscussionsPerCategory) {
      ?>
      <div class="More"><?php echo Anchor('More Discussions', '/categories/'.urlencode($this->Category->Name)); ?></div>
      <?php
      }
      ?>
   </li>
   <?php }
   }
   ?>
</ul>