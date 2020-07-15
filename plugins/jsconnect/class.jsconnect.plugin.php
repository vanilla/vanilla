<?php
/**
 * @copyright 2008-2016 Vanilla Forums, Inc.
 * @license GNU GPLv2 http://www.opensource.org/licenses/gpl-2.0.php
 * @since 1.1.2b Fixed ConnectUrl to examine given url for existing querystring params and concatenate query params appropriately.
 */

use Vanilla\JsConnect\JsConnect;
use Vanilla\JsConnect\JsConnectJSONP;
use Vanilla\JsConnect\JsConnectServer;
use Vanilla\Utility\CamelCaseScheme;

/**
 * Class JsConnectPlugin
 */
class JsConnectPlugin extends SSOAddon {

    const DEFAULT_SECRET_LENGTH = 64;

    const NONCE_EXPIRATION = 5 * 60;
    const PROTOCOL_V3 = 'v3';
    const PROTOCOL_V2 = 'v2';

    const FIELD_ACTION = 'act';
    const ACTION_SIGN_IN = 'signin';
    const ACTION_REGISTER = 'register';
    const FIELD_PROVIDER_CLIENT_ID = 'AuthenticationKey';
    private const AUTHENTICATION_SCHEME = 'jsconnect';

    /**
     * @var \Garden\Web\Cookie
     */
    private $cookie;

    /** @var UserModel */
    private $userModel;

    /**
     * JsConnectPlugin constructor.
     *
     * @param \Garden\Web\Cookie $cookie
     * @param UserModel $userModel
     */
    public function __construct(\Garden\Web\Cookie $cookie, UserModel $userModel) {
        parent::__construct();
        $this->cookie = $cookie;
        $this->userModel = $userModel;
    }

    /**
     * Create a URL that directs the browser to the V3 redirect to create the JWT.
     *
     * @param array $provider JSConnect settings.
     * @return string URL with the target.
     */
    private static function entryRedirectURL(array $provider): string {
        $target = Gdn::request()->get('Target', Gdn::request()->get('Target'));
        if (!$target) {
            $target = '/' . ltrim(Gdn::request()->path());
        }
        if (stringBeginsWith($target, '/entry/signin')) {
            $target = '/';
        }

        $redictPath = '/entry/jsconnect-redirect';
        return $redictPath . '?' . http_build_query([
                'client_id' => $provider[self::FIELD_PROVIDER_CLIENT_ID],
                'target' => $target
            ]);
    }

    /**
     * Get the AuthenticationSchemeAlias value.
     *
     * @return string The AuthenticationSchemeAlias.
     */
    protected function getAuthenticationSchemeAlias(): string {
        return self::AUTHENTICATION_SCHEME;
    }

    /**
     * Add an element to the controls collection. Used to render settings forms.
     *
     * @param string $key
     * @param array $item
     */
    public function addControl($key, $item) {
        // Make sure this isn't called before it's ready.
        if (!isset(Gdn::controller()->Data['_Controls'])) {
            throw new Exception("You can't add a control before the controls collection has been initialized.", 500);
        }

        Gdn::controller()->Data['_Controls'][$key] = $item;
    }

    /**
     * Return a string with all of the connect buttons.
     *
     * @param array $options
     * @return string
     */
    public static function allConnectButtons($options = []) {
        $result = '';

        $providers = self::getAllProviders();
        foreach ($providers as $provider) {
            $result .= self::connectButton($provider, $options);
        }
        return $result;
    }

    /**
     * Get the button used for signing in.
     *
     * @param array|string $provider
     * @param array $options
     * @return string
     */
    public static function connectButton($provider, $options = []) {
        if (!is_array($provider)) {
            $provider = self::getProvider($provider);
        }

        if ($provider['Protocol'] === self::PROTOCOL_V3) {
            $result = self::connectButtonV3($provider);
            return $result;
        }


        $url = htmlspecialchars(self::connectUrl($provider));
        $data = $provider;

        $target = Gdn::request()->get('Target');
        if (!$target) {
            $target = '/'.ltrim(Gdn::request()->path());
        }

        if (stringBeginsWith($target, '/entry/signin')) {
            $target = '/';
        }

        $connectQuery = ['client_id' => $provider[self::FIELD_PROVIDER_CLIENT_ID], 'Target' => $target];
        $data['Target'] = urlencode(url('entry/jsconnect', true).'?'.http_build_query($connectQuery));
        $data['Redirect'] = $data['target'] = $data['redirect'] = $data['Target'];

        $signInUrl = formatString(val('SignInUrl', $provider, ''), $data);
        $registerUrl = formatString(val('RegisterUrl', $provider, ''), $data);

        if ($registerUrl && !val('NoRegister', $options)) {
            $registerLink = ' '.anchor(sprintf(t('Register with %s', 'Register'), $provider['Name']), $registerUrl, 'Button RegisterLink');
        } else {
            $registerLink = '';
        }

        if (val('NoConnectLabel', $options)) {
            $connectLabel = '';
        } else {
            $connectLabel = '<span class="Username"></span><div class="ConnectLabel TextColor">'.
                sprintf(t('Sign In with %s'), $provider['Name']).
                '</div>';
        }

        if (!c('Plugins.JsConnect.NoGuestCheck')) {
            $result = '<div style="display: none" class="JsConnect-Container ConnectButton Small UserInfo" rel="'.$url.'">';

            if (!val('IsDefault', $provider)) {
                $result .= '<div class="JsConnect-Guest">'.
                    anchor(sprintf(t('Sign In with %s'), $provider['Name']), $signInUrl, 'Button Primary SignInLink').
                    $registerLink.
                    '</div>';
            }
            $result .=
                '<div class="JsConnect-Connect"><a class="ConnectLink">'
                .img('https://images.v-cdn.net/usericon_50.png', ['class' => 'ProfilePhotoSmall UserPhoto'])
                .$connectLabel
                .'</a></div>';

            $result .= '</div>';
        } else {
            if (!val('IsDefault', $provider)) {
                $result = '<div class="JsConnect-Guest">'.
                    anchor(sprintf(t('Sign In with %s'), $provider['Name']), $signInUrl, 'Button Primary SignInLink').
                    $registerLink.
                    '</div>';
            }
        }

        return $result;
    }

