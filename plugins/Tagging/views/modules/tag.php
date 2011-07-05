<?php if (!defined('APPLICATION')) return; ?>
<div class="Box Tags">
   <h4><?php echo T($this->_DiscussionID > 0 ? 'Tagged' : 'Popular Tags'); ?></h4>
   <ul class="PanelInfo">
   <?php
   foreach ($this->_TagData as $Tag) {
      $Name = $Tag['Name'];
      if (empty($Name))
         continue;
      ?>
      <li><strong><?php echo Anchor(htmlspecialchars($Name), 'discussions/tagged/'.rawurlencode($Name)); ?></strong>
         <span class="Count"><?php echo Gdn_Format::BigNumber($Tag['CountDiscussions']); ?></span></li>
      <?php
   }
   ?>
   </ul>
</div>