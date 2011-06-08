<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Box">
   <h4><?php echo T('In this Conversation'); ?></h4>
   <ul class="PanelInfo">
   <?php
   $Result = $this->Data->Result();
   foreach ($this->Data->Result() as $User) {
      echo '<li>';

      if (GetValue('Deleted', $User))
         echo Wrap(UserAnchor($User, 'UserLink'), 'del',
            array('title' => sprintf(T('%s has left this conversation.'), htmlspecialchars(GetValue('Name', $User))))
            );
      else
         echo Wrap(UserAnchor($User, 'UserLink'), 'strong');
      
      echo Gdn_Format::Date($User->DateLastActive);

      echo '</li>';
   }
   ?>
   </ul>
</div>