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
	'Name' => 'Twitter',
   'Description' => 'This plugin integrates Twitter with Vanilla. <b>You must register your application with Twitter for this plugin to work.</b>',
   'Version' => '0.1a',
   'RequiredApplications' => array('Vanilla' => '2.0.12a'),
   'RequiredTheme' => FALSE,
   'RequiredPlugins' => FALSE,
	'MobileFriendly' => TRUE,
   'SettingsUrl' => '/dashboard/settings/twitter',
   'SettingsPermission' => 'Garden.Settings.Manage',
   'HasLocale' => TRUE,
   'RegisterPermissions' => FALSE,
   'Author' => "Todd Burry",
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.org/profile/todd'
);


require_once PATH_LIBRARY.'/vendors/oauth/OAuth.php';

class TwitterPlugin extends Gdn_Plugin {
   public static $ProviderKey = 'Twitter';
   public static $BaseApiUrl = 'http://api.twitter.com/1/';

   protected $_AccessToken = NULL;

   /**
    * Gets/sets the current oauth access token.
    *
    * @param string $Token
    * @param string $Secret
    * @return OAuthToken
    */
   public function AccessToken($Token = NULL, $Secret = NULL) {
      if ($Token !== NULL && $Secret !== NULL) {
         $this->_AccessToken = new OAuthToken($Token, $Secret);
         setcookie('tw_access_token', $Token, 0, C('Garden.Cookie.Path', '/'), C('Garden.Cookie.Domain', ''));
      } elseif ($this->_AccessToken == NULL) {
         $Token = GetValue('tw_access_token', $_COOKIE, NULL);
         if ($Token)
            $this->_AccessToken = $this->GetOAuthToken($Token);
      }
      return $this->_AccessToken;
   }

   public function AuthenticationController_Render_Before($Sender, $Args) {
      if (isset($Sender->ChooserList)) {
         $Sender->ChooserList['twitter'] = 'Twitter';
      }
      if (is_array($Sender->Data('AuthenticationConfigureList'))) {
         $List = $Sender->Data('AuthenticationConfigureList');
         $List['twitter'] = '/dashboard/settings/twitter';
         $Sender->SetData('AuthenticationConfigureList', $List);
      }
   }

   protected function _AuthorizeHref($Popup = FALSE) {
      $Url = Url('/entry/twauthorize', TRUE);
      $UrlParts = explode('?', $Url);

      parse_str(GetValue(1, $UrlParts, ''), $Query);
      $Path = Gdn::Request()->Path();
      $Query['Target'] = GetValue('Target', $_GET, $Path ? $Path : '/');

//      if (isset($_GET['Target']))
//         $Query['Target'] = $_GET['Target'];
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

         $AccessToken = $this->AccessToken();

         $ImgSrc = Asset('/plugins/Twitter/design/twitter-signin.png');
         $ImgAlt = T('Sign In with Twitter');

         if (FALSE && $AccessToken) {
            $SigninHref = $this->RedirectUri();

            // We already have an access token so we can just link to the connect page.
            $TwMethod = array(
                'Name' => 'Twitter',
                'SignInHtml' => "<a id=\"TwitterAuth\" href=\"$SigninHref\" class=\"PopLink\" ><img src=\"$ImgSrc\" alt=\"$ImgAlt\" /></a>");
         } else {
            $SigninHref = $this->_AuthorizeHref();
            $PopupSigninHref = $this->_AuthorizeHref(TRUE);

            // Add the twitter method to the controller.
            $TwMethod = array(
               'Name' => 'Twitter',
               'SignInHtml' => "<a id=\"TwitterAuth\" href=\"$SigninHref\" class=\"PopupWindow\" popupHref=\"$PopupSigninHref\" popupHeight=\"400\" popupWidth=\"800\" ><img src=\"$ImgSrc\" alt=\"$ImgAlt\" /></a>");
         }

         $Sender->Data['Methods'][] = $TwMethod;
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
			echo "\n".Wrap($this->_GetButton(), 'li', array('class' => 'Connect TwitterConnect'));
	}
	
	private function _GetButton() {      
      $ImgSrc = Asset('/plugins/Twitter/design/twitter-icon.png');
      $ImgAlt = T('Sign In with Twitter');
      $SigninHref = $this->_AuthorizeHref();
      $PopupSigninHref = $this->_AuthorizeHref(TRUE);
		return "<a id=\"TwitterAuth\" href=\"$SigninHref\" class=\"PopupWindow\" title=\"$ImgAlt\" popupHref=\"$PopupSigninHref\" popupHeight=\"400\" popupWidth=\"800\" ><img src=\"$ImgSrc\" alt=\"$ImgAlt\" /></a>";
   }

