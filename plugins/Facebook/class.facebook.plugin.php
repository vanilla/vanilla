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
$PluginInfo['Facebook'] = array(
	'Name' => 'Facebook',
   'Description' => 'This plugin integrates Vanilla with Facebook. <b>You must register your application with Facebook for this plugin to work.</b>',
   'Version' => '0.1a',
   'RequiredApplications' => array('Vanilla' => '2.0.14a'),
   'RequiredTheme' => FALSE,
   'RequiredPlugins' => FALSE,
	'MobileFriendly' => TRUE,
   'SettingsUrl' => '/dashboard/settings/facebook',
   'SettingsPermission' => 'Garden.Settings.Manage',
   'HasLocale' => TRUE,
   'RegisterPermissions' => FALSE,
   'Author' => "Todd Burry",
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.org/profile/todd'
);

class FacebookPlugin extends Gdn_Plugin {
   public function AccessToken() {
      $Token = GetValue('fb_access_token', $_COOKIE);
      return $Token;
   }

   public function Authorize($Query = FALSE) {
      $Uri = $this->AuthorizeUri($Query);
      Redirect($Uri);
   }

   public function AuthenticationController_Render_Before($Sender, $Args) {
      if (isset($Sender->ChooserList)) {
         $Sender->ChooserList['facebook'] = 'Facebook';
      }
      if (is_array($Sender->Data('AuthenticationConfigureList'))) {
         $List = $Sender->Data('AuthenticationConfigureList');
         $List['facebook'] = '/dashboard/settings/facebook';
         $Sender->SetData('AuthenticationConfigureList', $List);
      }
   }

   /**
    *
    * @param Gdn_Controller $Sender
    */
   public function EntryController_SignIn_Handler($Sender, $Args) {
      if (!$this->IsConfigured())
         return;
      
      if (isset($Sender->Data['Methods'])) {
         $AccessToken = $this->AccessToken();

         $ImgSrc = Asset('/plugins/Facebook/design/facebook-login.png');
         $ImgAlt = T('Login with Facebook');

//         if ($AccessToken) {
//            $SigninHref = $this->RedirectUri();
//
//            // We already have an access token so we can just link to the connect page.
//            $FbMethod = array(
//                'Name' => 'Facebook',
//                'SignInHtml' => "<a id=\"FacebookAuth\" href=\"$SigninHref\" class=\"PopLink\" ><img src=\"$ImgSrc\" alt=\"$ImgAlt\" /></a>");
//         } else {
            $SigninHref = $this->AuthorizeUri();
            $PopupSigninHref = $this->AuthorizeUri('display=popup');

            // Add the facebook method to the controller.
            $FbMethod = array(
               'Name' => 'Facebook',
               'SignInHtml' => "<a id=\"FacebookAuth\" href=\"$SigninHref\" class=\"PopupWindow\" popupHref=\"$PopupSigninHref\" popupHeight=\"326\" popupWidth=\"627\" ><img src=\"$ImgSrc\" alt=\"$ImgAlt\" /></a>");
//         }

         $Sender->Data['Methods'][] = $FbMethod;
      }
   }

   public function Base_BeforeSignInButton_Handler($Sender, $Args) {
      if (!$this->IsConfigured())
         return;
		
		echo "\n".$this->_GetButton();
	}
	
	public function Base_BeforeSignInLink_Handler($Sender) {
      if (!$this->IsConfigured())
			return;
		
		// if (!IsMobile())
		// 	return;

		if (!Gdn::Session()->IsValid())
			echo "\n".Wrap($this->_GetButton(), 'li', array('class' => 'Connect FacebookConnect'));
	}
	
	private function _GetButton() {
      $ImgSrc = Asset('/plugins/Facebook/design/facebook-icon.png');
      $ImgAlt = T('Login with Facebook');
      $SigninHref = $this->AuthorizeUri();
      $PopupSigninHref = $this->AuthorizeUri('display=popup');
      return "<a id=\"FacebookAuth\" href=\"$SigninHref\" class=\"PopupWindow\" title=\"$ImgAlt\" popupHref=\"$PopupSigninHref\" popupHeight=\"326\" popupWidth=\"627\" ><img src=\"$ImgSrc\" alt=\"$ImgAlt\" align=\"bottom\" /></a>";
   }
	
   public function SettingsController_Facebook_Create($Sender, $Args) {
      if ($Sender->Form->IsPostBack()) {
         $Settings = array(
             'Plugins.Facebook.ApplicationID' => $Sender->Form->GetFormValue('ApplicationID'),
             'Plugins.Facebook.Secret' => $Sender->Form->GetFormValue('Secret'));

         SaveToConfig($Settings);
         $Sender->StatusMessage = T("Your settings have been saved.");

      } else {
         $Sender->Form->SetFormValue('ApplicationID', C('Plugins.Facebook.ApplicationID'));
         $Sender->Form->SetFormValue('Secret', C('Plugins.Facebook.Secret'));
      }

      $Sender->AddSideMenu();
      $Sender->SetData('Title', T('Facebook Settings'));
      $Sender->Render('Settings', '', 'plugins/Facebook');
   }

