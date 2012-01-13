<?php if (!defined('APPLICATION')) exit();
require_once $this->FetchViewLocation('helper_functions');
?>
<div id="Bookmarks" class="Box BoxBookmarks">
   <h4><?php echo T('Bookmarked Discussions'); ?></h4>
   <ul id="Bookmark_List" class="PanelInfo PanelDiscussions">
      <?php
      foreach ($this->Data->Result() as $Discussion) {
         WriteModuleDiscussion($Discussion);
      }
      if ($this->Data->NumRows() == $this->Limit) {
      ?>
      <li class="ShowAll"><?php echo Anchor(T('All Bookmarks'), 'discussions/bookmarked'); ?></li>
      <?php } ?>
   </ul>
</div>