    /**
     * Generate a V3 version of the JsConnect button.
     *
     * @param array $provider
     * @return string
     */
    private static function connectButtonV3(array $provider): string {
        $target = Gdn::request()->get('Target', Gdn::request()->get('target'));
        if (!$target) {
            $target = '/'.ltrim(Gdn::request()->path());
        }
        if (stringBeginsWith($target, '/entry/signin')) {
            $target = '/';
        }

        $redictPath = '/entry/jsconnect-redirect'.'?'.http_build_query([
            'client_id' => $provider[self::FIELD_PROVIDER_CLIENT_ID],
            'target' => $target
        ]);

        $result = '<div class="JsConnect-Guest">'.
            anchor(
                sprintf(t('Sign In with %s'), $provider['Name']),
                $redictPath,
                'Button Primary SignInLink'
            ).
            '</div>';

        return $result;
    }

    /**
     * Calculat the querystring for connecting.
     *
     * @param array $provider
     * @param ?string $target
     * @return array
     */
    protected static function connectQueryString($provider, $target = null) {
        if ($target === null) {
            $target = Gdn::request()->get('Target');
            if (!$target) {
                $target = '/'.ltrim(Gdn::request()->path(), '/');
            }
        }

        if (stringBeginsWith($target, '/entry/signin')) {
            $target = '/';
        }

        $qs = ['client_id' => $provider[self::FIELD_PROVIDER_CLIENT_ID], 'Target' => $target];
        return $qs;
    }

    /**
     * Calculate v2 connect URL.
     *
     * @param string|array $Provider
     * @param bool $secure
     * @param bool $Callback
     * @return bool|string
     * @deprecated
     */
    public static function connectUrl($Provider, $secure = false, $Callback = true) {
        if (!is_array($Provider)) {
            $Provider = self::getProvider($Provider);
        }

        if (!is_array($Provider)) {
            return false;
        }

        $Url = $Provider['AuthenticateUrl'];
        $query = ['client_id' => $Provider[self::FIELD_PROVIDER_CLIENT_ID]];

        if ($secure) {
            $nonceModel = new UserAuthenticationNonceModel();
            $nonce = uniqid('jsconnect_', true);
            $nonceModel->insert(['Nonce' => $nonce, 'Token' => 'jsConnect']);

            $query['ip'] = Gdn::request()->ipAddress();
            $query['nonce'] = $nonce;
            $query['timestamp'] = JsConnectJSONP::timestamp();

            // v2 compatible sig
            $query['sig'] = JsConnectJSONP::hash(
                $query['ip'].$query['nonce'].$query['timestamp'].$Provider['AssociationSecret'],
                val('HashType', $Provider)
            );
            // v1 compatible sig
            $query['signature'] = jsHash(
                $query['timestamp'].$Provider['AssociationSecret'],
                val('HashType', $Provider)
            );
        }

        if (($Target = Gdn::request()->get('Target'))) {
            $query['Target'] = $Target;
        } else {
            $query['Target'] = '/'.ltrim(Gdn::request()->path(), '/');
        }

        if (stringBeginsWith($query['Target'], '/entry/signin')) {
            $query['Target'] = '/';
        }

        $Result = $Url.(strpos($Url, '?') === false ? '?' : '&').'v=2&'.http_build_query($query);
        if ($Callback) {
            $Result .= '&callback=?';
        }

        return $Result;
    }

    /**
     * Convenience method for functional clarity.
     *
     * @return array|mixed
     */
    public static function getAllProviders() {
        return self::getProvider();
    }

    /**
     * Generate cache key for get provider info sql query
     *
     * @param int|null $client_id
     * @return string
     */
    public static function getProviderSqlCacheKey($client_id) {
        $key = 'getProvider:';
        if ($client_id !== null) {
            $key .= 'AuthenticationKey:'.$client_id;
        } else {
            $key .= 'AuthenticationSchemeAlias:jsconnect';
        }
        return $key;
    }

    /**
     * Ge the provider array for a client ID.
     *
     * @param ?string $client_id
     * @return array|mixed
     */
    public static function getProvider($client_id = null) {
        if ($client_id !== null) {
            $where = [self::FIELD_PROVIDER_CLIENT_ID => $client_id];
        } else {
            $where = ['AuthenticationSchemeAlias' => self::AUTHENTICATION_SCHEME];
        }

        $sql = Gdn::sql();
        if ($client_id !== null) {
            $sqlCacheKey = self::getProviderSqlCacheKey($client_id);
            $sql->cache($sqlCacheKey, null, [Gdn_Cache::FEATURE_EXPIRY => 900]);
        }
        $result = $sql->getWhere('UserAuthenticationProvider', $where)->resultArray();

        foreach ($result as &$row) {
            $attributes = dbdecode($row['Attributes']);
            if (is_array($attributes)) {
                $row = array_merge($attributes, $row);
            }
            $row += [
                'Protocol' => self::PROTOCOL_V2,
            ];
        }

        if ($client_id) {
            return val(0, $result, false);
        } else {
            return $result;
        }
    }

    /**
     * Gets the full sign in url with the jsConnect redirect added.
     *
     * @param array|string $provider The authentication provider or its ID.
     * @param string|null $target The url to redirect to after signing in or null to guess the target.
     * @return string Returns the sign in url.
     * @deprecated
     */
    public static function getSignInUrl($provider, $target = null) {
        if (!is_array($provider)) {
            $provider = static::getProvider($provider);
        }

        $signInUrl = val('SignInUrl', $provider);
        if (!$signInUrl) {
            return '';
        }

        $qs = static::connectQueryString($provider, $target);
        $finalTarget = urlencode(url('/entry/jsconnect', true).'?'.http_build_query($qs));

        $signInUrl = str_ireplace(
            ['{target}', '{redirect}'],
            $finalTarget,
            $signInUrl
        );

        return $signInUrl;
    }

    /**
     * Gets the full sign in url with the jsConnect redirect added.
     *
     * @param array|int $provider The authentication provider or its ID.
     * @param string|null $target The url to redirect to after signing in or null to guess the target.
     * @return string Returns the sign in url.
     * @deprecated
     */
    public static function getRegisterUrl($provider, $target = null) {
        if (!is_array($provider)) {
            $provider = static::getProvider($provider);
        }

        $registerUrl = val('RegisterUrl', $provider);
        if (!$registerUrl) {
            return '';
        }

        $qs = static::connectQueryString($provider, $target);
        $finalTarget = urlencode(url('/entry/jsconnect', true).'?'.http_build_query($qs));

        $registerUrl = str_ireplace(
            ['{target}', '{redirect}'],
            $finalTarget,
            $registerUrl
        );

        return $registerUrl;
    }


    /// EVENT HANDLERS ///


    /**
     * Calculate the final sign in and register urls for jsConnect.
     *
     * @param AuthenticationProviderModel $sender Not used.
     * @param array $args Contains the provider and
     * @deprecated
     */
    public function authenticationProviderModel_calculateJsConnect_handler($sender, $args) {
        $provider =& $args['Provider'];
        $target = val('Target', $args, null);

        $provider['SignInUrlFinal'] = static::getSignInUrl($provider, $target);
        $provider['RegisterUrlFinal'] = static::getRegisterUrl($provider, $target);
    }

