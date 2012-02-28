<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();

if ($Session->IsValid()):
   echo '<div class="MeBox">';


   $Name = $Session->User->Name;

   echo UserPhoto($Session->User);
   echo '<div class="WhoIs">';
      echo UserAnchor($Session->User, 'Username');
      echo '<div class="MeMenu">';
         // Notifications
         $CountNotifications = $Session->User->CountNotifications;
         $CNotifications = is_numeric($CountNotifications) && $CountNotifications > 0 ? '<span class="Alert">'.$CountNotifications.'</span>' : '';
         $ProfileSlug = urlencode($Session->User->Name) == $Session->User->Name ? $Session->User->Name : $Session->UserID.'/'.urlencode($Session->User->Name);
         echo Anchor(Sprite('SpNotifications', 'Sprite16').Wrap(T('Notifications'), 'em').$CNotifications, '/profile/'.$ProfileSlug, array('title' => T('Notifications')));

         // Inbox
         $CountInbox = GetValue('CountUnreadConversations', Gdn::Session()->User);
         $CInbox = is_numeric($CountInbox) && $CountInbox > 0 ? ' <span class="Alert">'.$CountInbox.'</span>' : '';
         echo Anchor(Sprite('SpInbox', 'Sprite16').Wrap(T('Inbox'), 'em').$CInbox, '/messages/all', array('title' => T('Inbox')));

         // Bookmarks
         echo Anchor(Sprite('SpBookmarks', 'Sprite16').Wrap(T('Bookmarks'), 'em'), '/discussions/bookmarked', array('title' => T('Bookmarks')));

         // Dashboard
         if ($Session->CheckPermission('Garden.Settings.Manage'))
            echo Anchor(Sprite('SpDashboard', 'Sprite16').Wrap(T('Dashboard'), 'em'), '/dashboard/settings', array('title' => T('Dashboard')));

         // Sign Out
         // echo Anchor(Sprite('SpSignOut', 'Sprite16').Wrap(T('Sign Out'), 'em'), SignOutUrl(), array('title' => T('Sign Out')));

      echo '</div>';
   echo '</div>';
   echo '</div>';
else:
   echo '<div class="MeBox MeBox-SignIn">';

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