   /**
    *
    * @param Gdn_Controller $Sender
    * @param array $Args
    */
   public function Base_ConnectData_Handler($Sender, $Args) {
      if (GetValue(0, $Args) != 'facebook')
         return;

      if (isset($_GET['error'])) {
         throw new Gdn_UserException(GetValue('error_description', $_GET, T('There was an error connecting to Facebook')));
      }

      $AppID = C('Plugins.Facebook.ApplicationID');
      $Secret = C('Plugins.Facebook.Secret');
      $Code = GetValue('code', $_GET);
      $Query = '';
      if ($Sender->Request->Get('display'))
         $Query = 'display='.urlencode($Sender->Request->Get('display'));

      $RedirectUri = ConcatSep('&', $this->RedirectUri(), $Query);
      $RedirectUri = urlencode($RedirectUri);

      // Get the access token.
      if ($Code || !($AccessToken = $this->AccessToken())) {
         // Exchange the token for an access token.
         $Code = urlencode($Code);

         $Url = "https://graph.facebook.com/oauth/access_token?client_id=$AppID&client_secret=$Secret&code=$Code&redirect_uri=$RedirectUri";

         // Get the redirect URI.
         $C = curl_init();
         curl_setopt($C, CURLOPT_RETURNTRANSFER, TRUE);
         curl_setopt($C, CURLOPT_SSL_VERIFYPEER, FALSE);
         curl_setopt($C, CURLOPT_URL, $Url);
         $Contents = curl_exec($C);
         $Info = curl_getinfo($C);
         if (strpos(GetValue('content_type', $Info, ''), '/javascript') !== FALSE) {
            $Tokens = json_decode($Contents, TRUE);
         } else {
            parse_str($Contents, $Tokens);
         }

         if (GetValue('error', $Tokens)) {
            throw new Gdn_UserException('Facebook returned the following error: '.GetValueR('error.message', $Tokens, 'Unknown error.'), 400);
         }

         $AccessToken = GetValue('access_token', $Tokens);
         $Expires = GetValue('expires', $Tokens, NULL);

         setcookie('fb_access_token', $AccessToken, time() + $Expires, C('Garden.Cookie.Path', '/'), C('Garden.Cookie.Domain', ''));
         $NewToken = TRUE;
      }

      // Get the profile.
      try {
         $Profile = $this->GetProfile($AccessToken);
      } catch (Exception $Ex) {
         if (!isset($NewToken)) {
            // There was an error getting the profile, which probably means the saved access token is no longer valid. Try and reauthorize.
            if ($Sender->DeliveryType() == DELIVERY_TYPE_ALL) {
               Redirect($this->AuthorizeUri());
            } else {
               $Sender->SetHeader('Content-type', 'application/json');
               $Sender->DeliveryMethod(DELIVERY_METHOD_JSON);
               $Sender->RedirectUrl = $this->AuthorizeUri();
            }
         } else {
            $Sender->Form->AddError('There was an error with the Facebook connection.');
         }
      }

      $Form = $Sender->Form; //new Gdn_Form();
      $ID = GetValue('id', $Profile);
      $Form->SetFormValue('UniqueID', $ID);
      $Form->SetFormValue('Provider', 'facebook');
      $Form->SetFormValue('ProviderName', 'Facebook');
      $Form->SetFormValue('FullName', GetValue('name', $Profile));
      $Form->SetFormValue('Email', GetValue('email', $Profile));
      $Form->SetFormValue('Photo', "http://graph.facebook.com/$ID/picture");
      $Sender->SetData('Verified', TRUE);
   }

   public function GetProfile($AccessToken) {
      $Url = "https://graph.facebook.com/me?access_token=$AccessToken";

      $Contents = file_get_contents($Url);
      $Profile = json_decode($Contents, TRUE);
      return $Profile;
   }

   public function AuthorizeUri($Query = FALSE) {
      $AppID = C('Plugins.Facebook.ApplicationID');

      $RedirectUri = $this->RedirectUri();
      if ($Query)
         $RedirectUri .= '&'.$Query;
      $RedirectUri = urlencode($RedirectUri);

      $SigninHref = "https://graph.facebook.com/oauth/authorize?client_id=$AppID&redirect_uri=$RedirectUri&scope=email,publish_stream";
      if ($Query)
         $SigninHref .= '&'.$Query;
      return $SigninHref;
   }

   protected $_RedirectUri = NULL;

