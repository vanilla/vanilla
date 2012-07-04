<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
$User = $Session->User;
$CssClass = '';
if ($this->CssClass)
   $CssClass .= ' '.$this->CssClass;


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
         
         // Dashboard
         if ($Session->CheckPermission('Garden.Settings.Manage') || $Session->CheckPermission('Garden.Moderation.Manage'))
            echo Anchor(Sprite('SpDashboard', 'Sprite16').Wrap(T('Dashboard'), 'em'), '/dashboard/settings', 'MeButton', array('title' => T('Dashboard')));

         // Sign Out
         echo Anchor(Sprite('SpSignOut', 'Sprite16').Wrap(T('Sign Out'), 'em'), SignOutUrl(), 'MeButton', array('title' => T('Sign Out')));

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