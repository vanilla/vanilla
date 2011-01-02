<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Box RecentUsers">
   <h4><?php echo T('Recently Active Users'); ?></h4>
   <div class="Icons">
      <?php
      $Data = $this->_Sender->RecentUserData;
      foreach ($Data->Result() as $User) {
         $Visitor = UserBuilder($User);
         echo UserPhoto($Visitor);
      }
      ?>
   </div>
</div>