   public function RedirectUri($NewValue = NULL) {
      if ($NewValue !== NULL)
         $this->_RedirectUri = $NewValue;
      elseif ($this->_RedirectUri === NULL) {
         $RedirectUri = Url('/entry/connect/facebook', TRUE);
         if (strpos($RedirectUri, '=') !== FALSE) {
            $p = strrchr($RedirectUri, '=');
            $Uri = substr($RedirectUri, 0, -strlen($p));
            $p = urlencode(ltrim($p, '='));
            $RedirectUri = $Uri.'='.$p;
         }

         $Path = Gdn::Request()->Path();
         $Args = array('Target' => GetValue('Target', $_GET, $Path ? $Path : '/'));
         $RedirectUri .= strpos($RedirectUri, '?') === FALSE ? '?' : '&';
         $RedirectUri .= http_build_query($Args);
         $this->_RedirectUri = $RedirectUri;
      }
      
      return $this->_RedirectUri;
   }

   public function IsConfigured() {
      $AppID = C('Plugins.Facebook.ApplicationID');
      $Secret = C('Plugins.Facebook.Secret');
      if (!$AppID || !$Secret)
         return FALSE;
      return TRUE;
   }
   
   public function Setup() {
      $Error = '';
      if (!ini_get('allow_url_fopen'))
         $Error = ConcatSep("\n", $Error, 'This plugin requires the allow_url_fopen php.ini setting.');
      if (!function_exists('curl_init'))
         $Error = ConcatSep("\n", $Error, 'This plugin requires curl.');
      if ($Error)
         throw new Gdn_UserException($Error, 400);


      // Save the facebook provider type.
      Gdn::SQL()->Replace('UserAuthenticationProvider',
         array('AuthenticationSchemeAlias' => 'facebook', 'URL' => '...', 'AssociationSecret' => '...', 'AssociationHashMethod' => '...'),
         array('AuthenticationKey' => 'Facebook'));
   }

   public function OnDisable() {
   }

//   public function OnDisable() {
//		$this->_Disable();
//   }

//   protected function _CreateProviderModel() {
//      $Key = 'k'.sha1(implode('.',array(
//         'vanillaconnect',
//         'key',
//         microtime(true),
//         RandomString(16),
//         Gdn::Session()->User->Name
//      )));
//
//      $Secret = 's'.sha1(implode('.',array(
//         'vanillaconnect',
//         'secret',
//         md5(microtime(true)),
//         RandomString(16),
//         Gdn::Session()->User->Name
//      )));
//
//      $ProviderModel = new Gdn_AuthenticationProviderModel();
//      $ProviderModel->Insert($Provider = array(
//         'AuthenticationKey'           => $Key,
//         'AuthenticationSchemeAlias'   => 'handshake',
//         'URL'                         => 'Enter your site url',
//         'AssociationSecret'           => $Secret,
//         'AssociationHashMethod'       => 'HMAC-SHA1'
//      ));
//
//      return $Provider;
//   }
//
//   public function AuthenticationController_DisableAuthenticatorHandshake_Handler(&$Sender) {
//      $this->_Disable();
//   }
//
//   private function _Disable() {
//      RemoveFromConfig('Plugins.VanillaConnect.Enabled');
//		RemoveFromConfig('Garden.SignIn.Popup');
//		RemoveFromConfig('Garden.Authenticator.DefaultScheme');
//		RemoveFromConfig('Garden.Authenticators.handshake.Name');
//      RemoveFromConfig('Garden.Authenticators.handshake.CookieName');
//      RemoveFromConfig('Garden.Authenticators.handshake.TokenLifetime');
//   }
//
//   public function AuthenticationController_EnableAuthenticatorHandshake_Handler(&$Sender) {
//      $this->_Enable();
//   }
//
//	private function _Enable($FullEnable = TRUE) {
//		SaveToConfig('Garden.SignIn.Popup', FALSE);
//		SaveToConfig('Garden.Authenticators.handshake.Name', 'VanillaConnect');
//      SaveToConfig('Garden.Authenticators.handshake.CookieName', 'VanillaHandshake');
//      SaveToConfig('Garden.Authenticators.handshake.TokenLifetime', 0);
//
//      if ($FullEnable) {
//         SaveToConfig('Garden.Authenticator.DefaultScheme', 'handshake');
//         SaveToConfig('Plugins.VanillaConnect.Enabled', TRUE);
//      }
//
//      // Create a provider key/secret pair if needed
//      $SQL = Gdn::Database()->SQL();
//      $Provider = $SQL->Select('uap.*')
//         ->From('UserAuthenticationProvider uap')
//         ->Where('uap.AuthenticationSchemeAlias', 'handshake')
//         ->Get()
//         ->FirstRow(DATASET_TYPE_ARRAY);
//
//      if (!$Provider)
//         $this->_CreateProviderModel();
//	}
}