    /**
     * If this is the default provider and V3, make sure it goes through the redirect URL.
     *
     * @param EntryController $sender
     * @param array $args
     */
    public function entryController_overrideSignIn_handler($sender, $args) {
        $protocol = $args['DefaultProvider']['Protocol'] ?? null;
        if ($protocol === self::PROTOCOL_V3) {
            $args['DefaultProvider']['SignInUrl'] = static::entryRedirectURL($args['DefaultProvider']);
        }
    }
    /**
     * Add jsConnect buttons to the page.
     *
     * @param Gdn_Controller $sender
     * @param array $args
     */
    public function base_beforeSignInButton_handler($sender, $args) {
        $providers = self::getAllProviders();
        foreach ($providers as $provider) {
            if (empty($provider['IsDefault'])) {
                echo "\n" . self::connectButton($provider);
            }
        }
    }

    /**
     * Add jsConnect buttons to the page.
     *
     * @param Gdn_Controller $sender
     */
    public function base_beforeSignInLink_handler($sender) {
        if (Gdn::session()->isValid()) {
            return;
        }

        $providers = self::getAllProviders();
        foreach ($providers as $provider) {
            echo "\n".wrap(self::connectButton($provider, ['NoRegister' => true, 'NoConnectLabel' => true]), 'li', ['class' => 'Connect jsConnect']);
        }
    }

    /**
     * Handle the jsConnect SSO data.
     *
     * @param EntryController $sender
     * @param array $Args
     */
    public function base_connectData_handler($sender, $Args) {
        if (val(0, $Args) != 'jsconnect') {
            return;
        }

        $form = $sender->Form;
        $fragment = $form->getFormValue('fragment');
        if (!empty($fragment)) {
            $this->handleConnectDataV3($sender, $form);
        } else {
            $this->handleConnectDataV2($sender, $form);
        }
    }

    /**
     * Add the jsConnect settings link.
     *
     * @param Gdn_Controller $sender
     */
    public function base_getAppSettingsMenuItems_handler($sender) {
        $menu = $sender->EventArguments['SideMenu'];
        $menu->addItem('Connect', t('Connections'));
        $menu->addLink('Connect', 'jsConnect', '/settings/jsconnect', 'Garden.Settings.Manage', ['class' => 'nav-jsconnect']);
    }

    /**
     * Add jsConnect specific CSS and JavaScript.
     *
     * @param Gdn_Controller $sender
     * @param array $args
     */
    public function base_render_before($sender, $args) {
        if (!Gdn::session()->UserID) {
            $sender->addJSFile('jsconnect.js', 'plugins/jsconnect');
            $sender->addCssFile('jsconnect.css', 'plugins/jsconnect');
        } else {
            // Unset the nonce!
            Gdn::session()->stash('jsConnectNonce');
        }
    }

    /**
     * Generate a test embedd SSO token.
     *
     * @param string $client_id
     * @param array $user
     * @return string
     */
    public function generateTestEmbed(string $client_id = '', array $user = []) : string {
        $provider = self::getProvider($client_id);
        $secret = $provider['AssociationSecret'] ?? '';
        if (!isset($user['client_id'])) {
            $user['client_id'] = $client_id;
        }
        $string = base64_encode(json_encode($user));
        $timestamp = time();
        $hash = hash_hmac('sha1', "{$string} {$timestamp}", $secret);
        $result = "{$string} {$hash} {$timestamp} hmacsha1";
        return $result;
    }

    /**
     * An intermediate page for jsConnect that checks SSO against and then posts the information to /entry/connect.
     *
     * @param EntryController $sender
     * @param string $action A specific action. It can be one of the following:
     *
     * - blank: The default action.
     * - guest: There is no user signed in.
     * -
     * @param string $target The url to redirect to after a successful connect.
     * @throws /Exception Throws an exception when the jsConnect provider is not found.
     */
    public function entryController_jsconnect_create($sender, $action = '', $target = '') {
        $sender->setHeader('Cache-Control', \Vanilla\Web\CacheControlMiddleware::NO_CACHE);


        $clientID = $sender->setData('client_id', $sender->Request->get('client_id', 0));
        if (!empty($clientID)) {
            $provider = self::getProvider($clientID);
            $protocol = $provider['Protocol'] ?? self::PROTOCOL_V2;

            $sender->addDefinition('jsconnect', [
                'protocol' => $protocol,
                'authenticateUrl' => url('/entry/jsconnect-redirect') . '?' . http_build_query([
                        'client_id' => $clientID,
                        'target' => $target,
                    ])
            ]);

            if ($protocol !== self::PROTOCOL_V3) {
                $this->entryJsConnectV2($sender, $action, $target);
            } else {
                $this->entryJsConnectV3($sender, $target);
            }
        } else {
            $sender->addDefinition('jsconnect', [
                'protocol' => self::PROTOCOL_V3,
                // Kludge, but we can't know if there is a fragment.
                'authenticateUrl' => url('/').'?invalidJsConnect=1',
            ]);

            // This might be a v3 return with the hash.
            $this->entryJsConnectV3($sender);
        }
    }

    /**
     * Implementation of `/entry/jsconnect-redirect` for redirecting to v3 protocol authentication pages.
     *
     * @param EntryController $sender
     * @param string $client_id
     * @param string $target
     * @param string $action
     */
    public function entryController_jsconnectRedirect_create($sender, $client_id = '', $target = '', $action = self::ACTION_SIGN_IN) {
        $provider = self::getProvider($client_id);
        if (empty($provider)) {
            throw notFoundException("Provider");
        }

        $sender->setHeader('Cache-Control', \Vanilla\Web\CacheControlMiddleware::NO_CACHE);
        switch ($provider['Protocol'] ?? self::PROTOCOL_V2) {
            case self::PROTOCOL_V3:
                $state = [
                    JsConnectServer::FIELD_TARGET => $target,
                    self::FIELD_ACTION => $action,
                ];
                // Check to see if a cookie has already been issued. We do this mainly for private communities or
                // multiple browser tabs where several requests may be made. If we don't re-use cookies then there will
                // be race conditions. However, we may also see an old cookie stick around which will cause people to
                // get expired SSO token errors if their cookies hang around. This big-ass comment is mainly to explain
                // this situation to the next dev.
                if ($csrfToken = $this->cookie->get($this->getCSRFCookieName(), false)) {
                    $state[JsConnectServer::FIELD_COOKIE] = $csrfToken;
                }

                $jsc = $this->createJsConnectFromProvider($provider);
                try {
                    [$requestUrl, $cookie] = $jsc->generateRequest($state);
                    $this->cookie->set($this->getCSRFCookieName(), $cookie);
                    redirectTo($requestUrl, 302, false);
                } catch (\Vanilla\JsConnect\Exceptions\InvalidValueException $ex) {
                    $this->cookie->delete($this->getCSRFCookieName());
                    throw new \Gdn_UserException($ex->getMessage());
                }
                break;
            case self::PROTOCOL_V2:
            default:
                throw new \Gdn_UserException("This page does not support the jsConnect v2 protocol.");
                break;
        }
    }

