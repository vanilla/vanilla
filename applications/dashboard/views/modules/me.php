<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
$User = $Session->User;
$CssClass = '';
if ($this->CssClass)
   $CssClass .= ' '.$this->CssClass;

$DashboardCount = 0;
// Spam & Moderation Queue
if ($Session->CheckPermission('Garden.Settings.Manage') || $Session->CheckPermission('Garden.Moderation.Manage')) {
   $LogModel = new LogModel();
   //$SpamCount = $LogModel->GetOperationCount('spam');
   $ModerationCount = $LogModel->GetOperationCount('moderate');
   $DashboardCount += $ModerationCount;
}
// Applicant Count
if ($Session->CheckPermission('Garden.Users.Approve')) {
   $RoleModel = new RoleModel();
   $ApplicantCount = $RoleModel->GetApplicantCount();
   $DashboardCount += $ApplicantCount;
}

if ($Session->IsValid()):
   echo '<div class="MeBox'.$CssClass.'">';
   echo UserPhoto($User);
   echo '<div class="WhoIs">';
      echo UserAnchor($User, 'Username');
      echo '<div class="MeMenu">';
         // Notifications
         $CountNotifications = $User->CountNotifications;
         $CNotifications = is_numeric($CountNotifications) && $CountNotifications > 0 ? '<span class="Alert">'.$CountNotifications.'</span>' : '';
         
         echo '<span class="ToggleFlyout" rel="/profile/notificationspopin">';
         echo Anchor(Sprite('SpNotifications', 'Sprite Sprite16').Wrap(T('Notifications'), 'em').$CNotifications, UserUrl($User), 'MeButton FlyoutButton', array('title' => T('Notifications')));
         echo Sprite('SpFlyoutHandle', 'Arrow');
         echo '<div class="Flyout FlyoutMenu"></div></span>';
         
         // Inbox
         if (Gdn::ApplicationManager()->CheckApplication('Conversations')) {
            $CountInbox = GetValue('CountUnreadConversations', Gdn::Session()->User);
            $CInbox = is_numeric($CountInbox) && $CountInbox > 0 ? ' <span class="Alert">'.$CountInbox.'</span>' : '';
            echo '<span class="ToggleFlyout" rel="/messages/popin">';
            echo Anchor(Sprite('SpInbox', 'Sprite Sprite16').Wrap(T('Inbox'), 'em').$CInbox, '/messages/all', 'MeButton FlyoutButton', array('title' => T('Inbox')));
            echo Sprite('SpFlyoutHandle', 'Arrow');
            echo '<div class="Flyout FlyoutMenu"></div></span>';
         }
         
         // Bookmarks
         if (Gdn::ApplicationManager()->CheckApplication('Vanilla')) {
            echo '<span class="ToggleFlyout" rel="/discussions/bookmarkedpopin">';
            echo Anchor(Sprite('SpBookmarks', 'Sprite Sprite16').Wrap(T('Bookmarks'), 'em'), '/discussions/bookmarked', 'MeButton FlyoutButton', array('title' => T('Bookmarks')));
            echo Sprite('SpFlyoutHandle', 'Arrow');
            echo '<div class="Flyout FlyoutMenu"></div></span>';
         }
         
         // Profile Settings & Logout
         echo '<span class="ToggleFlyout">';
         $CDashboard = $DashboardCount > 0 ? Wrap($DashboardCount, 'span class="Alert"') : '';
         echo Anchor(Sprite('SpOptions', 'Sprite Sprite16').Wrap(T('Account Options'), 'em').$CDashboard, '/profile/edit', 'MeButton FlyoutButton', array('title' => T('Account Options')));
         echo Sprite('SpFlyoutHandle', 'Arrow');
         echo '<div class="Flyout MenuItems">';
            echo '<ul>';
               // echo Wrap(Wrap(T('My Account'), 'strong'), 'li');
               // echo Wrap('<hr />', 'li');
               if (C('Garden.UserAccount.AllowEdit') && C('Garden.Registration.Method') != 'Connect') {
                  echo Wrap(Anchor(Sprite('SpEditProfile').' '.T('Edit Profile'), 'profile/edit'), 'li');
               } else {
                  echo Wrap(Anchor(Sprite('SpEditProfile').' '.T('Preferences'), 'profile/preferences'), 'li');
               }
               
               if ($Session->CheckPermission('Garden.Settings.Manage') || $Session->CheckPermission('Garden.Moderation.Manage')) {
                  echo Wrap('<hr />', 'li');
                  $CApplicant = $ApplicantCount > 0 ? ' '.Wrap($ApplicantCount, 'span class="Alert"') : '';
                  $CSpam = ''; //$SpamCount > 0 ? ' '.Wrap($SpamCount, 'span class="Alert"') : '';
                  $CModeration = $ModerationCount > 0 ? ' '.Wrap($ModerationCount, 'span class="Alert"') : '';
                  echo Wrap(Anchor(Sprite('SpApplicants').' '.T('Applicants').$CApplicant, '/dashboard/user/applicants'), 'li');
                  echo Wrap(Anchor(Sprite('SpSpam').' '.T('Spam Queue').$CSpam, '/dashboard/log/spam'), 'li');
                  echo Wrap(Anchor(Sprite('SpMod').' '.T('Moderation Queue').$CModeration, '/dashboard/log/moderation'), 'li');
                  if ($Session->CheckPermission('Garden.Settings.Manage')) {
                     echo Wrap(Anchor(Sprite('SpDashboard').' '.T('Dashboard'), '/dashboard/settings'), 'li');
                  }
               }
               
               $this->FireEvent('FlyoutMenu');
               echo Wrap('<hr />'.Anchor(Sprite('SpSignOut').' '.T('Sign Out'), SignOutUrl()), 'li', array('class' => 'SignInOutWrap SignOutWrap'));
         echo '</div>';
         echo '</span>';

         // Sign Out
         // echo Anchor(Sprite('SpSignOut', 'Sprite16').Wrap(T('Sign Out'), 'em'), SignOutUrl(), 'MeButton', array('title' => T('Sign Out')));

      echo '</div>';
   echo '</div>';
   echo '</div>';
else:
   echo '<div class="MeBox MeBox-SignIn'.$CssClass.'">';

   echo '<div class="SignInLinks">';

   echo Anchor(T('Sign In'), SignInUrl($this->_Sender->SelfUrl), (SignInPopup() ? ' SignInPopup' : ''), array('rel' => 'nofollow'));
   $Url = RegisterUrl($this->_Sender->SelfUrl);
      if(!empty($Url))
         echo ' <span class="Bullet">•</span> '.Anchor(T('Register'), $Url, 'ApplyButton', array('rel' => 'nofollow')).' ';
   echo '</div>';
      
   echo ' <div class="SignInIcons">';
   $this->FireEvent('SignInIcons');
   echo '</div>';
   
   echo '</div>';
endif;