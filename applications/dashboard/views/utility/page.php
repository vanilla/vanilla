<?php if (!defined('APPLICATION')) exit(); ?>
<div class="PageBox">
   <h3><?php echo Anchor(htmlspecialchars($this->Data('PageInfo.Title')), $this->Data('PageInfo.Url')); ?></h3>
   <div class="Thumbnail">
      <?php
      foreach ($this->Data('PageInfo.Images') as $Src) {
         echo Img($Src);
      }
      ?>
   </div>
   <div class="Description">
      <?php echo htmlspecialchars($this->Data('PageInfo.Description')); ?>
   </div>
</div>