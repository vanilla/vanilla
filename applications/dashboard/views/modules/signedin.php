<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::session();
if ($Session->isValid() && c('Garden.Modules.ShowSignedInModule')) {
    $Name = $Session->User->Name;

    if (c('EnabledApplications.Conversations')) {
        $CountInbox = $Session->User->CountUnreadConversations;
        $CountInbox = (is_numeric($CountInbox) && $CountInbox > 0) ? $CountInbox : 0;
    }
    $CountNotifications = $Session->User->CountNotifications;
    $CountNotifications = (is_numeric($CountNotifications) && $CountNotifications > 0) ? $CountNotifications : 0;

    ?>
    <div class="Box ProfileBox">
        <h4>My Profile</h4>
        <ul class="PanelInfo">
            <li><?php echo anchor($Name, 'profile/'.$Session->User->UserID.'/'.Gdn_Format::url($Name)); ?></li>
            <?php if (c('EnabledApplications.Conversations')) { ?>
                <li><?php echo anchor(t('Inbox'), '/messages/all'); ?><span class="Aside"><span
                            class="Count"><?php echo $CountInbox; ?></span></span></li>
            <?php } ?>
            <li><?php echo anchor(t('Notifications'), '/profile/notifications'); ?><span class="Aside"><span
                        class="Count"><?php echo $CountNotifications; ?></span></span></li>
            <?php if ($Session->checkPermission('Garden.Settings.Manage')) { ?>
                <li><?php echo anchor(t('Dashboard'), '/dashboard/settings'); ?></li>
            <?php } ?>
            <li><?php echo anchor(t('Sign Out'), signOutUrl()); ?></li>
        </ul>
    </div>
<?php
}