    /**
     * Return sign in button information for jsConnect.
     *
     * @param Gdn_Controller $sender
     * @param array $args
     */
    public function entryController_signIn_handler($sender, $args) {
        $providers = self::getAllProviders();

        foreach ($providers as $provider) {
            $method = [
                'Name' => $provider['Name'],
                'SignInHtml' => self::connectButton($provider)
            ];

            $sender->Data['Methods'][] = $method;
        }
    }

    /**
     * Handle connecting from the profile.
     *
     * @param Gdn_Controller $sender
     * @param array $args
     */
    public function profileController_jsConnect_create($sender, $args = []) {
        $client_id = $sender->Request->get('client_id', 0);

        $provider = self::getProvider($client_id);

        $client_id = val(self::FIELD_PROVIDER_CLIENT_ID, $provider);
        $secret = val('AssociationSecret', $provider);

        if (Gdn::session()->isValid()) {
            $user = arrayTranslate((array)Gdn::session()->User, ['UserID' => 'UniqueID', 'Name', 'Email', 'PhotoUrl', 'DateOfBirth', 'Gender']);

            // Grab the user's roles.
            $roles = Gdn::userModel()->getRoles(Gdn::session()->UserID)->resultArray();
            $roles = array_column($roles, 'Name');
            $user['roles'] = '';
            if (is_array($roles) && sizeof($roles)) {
                $user['roles'] = implode(',', $roles);
            }

            if (!$user['PhotoUrl'] && function_exists('UserPhotoDefaultUrl')) {
                $user['PhotoUrl'] = url(userPhotoDefaultUrl(Gdn::session()->User), true);
            }
        } else {
            $user = [];
        }

        ob_clean();
        writeJsConnect($user, $sender->Request->get(), $client_id, $secret, val('HashType', $provider, true));
        exit();
    }

    /**
     * Handle the /sso endpoint.
     *
     * @param RootController $sender
     * @param array $args
     */
    public function rootController_sso_handler($sender, $args) {
        $provider = $args['DefaultProvider'];
        if (val('AuthenticationSchemeAlias', $provider) !== 'jsconnect') {
            return;
        }

        // The default provider is jsconnect so let's redispatch there.
        $get = [
            'client_id' => val(self::FIELD_PROVIDER_CLIENT_ID, $provider),
            'target' => val('Target', $args, '/')
        ];
        $url = '/entry/jsconnect?'.http_build_query($get);
        Gdn::request()->pathAndQuery($url);
        Gdn::dispatcher()->dispatch();
        $args['Handled'] = true;
    }

    /**
     * The /settings/jsconnect page.
     *
     * @param SettingsController $sender
     * @param array $args
     */
    public function settingsController_jsconnect_create($sender, $args = []) {
        $sender->addJsFile('jsconnect-settings.js', 'plugins/jsconnect');
        $sender->permission('Garden.Settings.Manage');
        $sender->addSideMenu();

        switch (strtolower(val(0, $args))) {
            case 'addedit':
                $this->settingsAddEdit($sender, $args);
                break;
            case 'delete':
                $this->settingsDelete($sender, $args);
                break;
            case 'test':
                $this->settingsTest($sender);
                break;
            case 'test-verify':
                $this->settingsTestVerify($sender);
                break;
            default:
                $this->settingsIndex($sender, $args);
                break;
        }
    }

