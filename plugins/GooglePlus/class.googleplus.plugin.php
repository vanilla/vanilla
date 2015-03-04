<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

// Define the plugin:
$PluginInfo['GooglePlus'] = array(
   'Name' => 'Google+ Social Connect',
   'Description' => 'Users may sign into your site using their Google Plus account.',
   'Version' => '1.1.0',
   'RequiredApplications' => array('Vanilla' => '2.1'),
   'MobileFriendly' => TRUE,
   'Author' => 'Todd Burry',
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.org/profile/todd',
   'SettingsUrl' => '/dashboard/social/googleplus',
   'SettingsPermission' => 'Garden.Settings.Manage',
   'Hidden' => FALSE,
   'SocialConnect' => TRUE,
   'RequiresRegistration' => FALSE
);

class GooglePlusPlugin extends Gdn_Plugin {
   /// Properties ///
   const ProviderKey = 'GooglePlus';
   const APIUrl = 'https://www.googleapis.com/oauth2/v1';

   /// Methods ///

   protected $_AccessToken = NULL;

   public function AccessToken($NewValue = FALSE) {
      if (!$this->IsConfigured())
         return FALSE;

      if ($NewValue !== FALSE)
         $this->_AccessToken = $NewValue;

      if ($this->_AccessToken === NULL) {
         $this->_AccessToken = GetValueR(self::ProviderKey.'.AccessToken', Gdn::Session()->User->Attributes);
      }

      return $this->_AccessToken;
   }

   public function API($Path, $Post = array()) {
      $Url = self::APIUrl.'/'.ltrim($Path, '/');
      if (strpos($Url, '?') === FALSE)
         $Url .= '?';
      else
         $Url .= '&';
      $Url .= 'access_token='.urlencode($this->AccessToken());

      $Result = $this->Curl($Url, empty($Post) ? 'GET' : 'POST', $Post);
      return $Result;
   }

   public function AuthorizeUri($State = array()) {
      $Url = 'https://accounts.google.com/o/oauth2/auth';
      $Get = array(
          'response_type' => 'code',
          'client_id' => C('Plugins.GooglePlus.ClientID'),
          'redirect_uri' => Url('/entry/googleplus', TRUE),
          'scope' => 'https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email'
          );

      if (is_array($State)) {
         $Get['state'] = http_build_query($State);
      }

      return $Url.'?'.http_build_query($Get);
   }

   public function GetAccessToken($Code) {
      $Url = 'https://accounts.google.com/o/oauth2/token';
      $Post = array(
          'code' => $Code,
          'client_id' => C('Plugins.GooglePlus.ClientID'),
          'client_secret' => C('Plugins.GooglePlus.Secret'),
          'redirect_uri' => Url('/entry/googleplus', TRUE),
          'grant_type' => 'authorization_code'
          );

      $Data = self::Curl($Url, 'POST', $Post);
      $AccessToken = $Data['access_token'];
      return $AccessToken;
   }

   public function IsConfigured() {
      $Result = C('Plugins.GooglePlus.ClientID') && C('Plugins.GooglePlus.Secret');
      return $Result;
   }

   public function IsDefault() {
      return (bool)C('Plugins.GooglePlus.Default');
   }

   public function SocialSharing() {
      return C('Plugins.GooglePlus.SocialSharing', TRUE);
   }

   public function SocialReactions() {
      return C('Plugins.GooglePlus.SocialReactions', TRUE);
   }

   public static function Curl($Url, $Method = 'GET', $Data = array()) {
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_HEADER, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_URL, $Url);

