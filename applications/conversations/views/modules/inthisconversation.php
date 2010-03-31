<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Box">
   <h4><?php echo T('In this Conversation'); ?></h4>
   <ul class="PanelInfo">
   <?php
   foreach ($this->_UserData->Result() as $User) {
      ?>
      <li>
         <strong><?php
            echo UserAnchor($User, 'UserLink');
         ?></strong>
         <?php
            echo Format::Date($User->DateLastActive);
         ?>
      </li>
      <?php
   }
   ?>
   </ul>
</div>