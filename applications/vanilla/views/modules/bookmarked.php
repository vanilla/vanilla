<?php if (!defined('APPLICATION')) exit();

if ($this->_DiscussionData !== FALSE && $this->_DiscussionData->NumRows() > 0) {
   $DiscussionView = $this->FetchViewLocation('discussion');
   ?>
<div class="Box">
   <h4><?php echo Gdn::Translate('Bookmarked Discussions'); ?></h4>
   <ul class="PanelDiscussions">
      <?php
   foreach ($this->_DiscussionData->Result() as $Discussion) {
      include($DiscussionView);
   }
      ?>
   </ul>
</div>
   <?php
}