      if ($Method == 'POST') {
         curl_setopt($ch, CURLOPT_POST, true);
         curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($Data));
         Trace("  POST $Url");
      } else {
         Trace("  GET  $Url");
      }

      $Response = curl_exec($ch);

      $HttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      $ContentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
      curl_close($ch);

      $Result = @json_decode($Response, TRUE);
      if (!$Result) {
         $Result = $Response;
      }

      if ($HttpCode != 200) {
         $Error = GetValue('error', $Result, $Response);

         throw new Gdn_UserException($Error, $HttpCode);
      }

      return $Result;
   }

   public function Setup() {
      $this->Structure();
   }

   public function SignInButton($type = 'button') {
      $Target = Gdn::Request()->Post('Target', Gdn::Request()->Get('Target', Url('', '/')));
      $Url = $this->AuthorizeUri(array('target' => $Target));

      $Result = SocialSignInButton('Google', $Url, $type);
      return $Result;
   }

   public function Structure() {
      Gdn::SQL()->Put('UserAuthenticationProvider', array('AuthenticationSchemeAlias' => self::ProviderKey), array('AuthenticationSchemeAlias' => 'Google+'));

      // Save the google+ provider type.
      Gdn::SQL()->Replace('UserAuthenticationProvider',
         array('AuthenticationSchemeAlias' => self::ProviderKey, 'URL' => '', 'AssociationSecret' => '', 'AssociationHashMethod' => '...'),
         array('AuthenticationKey' => self::ProviderKey), TRUE);
   }

   /// Event Handlers ///

   /**
    * Calculate the final sign in and register urls for google+.
    *
    * @param object $sender Not used.
    * @param array $args Contains the provider and
    */
   public function authenticationProviderModel_calculateGooglePlus_handler($sender, $args) {
      $provider =& $args['Provider'];
      $target = val('Target', null);

      if (!$target) {
         $target = Gdn::Request()->Post('Target', Gdn::Request()->Get('Target', Url('', '/')));
      }

      $provider['SignInUrlFinal'] = $this->AuthorizeUri(array('target' => $target));
//      $provider['RegisterUrlFinal'] = static::getRegisterUrl($provider, $target);
   }

   /**
    * Add 'Google+' option to the row.
    */
   public function Base_AfterReactions_Handler($Sender, $Args) {
      if (!$this->SocialReactions()) {
         return;
      }
      echo Gdn_Theme::BulletItem('Share');
//      if ($this->AccessToken()) {
//         $Url = Url("post/twitter/{$Args['RecordType']}?id={$Args['RecordID']}", TRUE);
//         $CssClass = 'ReactButton Hijack';
//      } else {
         $Url = Url("post/googleplus/{$Args['RecordType']}?id={$Args['RecordID']}", TRUE);
         $CssClass = 'ReactButton PopupWindow';
//      }

      echo ' '.Anchor(Sprite('ReactGooglePlus', 'ReactSprite'), $Url, $CssClass).' ';
   }

   /**
    *
    * @param EntryController $Sender
    * @param array $Args
    */
   public function Base_ConnectData_Handler($Sender, $Args) {
      if (GetValue(0, $Args) != 'googleplus')
         return;

      // Grab the google plus profile from the session staff.
      $GooglePlus = Gdn::Session()->Stash(self::ProviderKey, '', FALSE);
      $AccessToken = val('AccessToken', $GooglePlus);
      $Profile = val('Profile', $GooglePlus);

      $Form = $Sender->Form;
      $Form->SetFormValue('UniqueID', val('id', $Profile));
      $Form->SetFormValue('Provider', self::ProviderKey);
      $Form->SetFormValue('ProviderName', 'Google+');
      $Form->SetFormValue('FullName', val('name', $Profile));
      $Form->SetFormValue('Email', val('email', $Profile));
      if (C('Plugins.GooglePlus.UseAvatars', TRUE)) {
         $Form->SetFormValue('Photo', val('picture', $Profile));
      }

      if (C('Plugins.GooglePlus.UseFullNames')) {
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

      $this->EventArguments['Form'] = $Form;
      $this->FireEvent('AfterConnectData');
   }

   public function Base_SignInIcons_Handler($Sender, $Args) {
      if (!$this->IsDefault()) {
      echo ' '.$this->SignInButton('icon').' ';
   }
   }

   public function Base_BeforeSignInButton_Handler($Sender, $Args) {
      if (!$this->IsDefault()) {
      echo ' '.$this->SignInButton('icon').' ';
      }
   }

   public function Base_GetConnections_Handler($Sender, $Args) {
      $GPlus = GetValueR('User.Attributes.'.self::ProviderKey, $Args);
      $Profile = GetValueR('User.Attributes.'.self::ProviderKey.'.Profile', $Args);

      $Sender->Data['Connections'][self::ProviderKey] = array(
         'Icon' => $this->GetWebResource('icon.png'),
         'Name' => 'Google+',
         'ProviderKey' => self::ProviderKey,
         'ConnectUrl' => $this->AuthorizeUri(array('r' => 'profile', 'uid' => Gdn::Session()->UserID)),
         'Profile' => array(
            'Name' => GetValue('name', $Profile),
            'Photo' => GetValue('picture', $Profile)
            )
       );

      Trace(GetValue('AccessToken', $GPlus), 'google+ access token');
   }


   /**
    *
    * @param EntryController $Sender
    * @param string $Code
    * @param string $State
    * @throws Gdn_UserException
    */
   public function EntryController_GooglePlus_Create($Sender, $Code = FALSE, $State = FALSE) {
      if ($Error = $Sender->Request->Get('error')) {
         throw new Gdn_UserException($Error);
      }

      // Get an access token.
      Gdn::Session()->Stash(self::ProviderKey); // remove any old google plus.
      $AccessToken = $this->GetAccessToken($Code);
      $this->AccessToken($AccessToken);

      // Get the user's information.
      $Profile = $this->API('/userinfo');

      if ($State) {
         parse_str($State, $State);
      } else {
         $State = array('r' => 'entry', 'uid' => NULL);
      }

      switch ($State['r']) {
         case 'profile':
            // This is a connect request from the user's profile.

            $User = Gdn::UserModel()->GetID($State['uid']);
            if (!$User) {
               throw NotFoundException('User');
            }
            // Save the authentication.
            Gdn::UserModel()->SaveAuthentication(array(
               'UserID' => $User->UserID,
               'Provider' => self::ProviderKey,
               'UniqueID' => $Profile['id']));

            // Save the information as attributes.
            $Attributes = array(
                'AccessToken' => $AccessToken,
                'Profile' => $Profile
            );
            Gdn::UserModel()->SaveAttribute($User->UserID, self::ProviderKey, $Attributes);

            $this->EventArguments['Provider'] = self::ProviderKey;
            $this->EventArguments['User'] = $Sender->User;
            $this->FireEvent('AfterConnection');

            Redirect(UserUrl($User, '', 'connections'));
            break;
         case 'entry':
         default:
            // This is an sso request, we need to redispatch to /entry/connect/googleplus
            Gdn::Session()->Stash(self::ProviderKey, array('AccessToken' => $AccessToken, 'Profile' => $Profile));
            $url = '/entry/connect/googleplus';

            if ($target = val('target', $State)) {
               $url .= '?Target='.urlencode($target);
            }
            Redirect($url);
            break;
      }
   }

    /**
     *
     * @param Gdn_Controller $Sender
     */
    public function EntryController_SignIn_Handler($Sender, $Args) {
//      if (!$this->IsEnabled()) return;

        if (isset($Sender->Data['Methods'])) {
            $Url = $this->AuthorizeUri();

            // Add the twitter method to the controller.
            $Method = array(
                'Name' => 'Google',
                'SignInHtml' => $this->SignInButton() //SocialSigninButton('Google', $Url, 'button', array('class' => 'js-extern'))
            );

            $Sender->Data['Methods'][] = $Method;
    }
      }

   /**
    * Override the sign in if Google+ is the default sign-in method.
    * @param EntryController $Sender
    * @param array $Args
    */
   public function EntryController_OverrideSignIn_Handler($Sender, $Args) {
      if (valr('DefaultProvider.AuthenticationKey', $Args) !== self::ProviderKey || !$this->IsConfigured()) {
         return;
      }

      $Url = $this->AuthorizeUri(array('target' => $Args['Target']));
      $Args['DefaultProvider']['SignInUrl'] = $Url;

//      Redirect($Url);
   }

   /**
    *
    * @param PostController $Sender
    * @param type $RecordType
    * @param type $ID
    * @throws type
    */
   public function PostController_GooglePlus_Create($Sender, $RecordType, $ID) {
      $Row = GetRecord($RecordType, $ID);
      if ($Row) {
         $Message = SliceParagraph(Gdn_Format::PlainText($Row['Body'], $Row['Format']), 160);

         $Get = array(
            'url' => $Row['ShareUrl']
          );

         $Url = 'https://plus.google.com/share?'.http_build_query($Get);
         Redirect($Url);
      }

      $Sender->Render('Blank', 'Utility', 'Dashboard');
   }

   public function SocialController_GooglePlus_Create($Sender, $Args) {
      $Sender->Permission('Garden.Settings.Manage');

      $Conf = new ConfigurationModule($Sender);
      $Conf->Initialize(array(
          'Plugins.GooglePlus.ClientID' => array('LabelCode' => 'Client ID', 'Options' => array('class' => 'InputBox BigInput')),
          'Plugins.GooglePlus.Secret' => array('LabelCode' => 'Client secret', 'Options' => array('class' => 'InputBox BigInput')),
          'Plugins.GooglePlus.SocialReactions' => array('Control' => 'checkbox', 'Default' => TRUE),
          'Plugins.GooglePlus.SocialSharing' => array('Control' => 'checkbox', 'Default' => TRUE),
          'Plugins.GooglePlus.UseAvatars' => array('Control' => 'checkbox', 'Default' => TRUE),
          'Plugins.GooglePlus.Default' => array('Control' => 'checkbox', 'LabelCode' => 'Make this connection your default signin method.')
      ));

      if (Gdn::Request()->IsAuthenticatedPostBack()) {
         $Model = new Gdn_AuthenticationProviderModel();
         $Model->Save(array('AuthenticationKey' => self::ProviderKey, 'IsDefault' => C('Plugins.GooglePlus.Default')));
      }

      $Sender->AddSideMenu('dashboard/social');
      $Sender->SetData('Title', sprintf(T('%s Settings'), 'Google+'));
      $Sender->ConfigurationModule = $Conf;
      $Sender->Render('Settings', '', 'plugins/GooglePlus');
   }
}
