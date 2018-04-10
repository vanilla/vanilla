<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::session();
$User = $Session->User;
$CssClass = '';
$transientKey = Gdn::session()->transientKey();

if ($this->CssClass)
    $CssClass .= ' '.$this->CssClass;

$DashboardCount = 0;
$ModerationCount = 0;
// Spam & Moderation Queue
if ($Session->checkPermission(['Garden.Settings.Manage', 'Garden.Moderation.Manage', 'Moderation.Spam.Manage', 'Moderation.ModerationQueue.Manage'], false)) {
    $LogModel = new LogModel();
    //$SpamCount = $LogModel->getOperationCount('spam');
    $ModerationCount = $LogModel->getOperationCount('moderate,pending');
    $DashboardCount += $ModerationCount;
}
// Applicant Count
if ($Session->checkPermission('Garden.Users.Approve')) {
    $RoleModel = new RoleModel();
    $ApplicantCount = $RoleModel->getApplicantCount();
    $DashboardCount += $ApplicantCount;
} else {
    $ApplicantCount = null;
}

$this->EventArguments['DashboardCount'] = &$DashboardCount;
$this->fireEvent('BeforeFlyoutMenu');

if ($Session->isValid()):
    echo '<div class="MeBox'.$CssClass.'">';
    echo userPhoto($User);
    echo '<div class="WhoIs">';
    echo userAnchor($User, 'Username');
    echo '<div class="MeMenu">';
    // Notifications
    $CountNotifications = $User->CountNotifications;
    $CNotifications = is_numeric($CountNotifications) && $CountNotifications > 0 ? '<span class="Alert NotificationsAlert">'.$CountNotifications.'</span>' : '';

    echo '<span class="ToggleFlyout" rel="/profile/notificationspopin?TransientKey='.htmlspecialchars(urlencode($transientKey)).'">';
    echo anchor(sprite('SpNotifications', 'Sprite Sprite16', t('Notifications')).$CNotifications, userUrl($User), 'MeButton FlyoutButton js-clear-notifications', ['title' => t('Notifications'), 'tabindex' => '0', "role" => "button", "aria-haspopup" => "true"]);
    echo sprite('SpFlyoutHandle', 'Arrow');
    echo '<div class="Flyout FlyoutMenu"></div></span>';

    // Inbox
    if (Gdn::addonManager()->lookupAddon('conversations')) {
        $CountInbox = val('CountUnreadConversations', Gdn::session()->User);
        $CInbox = is_numeric($CountInbox) && $CountInbox > 0 ? ' <span class="Alert">'.$CountInbox.'</span>' : '';
        echo '<span class="ToggleFlyout" rel="/messages/popin">';
        echo anchor(sprite('SpInbox', 'Sprite Sprite16', t('Inbox')).$CInbox, '/messages/all', 'MeButton FlyoutButton', ['title' => t('Inbox'), 'tabindex' => '0', "role" => "button", "aria-haspopup" => "true"]);
        echo sprite('SpFlyoutHandle', 'Arrow');
        echo '<div class="Flyout FlyoutMenu"></div></span>';
    }

    // Bookmarks
    if (Gdn::addonManager()->lookupAddon('Vanilla')) {
        echo '<span class="ToggleFlyout" rel="/discussions/bookmarkedpopin">';
        echo anchor(sprite('SpBookmarks', 'Sprite Sprite16', t('Bookmarks')), '/discussions/bookmarked', 'MeButton FlyoutButton', ['title' => t('Bookmarks'), 'tabindex' => '0', "role" => "button", "aria-haspopup" => "true"]);
        echo sprite('SpFlyoutHandle', 'Arrow');
        echo '<div class="Flyout FlyoutMenu"></div></span>';
    }

    // Profile Settings & Logout
    $dropdown = new DropdownModule();
    $dropdown->setData('DashboardCount', $DashboardCount);
    $triggerTitle = t('Account Options');
    $triggerIcon = sprite('SpOptions', 'Sprite Sprite16', $triggerTitle);
    $dropdown->setTrigger('', 'anchor', 'MeButton FlyoutButton', $triggerIcon, '/profile/edit', ['title' => $triggerTitle, 'tabindex' => '0', "role" => "button", "aria-haspopup" => "true"]);
    $editModifiers['listItemCssClasses'] = ['EditProfileWrap', 'link-editprofile'];
    $preferencesModifiers['listItemCssClasses'] = ['EditProfileWrap', 'link-preferences'];

    $dropdown->addLinkIf(hasEditProfile(Gdn::session()->UserID), t('Edit Profile'), '/profile/edit', 'profile.edit', '', [], $editModifiers);
    $dropdown->addLinkIf(!hasEditProfile(Gdn::session()->UserID), t('Preferences'), '/profile/preferences', 'profile.preferences', '', [], $preferencesModifiers);

    $applicantModifiers = $ApplicantCount > 0 ? ['badge' => $ApplicantCount] : [];
    $applicantModifiers['listItemCssClasses'] = ['link-applicants'];
    $modModifiers = $ModerationCount > 0 ? ['badge' => $ModerationCount] : [];
    $modModifiers['listItemCssClasses'] = ['link-moderation'];
    $spamModifiers['listItemCssClasses'] = ['link-spam'];
    $dashboardModifiers['listItemCssClasses'] = ['link-dashboard'];
    $signoutModifiers['listItemCssClasses'] = ['link-signout', 'SignInOutWrap', 'SignOutWrap'];

    $spamPermission = $Session->checkPermission(['Garden.Settings.Manage', 'Garden.Moderation.Manage', 'Moderation.ModerationQueue.Manage'], false);
    $modPermission = $Session->checkPermission(['Garden.Settings.Manage', 'Garden.Moderation.Manage', 'Moderation.ModerationQueue.Manage'], false);
    $dashboardPermission = $Session->checkPermission(['Garden.Settings.View', 'Garden.Settings.Manage'], false);

    $dropdown->addLinkIf('Garden.Users.Approve', t('Applicants'), '/dashboard/user/applicants', 'moderation.applicants', '', [], $applicantModifiers);
    $dropdown->addLinkIf($spamPermission, t('Spam Queue'), '/dashboard/log/spam', 'moderation.spam', '', [], $spamModifiers);
    $dropdown->addLinkIf($modPermission, t('Moderation Queue'), '/dashboard/log/moderation', 'moderation.moderation', '', [], $modModifiers);
    $dropdown->addLinkIf($dashboardPermission, t('Dashboard'), '/dashboard/settings', 'dashboard.dashboard', '', [], $dashboardModifiers);

    $dropdown->addLink(t('Sign Out'), signOutUrl(), 'entry.signout', '', [], $signoutModifiers);

    $this->EventArguments['Dropdown'] = &$dropdown;
    $this->fireEvent('FlyoutMenu');
    echo $dropdown;

    echo '</div>';
    echo '</div>';
    echo '</div>';
else:
    echo '<div class="MeBox MeBox-SignIn'.$CssClass.'">';

    echo '<div class="SignInLinks">';

    echo anchor(t('Sign In'), signInUrl($this->_Sender->SelfUrl), (signInPopup() ? ' SignInPopup' : ''), ['rel' => 'nofollow']);
    $Url = registerUrl($this->_Sender->SelfUrl);
    if (!empty($Url))
        echo bullet(' ').anchor(t('Register'), $Url, 'ApplyButton', ['rel' => 'nofollow']).' ';
    echo '</div>';

    echo ' <div class="SignInIcons">';
    $this->fireEvent('SignInIcons');
    echo '</div>';

    echo '</div>';
endif;
