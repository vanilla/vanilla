<?php
/**
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Facebook
 */

// Define the plugin:
$PluginInfo['Facebook'] = array(
    'Name' => 'Facebook Social Connect',
    'Description' => 'Users may sign into your site using their Facebook account.',
    'Version' => '1.2.0',
    'RequiredApplications' => array('Vanilla' => '2.2'),
    'RequiredTheme' => false,
    'RequiredPlugins' => false,
    'MobileFriendly' => true,
    'SettingsUrl' => '/dashboard/social/facebook',
    'SettingsPermission' => 'Garden.Settings.Manage',
    'HasLocale' => true,
    'RegisterPermissions' => false,
    'Author' => "Todd Burry",
    'AuthorEmail' => 'todd@vanillaforums.com',
    'AuthorUrl' => 'http://www.vanillaforums.org/profile/todd',
    'Hidden' => true,
    'SocialConnect' => true,
    'RequiresRegistration' => true
);

/**
 * Class FacebookPlugin
 */
class FacebookPlugin extends Gdn_Plugin {
    const ProviderKey = 'Facebook';

    protected $_AccessToken = null;

    public function AccessToken() {
        if (!$this->IsConfigured()) {
            return false;
        }

        if ($this->_AccessToken === null) {
            if (Gdn::session()->isValid()) {
                $this->_AccessToken = valr(self::ProviderKey.'.AccessToken', Gdn::session()->User->Attributes);
            } else {
                $this->_AccessToken = false;
            }
        }

        return $this->_AccessToken;
    }

    public function Authorize($Query = false) {
        $Uri = $this->AuthorizeUri($Query);
        redirect($Uri);
    }

    public function API($Path, $Post = false) {
        // Build the url.
        $Url = 'https://graph.facebook.com/'.ltrim($Path, '/');
        $AccessToken = $this->AccessToken();
        if (!$AccessToken) {
            throw new Gdn_UserException("You don't have a valid Facebook connection.");
        }

        if (strpos($Url, '?') === false) {
            $Url .= '?';
        } else {
            $Url .= '&';
        }

        $Url .= 'access_token='.urlencode($AccessToken);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_URL, $Url);

