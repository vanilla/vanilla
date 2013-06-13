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
$PluginInfo['Twitter'] = array(
	'Name' => 'Twitter Social Connect',
   'Description' => 'Users may sign into your site using their Twitter account.',
   'Version' => '1.0.4',
   'RequiredApplications' => array('Vanilla' => '2.0.12a'),
   'RequiredTheme' => FALSE,
   'RequiredPlugins' => FALSE,
	'MobileFriendly' => TRUE,
   'SettingsUrl' => '/dashboard/social/twitter',
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

require_once PATH_LIBRARY.'/vendors/oauth/OAuth.php';

class TwitterPlugin extends Gdn_Plugin {
   const ProviderKey = 'Twitter';
   public static $BaseApiUrl = 'http://api.twitter.com/1.1/';

   protected $_AccessToken = NULL;
   
   /**
    * Gets/sets the current oauth access token.
    *
    * @param string $Token
    * @param string $Secret
    * @return OAuthToken
    */
   public function AccessToken($Token = NULL, $Secret = NULL) {
      if (!$this->IsConfigured()) 
         return FALSE;
      
      if (is_object($Token)) {
         $this->_AccessToken = $Token;
      } if ($Token !== NULL && $Secret !== NULL) {
         $this->_AccessToken = new OAuthToken($Token, $Secret);
//         setcookie('tw_access_token', $Token, 0, C('Garden.Cookie.Path', '/'), C('Garden.Cookie.Domain', ''));
      } elseif ($this->_AccessToken == NULL) {
//         $Token = GetValue('tw_access_token', $_COOKIE, NULL);
         if ($Token)
            $this->_AccessToken = $this->GetOAuthToken($Token);
         elseif (Gdn::Session()->User) {
            $AccessToken = GetValueR(self::ProviderKey.'.AccessToken', Gdn::Session()->User->Attributes);
            
            if (is_array($AccessToken)) {
               $this->_AccessToken = new OAuthToken($AccessToken[0], $AccessToken[1]);
            }
         }
      }
      return $this->_AccessToken;
   }

//   public function AuthenticationController_Render_Before($Sender, $Args) {
//      if (isset($Sender->ChooserList)) {
//         $Sender->ChooserList['twitter'] = 'Twitter';
//      }
//      if (is_array($Sender->Data('AuthenticationConfigureList'))) {
//         $List = $Sender->Data('AuthenticationConfigureList');
//         $List['twitter'] = '/dashboard/settings/twitter';
//         $Sender->SetData('AuthenticationConfigureList', $List);
//      }
//   }

   protected function _AuthorizeHref($Popup = FALSE) {
      $Url = Url('/entry/twauthorize', TRUE);
      $UrlParts = explode('?', $Url);

      parse_str(GetValue(1, $UrlParts, ''), $Query);
      $Path = Gdn::Request()->Path();

      $Target = GetValue('Target', $_GET, $Path ? $Path : '/');
      if (ltrim($Target, '/') == 'entry/signin')
         $Target = '/';
      $Query['Target'] = $Target;

      if ($Popup)
         $Query['display'] = 'popup';
      $Result = $UrlParts[0].'?'.http_build_query($Query);

      return $Result;
   }

   /**
    *
    * @param Gdn_Controller $Sender
    */
   public function EntryController_SignIn_Handler($Sender, $Args) {
      if (isset($Sender->Data['Methods'])) {
         if (!$this->IsConfigured())
            return;

         $ImgSrc = Asset('/plugins/Twitter/design/twitter-signin.png');
         $ImgAlt = T('Sign In with Twitter');
            $SigninHref = $this->_AuthorizeHref();
            $PopupSigninHref = $this->_AuthorizeHref(TRUE);

            // Add the twitter method to the controller.
            $TwMethod = array(
               'Name' => 'Twitter',
               'SignInHtml' => "<a id=\"TwitterAuth\" href=\"$SigninHref\" class=\"PopupWindow\" popupHref=\"$PopupSigninHref\" popupHeight=\"400\" popupWidth=\"800\" rel=\"nofollow\"><img src=\"$ImgSrc\" alt=\"$ImgAlt\" /></a>");

         $Sender->Data['Methods'][] = $TwMethod;
      }
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
			echo "\n".Wrap($this->_GetButton(), 'li', array('class' => 'Connect TwitterConnect'));
	}
   
   public function Base_DiscussionFormOptions_Handler($Sender, $Args) {
      if (!$this->SocialSharing()) 
         return;
      
      if (!$this->AccessToken())
         return;
      
      $Options =& $Args['Options'];
      
      $Options .= ' <li>'.
         $Sender->Form->CheckBox('ShareTwitter', '@'.Sprite('ReactTwitter', 'ReactSprite'), array('value' => '1', 'title' => sprintf(T('Share to %s.'), 'Twitter'))).
         '</li> ';
   }
   
   public function DiscussionController_AfterBodyField_Handler($Sender, $Args) {
      if (!$this->SocialSharing()) 
         return;
      
      if (!$this->AccessToken())
         return;
      
      echo ' '.
         $Sender->Form->CheckBox('ShareTwitter', '@'.Sprite('ReactTwitter', 'ReactSprite'), array('value' => '1', 'title' => sprintf(T('Share to %s.'), 'Twitter'))).
         ' ';
   }
   
   public function DiscussionModel_AfterSaveDiscussion_Handler($Sender, $Args) {
      if (!$this->SocialSharing()) 
         return;
      
      if (!$this->AccessToken())
         return;
      
      $Share = GetValueR('FormPostValues.ShareTwitter', $Args);
      
      if ($Share && $this->AccessToken()) {
         $Row = $Args['Fields'];
         $Url = DiscussionUrl($Row, '', TRUE);
         $Message = SliceTwitter(Gdn_Format::PlainText($Row['Body'], $Row['Format'])).' '.$Url;
         
         $R = $this->API('/statuses/update.json', array(
             'status' => $Message
             ),
             'POST');
      }
   }
   
   public function CommentModel_AfterSaveComment_Handler($Sender, $Args) {
      if (!$this->SocialSharing()) 
         return;
      
      if (!$this->AccessToken())
         return;
      
      $Share = GetValueR('FormPostValues.ShareTwitter', $Args);
      
      if ($Share && $this->AccessToken()) {
         $Row = $Args['FormPostValues'];
         
         $DiscussionModel = new DiscussionModel();
         $Discussion = $DiscussionModel->GetID(GetValue('DiscussionID', $Row));
         if (!$Discussion)
            return;
         
         $Url = DiscussionUrl($Discussion, '', TRUE);
         $Message = SliceTwitter(Gdn_Format::PlainText($Row['Body'], $Row['Format'])).' '.$Url;
         
         $R = $this->API('/statuses/update.json', array(
             'status' => $Message
             ),
             'POST');
         
//         decho($R);
//         die();
//      } else {
//         die("$Share ".$this->AccessToken());
      }
   }
	
	private function _GetButton() {      
      $ImgSrc = Asset('/plugins/Twitter/design/twitter-icon.png');
      $ImgAlt = T('Sign In with Twitter');
      $SigninHref = $this->_AuthorizeHref();
      $PopupSigninHref = $this->_AuthorizeHref(TRUE);
		return "<a id=\"TwitterAuth\" href=\"$SigninHref\" class=\"PopupWindow\" title=\"$ImgAlt\" popupHref=\"$PopupSigninHref\" popupHeight=\"800\" popupWidth=\"800\" rel=\"nofollow\"><img src=\"$ImgSrc\" alt=\"$ImgAlt\" /></a>";
   }

	public function Authorize($Query = FALSE) {
      // Aquire the request token.
      $Consumer = new OAuthConsumer(C('Plugins.Twitter.ConsumerKey'), C('Plugins.Twitter.Secret'));
      $RedirectUri = $this->RedirectUri();
      if ($Query)
         $RedirectUri .= (strpos($RedirectUri, '?') === FALSE ? '?' : '&').$Query;

      $Params = array('oauth_callback' => $RedirectUri);
      
      $Url = 'https://api.twitter.com/oauth/request_token';
      $Request = OAuthRequest::from_consumer_and_token($Consumer, NULL, 'POST', $Url, $Params);
      $SignatureMethod = new OAuthSignatureMethod_HMAC_SHA1();
      $Request->sign_request($SignatureMethod, $Consumer, null);

      $Curl = $this->_Curl($Request, $Params);
      $Response = curl_exec($Curl);
      if ($Response === FALSE) {
         $Response = curl_error($Curl);
      }

      $HttpCode = curl_getinfo($Curl, CURLINFO_HTTP_CODE);
      curl_close($Curl);

      if ($HttpCode == '200') {
         // Parse the reponse.
         $Data = OAuthUtil::parse_parameters($Response);

         if (!isset($Data['oauth_token']) || !isset($Data['oauth_token_secret'])) {
            $Response = T('The response was not in the correct format.');
         } else {
            // Save the token for later reference.
            $this->SetOAuthToken($Data['oauth_token'], $Data['oauth_token_secret'], 'request');

            // Redirect to twitter's authorization page.
            $Url = "http://api.twitter.com/oauth/authenticate?oauth_token={$Data['oauth_token']}";
            Redirect($Url);
         }
      }

      // There was an error. Echo the error.
      echo $Response;
   }

   public function EntryController_Twauthorize_Create($Sender, $Dir = '') {
      $Query = ArrayTranslate($Sender->Request->Get(), array('display', 'Target'));
      $Query = http_build_query($Query);
      
      if ($Dir == 'profile') {
         // This is a profile connection.
         $this->RedirectUri(self::ProfileConnecUrl());
      }
      
      $this->Authorize($Query);
   }
   
   /**
    * 
    * @param PostController $Sender
    * @param type $RecordType
    * @param type $ID
    * @throws type
    */
   public function PostController_Twitter_Create($Sender, $RecordType, $ID) {
      if (!$this->SocialReactions()) 
         throw PermissionException();
            
//      if (!Gdn::Request()->IsPostBack())
//         throw PermissionException('Javascript');
      
      $Row = GetRecord($RecordType, $ID);
      if ($Row) {
         // Grab the tweet message.
         switch (strtolower($RecordType)) {
            case 'discussion':
               $Message = Gdn_Format::PlainText($Row['Name'], 'Text');
               break;
            case 'comment':
            default:
               $Message = Gdn_Format::PlainText($Row['Body'], $Row['Format']);
         }
         
         $Elips = '...';
         
         $Message = preg_replace('`\s+`', ' ', $Message);
         
//         if (function_exists('normalizer_is_normalized')) {
//            // Slice the string to 119 characters (21 reservered for the url.
//            if (!normalizer_is_normalized($Message))
//               $Message = Normalizer::normalize($Message, Normalizer::FORM_D);
//            $Elips = Normalizer::normalize($Elips, Normalizer::FORM_D);
//         }
         
         $Max = 140;
         $LinkLen = 22;
         
         $Max -= $LinkLen;
         
         $Message = SliceParagraph($Message, $Max);
         if (strlen($Message) > $Max) {
            $Message = substr($Message, 0, $Max - strlen($Elips)).$Elips;
         }
         
//         echo $Message.strlen($Message);
         
         if ($this->AccessToken()) {
            Gdn::Controller()->SetData('Message', $Message);
            
            $Message .= ' '.$Row['ShareUrl'];
            $R = $this->API('/statuses/update.json', array(
                'status' => $Message
                ),
                'POST');

            $Sender->SetJson('R', $R);
            $Sender->InformMessage(T('Thanks for sharing!'));
         } else {
            $Get = array(
                'text' => $Message,
                'url' => $Row['ShareUrl']
                );
            $Url = "http://twitter.com/share?".http_build_query($Get);
            Redirect($Url);
         }
      }
      
      $Sender->Render('Blank', 'Utility', 'Dashboard');
   }
   
   /**
    * 
    * @param ProfileController $Sender
    * @param type $UserReference
    * @param type $Username
    * @param type $oauth_token
    * @param type $oauth_verifier
    */
   public function ProfileController_TwitterConnect_Create($Sender, $UserReference = '', $Username = '', $oauth_token = '', $oauth_verifier = '') {
      $Sender->Permission('Garden.SignIn.Allow');
      
      $Sender->GetUserInfo($UserReference, $Username, '', TRUE);
      
      $Sender->_SetBreadcrumbs(T('Connections'), '/profile/connections');
      
      // Get the access token.
      Trace('GetAccessToken()');
      $AccessToken = $this->GetAccessToken($oauth_token, $oauth_verifier);
      $this->AccessToken($AccessToken);
      
      // Get the profile.
      Trace('GetProfile()');
      $Profile = $this->GetProfile();
      
      // Save the authentication.
      Gdn::UserModel()->SaveAuthentication(array(
         'UserID' => $Sender->User->UserID,
         'Provider' => self::ProviderKey,
         'UniqueID' => $Profile['id']));
      
      // Save the information as attributes.
      $Attributes = array(
          'AccessToken' => array($AccessToken->key, $AccessToken->secret),
          'Profile' => $Profile
      );
      Gdn::UserModel()->SaveAttribute($Sender->User->UserID, self::ProviderKey, $Attributes);
      
      $this->EventArguments['Provider'] = self::ProviderKey;
      $this->EventArguments['User'] = $Sender->User;
      $this->FireEvent('AfterConnection');
      
      Redirect(UserUrl($Sender->User, '', 'connections'));
   }
   
   public function GetAccessToken($RequestToken, $Verifier) {
      if ((!$RequestToken || !$Verifier) && Gdn::Request()->Get('denied')) {
         throw new Gdn_UserException(T('Looks like you denied our request.'), 401);
      }
      
      // Get the request secret.
      $RequestToken = $this->GetOAuthToken($RequestToken);

      $Consumer = new OAuthConsumer(C('Plugins.Twitter.ConsumerKey'), C('Plugins.Twitter.Secret'));

      $Url = 'https://api.twitter.com/oauth/access_token';
      $Params = array(
          'oauth_verifier' => $Verifier //GetValue('oauth_verifier', $_GET)
      );
      $Request = OAuthRequest::from_consumer_and_token($Consumer, $RequestToken, 'POST', $Url, $Params);

      $SignatureMethod = new OAuthSignatureMethod_HMAC_SHA1();
      $Request->sign_request($SignatureMethod, $Consumer, $RequestToken);
      $Post = $Request->to_postdata();

      $Curl = $this->_Curl($Request);
      $Response = curl_exec($Curl);
      if ($Response === FALSE) {
         $Response = curl_error($Curl);
      }
      $HttpCode = curl_getinfo($Curl, CURLINFO_HTTP_CODE);
      curl_close($Curl);

      if ($HttpCode == '200') {
         $Data = OAuthUtil::parse_parameters($Response);

         $AccessToken = new OAuthToken(GetValue('oauth_token', $Data), GetValue('oauth_token_secret', $Data));

         // Save the access token to the database.
//         $this->SetOAuthToken($AccessToken->key, $AccessToken->secret, 'access');
//         $this->AccessToken($AccessToken->key, $AccessToken->secret);

         // Delete the request token.
         $this->DeleteOAuthToken($RequestToken);

      } else {
         // There was some sort of error.
         throw new Gdn_UserException('There was an error authenticating with twitter. '.$Response, $HttpCode);
      }

      return $AccessToken;
   }

   /**
    *
    * @param Gdn_Controller $Sender
    * @param array $Args
    */
   public function Base_ConnectData_Handler($Sender, $Args) {
      if (GetValue(0, $Args) != 'twitter')
         return;
      
      $Form = $Sender->Form; //new Gdn_Form();

      $RequestToken = GetValue('oauth_token', $_GET);
      $AccessToken = $Form->GetFormValue('AccessToken');
      
      if ($AccessToken) {
         $AccessToken = $this->GetOAuthToken($AccessToken);
         $this->AccessToken($AccessToken);
      }
      
      // Get the access token.
      if ($RequestToken && !$AccessToken) {
         // Get the request secret.
         $RequestToken = $this->GetOAuthToken($RequestToken);

         $Consumer = new OAuthConsumer(C('Plugins.Twitter.ConsumerKey'), C('Plugins.Twitter.Secret'));

         $Url = 'https://api.twitter.com/oauth/access_token';
         $Params = array(
             'oauth_verifier' => GetValue('oauth_verifier', $_GET)
         );
         $Request = OAuthRequest::from_consumer_and_token($Consumer, $RequestToken, 'POST', $Url, $Params);
         
         $SignatureMethod = new OAuthSignatureMethod_HMAC_SHA1();
         $Request->sign_request($SignatureMethod, $Consumer, $RequestToken);
         $Post = $Request->to_postdata();

         $Curl = $this->_Curl($Request);
         $Response = curl_exec($Curl);
         if ($Response === FALSE) {
            $Response = curl_error($Curl);
         }
         $HttpCode = curl_getinfo($Curl, CURLINFO_HTTP_CODE);
         curl_close($Curl);

         if ($HttpCode == '200') {
            $Data = OAuthUtil::parse_parameters($Response);

            $AccessToken = new OAuthToken(GetValue('oauth_token', $Data), GetValue('oauth_token_secret', $Data));
            
            // Save the access token to the database.
            $this->SetOAuthToken($AccessToken->key, $AccessToken->secret, 'access');
            $this->AccessToken($AccessToken->key, $AccessToken->secret);

            // Delete the request token.
            $this->DeleteOAuthToken($RequestToken);
            
         } else {
            // There was some sort of error.
            throw new Exception('There was an error authenticating with twitter.', 400);
         }
         
         $NewToken = TRUE;
      }

      // Get the profile.
      try {
         $Profile = $this->GetProfile($AccessToken);
      } catch (Exception $Ex) {
         if (!isset($NewToken)) {
            // There was an error getting the profile, which probably means the saved access token is no longer valid. Try and reauthorize.
            if ($Sender->DeliveryType() == DELIVERY_TYPE_ALL) {
               Redirect($this->_AuthorizeHref());
            } else {
               $Sender->SetHeader('Content-type', 'application/json');
               $Sender->DeliveryMethod(DELIVERY_METHOD_JSON);
               $Sender->RedirectUrl = $this->_AuthorizeHref();
            }
         } else {
            throw $Ex;
         }
      }
      
      $ID = GetValue('id', $Profile);
      $Form->SetFormValue('UniqueID', $ID);
      $Form->SetFormValue('Provider', self::ProviderKey);
      $Form->SetFormValue('ProviderName', 'Twitter');
      $Form->SetValue('ConnectName', GetValue('screen_name', $Profile));
      $Form->SetFormValue('Name', GetValue('screen_name', $Profile));
      $Form->SetFormValue('FullName', GetValue('name', $Profile));
      $Form->SetFormValue('Photo', GetValue('profile_image_url', $Profile));
      $Form->AddHidden('AccessToken', $AccessToken->key);
      
      // Save some original data in the attributes of the connection for later API calls.
      $Attributes = array(self::ProviderKey => array(
          'AccessToken' => array($AccessToken->key, $AccessToken->secret),
          'Profile' => $Profile
      ));
      $Form->SetFormValue('Attributes', $Attributes);
      
      $Sender->SetData('Verified', TRUE);
   }
   
   public function Base_GetConnections_Handler($Sender, $Args) {
      $Profile = GetValueR('User.Attributes.'.self::ProviderKey.'.Profile', $Args);
      
      $Sender->Data["Connections"][self::ProviderKey] = array(
         'Icon' => $this->GetWebResource('icon.png', '/'),
         'Name' => 'Twitter',
         'ProviderKey' => self::ProviderKey,
         'ConnectUrl' => '/entry/twauthorize/profile',
         'Profile' => array(
             'Name' => '@'.GetValue('screen_name', $Profile),
             'Photo' => GetValue('profile_image_url', $Profile)
             )
      );
   }

   public function API($Url, $Params = NULL, $Method = 'GET') {
      if (strpos($Url, '//') === FALSE)
         $Url = self::$BaseApiUrl.trim($Url, '/');
      $Consumer = new OAuthConsumer(C('Plugins.Twitter.ConsumerKey'), C('Plugins.Twitter.Secret'));
      
      if ($Method == 'POST') {
         $Post = $Params;
      } else
         $Post = NULL;

      $AccessToken = $this->AccessToken();
//      var_dump($AccessToken);
      
      $Request = OAuthRequest::from_consumer_and_token($Consumer, $AccessToken, $Method, $Url, $Params);
      
      $SignatureMethod = new OAuthSignatureMethod_HMAC_SHA1();
      $Request->sign_request($SignatureMethod, $Consumer, $AccessToken);
      
//      print_r($Params);

      $Curl = $this->_Curl($Request, $Post);
      curl_setopt($Curl, CURLINFO_HEADER_OUT, TRUE);
//      curl_setopt($Curl, CURLOPT_VERBOSE, TRUE);
//      $fp = fopen("php://stdout", 'w'); 
//      curl_setopt($Curl, CURLOPT_STDERR, $fp);
      $Response = curl_exec($Curl);
      $HttpCode = curl_getinfo($Curl, CURLINFO_HTTP_CODE);
      
      if ($Response == FALSE) {
         $Response = curl_error($Curl);
      }
      
//      echo curl_getinfo($Curl, CURLINFO_HEADER_OUT);
//      
//      echo($Request->to_postdata());
//      echo "\n\n";
      
      Trace(curl_getinfo($Curl, CURLINFO_HEADER_OUT));
      
      Trace($Response, 'Response');
      
//      print_r(curl_getinfo($Curl));
//      die();
      
      curl_close($Curl);

      Gdn::Controller()->SetJson('Response', $Response);
      if (strpos($Url, '.json') !== FALSE) {
         $Result = @json_decode($Response, TRUE) or $Response;
      } else {
         $Result = $Response;
      }
      
//      print_r($Result);
      
      if ($HttpCode == '200')
         return $Result;
      else {
         throw new Gdn_UserException(GetValueR('errors.0.message', $Result, $Response), $HttpCode);
      }
   }

   public function GetProfile() {
      $Profile = $this->API('/account/verify_credentials.json', array('include_entities' => '0', 'skip_status' => '1'));
      return $Profile;
   }

   public function GetOAuthToken($Token) {
      $Row = Gdn::SQL()->GetWhere('UserAuthenticationToken', array('Token' => $Token, 'ProviderKey' => self::ProviderKey))->FirstRow(DATASET_TYPE_ARRAY);
      if ($Row) {
         return new OAuthToken($Row['Token'], $Row['TokenSecret']);
      } else {
         return NULL;
      }
   }

   public function IsConfigured() {
      $Result = C('Plugins.Twitter.ConsumerKey') && C('Plugins.Twitter.Secret');
      return $Result;
   }
   
   public function SocialSharing() {
      return C('Plugins.Twitter.SocialSharing', TRUE) && $this->IsConfigured();
   }
   
   public function SocialReactions() {
      return C('Plugins.Twitter.SocialReactions', TRUE) && $this->IsConfigured();
   }

   public function SetOAuthToken($Token, $Secret = NULL, $Type = 'request') {
      if (is_a($Token, 'OAuthToken')) {
         $Secret = $Token->secret;
         $Token = $Token->key;
      }

      // Insert the token.
      $Data = array(
                'Token' => $Token,
                'ProviderKey' => self::ProviderKey,
                'TokenSecret' => $Secret,
                'TokenType' => $Type,
                'Authorized' => FALSE,
                'Lifetime' => 60 * 5);
      Gdn::SQL()->Options('Ignore', TRUE)->Insert('UserAuthenticationToken', $Data);
   }

   public function DeleteOAuthToken($Token) {
      if (is_a($Token, 'OAuthToken')) {
         $Token = $Token->key;
      }
      
      Gdn::SQL()->Delete('UserAuthenticationToken', array('Token' => $Token, 'ProviderKey' => self::ProviderKey));
   }

   /**
    *
    * @param OAuthRequest $Request 
    */
   protected function _Curl($Request, $Post = NULL) {
      $C = curl_init();
      curl_setopt($C, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($C, CURLOPT_SSL_VERIFYPEER, FALSE);
      switch ($Request->get_normalized_http_method()) {
         case 'POST':
//            echo $Request->get_normalized_http_url();
//            echo "\n\n";
//            echo $Request->to_postdata();
            
            curl_setopt($C, CURLOPT_URL, $Request->get_normalized_http_url());
//            curl_setopt($C, CURLOPT_HTTPHEADER, array('Authorization' => $Request->to_header()));
            curl_setopt($C, CURLOPT_POST, TRUE);
            curl_setopt($C, CURLOPT_POSTFIELDS, $Request->to_postdata());
            break;
         default:
            curl_setopt($C, CURLOPT_URL, $Request->to_url());
      }
      return $C;
   }
   
   public static function ProfileConnecUrl() {
      return Url(UserUrl(Gdn::Session()->User, FALSE, 'twitterconnect'), TRUE);
   }

   protected $_RedirectUri = NULL;

   public function RedirectUri($NewValue = NULL) {
      if ($NewValue !== NULL)
         $this->_RedirectUri = $NewValue;
      elseif ($this->_RedirectUri === NULL) {
         $RedirectUri = Url('/entry/connect/twitter', TRUE);
         $this->_RedirectUri = $RedirectUri;
      }

      return $this->_RedirectUri;
   }
   
   /**
    * Add 'Twitter' option to the row.
    */
   public function Base_AfterReactions_Handler($Sender, $Args) {
      if (!$this->SocialReactions()) 
         return;
      
      echo Gdn_Theme::BulletItem('Share');
      $this->AddReactButton($Sender, $Args);
   }

   /**
    * Output Quote link.
    */
   protected function AddReactButton($Sender, $Args) {
      if ($this->AccessToken()) {
         $Url = Url("post/twitter/{$Args['RecordType']}?id={$Args['RecordID']}", TRUE);
         $CssClass = 'ReactButton Hijack';
      } else {
         $Url = Url("post/twitter/{$Args['RecordType']}?id={$Args['RecordID']}", TRUE);
         $CssClass = 'ReactButton PopupWindow';
      }
      
      echo Anchor(Sprite('ReactTwitter', 'ReactSprite'), $Url, $CssClass);
   }

   public function SocialController_Twitter_Create($Sender, $Args) {
   	  $Sender->Permission('Garden.Settings.Manage');
      if ($Sender->Form->IsPostBack()) {
         $Settings = array(
             'Plugins.Twitter.ConsumerKey' => $Sender->Form->GetFormValue('ConsumerKey'),
             'Plugins.Twitter.Secret' => $Sender->Form->GetFormValue('Secret'),
             'Plugins.Twitter.SocialReactions' => $Sender->Form->GetFormValue('SocialReactions'),
             'Plugins.Twitter.SocialSharing' => $Sender->Form->GetFormValue('SocialSharing')
         );

         SaveToConfig($Settings);
         $Sender->InformMessage(T("Your settings have been saved."));

      } else {
         $Sender->Form->SetValue('ConsumerKey', C('Plugins.Twitter.ConsumerKey'));
         $Sender->Form->SetValue('Secret', C('Plugins.Twitter.Secret'));
         $Sender->Form->SetValue('SocialReactions', $this->SocialReactions());
         $Sender->Form->SetValue('SocialSharing', $this->SocialSharing());
      }

      $Sender->AddSideMenu('dashboard/social');
      $Sender->SetData('Title', T('Twitter Settings'));
      $Sender->Render('Settings', '', 'plugins/Twitter');
   }

   public function Setup() {
      // Make sure the user has curl.
      if (!function_exists('curl_exec')) {
         throw new Gdn_UserException('This plugin requires curl.');
      }

      // Save the twitter provider type.
      Gdn::SQL()->Replace('UserAuthenticationProvider',
         array('AuthenticationSchemeAlias' => 'twitter', 'URL' => '...', 'AssociationSecret' => '...', 'AssociationHashMethod' => '...'),
         array('AuthenticationKey' => self::ProviderKey));
   }
}

function SliceTwitter($Str) {
   $Elips = '...';
         
   $Str = preg_replace('`\s+`', ' ', $Str);

//         if (function_exists('normalizer_is_normalized')) {
//            // Slice the string to 119 characters (21 reservered for the url.
//            if (!normalizer_is_normalized($Message))
//               $Message = Normalizer::normalize($Message, Normalizer::FORM_D);
//            $Elips = Normalizer::normalize($Elips, Normalizer::FORM_D);
//         }

   $Max = 140;
   $LinkLen = 22;

   $Max -= $LinkLen;

   $Str = SliceParagraph($Str, $Max);
   if (strlen($Str) > $Max) {
      $Str = substr($Str, 0, $Max - strlen($Elips)).$Elips;
   }
   
   return $Str;
}
