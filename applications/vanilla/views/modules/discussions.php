<?php if (!defined('APPLICATION')) exit();
$DiscussionView = $this->FetchViewLocation('discussion');
?>
<div id="Bookmarks" class="Box BoxDiscussions">
   <h4><?php echo T('Recent Discussions'); ?></h4>
   <ul id="Bookmark_List" class="PanelInfo PanelDiscussions">
      <?php
      foreach ($this->Data->Result() as $Discussion) {
         include($DiscussionView);
      }
      if ($this->Data->NumRows() >= 10) {
      ?>
      <li class="ShowAll"><?php echo Anchor(T('↳ Show All'), 'discussions'); ?></li>
      <?php } ?>
   </ul>
</div>