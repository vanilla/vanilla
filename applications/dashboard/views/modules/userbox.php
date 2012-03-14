<?php if (!defined('APPLICATION')) exit();
$User = GetValue('User', $this->_Sender);
if ($User):
   echo '<div class="UserBox">';
      echo UserPhoto($User);
      echo '<div class="WhoIs">';
         echo UserAnchor($User, 'Username');
         echo '<div class="Email">'.GetValue('Email', $User, '').'</div>';
      echo '</div>';
   echo '</div>';
endif;