    /**
     * The /settings/jsconnect/edit page.
     *
     * @param SettingsController $sender
     * @param array $args
     */
    protected function settingsAddEdit($sender, $args) {
        $sender->addJsFile('jsconnect-settings.js', 'plugins/jsconnect');

        $client_id = $sender->Request->get('client_id');

        Gdn::locale()->setTranslation(self::FIELD_PROVIDER_CLIENT_ID, 'Client ID');
        Gdn::locale()->setTranslation('AssociationSecret', 'Secret');
        Gdn::locale()->setTranslation('AuthenticateUrl', 'Authentication Url');

        /* @var Gdn_Form $form */
        $form = $sender->Form;
        $model = new Gdn_AuthenticationProviderModel();
        $form->setModel($model);
        $generate = false;

        if ($form->authenticatedPostBack()) {
            if ($form->getFormValue('Generate') || $sender->Request->post('Generate')) {
                $generate = true;
                $key = random_int(1000000, 9999999);
                $secret = betterRandomString(self::DEFAULT_SECRET_LENGTH, "Aa0");
                $sender->setFormSaved(false);
            } else {
                $form->validateRule(self::FIELD_PROVIDER_CLIENT_ID, 'ValidateRequired');
                $form->validateRule(
                    self::FIELD_PROVIDER_CLIENT_ID,
                    'regex:`^[a-z0-9_-]+$`i',
                    t('The client id must contain only letters, numbers and dashes.')
                );
                $form->validateRule('AssociationSecret', 'ValidateRequired');
                $form->validateRule('AuthenticateUrl', 'ValidateRequired');

                $form->setFormValue('AuthenticationSchemeAlias', 'jsconnect');

                if ($form->save(['ID' => $client_id])) {
                    Gdn::cache()->remove(self::getProviderSqlCacheKey($client_id));
                    Gdn::cache()->remove(self::getProviderSqlCacheKey(null));
                    $sender->setRedirectTo('/settings/jsconnect');
                }
            }
        } else {
            if ($client_id) {
                $provider = self::getProvider($client_id);
                $provider += [
                    'Protocol' => self::PROTOCOL_V2,
                    'Trusted' => 1
                ];
                $sender->setData('warnings', $this->getProviderWarnings($provider));
            } else {
                $provider = [];
            }
            $form->setData($provider);
        }

        // Set up the form controls for editing the connection.
        $hashTypes = hash_algos();
        $hashTypes = array_combine($hashTypes, $hashTypes);

        $controls = [
            self::FIELD_PROVIDER_CLIENT_ID => [
                'LabelCode' => 'Client ID',
                'Description' => t(
                    'The client ID uniquely identifies the site.',
                    'The client ID uniquely identifies the site. You can generate a new ID with the button at the bottom of this page.'
                )
            ],
            'AssociationSecret' => [
                'LabelCode' => 'Secret',
                'Description' => t(
                    'The secret secures the sign in process.',
                    'The secret secures the sign in process. Do <b>NOT</b> give the secret out to anyone.'
                )
            ],
            'Name' => [
                'LabelCode' => 'Site Name',
                'Description' => t('Enter a short name for the site.', 'Enter a short name for the site. This is displayed on the signin buttons.')
            ],
            'AuthenticateUrl' => [
                'LabelCode' => 'Authentication URL',
                'Description' => t('The location of the JSONP formatted authentication data.')
            ],
            'SignInUrl' => [
                'LabelCode' => 'Sign In URL',
                'Description' => t('The url that users use to sign in.').' '.t('Use {target} as placeholder to specify a redirect to where the user iniciated the signin.')
            ],
            'RegisterUrl' => [
                'LabelCode' => 'Registration URL',
                'Description' => t('The url that users use to register for a new account.')
            ],
            'SignOutUrl' => [
                'LabelCode' => 'Sign Out URL',
                'Description' => t('The url that users use to sign out of your site.')
            ],
            'Trusted' => [
                'Control' => 'toggle',
                'LabelCode' => 'This is trusted connection and can sync roles & permissions.'
            ],
            'IsDefault' => [
                'Control' => 'toggle',
                'LabelCode' => 'Make this connection your default signin method.'
            ],
            'Advanced' => [
                'Control' => 'callback',
                'Callback' => function ($form) {
                    return subheading(t('Advanced'));
                }
            ],
            'Protocol' => [
                'Control' => 'dropdown',
                'Description' => t(
                    'Choose the protocol version.',
                    'The protocol version must match your client library. You should always choose the most recent protocol if you can.'
                ),
                'Items' => [
                    self::PROTOCOL_V3 => t('Version 3 (recommend)'),
                    self::PROTOCOL_V2 => t('Version 2'),
                ],
            ],
            'HashType' => [
                'Control' => 'dropdown',
                'LabelCode' => 'Hash Algorithm',
                'Items' => $hashTypes,
                'Description' =>
                    t(
                        'You can select a custom hash algorithm to sign your requests.',
                        "You can select a custom hash algorithm to sign your requests. The hash algorithm must also be used in your client library."
                    ).' '.
                    t('Choose sha256 if you\'re not sure what to choose.')
                ,
                'Options' => ['Default' => 'md5']
            ],
            'TestMode' => ['Control' => 'toggle', 'LabelCode' => 'This connection is in test-mode.']
        ];
        $sender->setData('_Controls', $controls);
        $sender->setData('Title', sprintf(t($client_id ? 'Edit %s' : 'Add %s'), t('Connection')));

        // Throw a render event as this plugin so that handlers can call our methods.
        Gdn::pluginManager()->callEventHandlers($this, __CLASS__, 'addedit', 'render');
        if ($generate && $sender->deliveryType() === DELIVERY_TYPE_VIEW) {
            $sender->setJson(self::FIELD_PROVIDER_CLIENT_ID, $key);
            $sender->setJson('AssociationSecret', $secret);
            $sender->render('Blank', 'Utility', 'Dashboard');
        } else {
            $sender->render('Settings_AddEdit', '', 'plugins/jsconnect');
        }
    }

    /**
     * The /settings/jsconnect/delete page.
     *
     * @param SettingsController $sender
     * @param array $args
     */
    public function settingsDelete($sender, $args) {
        $client_id = $sender->Request->get('client_id');
        if ($sender->Form->authenticatedPostBack()) {
            $model = new Gdn_AuthenticationProviderModel();
            $model->delete([self::FIELD_PROVIDER_CLIENT_ID => $client_id]);
            $sender->setRedirectTo('/settings/jsconnect');
            $sender->render('Blank', 'Utility', 'Dashboard');
        }
    }

    /**
     * The /settings/jsconnect page.
     *
     * @param SettingsController $sender
     * @param array $args
     */
    protected function settingsIndex($sender, $args) {
        $validation = new Gdn_Validation();
        $configurationModel = new Gdn_ConfigurationModel($validation);
        $configurationModel->setField([
            'Garden.Registration.AutoConnect',
            'Garden.SignIn.Popup'
        ]);
        $sender->Form->setModel($configurationModel);
        if ($sender->Form->authenticatedPostback()) {
            if ($sender->Form->save() !== false) {
                $sender->informMessage(t('Your settings have been saved.'));
            }
        } else {
            $sender->Form->setData($configurationModel->Data);
        }

        $providers = self::getProvider();
        $hasWarnings = false;
        foreach ($providers as &$provider) {
            $warnings = $this->getProviderWarnings($provider);
            $hasWarnings |= $provider['hasWarnings'] = !empty($warnings);
        }
        $sender->setData('hasWarnings', $hasWarnings);

        $sender->setData('Providers', $providers);
        $sender->render('Settings', '', 'plugins/jsconnect');
    }

    /**
     * Redirect to the appropriate authenticate URL for the purposes of testing.
     *
     * @param SettingsController $sender
     */
    private function settingsTest(SettingsController $sender): void {
        $clientID = $sender->Request->get('client_id', '');
        $provider = self::getProvider($clientID);

        switch ($provider['Protocol'] ?? self::PROTOCOL_V2) {
            case self::PROTOCOL_V3:
                $jsc = $this->createJsConnectFromProvider($provider);
                $jsc->setRedirectUrl(url('/settings/jsconnect/test-verify', true));
                [$url, $cookie] = $jsc->generateRequest();
                $this->cookie->set($this->getCSRFCookieName(), $cookie);
                redirectTo($url, 302, false);
                break;
            case self::PROTOCOL_V2:
            default:
                redirectTo(str_replace('=?', '=test', self::connectUrl($provider, true)), 302, false);
                break;
        }
    }

