<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

/**
 * Utility class that helps to render theme elements.
 *
 * @author Mark O'Sullivan
 * @copyright 2009 Mark O'Sullivan
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @version @@GARDEN-VERSION@@
 * @namespace Garden.Core
 */
class Gdn_Theme {

   
   public static function Link($Path, $Text = FALSE, $Format = '<a href="%url" class="%class">%text</a>', $Options = array()) {
      $Session = Gdn::Session();
      $Class = GetValue('class', $Options, '');

      switch ($Path) {
         case 'dashboard':
            $Path = 'dashboard/settings';
            TouchValue('Permissions', $Options, 'Garden.Settings.Manage');
            if (!$Text)
               $Text = T('Dashboard');
            break;
         case 'inbox':
            $Path = 'messages/inbox';
            TouchValue('Permissions', $Options, 'Garden.SignIn.Allow');
            if (!$Text)
               $Text = T('Inbox');
            if ($Session->IsValid() && $Session->User->CountUnreadConversations)
               $Text .= ' <span>'.$Session->User->CountUnreadConversations.'</span>';
            
            break;
         case 'profile':
            TouchValue('Permissions', $Options, 'Garden.SignIn.Allow');
            if (!$Text && $Session->IsValid())
               $Text = $Session->User->Name;
            if ($Session->IsValid() && $Session->User->CountNotifications)
               $Text .= ' <span>'.$Session->User->CountNotifications.'</span>';

            break;
         case 'signin':
         case 'signinout':
            // The destination is the signin/signout toggle link.
            if ($Session->IsValid()) {
               if(!$Text)
                  $Text = T('Sign Out');
               $Path = Gdn::Authenticator()->SignOutUrl();
               $Class = ConcatSep(' ', $Class, 'SignOut');
            } else {
               if(!$Text)
                  $Text = T('Sign In');
               $Attribs = array();

               $Path = Gdn::Authenticator()->SignInUrl('');
               if (SignInPopup() && strpos(Gdn::Request()->Url(), 'entry') === FALSE)
                  $Class = ConcatSep(' ', $Class, 'SignInPopup');
            }
            break;
      }

      if (GetValue('Permissions', $Options) && !$Session->CheckPermission($Options['Permissions']))
         return '';

      $Url = Gdn::Request()->Url($Path, GetValue('WithDomain', $Options));

      if (strcasecmp(trim($Path, '/'), Gdn::Request()->Path()) == 0)
         $Class = ConcatSep(' ', $Class, 'Selected');

      // Build the final result.
      $Result = $Format;
      $Result = str_replace('%url', $Url, $Result);
      $Result = str_replace('%text', $Text, $Result);
      $Result = str_replace('%class', $Class, $Result);

      return $Result;
   }

   /**
    * Renders the banner logo, or just the banner title if the logo is not defined.
    */
   public static function Logo() {
      $Logo = C('Garden.Logo');
      $Title = C('Garden.Title', 'Title');
      echo $Logo ? Img($Logo, array('alt' => $Title)) : $Title;
   }
   
   public static function Pagename() {
      $Application = Gdn::Dispatcher()->Application();
      $Controller = Gdn::Dispatcher()->Controller();
      switch ($Controller) {
         case 'discussions':
         case 'discussion':
         case 'post':
            return 'discussions';
            
         case 'inbox':
            return 'inbox';
            
         case 'activity':
            return 'activity';
            
         case 'profile':
            $Args = Gdn::Dispatcher()->ControllerArguments();
            if (!sizeof($Args) || ( sizeof($Args) && $Args[0] == Gdn::Authenticator()->GetIdentity()))
               return 'profile';
            break;
      }
      
      return 'unknown';
   }

   public static function Text($Code, $Default) {
      return T('Theme_'.$Code, $Default);
   }
}