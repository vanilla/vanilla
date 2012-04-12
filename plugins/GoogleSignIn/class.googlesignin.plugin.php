<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

// Define the plugin:
$PluginInfo['GoogleSignIn'] = array(
	'Name' => 'Google Sign In',
   'Description' => 'Allows users to sign in with their Google accounts. Requires &lsquo;OpenID&rsquo; plugin to be enabled first.',
   'Version' => '1.1',
   'RequiredApplications' => array('Vanilla' => '2.0.14'),
   'RequiredPlugins' => array('OpenID' => '0.1a'),
   'RequiredTheme' => FALSE,
	'MobileFriendly' => TRUE,
   //'SettingsUrl' => '/dashboard/plugin/googlesignin',
   'SettingsPermission' => 'Garden.Settings.Manage',
   'HasLocale' => TRUE,
   'RegisterPermissions' => FALSE,
   'Author' => "Todd Burry",
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.org/profile/todd'
);

// 1.1 - Removed redundant enabling (2012-03-08 Lincoln)

class GoogleSignInPlugin extends Gdn_Plugin {

   /// Properties ///

   protected function _AuthorizeHref($Popup = FALSE) {
      $Url = Url('/entry/openid', TRUE);
      $UrlParts = explode('?', $Url);
      parse_str(GetValue(1, $UrlParts, ''), $Query);

      $Query['url'] = 'https://www.google.com/accounts/o8/id';
      $Path = '/'.Gdn::Request()->Path();
      $Query['Target'] = GetValue('Target', $_GET, $Path ? $Path : '/');
      if ($Popup)
         $Query['display'] = 'popup';

       $Result = $UrlParts[0].'?'.http_build_query($Query);
      return $Result;
   }
   
   /**
    * Act as a mini dispatcher for API requests to the plugin app
    */
//   public function PluginController_GoogleSignIn_Create($Sender) {
//      $Sender->Permission('Garden.Settings.Manage');
//		$this->Dispatch($Sender, $Sender->RequestArgs);
//   }
   
//   public function Controller_Toggle($Sender) {
//      $this->AutoToggle($Sender);
//   }
   
   public function AuthenticationController_Render_Before($Sender, $Args) {
      if (isset($Sender->ChooserList)) {
         $Sender->ChooserList['googlesignin'] = 'Google';
      }
      if (is_array($Sender->Data('AuthenticationConfigureList'))) {
         $List = $Sender->Data('AuthenticationConfigureList');
         $List['googlesignin'] = '/dashboard/plugin/googlesignin';
         $Sender->SetData('AuthenticationConfigureList', $List);
      }
   }

   /// Plugin Event Handlers ///

   /**
    *
    * @param Gdn_Controller $Sender
    */
   public function EntryController_SignIn_Handler($Sender, $Args) {
//      if (!$this->IsEnabled()) return;
      
      if (isset($Sender->Data['Methods'])) {
         $ImgSrc = Asset('/plugins/GoogleSignIn/design/google-signin.png');
         $ImgAlt = T('Sign In with Google');
         $SigninHref = $this->_AuthorizeHref();
         $PopupSigninHref = $this->_AuthorizeHref(TRUE);

         // Add the twitter method to the controller.
         $Method = array(
            'Name' => 'Google',
            'SignInHtml' => "<a id=\"GoogleAuth\" href=\"$SigninHref\" class=\"PopupWindow\" popupHref=\"$PopupSigninHref\" popupHeight=\"400\" popupWidth=\"800\" rel=\"nofollow\" ><img src=\"$ImgSrc\" alt=\"$ImgAlt\" /></a>");

         $Sender->Data['Methods'][] = $Method;
      }
   }
   
   public function Base_SignInIcons_Handler($Sender, $Args) {
//      if (!$this->IsEnabled()) return;
		echo "\n".$this->_GetButton();
	}

   public function Base_BeforeSignInButton_Handler($Sender, $Args) {
//      if (!$this->IsEnabled()) return;
		echo "\n".$this->_GetButton();
	}
	
	private function _GetButton() {      
      $ImgSrc = Asset('/plugins/GoogleSignIn/design/google-icon.png');
      $ImgAlt = T('Sign In with Google');
      $SigninHref = $this->_AuthorizeHref();
      $PopupSigninHref = $this->_AuthorizeHref(TRUE);
      return "<a id=\"GoogleAuth\" href=\"$SigninHref\" class=\"PopupWindow\" title=\"$ImgAlt\" popupHref=\"$PopupSigninHref\" popupHeight=\"400\" popupWidth=\"800\" rel=\"nofollow\" ><img src=\"$ImgSrc\" alt=\"$ImgAlt\" /></a>";
   }
	
	public function Base_BeforeSignInLink_Handler($Sender) {
//      if (!$this->IsEnabled())
//			return;

		if (!Gdn::Session()->IsValid())
			echo "\n".Wrap($this->_GetButton(), 'li', array('class' => 'Connect GoogleConnect'));
	}
}