    /**
     * Verify the results of the SSO test.
     *
     * @param SettingsController $sender
     */
    private function settingsTestVerify(SettingsController $sender): void {
        if ($sender->Request->isAuthenticatedPostBack(true)) {
            $fragment = $sender->Request->post('fragment', '');
            parse_str(ltrim($fragment, '#'), $args);
            if (empty($args) || empty($args['jwt'])) {
                $sender->Form->addError("The JWT token was not found.");
            } else {
                try {
                    $jsc = $this->createJsConnectFromJWT($args['jwt']);

                    [$user, $state, $payload] = $jsc->validateResponse(
                        $args['jwt'],
                        $this->cookie->get($this->getCSRFCookieName())
                    );
                    $this->cookie->delete($this->getCSRFCookieName());

                    $tokenDetails = [
                        'client' => $payload['v'],
                        'issued' => $this->formatDate($payload['iat'] ?? null),
                        'expires' => $this->formatDate($payload['exp'] ?? null),
                    ];

                    $sender->setData('tokenDetails', $tokenDetails);
                    $sender->setData('message', 'The SSO test was successful.');
                    $sender->setData('messageClass', 'alert-success');
                    $sender->setData('user', $user);

                    if (empty($user)) {
                        $header = JsConnect::decodeJWTHeader($args['jwt']);
                        $provider = self::getProvider($header[JsConnect::FIELD_CLIENT_ID] ?? '');

                        $signInUrl = $this->replaceUrlTarget(
                            $provider["SignInUrl"] ?? "#",
                            url('/settings/jsconnect/test', true).'?'.http_build_query(['client_id' => $header[JsConnect::FIELD_CLIENT_ID] ?? ''])
                        );

                        // Add the sign in URL redirect here.
                        $sender->setData('signinUrl', $signInUrl);
                    } else {
                        $userFields = $this->userFields();

                        // Check to see if the user has appropriate fields. Map known fields and unknown fields.
                        $standardFields = [
                            JsConnect::FIELD_UNIQUE_ID,
                            JsConnect::FIELD_ROLES,
                        ];
                        $fields = array_merge($standardFields, $userFields);
                        $known = [];
                        $unknown = [];
                        foreach ($user as $key => $value) {
                            $lkey = strtolower($key);

                            if (in_array($lkey, $fields)) {
                                $known[$key] = $value;
                            } else {
                                $unknown[$key] = $value;
                            }
                        }
                        $sender->setData('known', $known);
                        $sender->setData('unknown', $unknown);
                        $embedToken = $this->generateTestEmbed($jsc->getSigningClientID(), $known);
                        $sender->setData('embedToken', $embedToken);
                    }
                } catch (\Exception $ex) {
                    $sender->Form->addError($ex);
                }
            }
        }
        $sender->Form->addHidden('fragment', '');
        $sender->render('settings_test', '', 'plugins/jsconnect');
    }

    /**
     * Create a `JsConnectServer` from a provider.
     *
     * @param array $provider
     * @return JsConnectServer
     */
    private function createJsConnectFromProvider(array $provider): JsConnectServer {
        $jsc = new JsConnectServer();
        $jsc->setSigningCredentials($provider[self::FIELD_PROVIDER_CLIENT_ID], $provider['AssociationSecret']);
        $jsc->setAuthenticateUrl($provider['AuthenticateUrl']);
        $jsc->setRedirectUrl(url('/entry/jsconnect', true));

        return $jsc;
    }

    /**
     * Create a `JsConnectServer` by looking at the `kid` in a JWT.
     *
     * @param string $jwt
     * @return JsConnectServer
     */
    private function createJsConnectFromJWT(string $jwt): JsConnectServer {
        if (empty($jwt)) {
            throw new \Gdn_UserException("The JWT was not supplied or empty.");
        }

        $header = JsConnect::decodeJWTHeader($jwt);
        $clientID = $header[JsConnect::FIELD_CLIENT_ID];
        if (empty($clientID)) {
            throw new \Gdn_UserException("The kid was not found in the JWT header.", 400);
        }
        $provider = self::getProvider($clientID);
        if (empty($provider)) {
            throw notFoundException("Provider");
        }

        $jsc = $this->createJsConnectFromProvider($provider);
        return $jsc;
    }

    /**
     * Get a list of warnings for a connection.
     *
     * @param array $provider
     * @return array
     */
    private function getProviderWarnings(array $provider): array {
        $r = [];

        if (self::PROTOCOL_V3 !== ($provider['Protocol'] ?? self::PROTOCOL_V2)) {
            $r[] = 'You are using the old version 2 of the protocol. This will not work with many modern browsers. ' .
                'You need to upgrade your client libraries and switch to the new protocol.';
        }
        if (stripos($provider['SignInUrl'], '{target}') === false) {
            $r[] = 'Your sign in URL does not specify a {target}. You have to specify a redirect variable or else your SSO may not work.';
        }
        if (!empty($provider['RegisterUrl']) && stripos($provider['RegisterUrl'], '{target}') === false) {
            $r[] = 'Your register URL dies not specify a {target}. You have to specify a redirect variable or else your SSO may not work.';
        }

        return $r;
    }

    /**
     * @return string
     */
    private function getCSRFCookieName(): string {
        return '-ssostatetoken';
    }

    /**
     * Format a timestamp.
     *
     * @param ?int $timestamp
     * @return string
     */
    private function formatDate($timestamp): string {
        if (!is_int($timestamp)) {
            return 'unknown';
        } else {
            return Gdn_Format::dateFull($timestamp);
        }
    }

    /**
     * Redirect to the client's authenticate page
     *
     * @param array $provider
     * @param array $state
     */
    private function authenticateRedirectV3(array $provider, array $state) {
        $state += [
            JsConnectServer::FIELD_TARGET => '/',
            self::FIELD_ACTION => self::ACTION_SIGN_IN,
        ];

        $jsc = $this->createJsConnectFromProvider($provider);
        [$location, $cookie] = $jsc->generateRequest($state);

        redirectTo($location, 302, false);
    }

