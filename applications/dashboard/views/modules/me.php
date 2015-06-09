<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::session();
$User = $Session->User;
$CssClass = '';
if ($this->CssClass)
    $CssClass .= ' '.$this->CssClass;

$DashboardCount = 0;
// Spam & Moderation Queue
if ($Session->checkPermission(array('Garden.Settings.Manage', 'Garden.Moderation.Manage', 'Moderation.Spam.Manage', 'Moderation.ModerationQueue.Manage'), false)) {
    $LogModel = new LogModel();
    //$SpamCount = $LogModel->GetOperationCount('spam');
    $ModerationCount = $LogModel->GetOperationCount('moderate');
    $DashboardCount += $ModerationCount;
}
// Applicant Count
if ($Session->checkPermission('Garden.Users.Approve')) {
    $RoleModel = new RoleModel();
    $ApplicantCount = $RoleModel->GetApplicantCount();
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

    echo '<span class="ToggleFlyout" rel="/profile/notificationspopin">';
    echo anchor(sprite('SpNotifications', 'Sprite Sprite16').Wrap(t('Notifications'), 'em').$CNotifications, userUrl($User), 'MeButton FlyoutButton js-clear-notifications', array('title' => t('Notifications')));
    echo sprite('SpFlyoutHandle', 'Arrow');
    echo '<div class="Flyout FlyoutMenu"></div></span>';

    // Inbox
    if (Gdn::ApplicationManager()->CheckApplication('Conversations')) {
        $CountInbox = val('CountUnreadConversations', Gdn::session()->User);
        $CInbox = is_numeric($CountInbox) && $CountInbox > 0 ? ' <span class="Alert">'.$CountInbox.'</span>' : '';
        echo '<span class="ToggleFlyout" rel="/messages/popin">';
        echo anchor(sprite('SpInbox', 'Sprite Sprite16').Wrap(t('Inbox'), 'em').$CInbox, '/messages/all', 'MeButton FlyoutButton', array('title' => t('Inbox')));
        echo sprite('SpFlyoutHandle', 'Arrow');
        echo '<div class="Flyout FlyoutMenu"></div></span>';
    }

    // Bookmarks
    if (Gdn::ApplicationManager()->CheckApplication('Vanilla')) {
        echo '<span class="ToggleFlyout" rel="/discussions/bookmarkedpopin">';
        echo anchor(sprite('SpBookmarks', 'Sprite Sprite16').Wrap(t('Bookmarks'), 'em'), '/discussions/bookmarked', 'MeButton FlyoutButton', array('title' => t('Bookmarks')));
        echo sprite('SpFlyoutHandle', 'Arrow');
        echo '<div class="Flyout FlyoutMenu"></div></span>';
    }

    // Profile Settings & Logout
    echo '<span class="ToggleFlyout">';
    $CDashboard = $DashboardCount > 0 ? wrap($DashboardCount, 'span class="Alert"') : '';
    echo anchor(sprite('SpOptions', 'Sprite Sprite16').Wrap(t('Account Options'), 'em').$CDashboard, '/profile/edit', 'MeButton FlyoutButton', array('title' => t('Account Options')));
    echo sprite('SpFlyoutHandle', 'Arrow');
    echo '<div class="Flyout MenuItems">';
    echo '<ul>';
    // echo wrap(Wrap(t('My Account'), 'strong'), 'li');
    // echo wrap('<hr />', 'li');
    if (hasEditProfile(Gdn::session()->UserID)) {
        echo wrap(Anchor(sprite('SpEditProfile').' '.t('Edit Profile'), 'profile/edit', 'EditProfileLink'), 'li', array('class' => 'EditProfileWrap link-editprofile'));
    } else {
        echo wrap(Anchor(sprite('SpEditProfile').' '.t('Preferences'), 'profile/preferences', 'EditProfileLink'), 'li', array('class' => 'EditProfileWrap link-preferences'));
    }

    if ($Session->checkPermission(array('Garden.Settings.View', 'Garden.Settings.Manage', 'Garden.Moderation.Manage', 'Garden.Users.Approve', 'Moderation.Spam.Manage', 'Moderation.ModerationQueue.Manage'), false)) {
        echo wrap('<hr />', 'li');
        $CApplicant = $ApplicantCount > 0 ? ' '.Wrap($ApplicantCount, 'span class="Alert"') : '';
        $CSpam = ''; //$SpamCount > 0 ? ' '.Wrap($SpamCount, 'span class="Alert"') : '';
        $CModeration = $ModerationCount > 0 ? ' '.Wrap($ModerationCount, 'span class="Alert"') : '';

        if ($Session->checkPermission('Garden.Users.Approve')) {
            echo wrap(Anchor(sprite('SpApplicants').' '.t('Applicants').$CApplicant, '/dashboard/user/applicants'), 'li', array('class' => 'link-applicants'));
        }

        if ($Session->checkPermission(array('Garden.Settings.Manage', 'Garden.Moderation.Manage', 'Moderation.ModerationQueue.Manage'), false)) {
            echo wrap(Anchor(sprite('SpSpam').' '.t('Spam Queue').$CSpam, '/dashboard/log/spam'), 'li', array('class' => 'link-spam'));
        }

        if ($Session->checkPermission(array('Garden.Settings.Manage', 'Garden.Moderation.Manage', 'Moderation.ModerationQueue.Manage'), false)) {
            echo wrap(Anchor(sprite('SpMod').' '.t('Moderation Queue').$CModeration, '/dashboard/log/moderation'), 'li', array('class' => 'link-moderation'));
        }

        if ($Session->checkPermission(array('Garden.Settings.View', 'Garden.Settings.Manage'), false)) {
            echo wrap(Anchor(sprite('SpDashboard').' '.t('Dashboard'), '/dashboard/settings'), 'li', array('class' => 'link-dashboard'));
        }
    }

    $this->fireEvent('FlyoutMenu');
    echo wrap('<hr />'.anchor(sprite('SpSignOut').' '.t('Sign Out'), SignOutUrl()), 'li', array('class' => 'SignInOutWrap SignOutWrap link-signout'));
    echo '</ul>';
    echo '</div>';
    echo '</span>';

    // Sign Out
    // echo anchor(sprite('SpSignOut', 'Sprite16').Wrap(t('Sign Out'), 'em'), SignOutUrl(), 'MeButton', array('title' => t('Sign Out')));

    echo '</div>';
    echo '</div>';
    echo '</div>';
else:
    echo '<div class="MeBox MeBox-SignIn'.$CssClass.'">';

    echo '<div class="SignInLinks">';

    echo anchor(t('Sign In'), SignInUrl($this->_Sender->SelfUrl), (SignInPopup() ? ' SignInPopup' : ''), array('rel' => 'nofollow'));
    $Url = RegisterUrl($this->_Sender->SelfUrl);
    if (!empty($Url))
        echo Bullet(' ').anchor(t('Register'), $Url, 'ApplyButton', array('rel' => 'nofollow')).' ';
    echo '</div>';

    echo ' <div class="SignInIcons">';
    $this->fireEvent('SignInIcons');
    echo '</div>';

    echo '</div>';
endif;
