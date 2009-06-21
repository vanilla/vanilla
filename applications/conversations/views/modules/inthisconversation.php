<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Box">
   <h4><?php echo Gdn::Translate('In this Conversation'); ?></h4>
   <ul class="PanelInfo">
   <?php
   foreach ($this->_UserData->Result() as $User) {
      ?>
      <li>
         <h2><?php
            echo Anchor($User->Name, '/profile/'.urlencode($User->Name), 'UserLink');
         ?></h2>
         <?php
            echo Format::Date($User->DateLastActive);
         ?>
      </li>
      <?php
   }
   ?>
   </ul>
</div>