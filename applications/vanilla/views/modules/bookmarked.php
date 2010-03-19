<?php if (!defined('APPLICATION')) exit();

if ($this->_DiscussionData !== FALSE && $this->_DiscussionData->NumRows() > 0) {
   $DiscussionView = $this->FetchViewLocation('discussion');
   ?>
<div id="Bookmarks" class="Box">
   <h4><?php echo Gdn::Translate('Bookmarked Discussions'); ?></h4>
   <ul id="Bookmark_List" class="PanelInfo PanelDiscussions">
      <?php
      foreach ($this->_DiscussionData->Result() as $Discussion) {
         include($DiscussionView);
      }
      if ($this->_DiscussionData->NumRows() >= 10) {
      ?>
      <li class="ShowAll"><?php echo Anchor(Gdn::Translate('↳ Show All'), 'discussions/bookmarked'); ?></li>
      <?php } ?>
   </ul>
</div>
   <?php
}