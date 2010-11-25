<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Box">
   <h4><?php echo T('In this Conversation'); ?></h4>
   <ul class="PanelInfo">
   <?php
   foreach ($this->Data->Result() as $User) {
      if($User->Deleted)
         echo '<li class="Deleted">';
      else
         echo '<li>';

      echo Wrap(UserAnchor($User, 'UserLink'), 'strong',
         $User->Deleted ?
         array('title' => sprintf(T('%s deleted this conversation.'), $User->Name))
         : '');
      echo Gdn_Format::Date($User->DateLastActive);

      echo '</li>';
   }
   ?>
   </ul>
</div>