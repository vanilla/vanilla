<?php if (!defined('APPLICATION')) exit();
$User = GetValue('User', $this->_Sender);
if ($User):
   echo '<div class="UserBox">';
      echo UserPhoto($User);
      echo '<div class="WhoIs">';
         echo UserAnchor($User, 'Username');
         echo '<div class="Email">';

         // Only show the email address if allowed.
         if (GetValue('UserID', $User) == Gdn::Session()->UserID || Gdn::Session()->CheckPermission('Garden.Moderation.Manage'))
            echo GetValue('Email', $User, '');
         else
            echo '&nbsp;';

         echo '</div>';
      echo '</div>';
   echo '</div>';
endif;