	public function Authorize($Query = FALSE) {
      // Aquire the request token.
      $Consumer = new OAuthConsumer(C('Plugins.Twitter.ConsumerKey'), C('Plugins.Twitter.Secret'));

      $Params = array(
          'oauth_callback' => $this->RedirectUri()
      );
      if ($Query)
         $Params['oauth_callback'] .= '&'.$Query;
      
      $Url = 'https://api.twitter.com/oauth/request_token';
      $Request = OAuthRequest::from_consumer_and_token($Consumer, NULL, 'POST', $Url, $Params);
      $SignatureMethod = new OAuthSignatureMethod_HMAC_SHA1();
      $Request->sign_request($SignatureMethod, $Consumer, null);

      $Curl = $this->_Curl($Request);
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
            $this->SetOAuthToken($Data['oauth_token'], $Data['oauth_token_secret'], 'access');

            // Redirect to twitter's authorization page.
            $Url = "http://api.twitter.com/oauth/authorize?oauth_token={$Data['oauth_token']}";
            Redirect($Url);
         }
      }

      // There was an error. Echo the error.
      echo $Response;
   }

   public function EntryController_Twauthorize_Create($Sender, $Args) {
      $Query = ArrayTranslate($Sender->Request->Get(), array('display', 'Target'));
      $Query = http_build_query($Query);
      $this->Authorize($Query);
   }

   /**
    *
    * @param Gdn_Controller $Sender
    * @param array $Args
    */
   public function Base_ConnectData_Handler($Sender, $Args) {
      if (GetValue(0, $Args) != 'twitter')
         return;

      $RequestToken = GetValue('oauth_token', $_GET);

      // Get the access token.
      if ($RequestToken || !($AccessToken = $this->AccessToken())) {
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

            $AccessToken = $this->AccessToken(GetValue('oauth_token', $Data), GetValue('oauth_token_secret', $Data));
            // Save the access token to the database.
            $this->SetOAuthToken($AccessToken);

            // Delete the request token.
            $this->DeleteOAuthToken($RequestToken);
            
         } else {
            // There was some sort of error.
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
            $Sender->Form->AddError($Ex);
         }
      }

      $Form = $Sender->Form; //new Gdn_Form();
      $ID = GetValue('id', $Profile);
      $Form->SetFormValue('UniqueID', $ID);
      $Form->SetFormValue('Provider', self::$ProviderKey);
      $Form->SetFormValue('ProviderName', 'Twitter');
      $Form->SetFormValue('Name', GetValue('screen_name', $Profile));
      $Form->SetFormValue('FullName', GetValue('name', $Profile));
      $Form->SetFormValue('Email', GetValue('screen_name', $Profile).'@foo.com');
      $Form->SetFormValue('Photo', GetValue('profile_image_url', $Profile));
      $Sender->SetData('Verified', TRUE);
   }

   public function API($Url, $Params = NULL) {
      if (strpos($Url, '//') === FALSE)
         $Url = self::$BaseApiUrl.trim($Url, '/');
      $Consumer = new OAuthConsumer(C('Plugins.Twitter.ConsumerKey'), C('Plugins.Twitter.Secret'));

      $AccessToken = $this->AccessToken();
      $Request = OAuthRequest::from_consumer_and_token($Consumer, $AccessToken, 'GET', $Url, $Params);
      $SignatureMethod = new OAuthSignatureMethod_HMAC_SHA1();
      $Request->sign_request($SignatureMethod, $Consumer, $AccessToken);

      $Curl = $this->_Curl($Request);
      $Response = curl_exec($Curl);
      $HttpCode = curl_getinfo($Curl, CURLINFO_HTTP_CODE);
      curl_close($Curl);

      if (StringEndsWith($Url, 'json', TRUE)) {
         $Result = @json_decode($Response) or $Response;
      } else {
         $Result = $Response;
      }

      if ($HttpCode == '200')
         return $Result;
      else
         throw new OAuthException(GetValue('error', $Result, $Result), $HttpCode, $previous);
   }

   public function GetProfile() {
      $Profile = $this->API('/account/verify_credentials.json');
      return $Profile;
   }

   public function GetOAuthToken($Token) {
      $Row = Gdn::SQL()->GetWhere('UserAuthenticationToken', array('Token' => $Token, 'ProviderKey' => self::$ProviderKey))->FirstRow(DATASET_TYPE_ARRAY);
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

   public function SetOAuthToken($Token, $Secret = NULL, $Type = 'request') {
      if (is_a($Token, 'OAuthToken')) {
         $Secret = $Token->secret;
         $Token = $Token->key;
      }

      // Insert the token.
      $Data = array(
                'Token' => $Token,
                'ProviderKey' => self::$ProviderKey,
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
      
      Gdn::SQL()->Delete('UserAuthenticationToken', array('Token' => $Token, 'ProviderKey' => self::$ProviderKey));
   }

   /**
    *
    * @param OAuthRequest $Request 
    */
   protected function _Curl($Request) {
      $C = curl_init();
      curl_setopt($C, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($C, CURLOPT_SSL_VERIFYPEER, FALSE);
      switch ($Request->get_normalized_http_method()) {
         case 'POST':
            curl_setopt($C, CURLOPT_URL, $Request->get_normalized_http_url());
            curl_setopt($C, CURLOPT_POST, TRUE);
            curl_setopt($C, CURLOPT_POSTFIELDS, $Request->to_postdata());
            break;
         default:
            curl_setopt($C, CURLOPT_URL, $Request->to_url());
      }
      return $C;
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

   public function SettingsController_Twitter_Create($Sender, $Args) {
      if ($Sender->Form->IsPostBack()) {
         $Settings = array(
             'Plugins.Twitter.ConsumerKey' => $Sender->Form->GetFormValue('ConsumerKey'),
             'Plugins.Twitter.Secret' => $Sender->Form->GetFormValue('Secret'));

         SaveToConfig($Settings);
         $Sender->StatusMessage = T("Your settings have been saved.");

      } else {
         $Sender->Form->SetFormValue('ConsumerKey', C('Plugins.Twitter.ConsumerKey'));
         $Sender->Form->SetFormValue('Secret', C('Plugins.Twitter.Secret'));
      }

      $Sender->AddSideMenu();
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
         array('AuthenticationKey' => self::$ProviderKey));
   }
}