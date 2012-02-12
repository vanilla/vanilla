<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
if ($Session->IsValid() && C('Garden.Modules.ShowSignedInModule')) {
	$Name = $Session->User->Name;

   if (C('EnabledApplications.Conversations')) {
      $CountInbox = $Session->User->CountUnreadConversations;
      $CountInbox = (is_numeric($CountInbox) && $CountInbox > 0) ? $CountInbox : 0;
	}
	$CountNotifications = $Session->User->CountNotifications;
	$CountNotifications = (is_numeric($CountNotifications) && $CountNotifications > 0) ? $CountNotifications : 0;

?>
<div class="Box ProfileBox">
   <h4>My Profile</h4>
   <ul class="PanelInfo">
      <li><?php echo Anchor($Name, 'profile/'.$Session->User->UserID.'/'.Gdn_Format::Url($Name)); ?></li>
      <?php if (C('EnabledApplications.Conversations')) { ?>
      <li><?php echo Anchor(T('Inbox'), '/messages/all'); ?><span class="Aside"><span class="Count"><?php echo $CountInbox; ?></span></span></li>
      <?php } ?>
      <li><?php echo Anchor(T('Notifications'), '/profile/notifications'); ?><span class="Aside"><span class="Count"><?php echo $CountNotifications; ?></span></span></li>
      <?php if ($Session->CheckPermission('Garden.Settings.Manage')) { ?>
      <li><?php echo Anchor(T('Dashboard'), '/dashboard/settings'); ?></li>
      <?php } ?>
      <li><?php echo Anchor(T('Sign Out'), SignOutUrl()); ?></li>
   </ul>
</div>
<?php
}