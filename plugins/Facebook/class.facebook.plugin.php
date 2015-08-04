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

    /** Authentication table key. */
    const ProviderKey = 'Facebook';

    /** @var string  */
    protected $_AccessToken = null;

    /** @var null  */
    protected $_RedirectUri = null;

    /**
     *
     *
     * @return bool|mixed|null
     */
    public function accessToken() {
        if (!$this->isConfigured()) {
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

    /**
     *
     *
     * @param bool $Query
     */
    public function authorize($Query = false) {
        $Uri = $this->authorizeUri($Query);
        redirect($Uri);
    }

    /**
     *
     *
     * @param $Path
     * @param bool $Post
     * @return mixed
     * @throws Gdn_UserException
     */
    public function api($Path, $Post = false) {
        // Build the url.
        $Url = 'https://graph.facebook.com/'.ltrim($Path, '/');
        $AccessToken = $this->accessToken();
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
                Gdn::dispatcher()->passData('FacebookResponse', $Result);
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
    public function entryController_signIn_handler($Sender, $Args) {
        if (!$this->socialSignIn()) {
            return;
        }

        if (isset($Sender->Data['Methods'])) {
            $Url = $this->authorizeUri();

            // Add the facebook method to the controller.
            $FbMethod = array(
                'Name' => self::ProviderKey,
                'SignInHtml' => socialSigninButton('Facebook', $Url, 'button')
            );

            $Sender->Data['Methods'][] = $FbMethod;
        }
    }

    /**
     * Add 'Facebook' option to the row.
     */
    public function base_afterReactions_handler($Sender, $Args) {
        if (!$this->socialReactions()) {
            return;
        }

        echo Gdn_Theme::bulletItem('Share');
        $this->addReactButton($Sender, $Args);
    }

    /**
     *
     *
     * @param $Sender
     * @param $Args
     */
    public function base_discussionFormOptions_handler($Sender, $Args) {
        if (!$this->socialSharing()) {
            return;
        }

        if (!$this->accessToken()) {
            return;
        }

        $Options =& $Args['Options'];

        $Options .= ' <li>'.
            $Sender->Form->checkBox('ShareFacebook', '@'.Sprite('ReactFacebook', 'ReactSprite'), array('value' => '1', 'title' => sprintf(t('Share to %s.'), 'Facebook'))).
            '</li> ';
    }

    /**
     *
     *
     * @param $Sender
     * @param $Args
     */
    public function discussionController_afterBodyField_handler($Sender, $Args) {
        if (!$this->socialSharing()) {
            return;
        }

        if (!$this->accessToken()) {
            return;
        }

        echo ' '.
            $Sender->Form->checkBox('ShareFacebook', '@'.Sprite('ReactFacebook', 'ReactSprite'), array('value' => '1', 'title' => sprintf(t('Share to %s.'), 'Facebook'))).
            ' ';
    }

    public function discussionModel_afterSaveDiscussion_handler($Sender, $Args) {
        if (!$this->socialSharing()) {
            return;
        }

        if (!$this->accessToken()) {
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

    /**
     *
     *
     * @param $Sender
     * @param $Args
     * @throws Gdn_UserException
     */
    public function commentModel_afterSaveComment_handler($Sender, $Args) {
        if (!$this->socialSharing()) {
            return;
        }

        if (!$this->accessToken()) {
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
            $Message = SliceParagraph(Gdn_Format::plainText($Row['Body'], $Row['Format']), 160);

            if ($this->accessToken()) {
                $R = $this->api('/me/feed', array(
                    'link' => $Url,
                    'message' => $Message
                ));
            }
        }
    }

    /**
     * Output Quote link.
     */
    protected function addReactButton($Sender, $Args) {
        if ($this->accessToken()) {
            $CssClass = 'ReactButton Hijack';
        } else {
            $CssClass = 'ReactButton PopupWindow';
        }

        echo ' '.anchor(sprite('ReactFacebook', 'Sprite ReactSprite', t('Share on Facebook')), url("post/facebook/{$Args['RecordType']}?id={$Args['RecordID']}", true), $CssClass).' ';
    }

    /**
     *
     *
     * @param $Sender
     * @param $Args
     */
    public function base_signInIcons_handler($Sender, $Args) {
        if (!$this->socialSignIn()) {
            return;
        }

        echo "\n".$this->_getButton();
    }

    /**
     *
     *
     * @param $Sender
     * @param $Args
     */
    public function base_beforeSignInButton_handler($Sender, $Args) {
        if (!$this->socialSignIn()) {
            return;
        }

        echo "\n".$this->_getButton();
    }

    /**
     *
     *
     * @param $Sender
     */
    public function base_beforeSignInLink_handler($Sender) {
        if (!$this->socialSignIn()) {
            return;
        }

        if (!Gdn::session()->isValid()) {
            echo "\n".Wrap($this->_getButton(), 'li', array('class' => 'Connect FacebookConnect'));
        }
    }

    /**
     *
     *
     * @param $Sender
     * @param $Args
     */
    public function base_getConnections_handler($Sender, $Args) {
        $Profile = valr('User.Attributes.'.self::ProviderKey.'.Profile', $Args);

        $Sender->Data["Connections"][self::ProviderKey] = array(
            'Icon' => $this->getWebResource('icon.png', '/'),
            'Name' => 'Facebook',
            'ProviderKey' => self::ProviderKey,
            'ConnectUrl' => $this->authorizeUri(false, self::profileConnecUrl()),
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
    public function postController_facebook_create($Sender, $RecordType, $ID) {
        if (!$this->socialReactions()) {
            throw permissionException();
        }

        $Row = getRecord($RecordType, $ID, true);
        if ($Row) {
            $Message = sliceParagraph(Gdn_Format::plainText($Row['Body'], $Row['Format']), 160);

            if ($this->accessToken() && $Sender->Request->isPostBack()) {
                $R = $this->api('/me/feed', array('link' => $Row['ShareUrl'], 'message' => $Message));

                $Sender->setJson('R', $R);
                $Sender->informMessage(t('Thanks for sharing!'));
            } else {
                $Get = array(
                    'app_id' => c('Plugins.Facebook.ApplicationID'),
                    'link' => $Row['ShareUrl'],
                    'name' => Gdn_Format::plainText($Row['Name'], 'Text'),
                    'description' => $Message,
                    'redirect_uri' => url('/post/shared/facebook', true)
                );

                $Url = 'http://www.facebook.com/dialog/feed?'.http_build_query($Get);
                redirect($Url);
            }
        }

        $Sender->render('Blank', 'Utility', 'Dashboard');
    }

    /**
     *
     *
     * @param ProfileController $Sender
     * @param type $UserReference
     * @param type $Username
     * @param type $Code
     */
    public function profileController_FacebookConnect_create($Sender, $UserReference, $Username, $Code = false) {
        $Sender->permission('Garden.SignIn.Allow');

        $Sender->getUserInfo($UserReference, $Username, '', true);
        $Sender->_setBreadcrumbs(t('Connections'), '/profile/connections');

        // Get the access token.
        $AccessToken = $this->getAccessToken($Code, self::profileConnecUrl());

        // Get the profile.
        $Profile = $this->getProfile($AccessToken);

        // Save the authentication.
        Gdn::userModel()->saveAuthentication(array(
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

    /**
     *
     *
     * @return string
     */
    private function _getButton() {
        $Url = $this->authorizeUri();

        return socialSigninButton('Facebook', $Url, 'icon', array('rel' => 'nofollow'));
    }

    /**
     *
     *
     * @param $Sender
     * @param $Args
     */
    public function socialController_facebook_create($Sender, $Args) {
        $Sender->permission('Garden.Settings.Manage');
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
            $Sender->informMessage(t("Your settings have been saved."));

        } else {
            $Sender->Form->setValue('ApplicationID', c('Plugins.Facebook.ApplicationID'));
            $Sender->Form->setValue('Secret', c('Plugins.Facebook.Secret'));
            $Sender->Form->setValue('UseFacebookNames', c('Plugins.Facebook.UseFacebookNames'));
            $Sender->Form->setValue('SendConnectEmail', c('Garden.Registration.SendConnectEmail', false));
            $Sender->Form->setValue('SocialSignIn', c('Plugins.Facebook.SocialSignIn', true));
            $Sender->Form->setValue('SocialReactions', $this->socialReactions());
            $Sender->Form->setValue('SocialSharing', $this->socialSharing());
        }

        $Sender->addSideMenu('dashboard/social');
        $Sender->setData('Title', t('Facebook Settings'));
        $Sender->render('Settings', '', 'plugins/Facebook');
    }

    /**
     *
     * @param Gdn_Controller $Sender
     * @param array $Args
     */
    public function base_connectData_handler($Sender, $Args) {
        if (val(0, $Args) != 'facebook') {
            return;
        }

        if (isset($_GET['error'])) { // TODO global nope x2
            throw new Gdn_UserException(val('error_description', $_GET, t('There was an error connecting to Facebook')));
        }

        $AppID = c('Plugins.Facebook.ApplicationID');
        $Secret = c('Plugins.Facebook.Secret');
        $Code = val('code', $_GET); // TODO nope
        $Query = '';
        if ($Sender->Request->get('display')) {
            $Query = 'display='.urlencode($Sender->Request->get('display'));
        }

        $RedirectUri = concatSep('&', $this->redirectUri(), $Query);

        $AccessToken = $Sender->Form->getFormValue('AccessToken');

        // Get the access token.
        if (!$AccessToken && $Code) {
            // Exchange the token for an access token.
            $Code = urlencode($Code);

            $AccessToken = $this->getAccessToken($Code, $RedirectUri);

            $NewToken = true;
        }

        // Get the profile.
        try {
            $Profile = $this->getProfile($AccessToken);
        } catch (Exception $Ex) {
            if (!isset($NewToken)) {
                // There was an error getting the profile, which probably means the saved access token is no longer valid. Try and reauthorize.
                if ($Sender->deliveryType() == DELIVERY_TYPE_ALL) {
                    redirect($this->authorizeUri());
                } else {
                    $Sender->setHeader('Content-type', 'application/json');
                    $Sender->deliveryMethod(DELIVERY_METHOD_JSON);
                    $Sender->RedirectUrl = $this->authorizeUri();
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

        if (c('Plugins.Facebook.UseFacebookNames')) {
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

        $Sender->setData('Verified', true);
    }

    /**
     *
     *
     * @param $Code
     * @param $RedirectUri
     * @param bool $ThrowError
     * @return mixed
     * @throws Gdn_UserException
     */
    protected function getAccessToken($Code, $RedirectUri, $ThrowError = true) {
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
            throw new Gdn_UserException('Facebook returned the following error: '.valr('error.message', $Tokens, 'Unknown error.'), 400);
        }

        $AccessToken = val('access_token', $Tokens);
//      $Expires = val('expires', $Tokens, null);
        return $AccessToken;
    }

    /**
     *
     *
     * @param $AccessToken
     * @return mixed
     */
    public function getProfile($AccessToken) {
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

    /**
     *
     *
     * @param bool $Query
     * @param bool $RedirectUri
     * @return string
     */
    public function authorizeUri($Query = false, $RedirectUri = false) {
        $AppID = c('Plugins.Facebook.ApplicationID');
        $FBScope = c('Plugins.Facebook.Scope', 'email,publish_actions');

        if (is_array($FBScope)) {
            $Scopes = implode(',', $FBScope);
        } else {
            $Scopes = $FBScope;
        }

        if (!$RedirectUri) {
            $RedirectUri = $this->redirectUri();
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

    /**
     *
     *
     * @param null $NewValue
     * @return null|string
     */
    public function redirectUri($NewValue = null) {
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

            $Path = Gdn::request()->path();

            $Target = val('Target', $_GET, $Path ? $Path : '/'); // TODO rm global

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

    /**
     *
     *
     * @return string
     */
    public static function profileConnecUrl() {
        return url(userUrl(Gdn::session()->User, false, 'facebookconnect'), true);
    }

    /**
     *
     *
     * @return bool
     */
    public function isConfigured() {
        $AppID = c('Plugins.Facebook.ApplicationID');
        $Secret = c('Plugins.Facebook.Secret');

        if (!$AppID || !$Secret) {
            return false;
        }

        return true;
    }

    /**
     *
     *
     * @return bool
     */
    public function socialSignIn() {
        return c('Plugins.Facebook.SocialSignIn', true) && $this->isConfigured();
    }

    /**
     *
     *
     * @return bool
     */
    public function socialSharing() {
        return c('Plugins.Facebook.SocialSharing', true) && $this->isConfigured();
    }

    public function socialReactions() {
        return c('Plugins.Facebook.SocialReactions', true) && $this->isConfigured();
    }

    /**
     *
     *
     * @throws Gdn_UserException
     */
    public function setup() {
        $Error = '';
        if (!function_exists('curl_init')) {
            $Error = concatSep("\n", $Error, 'This plugin requires curl.');
        }

        if ($Error) {
            throw new Gdn_UserException($Error, 400);
        }

        $this->structure();
    }

    /**
     *
     */
    public function structure() {
        // Save the facebook provider type.
        Gdn::sql()->replace(
            'UserAuthenticationProvider',
            array('AuthenticationSchemeAlias' => 'facebook', 'URL' => '...', 'AssociationSecret' => '...', 'AssociationHashMethod' => '...'),
            array('AuthenticationKey' => self::ProviderKey),
            true
        );
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
//   public function AuthenticationController_DisableAuthenticatorHandshake_handler(&$Sender) {
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
//   public function AuthenticationController_EnableAuthenticatorHandshake_handler(&$Sender) {
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
