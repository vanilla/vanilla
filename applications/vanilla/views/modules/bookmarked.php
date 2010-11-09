<?php if (!defined('APPLICATION')) exit();
$Data = $this->_Sender->Data('BookmarkedModuleData');
if (is_object($Data) && $Data->NumRows() > 0) {
   $DiscussionView = $this->FetchViewLocation('discussion');
   ?>
<div id="Bookmarks" class="Box">
   <h4><?php echo T('Bookmarked Discussions'); ?></h4>
   <ul id="Bookmark_List" class="PanelInfo PanelDiscussions">
      <?php
      foreach ($Data->Result() as $Discussion) {
         include($DiscussionView);
      }
      if ($Data->NumRows() >= 10) {
      ?>
      <li class="ShowAll"><?php echo Anchor(T('↳ Show All'), 'discussions/bookmarked'); ?></li>
      <?php } ?>
   </ul>
</div>
   <?php
}