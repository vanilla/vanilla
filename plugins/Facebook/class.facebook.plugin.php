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
	'Name' => 'Facebook Social Connect',
   'Description' => 'Users may sign into your site using their Facebook account.',	
   'Version' => '1.0.9',
   'RequiredApplications' => array('Vanilla' => '2.0.14a'),
   'RequiredTheme' => FALSE,
   'RequiredPlugins' => FALSE,
	'MobileFriendly' => TRUE,
   'SettingsUrl' => '/dashboard/social/facebook',
   'SettingsPermission' => 'Garden.Settings.Manage',
   'HasLocale' => TRUE,
   'RegisterPermissions' => FALSE,
   'Author' => "Todd Burry",
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.org/profile/todd',
   'Hidden' => TRUE,
   'SocialConnect' => TRUE,
   'RequiresRegistration' => TRUE
);

class FacebookPlugin extends Gdn_Plugin {
   const ProviderKey = 'Facebook';
   
   protected $_AccessToken = NULL;
   
   public function AccessToken() {
      if (!$this->IsConfigured()) 
         return FALSE;
      
      if ($this->_AccessToken === NULL) {
         if (Gdn::Session()->IsValid())
            $this->_AccessToken = GetValueR(self::ProviderKey.'.AccessToken', Gdn::Session()->User->Attributes);
         else
            $this->_AccessToken = FALSE;
      }
      
      return $this->_AccessToken;
   }

   public function Authorize($Query = FALSE) {
      $Uri = $this->AuthorizeUri($Query);
      Redirect($Uri);
   }
   
   public function API($Path, $Post = FALSE) {
      // Build the url.
      $Url = 'https://graph.facebook.com/'.ltrim($Path, '/');
      
      $AccessToken = $this->AccessToken();
      if (!$AccessToken)
         throw new Gdn_UserException("You don't have a valid Facebook connection.");
      
      if (strpos($Url, '?') === false)
         $Url .= '?';
      else
         $Url .= '&';
      $Url .= 'access_token='.urlencode($AccessToken);

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_HEADER, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_URL, $Url);

      if ($Post !== false) {
         curl_setopt($ch, CURLOPT_POST, true);
         curl_setopt($ch, CURLOPT_POSTFIELDS, $Post); 
         Trace("  POST $Url");
      } else {
         Trace("  GET  $Url");
      }

      $Response = curl_exec($ch);

      $HttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      $ContentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
      curl_close($ch);
      
      Gdn::Controller()->SetJson('Type', $ContentType);

      if (strpos($ContentType, 'javascript') !== FALSE) {
         $Result = json_decode($Response, TRUE);
         
         if (isset($Result['error'])) {
            Gdn::Dispatcher()->PassData('FacebookResponse', $Result);
            throw new Gdn_UserException($Result['error']['message']);
         }
      } else
         $Result = $Response;

      return $Result;
   }

