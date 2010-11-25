<?php if (!defined('APPLICATION')) exit();
$CountDiscussions = 0;
$CategoryID = isset($this->_Sender->CategoryID) ? $this->_Sender->CategoryID : '';

if ($this->Data !== FALSE) {
   foreach ($this->Data->Result() as $Category) {
      $CountDiscussions = $CountDiscussions + $Category->CountDiscussions;
   }
   ?>
<div class="Box">
   <h4><?php echo T('Categories'); ?></h4>
   <ul class="PanelInfo">
      <li<?php
      if (!is_numeric($CategoryID))
         echo ' class="Active"';
         
      ?>><strong><?php echo Anchor(Gdn_Format::Text(T('All Discussions')), '/discussions'); ?></strong> <?php echo $CountDiscussions; ?></li>
      <?php
   $ParentName = '';
   foreach ($this->Data->Result() as $Category) {
      if ($Category->ParentName != '' && $Category->ParentName != $ParentName) {
         $ParentName = $Category->ParentName;
         ?>
         <li class="Parent"><?php echo Gdn_Format::Text($Category->ParentName); ?></li>
         <?php
      }
      ?>
      <li<?php
      if ($CategoryID == $Category->CategoryID)
         echo ' class="Active"';
         
      ?>><strong><?php echo Anchor(Gdn_Format::Text($Category->Name), '/categories/'.$Category->UrlCode); ?></strong> <?php echo $Category->CountDiscussions; ?></li>
      <?php
   }
      ?>
   </ul>
</div>
   <?php
}