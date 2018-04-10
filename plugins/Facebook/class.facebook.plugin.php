<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Facebook
 */

/**
 * Class FacebookPlugin
 */
class FacebookPlugin extends Gdn_Plugin {

    const API_VERSION = '2.7';

    /** Authentication table key. */
    const PROVIDER_KEY = 'Facebook';

    /** @var string  */
    protected $_AccessToken = null;

    /** @var null  */
    protected $_RedirectUri = null;

    /** @var SsoUtils */
    private $ssoUtils;

    /**
     * Constructor.
     *
     * @param SsoUtils $ssoUtils
     */
    public function __construct(SsoUtils $ssoUtils) {
        parent::__construct();
        $this->ssoUtils = $ssoUtils;
    }

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
                $this->_AccessToken = valr(self::PROVIDER_KEY.'.AccessToken', Gdn::session()->User->Attributes);
            } else {
                $this->_AccessToken = false;
            }
        }

        return $this->_AccessToken;
    }

    /**
     * Redirect current user to the authorization URI.
     *
     * @param bool $query
     */
    public function authorize($query = false) {
        $uri = $this->authorizeUri($query);
        redirectTo($uri, 302, false);
    }

    /**
     * Send a request to Facebook's API.
     *
     * @param string $path
     * @param bool $post
     *
     * @return string|array Response from the API.
     * @throws Gdn_UserException
     */
    public function api($path, $post = false) {
        // Build the url.
        $url = 'https://graph.facebook.com/v'.self::API_VERSION.'/'.ltrim($path, '/');
        $accessToken = $this->accessToken();
        if (!$accessToken) {
            throw new Gdn_UserException("You don't have a valid Facebook connection.");
        }

        if (strpos($url, '?') === false) {
            $url .= '?';
        } else {
            $url .= '&';
        }

        $url .= 'access_token='.urlencode($accessToken);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_URL, $url);

        if ($post !== false) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
            trace("  POST $url");
        } else {
            trace("  GET  $url");
        }

        $response = curl_exec($ch);

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        Gdn::controller()->setJson('Type', $contentType);

        if (strpos($contentType, 'javascript') !== false) {
            $result = json_decode($response, true);

            if (isset($result['error'])) {
                Gdn::dispatcher()->passData('FacebookResponse', $result);
                throw new Gdn_UserException($result['error']['message']);
            }
        } else {
            $result = $response;
        }

        return $result;
    }

    /**
     * Add Facebook button to normal signin page.
     *
     * @param Gdn_Controller $sender
     * @param array $args
     */
    public function entryController_signIn_handler($sender, $args) {
        if (!$this->socialSignIn()) {
            return;
        }

        if (isset($sender->Data['Methods'])) {
            // pass the relative path, socialSigninButton handles the URL.
            $url = '/entry/facebook';

            // Add the facebook method to the controller.
            $fbMethod = [
                'Name' => self::PROVIDER_KEY,
                'SignInHtml' => socialSigninButton('Facebook', $url, 'button')
            ];

            $sender->Data['Methods'][] = $fbMethod;
        }
    }

    /**
     * Add 'Facebook' option to the reactions row under posts.
     *
     * @param Gdn_Controller $sender
     * @param array $args
     */
    public function base_afterReactions_handler($sender, $args) {
        if (!$this->socialReactions()) {
            return;
        }

        echo Gdn_Theme::bulletItem('Share');
        $this->addReactButton($sender, $args);
    }

    /**
     * Output Quote link to share via Facebook.
     *
     * @param Gdn_Controller $sender
     * @param array $args
     */
    protected function addReactButton($sender, $args) {
        echo anchor(
            sprite('ReactFacebook', 'Sprite ReactSprite', t('Share on Facebook')),
            url("post/facebook/{$args['RecordType']}?id={$args['RecordID']}", true),
            'ReactButton PopupWindow')
        ;
    }

    /**
     * Add Facebook button to MeModule.
     *
     * @param Gdn_Controller $sender
     * @param array $args
     */
    public function base_signInIcons_handler($sender, $args) {
        if (!$this->socialSignIn()) {
            return;
        }

        echo "\n".$this->_getButton();
    }

    /**
     * Add Facebook button to GuestModule.
     *
     * @param Gdn_Controller $sender
     * @param array $args
     */
    public function base_beforeSignInButton_handler($sender, $args) {
        if (!$this->socialSignIn()) {
            return;
        }

        echo "\n".$this->_getButton();
    }

    /**
     * Add Facebook button to default mobile theme.
     *
     * @param Gdn_Controller $sender
     */
    public function base_beforeSignInLink_handler($sender) {
        if (!$this->socialSignIn()) {
            return;
        }

        if (!Gdn::session()->isValid()) {
            echo "\n".wrap($this->_getButton(), 'li', ['class' => 'Connect FacebookConnect']);
        }
    }

    /**
     * Make this available as an SSO method to users.
     *
     * @param Gdn_Controller $sender
     * @param array $args
     */
    public function base_getConnections_handler($sender, $args) {
        $profile = valr('User.Attributes.'.self::PROVIDER_KEY.'.Profile', $args);

        $sender->Data["Connections"][self::PROVIDER_KEY] = [
            'Icon' => $this->getWebResource('icon.png', '/'),
            'Name' => 'Facebook',
            'ProviderKey' => self::PROVIDER_KEY,
            'ConnectUrl' => $this->authorizeUri(false, self::profileConnecUrl()),
            'Profile' => [
                'Name' => val('name', $profile),
                'Photo' => "//graph.facebook.com/{$profile['id']}/picture?width=200&height=200"
            ]
        ];
    }

    /**
     * Endpoint to share via Facebook.
     *
     * @param PostController $sender
     * @param string $recordType
     * @param int $iD
     *
     * @throws Gdn_UserException
     */
    public function postController_facebook_create($sender, $recordType, $iD) {
        if (!$this->socialReactions()) {
            throw permissionException();
        }

        $row = getRecord($recordType, $iD, true);
        if ($row) {
            if ($this->accessToken() && $sender->Request->isPostBack()) {
                $r = $this->api('/me/feed', ['link' => $row['ShareUrl']]);

                $sender->setJson('R', $r);
                $sender->informMessage(t('Thanks for sharing!'));
            } else {
                $get = [
                    'app_id' => c('Plugins.Facebook.ApplicationID'),
                    'link' => $row['ShareUrl'],
                ];

                // Do not redirect if we are in a popup (It will close itself :D)
                if ($sender->Request->get('display') !== 'popup') {
                    $get['redirect_uri'] = url('/post/shared/facebook', true);
                }

                $url = 'http://www.facebook.com/dialog/feed?'.http_build_query($get);
                redirectTo($url, 302, false);
            }
        }

        $sender->render('Blank', 'Utility', 'Dashboard');
    }

    /**
     * Endpoint to handle connecting user to Facebook.
     *
     * @param ProfileController $sender
     * @param mixed $userReference
     * @param string $username
     * @param string|bool $code
     *
     * @throws Gdn_UserException
     */
    public function profileController_facebookConnect_create($sender, $userReference, $username, $code = false) {
        $sender->permission('Garden.SignIn.Allow');

        $state = json_decode(Gdn::request()->get('state', ''), true);
        $suppliedStateToken = val('token', $state);
        $this->ssoUtils->verifyStateToken('facebookSocial', $suppliedStateToken);

        $sender->getUserInfo($userReference, $username, '', true);
        $sender->_setBreadcrumbs(t('Connections'), '/profile/connections');

        // Get the access token.
        $accessToken = $this->getAccessToken($code, self::profileConnecUrl());

        // Get the profile.
        $profile = $this->getProfile($accessToken);

        // Save the authentication.
        Gdn::userModel()->saveAuthentication([
            'UserID' => $sender->User->UserID,
            'Provider' => self::PROVIDER_KEY,
            'UniqueID' => $profile['id']]);

        // Save the information as attributes.
        $attributes = [
            'AccessToken' => $accessToken,
            'Profile' => $profile
        ];
        Gdn::userModel()->saveAttribute($sender->User->UserID, self::PROVIDER_KEY, $attributes);

        $this->EventArguments['Provider'] = self::PROVIDER_KEY;
        $this->EventArguments['User'] = $sender->User;
        $this->fireEvent('AfterConnection');

        redirectTo(userUrl($sender->User, '', 'connections'));
    }

    /**
     * Build-A-Button.
     *
     * @return string
     */
    private function _getButton() {
        // pass the relative path, socialSigninButton handles the URL.
        $url = '/entry/facebook';
        return socialSigninButton('Facebook', $url, 'icon', ['rel' => 'nofollow']);
    }

    /**
     * Endpoint for configuring this addon.
     *
     * @param $sender
     * @param $args
     */
    public function socialController_facebook_create($sender, $args) {
        $sender->permission('Garden.Settings.Manage');
        if ($sender->Form->authenticatedPostBack()) {
            $settings = [
                'Plugins.Facebook.ApplicationID' => trim($sender->Form->getFormValue('ApplicationID')),
                'Plugins.Facebook.Secret' => trim($sender->Form->getFormValue('Secret')),
                'Plugins.Facebook.UseFacebookNames' => $sender->Form->getFormValue('UseFacebookNames'),
                'Plugins.Facebook.SocialSignIn' => $sender->Form->getFormValue('SocialSignIn'),
                'Plugins.Facebook.SocialReactions' => $sender->Form->getFormValue('SocialReactions'),
                'Garden.Registration.SendConnectEmail' => $sender->Form->getFormValue('SendConnectEmail')];

            saveToConfig($settings);
            $sender->informMessage(t("Your settings have been saved."));

        } else {
            $sender->Form->setValue('ApplicationID', c('Plugins.Facebook.ApplicationID'));
            $sender->Form->setValue('Secret', c('Plugins.Facebook.Secret'));
            $sender->Form->setValue('UseFacebookNames', c('Plugins.Facebook.UseFacebookNames'));
            $sender->Form->setValue('SendConnectEmail', c('Garden.Registration.SendConnectEmail', false));
            $sender->Form->setValue('SocialSignIn', c('Plugins.Facebook.SocialSignIn', true));
            $sender->Form->setValue('SocialReactions', $this->socialReactions());
        }

        $sender->setHighlightRoute('dashboard/social');
        $sender->setData('Title', t('Facebook Settings'));
        $sender->render('Settings', '', 'plugins/Facebook');
    }

    /**
     * Standard SSO hook into Vanilla to handle authentication & user info transfer.
     *
     * @param Gdn_Controller $sender
     * @param array $args
     */
    public function base_connectData_handler($sender, $args) {
        if (val(0, $args) != 'facebook') {
            return;
        }

        $state = json_decode(Gdn::request()->get('state', ''), true);
        $suppliedStateToken = val('token', $state);
        $this->ssoUtils->verifyStateToken('facebook', $suppliedStateToken);

        if (isset($_GET['error'])) { // TODO global nope x2
            throw new Gdn_UserException(val('error_description', $_GET, t('There was an error connecting to Facebook')));
        }

        $code = val('code', $_GET); // TODO nope
        $query = '';
        if ($sender->Request->get('display')) {
            $query = 'display='.urlencode($sender->Request->get('display'));
        }

        $redirectUri = concatSep('&', $this->redirectUri(), $query);

        $accessToken = $sender->Form->getFormValue('AccessToken');

        // Get the access token.
        if (!$accessToken && $code) {
            // Exchange the token for an access token.
            $code = urlencode($code);

            $accessToken = $this->getAccessToken($code, $redirectUri);

            $newToken = true;
        }

        // Get the profile.
        try {
            $profile = $this->getProfile($accessToken);
        } catch (Exception $ex) {
            if (!isset($newToken)) {
                // There was an error getting the profile, which probably means the saved access token is no longer valid. Try and reauthorize.
                if ($sender->deliveryType() == DELIVERY_TYPE_ALL) {
                    redirectTo($this->authorizeUri(), 302, false);
                } else {
                    $sender->setHeader('Content-type', 'application/json');
                    $sender->deliveryMethod(DELIVERY_METHOD_JSON);
                    $sender->setRedirectTo($this->authorizeUri(), false);
                }
            } else {
                $sender->Form->addError('There was an error with the Facebook connection.');
            }
        }

        // This isn't a trusted connection. Don't allow it to automatically connect a user account.
        saveToConfig('Garden.Registration.AutoConnect', false, false);

        $form = $sender->Form; //new gdn_Form();
        $iD = val('id', $profile);
        $form->setFormValue('UniqueID', $iD);
        $form->setFormValue('Provider', self::PROVIDER_KEY);
        $form->setFormValue('ProviderName', 'Facebook');
        $form->setFormValue('FullName', val('name', $profile));
        $form->setFormValue('Email', val('email', $profile));
        $form->setFormValue('Photo', "//graph.facebook.com/{$iD}/picture?width=200&height=200");
        $form->setFormValue('Target', val('target', $state, '/'));
        $form->addHidden('AccessToken', $accessToken);

        if (c('Plugins.Facebook.UseFacebookNames')) {
            $form->setFormValue('Name', val('name', $profile));
            saveToConfig([
                'Garden.User.ValidationRegex' => UserModel::USERNAME_REGEX_MIN,
                'Garden.User.ValidationLength' => '{3,50}',
                'Garden.Registration.NameUnique' => false
            ], '', false);
        }

        // Save some original data in the attributes of the connection for later API calls.
        $attributes = [];
        $attributes[self::PROVIDER_KEY] = [
            'AccessToken' => $accessToken,
            'Profile' => $profile
        ];
        $form->setFormValue('Attributes', $attributes);

        $sender->setData('Verified', true);
    }

    /**
     * Retrieve a Facebook access token.
     *
     * @param string $code
     * @param string $redirectUri
     * @param bool $throwError
     *
     * @return mixed
     * @throws Gdn_UserException
     */
    protected function getAccessToken($code, $redirectUri, $throwError = true) {
        $get = [
            'client_id' => c('Plugins.Facebook.ApplicationID'),
            'client_secret' => c('Plugins.Facebook.Secret'),
            'code' => $code,
            'redirect_uri' => $redirectUri];

        $url = 'https://graph.facebook.com/oauth/access_token?'.http_build_query($get);

        // Get the redirect URI.
        $c = curl_init();
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($c, CURLOPT_URL, $url);
        $contents = curl_exec($c);

        $info = curl_getinfo($c);
        if (strpos(val('content_type', $info, ''), '/javascript') !== false) {
            $tokens = json_decode($contents, true);
        } else if (strpos(val('content_type', $info, ''), '/json') !== false) {
            $tokens = json_decode($contents, true);
        } else {
            parse_str($contents, $tokens);
        }

        if (val('error', $tokens)) {
            throw new Gdn_UserException('Facebook returned the following error: '.valr('error.message', $tokens, 'Unknown error.'), 400);
        }

        $accessToken = val('access_token', $tokens);
        return $accessToken;
    }

    /**
     * Get users' Facebook profile data.
     *
     * @param $accessToken
     * @return mixed
     */
    public function getProfile($accessToken) {
        $url = "https://graph.facebook.com/me?access_token=$accessToken&fields=name,id,email";
        $contents = file_get_contents($url);
        $profile = json_decode($contents, true);
        return $profile;
    }

    /**
     * Where to send authorization request.
     *
     * @param bool $query
     * @param bool $redirectUri
     *
     * @return string URL.
     */
    public function authorizeUri($query = false, $redirectUri = false) {
        $appID = c('Plugins.Facebook.ApplicationID');
        $fBScope = c('Plugins.Facebook.Scope', 'email');

        if (is_array($fBScope)) {
            $scopes = implode(',', $fBScope);
        } else {
            $scopes = $fBScope;
        }

        if (!$redirectUri) {
            $redirectUri = $this->redirectUri();
        }

        if ($query) {
            $redirectUri .= (stripos($redirectUri, '?') === false) ? '?' : '&' .$query;
        }

        // Get a state token.
        $stateToken = $this->ssoUtils->getStateToken();

        $authQuery = http_build_query([
            'client_id' => $appID,
            'redirect_uri' => $redirectUri,
            'scope' => $scopes,
            'state' => json_encode(['token' => $stateToken, 'target' => $this->getTargetUri()]),
        ]);
        $signinHref = "https://graph.facebook.com/oauth/authorize?{$authQuery}";

        if ($query) {
            $signinHref .= '&'.$query;
        }

        return $signinHref;
    }

    /**
     * Send the Facebook entry page to Facebook as the redirectURI.
     *
     * @param null $newValue
     *
     * @return null|string URL.
     */
    public function redirectUri($newValue = null) {
        if ($newValue !== null) {
            $this->_RedirectUri = $newValue;
        } elseif ($this->_RedirectUri === null) {
            $redirectUri = url('/entry/connect/facebook', true);
            if (strpos($redirectUri, '=') !== false) {
                $p = strrchr($redirectUri, '=');
                $uri = substr($redirectUri, 0, -strlen($p));
                $p = urlencode(ltrim($p, '='));
                $redirectUri = $uri.'='.$p;
            }
            $this->_RedirectUri = $redirectUri;
        }

        return $this->_RedirectUri;
    }

    /**
     * Get the target URL to pass to the state when making requests.
     *
     * @return mixed|string
     */
    public function getTargetUri() {
        $target = Gdn::request()->getValueFrom(Gdn_Request::INPUT_GET, 'Target', '/');
        if (ltrim($target, '/') == 'entry/signin' || ltrim($target, '/') == 'entry/facebook') {
            $target = '/';
        }
        return $target;
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
        $appID = c('Plugins.Facebook.ApplicationID');
        $secret = c('Plugins.Facebook.Secret');

        if (!$appID || !$secret) {
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
     * Create an entry/facebook endpoint that redirects to the authorization URI.
     */
    public function entryController_facebook_create() {
        redirectTo($this->authorizeUri(), 302, false);
    }

    /**
     * Run once on enable.
     *
     * @throws Gdn_UserException
     */
    public function setup() {
        $error = '';
        if (!function_exists('curl_init')) {
            $error = concatSep("\n", $error, 'This plugin requires curl.');
        }

        if ($error) {
            throw new Gdn_UserException($error, 400);
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
            ['AuthenticationSchemeAlias' => 'facebook', 'URL' => '...', 'AssociationSecret' => '...', 'AssociationHashMethod' => '...'],
            ['AuthenticationKey' => self::PROVIDER_KEY],
            true
        );
    }
}
