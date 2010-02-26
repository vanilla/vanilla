<?php if (!defined('APPLICATION')) exit();
$CountDiscussions = 0;
$CategoryID = isset($this->_Sender->CategoryID) ? $this->_Sender->CategoryID : '';

if ($this->_CategoryData !== FALSE) {
   foreach ($this->_CategoryData->Result() as $Category) {
      $CountDiscussions = $CountDiscussions + $Category->CountDiscussions;
   }
   ?>
<div class="Box">
   <h4><?php echo Gdn::Translate('Categories'); ?></h4>
   <ul class="PanelInfo">
      <li<?php
      if (!is_numeric($CategoryID))
         echo ' class="Active"';
         
      ?>><strong><?php echo Anchor(Format::Text(Gdn::Translate('All Discussions')), '/discussions'); ?></strong> <?php echo $CountDiscussions; ?></li>
      <?php
   foreach ($this->_CategoryData->Result() as $Category) {
      ?>
      <li<?php
      if ($CategoryID == $Category->CategoryID)
         echo ' class="Active"';
         
      ?>><strong><?php echo Anchor(Format::Text(str_replace('&rarr;', '→', $Category->Name)), '/categories/'.$Category->UrlCode); ?></strong> <?php echo $Category->CountDiscussions; ?></li>
      <?php
   }
      ?>
   </ul>
</div>
   <?php
}