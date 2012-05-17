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
      
      if ($Controller->User->UserID != $Session->UserID) {
         if ($Session->CheckPermission('Garden.Users.Edit'))
            echo ' '.Anchor(Sprite('SpEditProfile').T('Edit Profile'), '/profile/edit/'.$Controller->User->UserID.'/'.rawurlencode($Controller->User->Name), 'NavButton').' ';
      } else {
         if (C('Garden.UserAccount.AllowEdit')) // Don't allow account editing if it is turned off.
            echo Anchor(Sprite('SpEditProfile').T('Edit My Profile'), '/profile/edit', 'NavButton');
      }
   }
   $Controller->FireEvent('AfterProfileOptions');
   
   $this->FireEvent('AdvancedProfileOptions');
   $Advanced = $this->Data('Advanced');
   if (!empty($Advanced)) {
      ?>
      <span class="ToggleFlyout OptionsMenu">
         <span class="OptionsTitle" title="<?php echo T('Options'); ?>"><?php echo T('Options'); ?></span>
         <span class="SpFlyoutHandle"></span>
         <ul class="Flyout MenuItems" style="display: none;">
         <?php foreach ($Advanced as $Code => $Options) : ?>
            <li><?php echo Anchor($Options['Label'], $Options['Url'], GetValue('Class', $Options, $Code)); ?></li>
         <?php endforeach; ?>
         </ul>
      </span>
      <?php
   }
   ?>
</div>
