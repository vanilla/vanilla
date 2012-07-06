<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
$User = $Session->User;
$CssClass = '';
if ($this->CssClass)
   $CssClass .= ' '.$this->CssClass;

$DashboardCount = 0;
if ($Session->CheckPermission('Garden.Settings.Manage') || $Session->CheckPermission('Garden.Moderation.Manage')) {
   // Applicant Count
   $RoleModel = new RoleModel();
   $ApplicantCount = $RoleModel->GetApplicantCount();
   // Spam & Moderation Queue
   $LogModel = new LogModel();
   $SpamCount = $LogModel->GetOperationCount('spam');
   $ModerationCount = $LogModel->GetOperationCount('moderate');
   $DashboardCount = $ApplicantCount + $SpamCount + $ModerationCount;
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
         echo Anchor(Sprite('SpNotifications', 'Sprite16').Wrap(T('Notifications'), 'em').$CNotifications, UserUrl($User), 'MeButton FlyoutButton', array('title' => T('Notifications')));
         echo '<div class="Flyout FlyoutMenu"></div></span>';
         
         // Inbox
         $CountInbox = GetValue('CountUnreadConversations', Gdn::Session()->User);
         $CInbox = is_numeric($CountInbox) && $CountInbox > 0 ? ' <span class="Alert">'.$CountInbox.'</span>' : '';
         echo '<span class="ToggleFlyout" rel="/messages/popin">';
         echo Anchor(Sprite('SpInbox', 'Sprite16').Wrap(T('Inbox'), 'em').$CInbox, '/messages/all', 'MeButton FlyoutButton', array('title' => T('Inbox')));
         echo '<div class="Flyout FlyoutMenu"></div></span>';
         
         // Bookmarks
         echo '<span class="ToggleFlyout" rel="/discussions/bookmarkedpopin">';
         echo Anchor(Sprite('SpBookmarks', 'Sprite16').Wrap(T('Bookmarks'), 'em'), '/discussions/bookmarked', 'MeButton FlyoutButton', array('title' => T('Bookmarks')));
         echo '<div class="Flyout FlyoutMenu"></div></span>';
         
         // Profile Settings & Logout
         echo '<span class="ToggleFlyout">';
         $CDashboard = $DashboardCount > 0 ? Wrap($DashboardCount, 'span class="Alert"') : '';
         echo Anchor(Sprite('SpDashboard', 'Sprite16').Wrap(T('Account Options'), 'em').$CDashboard, '/profile/edit', 'MeButton FlyoutButton', array('title' => T('Account Options')));
         echo '<div class="Flyout MenuItems">';
            echo '<ul>';
               // echo Wrap(Wrap(T('My Account'), 'strong'), 'li');
               // echo Wrap('<hr />', 'li');
               echo Wrap(Anchor(T('Edit Profile'), 'profile/edit'), 'li');
               
               if ($Session->CheckPermission('Garden.Settings.Manage') || $Session->CheckPermission('Garden.Moderation.Manage')) {
                  echo Wrap('<hr />', 'li');
                  $CApplicant = $ApplicantCount > 0 ? ' '.Wrap($ApplicantCount, 'span class="Alert"') : '';
                  $CSpam = $SpamCount > 0 ? ' '.Wrap($SpamCount, 'span class="Alert"') : '';
                  $CModeration = $ModerationCount > 0 ? ' '.Wrap($ModerationCount, 'span class="Alert"') : '';
                  echo Wrap(Anchor(T('Applicants').$CApplicant, '/dashboard/user/applicants'), 'li');
                  echo Wrap(Anchor(T('Spam Queue').$CSpam, '/dashboard/log/spam'), 'li');
                  echo Wrap(Anchor(T('Moderation Queue').$CModeration, '/dashboard/log/moderation'), 'li');
                  echo Wrap(Anchor(T('Dashboard'), '/dashboard/settings'), 'li');
               }
               echo Wrap('<hr />', 'li');
               echo Wrap(Anchor(T('Sign Out'), SignOutUrl()), 'li');
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