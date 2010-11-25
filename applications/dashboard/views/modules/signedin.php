<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
if (C('Garden.Modules.ShowSignedInModule') && $Session->IsValid()) {
   $Authenticator = Gdn::Authenticator();
	$Name = $Session->User->Name;

   if (C('EnabledApplications.Conversations')) {
      $CountInbox = $Session->User->CountUnreadConversations;
      $CountInbox = (is_numeric($CountInbox) && $CountInbox > 0) ? $CountInbox : 0;
      
      $CountNotifications = $Session->User->CountNotifications;
      $CountNotifications = (is_numeric($CountNotifications) && $CountNotifications > 0) ? $CountNotifications : 0;
   }
?>
<div class="Box">
   <h4>My Profile</h4>
   <ul class="PanelInfo">
      <li><strong><?php echo Anchor($Name, 'profile/'.$Session->User->UserID.'/'.Gdn_Format::Url($Name)); ?></strong>&nbsp;</li>
      <?php if (C('EnabledApplications.Conversations')) { ?>
      <li><strong><?php echo Anchor(T('Inbox'), '/messages/all'); ?></strong> <?php echo $CountInbox; ?></li>
      <?php } ?>
      <li><strong><?php echo Anchor(T('Notifications'), '/profile/notifications'); ?></strong> <?php echo $CountNotifications; ?></li>
      <li><strong><?php echo Anchor(T('Sign Out'), $Authenticator->SignOutUrl()); ?></strong>&nbsp;</li>
      <?php if ($Session->CheckPermission('Garden.Settings.Manage')) { ?>
      <li><strong><?php echo Anchor(T('Dashboard'), '/dashboard/settings'); ?></strong>&nbsp;</li>
      <?php } ?>
   </ul>
</div>
<?php
}