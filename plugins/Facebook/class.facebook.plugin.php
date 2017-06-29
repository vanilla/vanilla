<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Facebook
 */

/**
 * Class FacebookPlugin
 */
class FacebookPlugin extends Gdn_Plugin {

    const API_VERSION = '2.7';

    /** Authentication table key. */
    const ProviderKey = 'Facebook';

    /** @var string  */
    protected $_AccessToken = null;

    /** @var null  */
    protected $_RedirectUri = null;

    /**
     * Retrieve an access token from the session.
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
     * Redirect current user to the authorization URI.
     *
     * @param bool $Query
     */
    public function authorize($Query = false) {
        $Uri = $this->authorizeUri($Query);
        redirectTo($Uri, 302, false);
    }

    /**
     * Send a request to Facebook's API.
     *
     * @param string $Path
     * @param bool $Post
     *
     * @return string|array Response from the API.
     * @throws Gdn_UserException
     */
    public function api($Path, $Post = false) {
        // Build the url.
        $Url = 'https://graph.facebook.com/v'.self::API_VERSION.'/'.ltrim($Path, '/');
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
     * Add Facebook button to normal signin page.
     *
     * @param Gdn_Controller $Sender
     * @param array $Args
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
     * Add 'Facebook' option to the reactions row under posts.
     *
     * @param Gdn_Controller $Sender
     * @param array $Args
     */
    public function base_afterReactions_handler($Sender, $Args) {
        if (!$this->socialReactions()) {
            return;
        }

        echo Gdn_Theme::bulletItem('Share');
        $this->addReactButton($Sender, $Args);
    }

    /**
     * Output Quote link to share via Facebook.
     *
     * @param Gdn_Controller $Sender
     * @param array $Args
     */
    protected function addReactButton($Sender, $Args) {
        echo anchor(
            sprite('ReactFacebook', 'Sprite ReactSprite', t('Share on Facebook')),
            url("post/facebook/{$Args['RecordType']}?id={$Args['RecordID']}", true),
            'ReactButton PopupWindow')
        ;
    }

    /**
     * Add Facebook button to MeModule.
     *
     * @param Gdn_Controller $Sender
     * @param array $Args
     */
    public function base_signInIcons_handler($Sender, $Args) {
        if (!$this->socialSignIn()) {
            return;
        }

        echo "\n".$this->_getButton();
    }

    /**
     * Add Facebook button to GuestModule.
     *
     * @param Gdn_Controller $Sender
     * @param array $Args
     */
    public function base_beforeSignInButton_handler($Sender, $Args) {
        if (!$this->socialSignIn()) {
            return;
        }

        echo "\n".$this->_getButton();
    }

    /**
     * Add Facebook button to default mobile theme.
     *
     * @param Gdn_Controller $Sender
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
     * Make this available as an SSO method to users.
     *
     * @param Gdn_Controller $Sender
     * @param array $Args
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
                'Photo' => "//graph.facebook.com/{$Profile['id']}/picture?width=200&height=200"
            )
        );
    }

    /**
     * Endpoint to share via Facebook.
     *
     * @param PostController $Sender
     * @param string $RecordType
     * @param int $ID
     *
     * @throws Gdn_UserException
     */
    public function postController_facebook_create($Sender, $RecordType, $ID) {
        if (!$this->socialReactions()) {
            throw permissionException();
        }

        $Row = getRecord($RecordType, $ID, true);
        if ($Row) {
            if ($this->accessToken() && $Sender->Request->isPostBack()) {
                $R = $this->api('/me/feed', array('link' => $Row['ShareUrl']));

                $Sender->setJson('R', $R);
                $Sender->informMessage(t('Thanks for sharing!'));
            } else {
                $Get = array(
                    'app_id' => c('Plugins.Facebook.ApplicationID'),
                    'link' => $Row['ShareUrl'],
                );

                // Do not redirect if we are in a popup (It will close itself :D)
                if ($Sender->Request->get('display') !== 'popup') {
                    $Get['redirect_uri'] = url('/post/shared/facebook', true);
                }

                $Url = 'http://www.facebook.com/dialog/feed?'.http_build_query($Get);
                redirectTo($Url, 302, false);
            }
        }

        $Sender->render('Blank', 'Utility', 'Dashboard');
    }

    /**
     * Endpoint to handle connecting user to Facebook.
     *
     * @param ProfileController $Sender
     * @param mixed $UserReference
     * @param string $Username
     * @param string|bool $Code
     *
     * @throws Gdn_UserException
     */
    public function profileController_FacebookConnect_create($Sender, $UserReference, $Username, $Code = false) {
        $transientKey = Gdn::request()->get('state');
        if (empty($transientKey) || Gdn::session()->validateTransientKey($transientKey) === false) {
            throw new Gdn_UserException(t('Invalid CSRF token.', 'Invalid CSRF token. Please try again.'), 403);
        }

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

        redirectTo(userUrl($Sender->User, '', 'connections'), 302, false);
    }

    /**
     * Build-A-Button.
     *
     * @return string
     */
    private function _getButton() {
        $Url = $this->authorizeUri();

        return socialSigninButton('Facebook', $Url, 'icon', array('rel' => 'nofollow'));
    }

    /**
     * Endpoint for configuring this addon.
     *
     * @param $Sender
     * @param $Args
     */
    public function socialController_facebook_create($Sender, $Args) {
        $Sender->permission('Garden.Settings.Manage');
        if ($Sender->Form->authenticatedPostBack()) {
            $Settings = array(
                'Plugins.Facebook.ApplicationID' => trim($Sender->Form->getFormValue('ApplicationID')),
                'Plugins.Facebook.Secret' => trim($Sender->Form->getFormValue('Secret')),
                'Plugins.Facebook.UseFacebookNames' => $Sender->Form->getFormValue('UseFacebookNames'),
                'Plugins.Facebook.SocialSignIn' => $Sender->Form->getFormValue('SocialSignIn'),
                'Plugins.Facebook.SocialReactions' => $Sender->Form->getFormValue('SocialReactions'),
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
        }

        $Sender->setHighlightRoute('dashboard/social');
        $Sender->setData('Title', t('Facebook Settings'));
        $Sender->render('Settings', '', 'plugins/Facebook');
    }

    /**
     * Standard SSO hook into Vanilla to handle authentication & user info transfer.
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
                    redirectTo($this->authorizeUri(), 302, false);
                } else {
                    $Sender->setHeader('Content-type', 'application/json');
                    $Sender->deliveryMethod(DELIVERY_METHOD_JSON);
                    $Sender->setRedirectTo($this->authorizeUri(), false);
                }
            } else {
                $Sender->Form->addError('There was an error with the Facebook connection.');
            }
        }

        // This isn't a trusted connection. Don't allow it to automatically connect a user account.
        saveToConfig('Garden.Registration.AutoConnect', false, false);

        $Form = $Sender->Form; //new Gdn_Form();
        $ID = val('id', $Profile);
        $Form->setFormValue('UniqueID', $ID);
        $Form->setFormValue('Provider', self::ProviderKey);
        $Form->setFormValue('ProviderName', 'Facebook');
        $Form->setFormValue('FullName', val('name', $Profile));
        $Form->setFormValue('Email', val('email', $Profile));
        $Form->setFormValue('Photo', "//graph.facebook.com/{$ID}/picture?width=200&height=200");
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
     * Retrieve a Facebook access token.
     *
     * @param string $Code
     * @param string $RedirectUri
     * @param bool $ThrowError
     *
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
        } else if (strpos(val('content_type', $Info, ''), '/json') !== false) {
            $Tokens = json_decode($Contents, true);
        } else {
            parse_str($Contents, $Tokens);
        }

        if (val('error', $Tokens)) {
            throw new Gdn_UserException('Facebook returned the following error: '.valr('error.message', $Tokens, 'Unknown error.'), 400);
        }

        $AccessToken = val('access_token', $Tokens);
        return $AccessToken;
    }

    /**
     * Get users' Facebook profile data.
     *
     * @param $AccessToken
     * @return mixed
     */
    public function getProfile($AccessToken) {
        $Url = "https://graph.facebook.com/me?access_token=$AccessToken&fields=name,id,email";
        $Contents = file_get_contents($Url);
        $Profile = json_decode($Contents, true);
        return $Profile;
    }

    /**
     * Where to send authorization request.
     *
     * @param bool $Query
     * @param bool $RedirectUri
     *
     * @return string URL.
     */
    public function authorizeUri($Query = false, $RedirectUri = false) {
        $AppID = c('Plugins.Facebook.ApplicationID');
        $FBScope = c('Plugins.Facebook.Scope', 'email');

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

        $authQuery = http_build_query([
            'client_id' => $AppID,
            'redirect_uri' => $RedirectUri,
            'scope' => $Scopes,
            'state' => Gdn::session()->transientKey()
        ]);
        $SigninHref = "https://graph.facebook.com/oauth/authorize?{$authQuery}";

        if ($Query) {
            $SigninHref .= '&'.$Query;
        }

        return $SigninHref;
    }

    /**
     * Figure out where to send user after auth step.
     *
     * @param null $NewValue
     *
     * @return null|string URL.
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
     * Get the URL for connection page.
     *
     * @return string URL.
     */
    public static function profileConnecUrl() {
        return url(userUrl(Gdn::session()->User, false, 'facebookconnect'), true);
    }

    /**
     * Whether this plugin is setup with enough info to function.
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
     * Whether social signin is enabled.
     *
     * @return bool
     */
    public function socialSignIn() {
        return c('Plugins.Facebook.SocialSignIn', true) && $this->isConfigured();
    }

    /**
     * Whether social reactions is enabled.
     *
     * @return bool
     */
    public function socialReactions() {
        return c('Plugins.Facebook.SocialReactions', true) && $this->isConfigured();
    }

    /**
     * Run once on enable.
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
     * Run on utility/update.
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
}
