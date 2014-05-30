<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Box InThisConversation">
   <?php echo panelHeading(T('In this Conversation')); ?>
   <ul class="PanelInfo">
   <?php
   $Result = $this->Data->Result();
   foreach ($this->Data->Result() as $User) {
      echo '<li>';

      if (GetValue('Deleted', $User))
         echo Wrap(UserPhoto($User, array('ImageClass' => 'ProfilePhotoSmall')).' '.UserAnchor($User, 'UserLink'), 'del',
            array('title' => sprintf(T('%s has left this conversation.'), htmlspecialchars(GetValue('Name', $User))))
            );
      else
         echo UserPhoto($User, array('ImageClass' => 'ProfilePhotoSmall')).' '.UserAnchor($User, 'UserLink');

      echo '</li>';
   }
   ?>
   </ul>
</div>