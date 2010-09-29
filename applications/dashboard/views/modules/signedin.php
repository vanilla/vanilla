<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
if (C('Garden.Modules.ShowSignedInModule') && $Session->IsValid()) {
   $Authenticator = Gdn::Authenticator();
	$Name = $Session->User->Name;

   $CountInbox = $Session->User->CountUnreadConversations;
   $CountInbox = (is_numeric($CountInbox) && $CountInbox > 0) ? $CountInbox : 0;
   
   $CountNotifications = $Session->User->CountNotifications;
   $CountNotifications = (is_numeric($CountNotifications) && $CountNotifications > 0) ? $CountNotifications : 0;
?>
<div class="Box">
   <h4>My Profile</h4>
   <ul class="PanelInfo">
      <li><strong><?php echo Anchor($Name, 'profile/'.$Session->User->UserID.'/'.Gdn_Format::Url($Name)); ?></strong>&nbsp;</li>
      <li><strong><?php echo Anchor('Inbox', '/messages/all'); ?></strong> <?php echo $CountInbox; ?></li>
      <li><strong><?php echo Anchor('Notifications', '/profile/notifications'); ?></strong> <?php echo $CountNotifications; ?></li>
      <li><strong><?php echo Anchor('Sign Out', $Authenticator->SignOutUrl()); ?></strong>&nbsp;</li>
   </ul>
</div>
<?php
}