    /**
     * Process `/entry/jsconnect` with the old jsConnect protocol.
     *
     * This is the old version of jsConnect that used to be `entryController_jsConnect_create()`.
     *
     * @param EntryController $sender
     * @param string $action
     * @param string $target
     * @deprecated
     */
    private function entryJsConnectV2($sender, $action, $target): void {
        // Clear the nonce from the stash if any!
        Gdn::session()->stash('jsConnectNonce');

        $sender->setData('_NoMessages', true);

        if ($action) {
            if ($action == 'guest') {
                $sender->addDefinition('CheckPopup', true);

                $target = $sender->Form->getFormValue('Target', '/');
                $sender->setRedirectTo($target, false);

                $sender->render('JsConnect', '', 'plugins/jsconnect');
            } else {
                parse_str($sender->Form->getFormValue('JsConnect'), $jsData);

                $error = val('error', $jsData);
                $message = val('message', $jsData);

                if ($error === 'timeout' && !$message) {
                    $message = t('Your sso timed out.', 'Your sso timed out during the request. Please try again.');
                }

                Logger::event('jsconnect_error', Logger::ERROR, 'Displaying Error Page.', ['JsData' => $jsData, 'ErrorMessage' => $message]);
                Gdn::dispatcher()
                    ->passData('Exception', $message ? htmlspecialchars($message) : htmlspecialchars($error))
                    ->dispatch('home/error');
            }
        } else {
            $client_id = $sender->setData('client_id', $sender->Request->get('client_id', 0));
            $provider = self::getProvider($client_id);

            if (empty($provider)) {
                Logger::event('jsconnect_error', Logger::ERROR, 'No Provider Found', ['Client ID' => $client_id]);
                throw notFoundException('Provider');
            }

            $get = arrayTranslate($sender->Request->get(), ['client_id', 'display']);

            $sender->addDefinition('JsAuthenticateUrl', self::connectUrl($provider, true));
            if ($provider['TestMode'] ?? false) {
                $sender->addDefinition('JsConnectTestMode', true);
            }

            if (gdn::config('Garden.PrivateCommunity') && $provider['IsDefault']) {
                // jsconnect.js needs to know to not to redirect if there is an error
                // and PrivateCommunity is on and this is the only log in method,
                // this causes a loop.
                $sender->addDefinition('PrivateCommunity', true);
                $sender->addDefinition('GenericSSOErrorMessage', gdn::translate('An error has occurred, please try again.'));
            }

            $sender->addJsFile('jsconnect.js', 'plugins/jsconnect');
            $sender->setData('Title', t('Connecting...'));
            $sender->Form->Action = url('/entry/connect/jsconnect?' . http_build_query($get));
            $sender->Form->addHidden('JsConnect', '');

            if (!empty($target)) {
                $sender->Form->addHidden('Target', safeURL($target));
            }

            $sender->MasterView = 'empty';
            $sender->render('JsConnect', '', 'plugins/jsconnect');
        }
    }

    /**
     * Process /entry/jsconnect using the new V3 protocol.
     *
     * Note: This work is done in js so this is just a placeholder page.
     *
     * @param EntryController $sender
     * @param string $target
     */
    private function entryJsConnectV3(EntryController $sender, $target = ''): void {
        $sender->addJsFile('jsconnect.js', 'plugins/jsconnect');
        $sender->setData('Title', t('Connecting...'));
        $sender->Form->Action = url('/entry/connect/jsconnect');
        $sender->Form->addHidden('fragment', '');

        if (!empty($target)) {
            $sender->Form->addHidden('Target', safeURL($target));
        }

        $sender->MasterView = 'empty';
        $sender->render('jsconnect', '', 'plugins/jsconnect');
    }

    /**
     * Handle the SSO data.
     *
     * @param \Gdn_Controller $sender
     * @param Gdn_Form $form
     */
    private function handleConnectDataV2($sender, Gdn_Form $form): void {
        $jsConnect = $form->getFormValue('JsConnect', $form->getFormValue('Form/JsConnect'));
        parse_str($jsConnect, $jsData);

        // Make sure the data is valid.
        $version = val('v', $jsData, null);
        $clientID = val('client_id', $jsData, val('clientid', $jsData, $sender->Request->get('client_id')));
        $signature = val('sig', $jsData, val('signature', $jsData, false));
        // This is for logging only.
        $jsDataReceived = $jsData;
        $string = val('sigStr', $jsData, false); // debugging
        unset($jsData['v'], $jsData['client_id'], $jsData['clientid'], $jsData['signature']);
        unset($jsData['sig'], $jsData['sigStr'], $jsData['string']);

        if (!$clientID) {
            Logger::event('jsconnect_error', Logger::ERROR, 'No Client ID Found', ['JsData' => $jsData, 'JsDataReceived' => $jsDataReceived]);
            throw new Gdn_UserException(sprintf(t('ValidateRequired'), 'client_id'), 400);
        }
        $provider = self::getProvider($clientID);
        if (!$provider) {
            Logger::event(
                'jsconnect_error',
                Logger::ERROR,
                'No Provider Found',
                ['JsData' => $jsData, 'JsDataReceived' => $jsDataReceived, 'Client_id' => $clientID]
            );
            throw new Gdn_UserException(sprintf(t('Unknown client: %s.'), htmlspecialchars($clientID)), 400);
        }

        if (!val('TestMode', $provider)) {
            if (!$signature) {
                Logger::event('jsconnect_error', Logger::ERROR, 'No Signature Found', ['JsData' => $jsData, 'JsDataReceived' => $jsDataReceived]);
                throw new Gdn_UserException(sprintf(t('ValidateRequired'), 'signature'), 400);
            }

            if ($version === '2') {
                // Verify IP Address.
                if (Gdn::request()->ipAddress() !== val('ip', $jsData, null)) {
                    Logger::event('jsconnect_error', Logger::ERROR, 'No IP Found', ['JsData' => $jsData, 'JsDataReceived' => $jsDataReceived]);
                    throw new Gdn_UserException(t('IP address invalid.'), 400);
                }

                // Verify nonce.
                $nonceModel = new UserAuthenticationNonceModel();
                $nonce = val('nonce', $jsData, null);
                if ($nonce === null) {
                    Logger::event(
                        'jsconnect_error',
                        Logger::ERROR,
                        'No Nonce Found in JSData',
                        ['JsData' => $jsData, 'JsDataReceived' => $jsDataReceived]
                    );
                    throw new Gdn_UserException(t('Nonce not found.'), 400);
                }

                // Grab the nonce from the session's stash.
                $foundNonce = Gdn::session()->stash('jsConnectNonce', '', false);
                $grabbedFromStash = (bool)$foundNonce;
                if (!$grabbedFromStash) {
                    $foundNonce = $nonceModel->getWhere(['Nonce' => $nonce])->firstRow(DATASET_TYPE_ARRAY);
                }
                if (!$foundNonce) {
                    Logger::event(
                        'jsconnect_error',
                        Logger::ERROR,
                        'No Nonce Found in Stash',
                        ['JsData' => $jsData, 'JsDataReceived' => $jsDataReceived]
                    );
                    throw new Gdn_UserException(t('Nonce not found.'), 400);
                }

                // Clear nonce from the database.
                $nonceModel->delete(['Nonce' => $nonce]);
                if (strtotime($foundNonce['Timestamp']) < time() - self::NONCE_EXPIRATION) {
                    Logger::event(
                        'jsconnect_error',
                        Logger::ERROR,
                        'Timestamp Failed',
                        [
                            'JsData' => $jsData,
                            'JsDataReceived' => $jsDataReceived,
                            'Timestamp' => $foundNonce['Timestamp'],
                            'Time' => time(),
                            'NonceExpiry' => self::NONCE_EXPIRATION
                        ]
                    );
                    throw new Gdn_UserException(t('Nonce expired.'), 400);
                }

                if (!$grabbedFromStash) {
                    // Stash nonce in case we post back!
                    Gdn::session()->stash('jsConnectNonce', $foundNonce);
                }
            }

            // Validate the signature.
            $calculatedSignature = signJsConnect($jsData, $clientID, val('AssociationSecret', $provider), val('HashType', $provider, 'md5'));
            if (hash_equals($signature, $calculatedSignature) === false) {
                Logger::event(
                    'jsconnect_error',
                    Logger::ERROR,
                    'Invalid Signature',
                    [
                        'JsData' => $jsData,
                        'JsDataReceived' => $jsDataReceived,
                        'Signature' => $signature,
                        'HashType' => $provider['HashType'] ?? 'md5'
                    ]
                );
                throw new Gdn_UserException(t("Signature invalid."), 400);
            }
        }
        Logger::event(
            'jsconnect_success',
            Logger::INFO,
            'JSData Passed Validation',
            ['JsData' => $jsData, 'JsDataReceived' => $jsDataReceived, 'HashType' => val('HashType', $provider, 'md5')]
        );
        $this->setSSOData($sender, $form, $jsData, $clientID, $provider);
    }

