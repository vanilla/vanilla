<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Box RecentUsers">
   <h4><?php echo T('Recently Active Users'); ?></h4>
   <ul class="PanelInfo">
      <?php
      $Data = $this->_Sender->Data['RecentUserData'];
      foreach ($Data->Result() as $User) {
         ?>
         <li><strong><?php
            $Visitor = UserBuilder($User);
            echo UserAnchor($Visitor);
         ?></strong> <?php
            echo Gdn_Format::Date($User->DateLastActive);
         ?></li>
         <?php
      }
      ?>
   </ul>
</div>