        if ($Post !== false) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $Post);
            trace("  POST $Url");
        } else {
            trace("  GET  $Url");
        }

        $Response = curl_exec($ch);

        $HttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $ContentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        Gdn::controller()->setJson('Type', $ContentType);

        if (strpos($ContentType, 'javascript') !== false) {
            $Result = json_decode($Response, true);

            if (isset($Result['error'])) {
                Gdn::dispatcher()->PassData('FacebookResponse', $Result);
                throw new Gdn_UserException($Result['error']['message']);
            }
        } else {
            $Result = $Response;
        }

        return $Result;
    }

    /**
     *
     * @param Gdn_Controller $Sender
     */
    public function EntryController_SignIn_Handler($Sender, $Args) {
        if (!$this->SocialSignIn()) {
            return;
        }

        if (isset($Sender->Data['Methods'])) {
            $Url = $this->AuthorizeUri();

            // Add the facebook method to the controller.
            $FbMethod = array(
                'Name' => self::ProviderKey,
                'SignInHtml' => SocialSigninButton('Facebook', $Url, 'button')
            );

            $Sender->Data['Methods'][] = $FbMethod;
        }
    }

    /**
     * Add 'Facebook' option to the row.
     */
    public function Base_AfterReactions_Handler($Sender, $Args) {
        if (!$this->SocialReactions()) {
            return;
        }

        echo Gdn_Theme::BulletItem('Share');
        $this->AddReactButton($Sender, $Args);
    }

    public function Base_DiscussionFormOptions_Handler($Sender, $Args) {
        if (!$this->SocialSharing()) {
            return;
        }

        if (!$this->AccessToken()) {
            return;
        }

        $Options =& $Args['Options'];

        $Options .= ' <li>'.
            $Sender->Form->CheckBox('ShareFacebook', '@'.Sprite('ReactFacebook', 'ReactSprite'), array('value' => '1', 'title' => sprintf(T('Share to %s.'), 'Facebook'))).
            '</li> ';
    }

    public function DiscussionController_AfterBodyField_Handler($Sender, $Args) {
        if (!$this->SocialSharing()) {
            return;
        }

        if (!$this->AccessToken()) {
            return;
        }

        echo ' '.
            $Sender->Form->CheckBox('ShareFacebook', '@'.Sprite('ReactFacebook', 'ReactSprite'), array('value' => '1', 'title' => sprintf(T('Share to %s.'), 'Facebook'))).
            ' ';
    }

    public function DiscussionModel_AfterSaveDiscussion_Handler($Sender, $Args) {
        if (!$this->SocialSharing()) {
            return;
        }

        if (!$this->AccessToken()) {
            return;
        }

        $ShareFacebook = valr('FormPostValues.ShareFacebook', $Args);

        if ($ShareFacebook) {
            $Url = DiscussionUrl($Args['Fields'], '', true);

            if ($this->AccessToken()) {
                $R = $this->API('/me/feed', array('link' => $Url));
            }
        }
    }

    public function CommentModel_AfterSaveComment_Handler($Sender, $Args) {
        if (!$this->SocialSharing()) {
            return;
        }

        if (!$this->AccessToken()) {
            return;
        }

        $ShareFacebook = valr('FormPostValues.ShareFacebook', $Args);

        if ($ShareFacebook) {
            $Row = $Args['FormPostValues'];

            $DiscussionModel = new DiscussionModel();
            $Discussion = $DiscussionModel->getID(val('DiscussionID', $Row));
            if (!$Discussion) {
                die('no discussion');
            }

            $Url = DiscussionUrl($Discussion, '', true);
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

        echo ' '.anchor(sprite('ReactFacebook', 'Sprite ReactSprite', T('Share on Facebook')), url("post/facebook/{$Args['RecordType']}?id={$Args['RecordID']}", true), $CssClass).' ';
    }

    public function Base_SignInIcons_Handler($Sender, $Args) {
        if (!$this->SocialSignIn()) {
            return;
        }

        echo "\n".$this->_GetButton();
    }

    public function Base_BeforeSignInButton_Handler($Sender, $Args) {
        if (!$this->SocialSignIn()) {
            return;
        }

        echo "\n".$this->_GetButton();
    }

    public function Base_BeforeSignInLink_Handler($Sender) {
        if (!$this->SocialSignIn()) {
            return;
        }

        if (!Gdn::session()->isValid()) {
            echo "\n".Wrap($this->_GetButton(), 'li', array('class' => 'Connect FacebookConnect'));
        }
    }

    public function Base_GetConnections_Handler($Sender, $Args) {


        $Profile = valr('User.Attributes.'.self::ProviderKey.'.Profile', $Args);

        $Sender->Data["Connections"][self::ProviderKey] = array(
            'Icon' => $this->GetWebResource('icon.png', '/'),
            'Name' => 'Facebook',
            'ProviderKey' => self::ProviderKey,
            'ConnectUrl' => $this->AuthorizeUri(false, self::ProfileConnecUrl()),
            'Profile' => array(
                'Name' => val('name', $Profile),
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
        if (!$this->SocialReactions()) {
            throw permissionException();
        }

        $Row = GetRecord($RecordType, $ID, true);
        if ($Row) {
            $Message = SliceParagraph(Gdn_Format::PlainText($Row['Body'], $Row['Format']), 160);

            if ($this->AccessToken() && $Sender->Request->isPostBack()) {
                $R = $this->API('/me/feed', array('link' => $Row['ShareUrl'], 'message' => $Message));

                $Sender->setJson('R', $R);
                $Sender->informMessage(T('Thanks for sharing!'));
            } else {
                $Get = array(
                    'app_id' => c('Plugins.Facebook.ApplicationID'),
                    'link' => $Row['ShareUrl'],
                    'name' => Gdn_Format::PlainText($Row['Name'], 'Text'),
                    'description' => $Message,
                    'redirect_uri' => url('/post/shared/facebook', true)
                );

                $Url = 'http://www.facebook.com/dialog/feed?'.http_build_query($Get);
                redirect($Url);
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
    public function ProfileController_FacebookConnect_Create($Sender, $UserReference, $Username, $Code = false) {
        $Sender->Permission('Garden.SignIn.Allow');

        $Sender->getUserInfo($UserReference, $Username, '', true);
        $Sender->_setBreadcrumbs(T('Connections'), '/profile/connections');

        // Get the access token.
        $AccessToken = $this->GetAccessToken($Code, self::ProfileConnecUrl());

        // Get the profile.
        $Profile = $this->GetProfile($AccessToken);

        // Save the authentication.
        Gdn::userModel()->SaveAuthentication(array(
            'UserID' => $Sender->User->UserID,
            'Provider' => self::ProviderKey,
            'UniqueID' => $Profile['id']));

        // Save the information as attributes.
        $Attributes = array(
            'AccessToken' => $AccessToken,
            'Profile' => $Profile
        );
        Gdn::userModel()->saveAttribute($Sender->User->UserID, self::ProviderKey, $Attributes);

        $this->EventArguments['Provider'] = self::ProviderKey;
        $this->EventArguments['User'] = $Sender->User;
        $this->fireEvent('AfterConnection');

        redirect(userUrl($Sender->User, '', 'connections'));
    }

    private function _GetButton() {
        $Url = $this->AuthorizeUri();

        return SocialSigninButton('Facebook', $Url, 'icon', array('rel' => 'nofollow'));
    }

    public function SocialController_Facebook_Create($Sender, $Args) {
        $Sender->Permission('Garden.Settings.Manage');
        if ($Sender->Form->authenticatedPostBack()) {
            $Settings = array(
                'Plugins.Facebook.ApplicationID' => $Sender->Form->getFormValue('ApplicationID'),
                'Plugins.Facebook.Secret' => $Sender->Form->getFormValue('Secret'),
                'Plugins.Facebook.UseFacebookNames' => $Sender->Form->getFormValue('UseFacebookNames'),
                'Plugins.Facebook.SocialSignIn' => $Sender->Form->getFormValue('SocialSignIn'),
                'Plugins.Facebook.SocialReactions' => $Sender->Form->getFormValue('SocialReactions'),
                'Plugins.Facebook.SocialSharing' => $Sender->Form->getFormValue('SocialSharing'),
                'Garden.Registration.SendConnectEmail' => $Sender->Form->getFormValue('SendConnectEmail'));

            saveToConfig($Settings);
            $Sender->informMessage(T("Your settings have been saved."));

        } else {
            $Sender->Form->setValue('ApplicationID', c('Plugins.Facebook.ApplicationID'));
            $Sender->Form->setValue('Secret', c('Plugins.Facebook.Secret'));
            $Sender->Form->setValue('UseFacebookNames', c('Plugins.Facebook.UseFacebookNames'));
            $Sender->Form->setValue('SendConnectEmail', c('Garden.Registration.SendConnectEmail', false));
            $Sender->Form->setValue('SocialSignIn', c('Plugins.Facebook.SocialSignIn', true));
            $Sender->Form->setValue('SocialReactions', $this->SocialReactions());
            $Sender->Form->setValue('SocialSharing', $this->SocialSharing());
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
        if (val(0, $Args) != 'facebook') {
            return;
        }

        if (isset($_GET['error'])) {
            throw new Gdn_UserException(val('error_description', $_GET, T('There was an error connecting to Facebook')));
        }

        $AppID = c('Plugins.Facebook.ApplicationID');
        $Secret = c('Plugins.Facebook.Secret');
        $Code = val('code', $_GET);
        $Query = '';
        if ($Sender->Request->get('display')) {
            $Query = 'display='.urlencode($Sender->Request->get('display'));
        }

        $RedirectUri = ConcatSep('&', $this->RedirectUri(), $Query);

        $AccessToken = $Sender->Form->getFormValue('AccessToken');

        // Get the access token.
        if (!$AccessToken && $Code) {
            // Exchange the token for an access token.
            $Code = urlencode($Code);

            $AccessToken = $this->GetAccessToken($Code, $RedirectUri);

            $NewToken = true;
        }

        // Get the profile.
        try {
            $Profile = $this->GetProfile($AccessToken);
        } catch (Exception $Ex) {
            if (!isset($NewToken)) {
                // There was an error getting the profile, which probably means the saved access token is no longer valid. Try and reauthorize.
                if ($Sender->deliveryType() == DELIVERY_TYPE_ALL) {
                    redirect($this->AuthorizeUri());
                } else {
                    $Sender->setHeader('Content-type', 'application/json');
                    $Sender->deliveryMethod(DELIVERY_METHOD_JSON);
                    $Sender->RedirectUrl = $this->AuthorizeUri();
                }
            } else {
                $Sender->Form->addError('There was an error with the Facebook connection.');
            }
        }

        $Form = $Sender->Form; //new Gdn_Form();
        $ID = val('id', $Profile);
        $Form->setFormValue('UniqueID', $ID);
        $Form->setFormValue('Provider', self::ProviderKey);
        $Form->setFormValue('ProviderName', 'Facebook');
        $Form->setFormValue('FullName', val('name', $Profile));
        $Form->setFormValue('Email', val('email', $Profile));
        $Form->setFormValue('Photo', "//graph.facebook.com/{$ID}/picture?type=large");
        $Form->addHidden('AccessToken', $AccessToken);

        if (C('Plugins.Facebook.UseFacebookNames')) {
            $Form->setFormValue('Name', val('name', $Profile));
            saveToConfig(array(
                'Garden.User.ValidationRegex' => UserModel::USERNAME_REGEX_MIN,
                'Garden.User.ValidationLength' => '{3,50}',
                'Garden.Registration.NameUnique' => false
            ), '', false);
        }

        // Save some original data in the attributes of the connection for later API calls.
        $Attributes = array();
        $Attributes[self::ProviderKey] = array(
            'AccessToken' => $AccessToken,
            'Profile' => $Profile
        );
        $Form->setFormValue('Attributes', $Attributes);

        $Sender->SetData('Verified', true);
    }

    protected function GetAccessToken($Code, $RedirectUri, $ThrowError = true) {
        $Get = array(
            'client_id' => c('Plugins.Facebook.ApplicationID'),
            'client_secret' => c('Plugins.Facebook.Secret'),
            'code' => $Code,
            'redirect_uri' => $RedirectUri);

        $Url = 'https://graph.facebook.com/oauth/access_token?'.http_build_query($Get);

        // Get the redirect URI.
        $C = curl_init();
        curl_setopt($C, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($C, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($C, CURLOPT_URL, $Url);
        $Contents = curl_exec($C);

        $Info = curl_getinfo($C);
        if (strpos(val('content_type', $Info, ''), '/javascript') !== false) {
            $Tokens = json_decode($Contents, true);
        } else {
            parse_str($Contents, $Tokens);
        }

        if (val('error', $Tokens)) {
            throw new Gdn_UserException('Facebook returned the following error: '.GetValueR('error.message', $Tokens, 'Unknown error.'), 400);
        }

        $AccessToken = val('access_token', $Tokens);
//      $Expires = val('expires', $Tokens, null);
        return $AccessToken;
    }

    public function GetProfile($AccessToken) {
        $Url = "https://graph.facebook.com/me?access_token=$AccessToken";
//      $C = curl_init();
//      curl_setopt($C, CURLOPT_RETURNTRANSFER, true);
//      curl_setopt($C, CURLOPT_SSL_VERIFYPEER, false);
//      curl_setopt($C, CURLOPT_URL, $Url);
//      $Contents = curl_exec($C);
//      $Contents = ProxyRequest($Url);
        $Contents = file_get_contents($Url);
        $Profile = json_decode($Contents, true);
        return $Profile;
    }

    public function AuthorizeUri($Query = false, $RedirectUri = false) {
        $AppID = c('Plugins.Facebook.ApplicationID');
        $FBScope = c('Plugins.Facebook.Scope', 'email,publish_actions');

        if (is_array($FBScope)) {
            $Scopes = implode(',', $FBScope);
        } else {
            $Scopes = $FBScope;
        }

        if (!$RedirectUri) {
            $RedirectUri = $this->RedirectUri();
        }

        if ($Query) {
            $RedirectUri .= '&'.$Query;
        }

        $RedirectUri = urlencode($RedirectUri);

        $SigninHref = "https://graph.facebook.com/oauth/authorize?client_id=$AppID&redirect_uri=$RedirectUri&scope=$Scopes";

        if ($Query) {
            $SigninHref .= '&'.$Query;
        }

        return $SigninHref;
    }

    protected $_RedirectUri = null;

    public function RedirectUri($NewValue = null) {
        if ($NewValue !== null) {
            $this->_RedirectUri = $NewValue;
        } elseif ($this->_RedirectUri === null) {
            $RedirectUri = url('/entry/connect/facebook', true);
            if (strpos($RedirectUri, '=') !== false) {
                $p = strrchr($RedirectUri, '=');
                $Uri = substr($RedirectUri, 0, -strlen($p));
                $p = urlencode(ltrim($p, '='));
                $RedirectUri = $Uri.'='.$p;
            }

            $Path = Gdn::request()->Path();

            $Target = val('Target', $_GET, $Path ? $Path : '/');

            if (ltrim($Target, '/') == 'entry/signin' || empty($Target)) {
                $Target = '/';
            }

            $Args = array('Target' => $Target);

            $RedirectUri .= strpos($RedirectUri, '?') === false ? '?' : '&';
            $RedirectUri .= http_build_query($Args);
            $this->_RedirectUri = $RedirectUri;
        }

        return $this->_RedirectUri;
    }

    public static function ProfileConnecUrl() {
        return url(userUrl(Gdn::session()->User, false, 'facebookconnect'), true);
    }

    public function IsConfigured() {
        $AppID = c('Plugins.Facebook.ApplicationID');
        $Secret = c('Plugins.Facebook.Secret');

        if (!$AppID || !$Secret) {
            return false;
        }

        return true;
    }

    public function SocialSignIn() {
        return c('Plugins.Facebook.SocialSignIn', true) && $this->IsConfigured();
    }

    public function SocialSharing() {
        return c('Plugins.Facebook.SocialSharing', true) && $this->IsConfigured();
    }

    public function SocialReactions() {
        return c('Plugins.Facebook.SocialReactions', true) && $this->IsConfigured();
    }

    public function Setup() {
        $Error = '';
        if (!function_exists('curl_init')) {
            $Error = ConcatSep("\n", $Error, 'This plugin requires curl.');
        }

        if ($Error) {
            throw new Gdn_UserException($Error, 400);
        }

        $this->Structure();
    }

    public function Structure() {
        // Save the facebook provider type.
        Gdn::sql()->replace(
            'UserAuthenticationProvider',
            array('AuthenticationSchemeAlias' => 'facebook', 'URL' => '...', 'AssociationSecret' => '...', 'AssociationHashMethod' => '...'),
            array('AuthenticationKey' => self::ProviderKey),
            true
        );
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
//         Gdn::session()->User->Name
//      )));
//
//      $Secret = 's'.sha1(implode('.',array(
//         'vanillaconnect',
//         'secret',
//         md5(microtime(true)),
//         RandomString(16),
//         Gdn::session()->User->Name
//      )));
//
//      $ProviderModel = new Gdn_AuthenticationProviderModel();
//      $ProviderModel->insert($Provider = array(
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
//		saveToConfig('Garden.SignIn.Popup', false);
//		saveToConfig('Garden.Authenticators.handshake.Name', 'VanillaConnect');
//      saveToConfig('Garden.Authenticators.handshake.CookieName', 'VanillaHandshake');
//      saveToConfig('Garden.Authenticators.handshake.TokenLifetime', 0);
//
//      if ($FullEnable) {
//         saveToConfig('Garden.Authenticator.DefaultScheme', 'handshake');
//         saveToConfig('Plugins.VanillaConnect.Enabled', true);
//      }
//
//      // Create a provider key/secret pair if needed
//      $SQL = Gdn::database()->sql();
//      $Provider = $SQL->select('uap.*')
//         ->from('UserAuthenticationProvider uap')
//         ->where('uap.AuthenticationSchemeAlias', 'handshake')
//         ->get()
//         ->firstRow(DATASET_TYPE_ARRAY);
//
//      if (!$Provider)
//         $this->_CreateProviderModel();
//	}
}