    /**
     * Handle the SSO data.
     *
     * @param \Gdn_Controller $sender
     * @param Gdn_Form $form
     */
    private function handleConnectDataV3($sender, Gdn_Form $form): void {
        $fragment = $form->getFormValue('fragment');
        $form->addHidden('fragment', $fragment); // for postbacks
        parse_str(ltrim($fragment, '#'), $args);
        $jwt = $args['jwt'] ?? '';

        try {
            if (!is_string($jwt)) {
                throw new \Gdn_UserException("The SSO JWT is not a valid string.");
            }

            $jsc = $this->createJsConnectFromJWT($jwt);
            [$user, $state] = $jsc->validateResponse($jwt, $this->cookie->get($this->getCSRFCookieName()));
            $form->addHidden('Target', $state[JsConnectServer::FIELD_TARGET] ?? '/');
            $form->setFormValue('Target', $state[JsConnectServer::FIELD_TARGET] ?? '/');
        } catch (\Exception $ex) {
            Logger::event('jsconnect_error', Logger::ERROR, $ex->getMessage(), ['jwt' => $jwt, 'protocol' => self::PROTOCOL_V3]);
            throw new \Gdn_UserException($ex->getMessage(), $ex->getCode());
        }

        $header = JsConnect::decodeJWTHeader($jwt);
        $clientID = $header[JsConnect::FIELD_CLIENT_ID];
        $provider = self::getProvider($clientID);

        if (empty($user)) {
            // The user wasn't signed in so we'll need to redirect to whatever page.
            if (($state[self::FIELD_ACTION] ?? '') === self::ACTION_REGISTER) {
                $url = $provider['RegisterUrl'] ?: $provider['SignInUrl'];
            } else {
                $url = $provider['SignInUrl'];
            }
            $target = url('/entry/jsconnect-redirect', true).'?'.
                http_build_query(['client_id' => $clientID, 'target' => $state[JsConnect::FIELD_TARGET] ?? '/']);
            $url = $this->replaceUrlTarget($url, $target);
            $sender->setHeader('Cache-Control', \Vanilla\Web\CacheControlMiddleware::NO_CACHE);
            redirectTo($url, 302, false);
        } else {
            Logger::event('jsconnect_success', Logger::INFO, 'JSData Passed Validation', ['user' => $user, 'protocol' => self::PROTOCOL_V3]);
            $this->setSSOData($sender, $form, $user, $clientID, $provider);
        }
    }

    /**
     * Set the SSO data for the user.
     *
     * Note: This was extraced out of the old `base_connectData()` method and then casened.
     *
     * @param Gdn_Controller $sender
     * @param Gdn_Form $form
     * @param array $user
     * @param string $clientID
     * @param array $provider
     */
    private function setSSOData($sender, Gdn_Form $form, $user, $clientID, array $provider) {
        // Map all of the standard jsConnect data.
        $Map = [
            'uniqueid' => 'UniqueID', JsConnect::FIELD_UNIQUE_ID => 'UniqueID', 'name' => 'Name', 'email' => 'Email',
            'photourl' => 'Photo', 'fullname' => 'FullName', 'roles' => 'Roles'
        ];
        foreach ($Map as $Key => $Value) {
            if (array_key_exists($Key, $user)) {
                $form->setFormValue($Value, $user[$Key]);
            }
        }

        // Now add any extended information that jsConnect might have sent.
        $ExtData = array_diff_key($user, $Map);

        if (class_exists('SimpleAPIPlugin')) {
            SimpleAPIPlugin::translatePost($ExtData, false);
        }

        Gdn::userModel()->defineSchema();
        $Keys = array_keys(Gdn::userModel()->Schema->fields());
        $UserFields = array_change_key_case(array_combine($Keys, $Keys));

        foreach ($ExtData as $Key => $Value) {
            $lkey = strtolower($Key);
            if (array_key_exists($lkey, $UserFields)) {
                $form->setFormValue($UserFields[$lkey], $Value);
            } else {
                $form->setFormValue($Key, $Value);
            }
        }

        $form->setFormValue('Provider', $clientID);
        $form->setFormValue('ProviderName', val('Name', $provider, ''));
        $form->addHidden('JsConnect', $user);

        $sender->setData('ClientID', $clientID);
        $sender->setData('Verified', true);
        $sender->setData('Trusted', val('Trusted', $provider, true)); // this is a trusted connection.
        $sender->setData('SSOUser', $user);
    }

    /**
     * Get available user fields based on the database table schema, removing blacklisted fields.
     *
     * @return array
     */
    private function userFields(): array {
        $scheme = new CamelCaseScheme();
        $schema = $this->userModel->defineSchema()->fields();

        $blacklist = [
            $this->userModel->PrimaryKey,
            "Attributes",
            "HashMethod",
            "Permissions",
            "Password",
            "Preferences",
        ];

        $result = [];
        foreach ($schema as $field => $config) {
            if (in_array($field, $blacklist) ||
                substr($field, 0, 5) === "Count" ||
                substr($field, 0, 4) === "Date" ||
                substr($field, -9) === "IPAddress" ||
                substr($field, -6) === "UserID"
            ) {
                continue;
            }

            $result[] = $scheme->convert($field);
        }

        return $result;
    }

    /**
     * Replace the `{target}` part of a configured client URL.
     *
     * @param string $url
     * @param string $target
     * @return string|string[]
     */
    private function replaceUrlTarget(string $url, string $target) {
        $url = str_ireplace(
            ['{target}', '{redirect}'],
            urlencode($target),
            $url
        );
        return $url;
    }
}
