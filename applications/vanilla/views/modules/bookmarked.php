<?php if (!defined('APPLICATION')) exit();
$Data = (array)$this->_Sender->Data('BookmarkedModuleData', array());
if (count($Data) > 0) {
   $DiscussionView = $this->FetchViewLocation('discussion');
   ?>
<div id="Bookmarks" class="Box">
   <h4><?php echo T('Bookmarked Discussions'); ?></h4>
   <ul id="Bookmark_List" class="PanelInfo PanelDiscussions">
      <?php
      foreach ($Data as $Discussion) {
         include($DiscussionView);
      }
      if (count($Data) > 10) {
      ?>
      <li class="ShowAll"><?php echo Anchor(T('↳ Show All'), 'discussions/bookmarked'); ?></li>
      <?php } ?>
   </ul>
</div>
   <?php
}