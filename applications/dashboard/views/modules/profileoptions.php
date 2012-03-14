<?php if (!defined('APPLICATION')) exit();
/** Displays the "Edit My Profile" or "Back to Profile" buttons on the top of the profile page. */
$Controller = Gdn::Controller();
?>
<div class="ProfileOptions">
   <?php
   $Controller->FireEvent('BeforeProfileOptions');
   if ($Controller->EditMode) {
      echo UserAnchor($Controller->User, 'BackToProfile NavButton', array('Text' => T('Back to Profile')));
   } else {
      $Session = Gdn::Session();
      
      if (Gdn::Session()->CheckPermission('Garden.Moderation.Manage') && $Controller->User->UserID != $Session->UserID) {
         if ($Controller->User->Banned) {
            echo ' '.Anchor(T('Unban'), '/user/ban?userid='.$Controller->User->UserID.'&unban=1', 'Button Popup').' ';
         } else {
            echo ' '.Anchor(T('Ban'), '/user/ban?userid='.$Controller->User->UserID, 'Button Popup').' ';
         }
      }
      
      if ($Controller->User->UserID != $Session->UserID) {
         if ($Session->CheckPermission('Garden.Users.Edit'))
            echo ' '.Anchor(Sprite('SpEditProfile').T('Edit Profile'), '/profile/edit/'.$Controller->User->UserID.'/'.rawurlencode($Controller->User->Name), 'NavButton').' ';
      } else {
         if (C('Garden.UserAccount.AllowEdit')) // Don't allow account editing if it is turned off.
            echo Anchor(Sprite('SpEditProfile').T('Edit My Profile'), '/profile/edit', 'NavButton');
      }
   }
   $Controller->FireEvent('AfterProfileOptions');
   ?>
</div>
