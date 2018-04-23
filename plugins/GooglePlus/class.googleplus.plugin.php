<?php
/**
 * GooglePlus Plugin.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package GooglePlus
 */

/**
 * Class GooglePlusPlugin
 */
class GooglePlusPlugin extends Gdn_Plugin {

    /** Authentication Provider key. */
    const PROVIDER_KEY = 'GooglePlus';

    /** Google's URL. */
    const API_URL = 'https://www.googleapis.com/oauth2/v1';

    /** @var string */
    protected $_AccessToken = null;

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
     * Get current access token.
     *
     * @param bool $newValue
     * @return bool|mixed|null
     */
    public function accessToken($newValue = false) {
        if (!$this->isConfigured()) {
            return false;
        }

        if ($newValue !== false) {
            $this->_AccessToken = $newValue;
        }

        if ($this->_AccessToken === null) {
            $this->_AccessToken = valr(self::PROVIDER_KEY.'.AccessToken', Gdn::session()->User->Attributes);
        }

        return $this->_AccessToken;
    }

    /**
     * Send request to the Twitter API.
     *
     * @param string $path
     * @param array $post
     *
     * @return mixed
     * @throws Gdn_UserException
     */
    public function api($path, $post = []) {
        $url = self::API_URL.'/'.ltrim($path, '/');
        if (strpos($url, '?') === false) {
            $url .= '?';
        } else {
            $url .= '&';
        }
        $url .= 'access_token='.urlencode($this->accessToken());

        $result = $this->curl($url, empty($post) ? 'GET' : 'POST', $post);
        return $result;
    }

    /**
     * Retrieve where to send the user for authorization.
     *
     * @param array $extraState
     *
     * @return string
     */
    public function authorizeUri($extraState = []) {
        $url = 'https://accounts.google.com/o/oauth2/auth';
        $get = [
            'response_type' => 'code',
            'client_id' => c('Plugins.GooglePlus.ClientID'),
            'redirect_uri' => url('/entry/googleplus', true),
            'scope' => 'https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email'
        ];

        // Get a state token.
        $stateToken = $this->ssoUtils->getStateToken();

        $state = array_merge(
            [
                'token' => $stateToken,
            ],
            (array)$extraState
        );


        if (isset($state['target'])) {
            $target = strtolower(ltrim($get['state']['target'], '/'));
            if (in_array($target, ['entry/signin', 'entry/googleplusauthredirect'])) {
                $get['state']['target'] = '/';
            }
        } else {
            $state['target'] = '/';
        }

        $get['state'] = json_encode($state);

        return $url.'?'.http_build_query($get);
    }

    /**
     * Get an access token from Google.
     *
     * @param $code
     *
     * @return mixed
     * @throws Gdn_UserException
     */
    public function getAccessToken($code) {
        $url = 'https://accounts.google.com/o/oauth2/token';
        $post = [
            'code' => $code,
            'client_id' => c('Plugins.GooglePlus.ClientID'),
            'client_secret' => c('Plugins.GooglePlus.Secret'),
            'redirect_uri' => url('/entry/googleplus', true),
            'grant_type' => 'authorization_code'
        ];

        $data = self::curl($url, 'POST', $post);
        $accessToken = $data['access_token'];
        return $accessToken;
    }

    /**
     * Whether this addon has enough configuration to work.
     *
     * @return bool
     */
    public function isConfigured() {
        $result = c('Plugins.GooglePlus.ClientID') && c('Plugins.GooglePlus.Secret');
        return $result;
    }

    /**
     *
     *
     * @return bool
     */
    public function isDefault() {
        return (bool)c('Plugins.GooglePlus.Default');
    }

    /**
     * Whether social sharing is enabled.
     *
     * @return bool
     */
    public function socialSharing() {
        return c('Plugins.GooglePlus.SocialSharing', true);
    }

    /**
     * Whether social reactions are enabled.
     *
     * @return bool
     */
    public function socialReactions() {
        return c('Plugins.GooglePlus.SocialReactions', true);
    }