//   public function AuthenticationController_Render_Before($Sender, $Args) {
//      if (isset($Sender->ChooserList)) {
//         $Sender->ChooserList['facebook'] = 'Facebook';
//      }
//      if (is_array($Sender->Data('AuthenticationConfigureList'))) {
//         $List = $Sender->Data('AuthenticationConfigureList');
//         $List['facebook'] = '/dashboard/settings/facebook';
//         $Sender->SetData('AuthenticationConfigureList', $List);
//      }
//   }

   /**
    *
    * @param Gdn_Controller $Sender
    */
   public function EntryController_SignIn_Handler($Sender, $Args) {
      if (!$this->IsConfigured())
         return;
      
      if (isset($Sender->Data['Methods'])) {
         $ImgSrc = Asset('/plugins/Facebook/design/facebook-login.png');
         $ImgAlt = T('Sign In with Facebook');

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
               'Name' => self::ProviderKey,
               'SignInHtml' => "<a id=\"FacebookAuth\" href=\"$SigninHref\" class=\"PopupWindow\" popupHref=\"$PopupSigninHref\" popupHeight=\"326\" popupWidth=\"627\" rel=\"nofollow\" ><img src=\"$ImgSrc\" alt=\"$ImgAlt\" /></a>");
//         }

         $Sender->Data['Methods'][] = $FbMethod;
      }
   }
   
   /**
    * Add 'Facebook' option to the row.
    */
   public function Base_AfterReactions_Handler($Sender, $Args) {
      if (!$this->SocialReactions()) 
         return;
      
      echo Gdn_Theme::BulletItem('Share');
      $this->AddReactButton($Sender, $Args);
   }
   
   public function Base_DiscussionFormOptions_Handler($Sender, $Args) {
      if (!$this->SocialSharing()) 
         return;
      
      if (!$this->AccessToken())
         return;
      
      $Options =& $Args['Options'];
      
      $Options .= ' <li>'.
         $Sender->Form->CheckBox('ShareFacebook', '@'.Sprite('ReactFacebook', 'ReactSprite'), array('value' => '1', 'title' => sprintf(T('Share to %s.'), 'Facebook'))).
         '</li> ';
   }
   
   public function DiscussionController_AfterBodyField_Handler($Sender, $Args) {
      if (!$this->SocialSharing()) 
         return;
      
      if (!$this->AccessToken())
         return;
      
      echo ' '.
         $Sender->Form->CheckBox('ShareFacebook', '@'.Sprite('ReactFacebook', 'ReactSprite'), array('value' => '1', 'title' => sprintf(T('Share to %s.'), 'Facebook'))).
         ' ';
   }
   
   public function DiscussionModel_AfterSaveDiscussion_Handler($Sender, $Args) {
      if (!$this->SocialSharing()) 
         return;
      
      if (!$this->AccessToken())
         return;
      
      $ShareFacebook = GetValueR('FormPostValues.ShareFacebook', $Args);
      
      if ($ShareFacebook) {
         $Url = DiscussionUrl($Args['Fields'], '', TRUE);
//         $Message = SliceParagraph(Gdn_Format::PlainText($Row['Body'], $Row['Format']), 160);
         
         if ($this->AccessToken()) {
            $R = $this->API('/me/feed', array(
                'link' => $Url
                ));
         }
      }
   }
   
   public function CommentModel_AfterSaveComment_Handler($Sender, $Args) {
      if (!$this->SocialSharing()) 
         return;
      
      if (!$this->AccessToken())
         return;
      
      $ShareFacebook = GetValueR('FormPostValues.ShareFacebook', $Args);
      
      if ($ShareFacebook) {
         $Row = $Args['FormPostValues'];
         
         $DiscussionModel = new DiscussionModel();
         $Discussion = $DiscussionModel->GetID(GetValue('DiscussionID', $Row));
         if (!$Discussion)
            die('no discussion');
         
         $Url = DiscussionUrl($Discussion, '', TRUE);
         $Message = SliceParagraph(Gdn_Format::PlainText($Row['Body'], $Row['Format']), 160);
         
         if ($this->AccessToken()) {
            $R = $this->API('/me/feed', array(
                'link' => $Url,
                'message' => $Message
                ));
         }
      }
   }

   /**
    * Output Quote link.
    */
   protected function AddReactButton($Sender, $Args) {
      if ($this->AccessToken()) {
         $CssClass = 'ReactButton Hijack';
      } else {
         $CssClass = 'ReactButton PopupWindow';
      }
      
      echo ' '.Anchor(Sprite('ReactFacebook', 'ReactSprite'), Url("post/facebook/{$Args['RecordType']}?id={$Args['RecordID']}", TRUE), $CssClass).' ';
   }
   
   public function Base_SignInIcons_Handler($Sender, $Args) {
      if (!$this->IsConfigured())
         return;
		
		echo "\n".$this->_GetButton();
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
   
   public function Base_GetConnections_Handler($Sender, $Args) {
      $Profile = GetValueR('User.Attributes.'.self::ProviderKey.'.Profile', $Args);
      
      $Sender->Data["Connections"][self::ProviderKey] = array(
         'Icon' => $this->GetWebResource('icon.png', '/'),
         'Name' => 'Facebook',
         'ProviderKey' => self::ProviderKey,
         'ConnectUrl' => $this->AuthorizeUri(FALSE, self::ProfileConnecUrl()),
         'Profile' => array(
            'Name' => GetValue('name', $Profile),
            'Photo' => "//graph.facebook.com/{$Profile['id']}/picture?type=large"
            )
      );
   }
   
   /**
    * 
    * @param PostController $Sender
    * @param type $RecordType
    * @param type $ID
    * @throws type
    */
   public function PostController_Facebook_Create($Sender, $RecordType, $ID) {
      if (!$this->SocialReactions()) 
         throw PermissionException();
            
//      if (!Gdn::Request()->IsPostBack())
//         throw PermissionException('Javascript');
      
      $Row = GetRecord($RecordType, $ID);
      if ($Row) {
         $Message = SliceParagraph(Gdn_Format::PlainText($Row['Body'], $Row['Format']), 160);
         
         if ($this->AccessToken() && $Sender->Request->IsPostBack()) {
            $R = $this->API('/me/feed', array(
                'link' => $Row['ShareUrl'],
                'message' => $Message
                ));

            $Sender->SetJson('R', $R);
            $Sender->InformMessage(T('Thanks for sharing!'));
         } else {
//            http://www.facebook.com/dialog/feed?app_id=231546166870342&redirect_uri=http%3A%2F%2Fvanillicon.com%2Fredirect%2Ffacebook%3Fhash%3Daad66afb13105676dffa79bfe2b8595f&link=http%3A%2F%2Fvanillicon.com&picture=http%3A%2F%2Fvanillicon.com%2Faad66afb13105676dffa79bfe2b8595f.png&name=Vanillicon&caption=What%27s+Your+Vanillicon+Look+Like%3F&description=Vanillicons+are+unique+avatars+generated+by+your+name+or+email+that+are+free+to+make+%26+use+around+the+web.+Create+yours+now%21
            $Get = array(
                  'app_id' => C('Plugins.Facebook.ApplicationID'),
                  'link' => $Row['ShareUrl'],
                  'name' => Gdn_Format::PlainText($Row['Name'], 'Text'),
//                  'caption' => 'Foo',
                  'description' => $Message,
                  'redirect_uri' => Url('/post/shared/facebook', TRUE)
                );
            
            $Url = 'http://www.facebook.com/dialog/feed?'.http_build_query($Get);
            Redirect($Url);
         }
      }
      
      $Sender->Render('Blank', 'Utility', 'Dashboard');
   }
   
   /**
    * 
    * 
    * @param ProfileController $Sender
    * @param type $UserReference
    * @param type $Username
    * @param type $Code
    */
   public function ProfileController_FacebookConnect_Create($Sender, $UserReference, $Username, $Code = FALSE) {
      $Sender->Permission('Garden.SignIn.Allow');
      
      $Sender->GetUserInfo($UserReference, $Username, '', TRUE);
      $Sender->_SetBreadcrumbs(T('Connections'), '/profile/connections');
      
      // Get the access token.
      $AccessToken = $this->GetAccessToken($Code, self::ProfileConnecUrl());
      
      // Get the profile.
      $Profile = $this->GetProfile($AccessToken);
      
      // Save the authentication.
      Gdn::UserModel()->SaveAuthentication(array(
         'UserID' => $Sender->User->UserID,
         'Provider' => self::ProviderKey,
         'UniqueID' => $Profile['id']));
      
      // Save the information as attributes.
      $Attributes = array(
          'AccessToken' => $AccessToken,
          'Profile' => $Profile
      );
      Gdn::UserModel()->SaveAttribute($Sender->User->UserID, self::ProviderKey, $Attributes);
      
      $this->EventArguments['Provider'] = self::ProviderKey;
      $this->EventArguments['User'] = $Sender->User;
      $this->FireEvent('AfterConnection');
      
      Redirect(UserUrl($Sender->User, '', 'connections'));
   }
	
	private function _GetButton() {
      $ImgSrc = Asset('/plugins/Facebook/design/facebook-icon.png');
      $ImgAlt = T('Sign In with Facebook');
      $SigninHref = $this->AuthorizeUri();
      $PopupSigninHref = $this->AuthorizeUri('display=popup');
      return "<a id=\"FacebookAuth\" href=\"$SigninHref\" class=\"PopupWindow\" title=\"$ImgAlt\" popupHref=\"$PopupSigninHref\" popupHeight=\"326\" popupWidth=\"627\" rel=\"nofollow\" ><img src=\"$ImgSrc\" alt=\"$ImgAlt\" align=\"bottom\" /></a>";
   }
	
   public function SocialController_Facebook_Create($Sender, $Args) {
      $Sender->Permission('Garden.Settings.Manage');
      if ($Sender->Form->AuthenticatedPostBack()) {
         $Settings = array(
             'Plugins.Facebook.ApplicationID' => $Sender->Form->GetFormValue('ApplicationID'),
             'Plugins.Facebook.Secret' => $Sender->Form->GetFormValue('Secret'),
             'Plugins.Facebook.UseFacebookNames' => $Sender->Form->GetFormValue('UseFacebookNames'),
             'Plugins.Facebook.SocialReactions' => $Sender->Form->GetFormValue('SocialReactions'),
             'Plugins.Facebook.SocialSharing' => $Sender->Form->GetFormValue('SocialSharing'),
             'Garden.Registration.SendConnectEmail' => $Sender->Form->GetFormValue('SendConnectEmail'));

         SaveToConfig($Settings);
         $Sender->InformMessage(T("Your settings have been saved."));

      } else {
         $Sender->Form->SetValue('ApplicationID', C('Plugins.Facebook.ApplicationID'));
         $Sender->Form->SetValue('Secret', C('Plugins.Facebook.Secret'));
         $Sender->Form->SetValue('UseFacebookNames', C('Plugins.Facebook.UseFacebookNames'));
         $Sender->Form->SetValue('SendConnectEmail', C('Garden.Registration.SendConnectEmail', TRUE));
         $Sender->Form->SetValue('SocialReactions', $this->SocialReactions());
         $Sender->Form->SetValue('SocialSharing', $this->SocialSharing());
      }

      $Sender->AddSideMenu('dashboard/social');
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
      
      $AccessToken = $Sender->Form->GetFormValue('AccessToken');
      
      // Get the access token.
      if (!$AccessToken && $Code) {
         // Exchange the token for an access token.
         $Code = urlencode($Code);
         
         $AccessToken = $this->GetAccessToken($Code, $RedirectUri);

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
      $Form->SetFormValue('Provider', self::ProviderKey);
      $Form->SetFormValue('ProviderName', 'Facebook');
      $Form->SetFormValue('FullName', GetValue('name', $Profile));
      $Form->SetFormValue('Email', GetValue('email', $Profile));
      $Form->SetFormValue('Photo', "//graph.facebook.com/{$ID}/picture?type=large");
      $Form->AddHidden('AccessToken', $AccessToken);
      
      if (C('Plugins.Facebook.UseFacebookNames')) {
         $Form->SetFormValue('Name', GetValue('name', $Profile));
         SaveToConfig(array(
             'Garden.User.ValidationRegex' => UserModel::USERNAME_REGEX_MIN,
             'Garden.User.ValidationLength' => '{3,50}',
             'Garden.Registration.NameUnique' => FALSE
         ), '', FALSE);
      }
      
      // Save some original data in the attributes of the connection for later API calls.
      $Attributes = array();
      $Attributes[self::ProviderKey] = array(
          'AccessToken' => $AccessToken,
          'Profile' => $Profile
      );
      $Form->SetFormValue('Attributes', $Attributes);
      
      $Sender->SetData('Verified', TRUE);
   }
   
   protected function GetAccessToken($Code, $RedirectUri, $ThrowError = TRUE) {
      $Get = array(
          'client_id' => C('Plugins.Facebook.ApplicationID'),
          'client_secret' => C('Plugins.Facebook.Secret'),
          'code' => $Code,
          'redirect_uri' => $RedirectUri);
      
      $Url = 'https://graph.facebook.com/oauth/access_token?'.http_build_query($Get);
      
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
//      $Expires = GetValue('expires', $Tokens, NULL);
      
      return $AccessToken;
   }

   public function GetProfile($AccessToken) {
      $Url = "https://graph.facebook.com/me?access_token=$AccessToken";
//      $C = curl_init();
//      curl_setopt($C, CURLOPT_RETURNTRANSFER, TRUE);
//      curl_setopt($C, CURLOPT_SSL_VERIFYPEER, FALSE);
//      curl_setopt($C, CURLOPT_URL, $Url);
//      $Contents = curl_exec($C);
//      $Contents = ProxyRequest($Url);
      $Contents = file_get_contents($Url);
      $Profile = json_decode($Contents, TRUE);
      return $Profile;
   }

   public function AuthorizeUri($Query = FALSE, $RedirectUri = FALSE) {
      $AppID = C('Plugins.Facebook.ApplicationID');
      $FBScope = C('Plugins.Facebook.Scope', Array('email','publish_stream'));

      if (!$RedirectUri)
         $RedirectUri = $this->RedirectUri();
      if ($Query)
         $RedirectUri .= '&'.$Query;
      $RedirectUri = urlencode($RedirectUri);

      $Scopes = implode(',', $FBScope);
      $SigninHref = "https://graph.facebook.com/oauth/authorize?client_id=$AppID&redirect_uri=$RedirectUri&scope=$Scopes";
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

         $Target = GetValue('Target', $_GET, $Path ? $Path : '/');
         if (ltrim($Target, '/') == 'entry/signin' || empty($Target))
            $Target = '/';
         $Args = array('Target' => $Target);


         $RedirectUri .= strpos($RedirectUri, '?') === FALSE ? '?' : '&';
         $RedirectUri .= http_build_query($Args);
         $this->_RedirectUri = $RedirectUri;
      }
      
      return $this->_RedirectUri;
   }
   
   public static function ProfileConnecUrl() {
      return Url(UserUrl(Gdn::Session()->User, FALSE, 'facebookconnect'), TRUE);
   }

   public function IsConfigured() {
      $AppID = C('Plugins.Facebook.ApplicationID');
      $Secret = C('Plugins.Facebook.Secret');
      if (!$AppID || !$Secret)
         return FALSE;
      return TRUE;
   }
   
   public function SocialSharing() {
      return C('Plugins.Facebook.SocialSharing', TRUE) && $this->IsConfigured();
   }
   
   public function SocialReactions() {
      return C('Plugins.Facebook.SocialReactions', TRUE) && $this->IsConfigured();
   }
   
   public function Setup() {
      $Error = '';
      if (!function_exists('curl_init'))
         $Error = ConcatSep("\n", $Error, 'This plugin requires curl.');
      if ($Error)
         throw new Gdn_UserException($Error, 400);

      $this->Structure();
   }

   public function Structure() {
      // Save the facebook provider type.
      Gdn::SQL()->Replace('UserAuthenticationProvider',
         array('AuthenticationSchemeAlias' => 'facebook', 'URL' => '...', 'AssociationSecret' => '...', 'AssociationHashMethod' => '...'),
         array('AuthenticationKey' => self::ProviderKey), TRUE);
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