    /**
     * Send a cURL request.
     *
     * @param $url
     * @param string $method
     * @param array $data
     * @return mixed
     * @throws Gdn_UserException
     */
    public static function curl($url, $method = 'GET', $data = []) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_URL, $url);

        if ($method == 'POST') {
            if (strpos($url, 'https://accounts.google.com/o/oauth2/token') === 0) {
                // We need to prevent the redirection so that the access token is not wiped from the form on postback.
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
            }

            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            trace("  POST $url");
        } else {
            trace("  GET  $url");
        }

        $response = curl_exec($ch);

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        $result = @json_decode($response, true);
        if (!$result) {
            $result = $response;
        }

        if ($httpCode != 200) {
            $error = val('error', $result, $response);
            if (is_array($error)) {
                $error = val('message', $error, $response);
            }

            throw new Gdn_UserException($error, $httpCode);
        }

        return $result;
    }

    /**
     * Run once on enable.
     */
    public function setup() {
        $this->structure();
    }

    /**
     * Gimme button!
     *
     * @param string $type
     * @return string
     */
    public function signInButton($type = 'button') {
        $target = Gdn::request()->post('Target', Gdn::request()->get('Target', url('', '/')));
        $url = 'entry/googleplusauthredirect?Target='.$target;

        $result = socialSignInButton('Google', $url, $type, ['rel' => 'nofollow']);
        return $result;
    }

    /**
     * Run on utility/update.
     */
    public function structure() {
        if (Gdn::sql()->getWhere('UserAuthenticationProvider', ['AuthenticationSchemeAlias' => 'Google+'])->firstRow()) {
            Gdn::sql()->put('UserAuthenticationProvider', ['AuthenticationSchemeAlias' => self::PROVIDER_KEY], ['AuthenticationSchemeAlias' => 'Google+']);
        }

        // Save the google+ provider type.
        Gdn::sql()->replace(
            'UserAuthenticationProvider',
            ['AuthenticationSchemeAlias' => self::PROVIDER_KEY, 'URL' => '', 'AssociationSecret' => '', 'AssociationHashMethod' => '...'],
            ['AuthenticationKey' => self::PROVIDER_KEY],
            true
        );
    }

    /**
     * Calculate the final sign in and register urls for google+.
     *
     * @param authenticationProviderModel $sender Not used.
     * @param array $args Contains the provider and data.
     */
    public function authenticationProviderModel_calculateGooglePlus_handler($sender, $args) {
        $provider =& $args['Provider'];
        $target = val('Target', null);

        if (!$target) {
            $target = Gdn::request()->post('Target', Gdn::request()->get('Target', url('', '/')));
        }

        $provider['SignInUrlFinal'] = $this->authorizeUri(['target' => $target]);
    }

    /**
     * Add 'Google+' option to the row.
     *
     * @param Gdn_Controller $sender
     * @param array $args
     */
    public function base_afterReactions_handler($sender, $args) {
        if (!$this->socialReactions()) {
            return;
        }
        echo Gdn_Theme::bulletItem('Share');
        $url = url("post/googleplus/{$args['RecordType']}?id={$args['RecordID']}", true);
        $cssClass = 'ReactButton PopupWindow';

        echo ' '.anchor(sprite('ReactGooglePlus', 'ReactSprite', t('Share on Google+')), $url, $cssClass).' ';
    }

    /**
     * Generic SSO hook into Vanilla for authorizing via Google+ and pass user info.
     *
     * @param EntryController $sender
     * @param array $args
     */
    public function base_connectData_handler($sender, $args) {
        if (val(0, $args) != 'googleplus') {
            return;
        }

        $state = json_decode(Gdn::request()->get('state', ''), true);
        $suppliedStateToken = val('token', $state);
        $this->ssoUtils->verifyStateToken(self::PROVIDER_KEY, $suppliedStateToken);

        $code = Gdn::request()->get('code');
        $accessToken = $sender->Form->getFormValue('AccessToken');

        // Get the access token.
        if (!$accessToken && $code) {
            // Exchange the token for an access token.
            $accessToken = $this->getAccessToken($code);
        }
        $this->accessToken($accessToken);

        // Get the profile.
        try {
            $profile = $this->api('/userinfo');
        } catch (Exception $ex) {
            $sender->Form->addError('There was an error with the Google+ connection.');
        }

        // This isn't a trusted connection. Don't allow it to automatically connect a user account.
        saveToConfig('Garden.Registration.AutoConnect', false, false);

        $form = $sender->Form;
        $form->setFormValue('UniqueID', val('id', $profile));
        $form->setFormValue('Provider', self::PROVIDER_KEY);
        $form->setFormValue('ProviderName', 'Google+');
        $form->setFormValue('FullName', val('name', $profile));
        $form->setFormValue('Email', val('email', $profile));
        if (c('Plugins.GooglePlus.UseAvatars', true)) {
            $form->setFormValue('Photo', val('picture', $profile));
        }
        $form->addHidden('AccessToken', $accessToken);

        if (c('Plugins.GooglePlus.UseFullNames')) {
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

        $this->EventArguments['Form'] = $form;
        $this->fireEvent('AfterConnectData');
    }

    /**
     * Add Google+ option to MeModule.
     *
     * @param Gdn_Controller $sender
     * @param array $args
     */
    public function base_signInIcons_handler($sender, $args) {
        if (!$this->isDefault()) {
            echo ' '.$this->signInButton('icon').' ';
        }
    }

    /**
     * Add Google+ option to GuestModule.
     *
     * @param Gdn_Controller $sender
     * @param array $args
     */
    public function base_beforeSignInButton_handler($sender, $args) {
        if (!$this->isConfigured()) {
            return;
        }
        if (!$this->isDefault()) {
            echo ' '.$this->signInButton('icon').' ';
        }
    }

    /**
     * Add Google+ to the list of available providers.
     *
     * @param Gdn_Controller $sender
     * @param array $args
     */
    public function base_getConnections_handler($sender, $args) {
        $gPlus = valr('User.Attributes.'.self::PROVIDER_KEY, $args);
        $profile = valr('User.Attributes.'.self::PROVIDER_KEY.'.Profile', $args);

        $sender->Data['Connections'][self::PROVIDER_KEY] = [
            'Icon' => $this->getWebResource('icon.png'),
            'Name' => 'Google+',
            'ProviderKey' => self::PROVIDER_KEY,
            'ConnectUrl' => $this->authorizeUri(['r' => 'profile', 'uid' => Gdn::session()->UserID]),
            'Profile' => [
                'Name' => val('name', $profile),
                'Photo' => val('picture', $profile)
            ]
        ];

        trace(val('AccessToken', $gPlus), 'google+ access token');
    }

    /**
     * Endpoint that redirects to the authorization URL.
     */
    public function entryController_googlePlusAuthRedirect_create() {
        redirectTo($this->authorizeUri(['target', Gdn::request()->get('Target', '/')]), 302, false);
    }

    /**
     * Endpoint for authenticating with Google+.
     *
     * @param EntryController $sender
     * @param string|bool $code
     * @param string|bool $state
     *
     * @throws Gdn_UserException
     */
    public function entryController_googlePlus_create($sender, $code = false, $state = false) {
        if ($error = $sender->Request->get('error')) {
            throw new Gdn_UserException($error);
        }

        $state = json_decode($state, true);

        // This is an sso request, we need to redispatch to /entry/connect/googleplus
        if (empty($state['r'])) {
            $url = '/entry/connect/googleplus?'.http_build_query($sender->Request->getQuery());
            redirectTo($url);
        }

        switch ($state['r']) {
            case 'profile':
                // This is a connect request from the user's profile.

                $this->accessToken($this->getAccessToken($code));

                // Get the user's information.
                $profile = $this->api('/userinfo');

                $user = Gdn::userModel()->getID($state['uid']);
                if (!$user) {
                    throw notFoundException('User');
                }
                // Save the authentication.
                Gdn::userModel()->saveAuthentication([
                    'UserID' => $user->UserID,
                    'Provider' => self::PROVIDER_KEY,
                    'UniqueID' => $profile['id']]);

                // Save the information as attributes.
                $attributes = [
                    'AccessToken' => $accessToken,
                    'Profile' => $profile
                ];
                Gdn::userModel()->saveAttribute($user->UserID, self::PROVIDER_KEY, $attributes);

                $this->EventArguments['Provider'] = self::PROVIDER_KEY;
                $this->EventArguments['User'] = $sender->User;
                $this->fireEvent('AfterConnection');

                redirectTo(userUrl($user, '', 'connections'));
                break;
        }
    }

    /**
     * Add Google+ as option to the normal signin page.
     *
     * @param Gdn_Controller $sender
     * @param array $args
     */
    public function entryController_signIn_handler($sender, $args) {
        if (!$this->isConfigured()) {
            return;
        }

        if (isset($sender->Data['Methods'])) {
            $url = $this->authorizeUri();

            // Add the Google method to the controller.
            $method = [
                'Name' => 'Google',
                'SignInHtml' => $this->signInButton() //SocialSigninButton('Google', $Url, 'button', array('class' => 'js-extern', 'rel' => 'nofollow'))
            ];

            $sender->Data['Methods'][] = $method;
        }
    }

    /**
     * Override the sign in if Google+ is the default signin method.
     *
     * @param EntryController $sender
     * @param array $args
     */
    public function entryController_overrideSignIn_handler($sender, $args) {
        if (valr('DefaultProvider.AuthenticationKey', $args) !== self::PROVIDER_KEY || !$this->isConfigured()) {
            return;
        }

        $url = $this->authorizeUri(['target' => $args['Target']]);
        $args['DefaultProvider']['SignInUrl'] = $url;
    }

    /**
     * Endpoint to share to Google+.
     *
     * I'm sure someone out there does this. Somewhere. Probably alone.
     *
     * @param PostController $sender
     * @param type $recordType
     * @param type $iD
     * @throws type
     */
    public function postController_googlePlus_create($sender, $recordType, $iD) {
        $row = getRecord($recordType, $iD);
        if ($row) {
            $message = sliceParagraph(Gdn_Format::plainText($row['Body'], $row['Format']), 160);

            $get = [
                'url' => $row['ShareUrl']
            ];

            $url = 'https://plus.google.com/share?'.http_build_query($get);
            redirectTo($url, 302, false);
        }

        $sender->render('Blank', 'Utility', 'Dashboard');
    }

    /**
     * Endpoint to configure this addon.
     *
     * @param $sender
     * @param $args
     */
    public function socialController_googlePlus_create($sender, $args) {
        $sender->permission('Garden.Settings.Manage');

        $conf = new ConfigurationModule($sender);
        $conf->initialize([
            'Plugins.GooglePlus.ClientID' => ['LabelCode' => 'Client ID'],
            'Plugins.GooglePlus.Secret' => ['LabelCode' => 'Client secret'],
            'Plugins.GooglePlus.SocialReactions' => ['Control' => 'checkbox', 'Default' => true],
            'Plugins.GooglePlus.SocialSharing' => ['Control' => 'checkbox', 'Default' => true],
            'Plugins.GooglePlus.UseAvatars' => ['Control' => 'checkbox', 'Default' => true],
            'Plugins.GooglePlus.Default' => ['Control' => 'checkbox', 'LabelCode' => 'Make this connection your default signin method.']
        ]);

        if (Gdn::request()->isAuthenticatedPostBack()) {
            $model = new Gdn_AuthenticationProviderModel();
            $model->save(['AuthenticationKey' => self::PROVIDER_KEY, 'IsDefault' => c('Plugins.GooglePlus.Default')]);
        }

        $sender->setHighlightRoute('dashboard/social');
        $sender->setData('Title', sprintf(t('%s Settings'), 'Google+'));
        $sender->ConfigurationModule = $conf;
        $sender->render('Settings', '', 'plugins/GooglePlus');
    }
}
