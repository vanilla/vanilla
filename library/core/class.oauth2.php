<?php
/**
 * @author Patrick Kelly <patrick.k@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 * @package Core
 * @since 2.0
 */

use Garden\Schema\Schema;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\ServerException;
use Psr\Container\ContainerExceptionInterface;
use Vanilla\Logging\AuditLogger;
use Vanilla\Models\UserAuthenticationProviderFragmentSchema;
use Vanilla\Permissions;
use Vanilla\SamlSSO\Events\OAuth2AuditEvent;
use Vanilla\Utility\ArrayUtils;
use Vanilla\Utility\StringUtils;
use Vanilla\Web\CacheControlConstantsInterface;
use Vanilla\Web\CacheControlTrait;

/**
 * Class Gdn_OAuth2
 *
 * Base class to be extended by any plugin that wants to use Oauth2 protocol for SSO.
 *
 * WARNING
 * This is a base class for the purposes of being extended by other plugins.
 * It is not to be instantiated on its own.
 *
 * For most OAuth2 SSO needs the generic plugins/OAuth2/class.Oauth2.plugin.php should
 * be adequate. If not, create a plugin that extends this class, Gdn_OAuth2, and overwrite
 * any of its methods of constants.
 *
 */
class Gdn_OAuth2 extends SSOAddon implements \Vanilla\InjectableInterface, CacheControlConstantsInterface
{
    use CacheControlTrait;

    const COLUMN_ASSOCIATION_KEY = "AssociationKey";

    const COOKIE_NONCE_NAME = "vanilla-nonce";

    /** @var string token provided by authenticator  */
    protected $accessToken;

    /** @var array response to token request by authenticator  */
    protected $accessTokenResponse;

    /** @var string AuthenticationSchemeAlias value */
    protected $authenticationSchemeAlias = "";

    /** @var string key for GDN_UserAuthenticationProvider table  */
    protected $providerKey = null;

    /** @var  string passing scope to authenticator */
    protected $scope;

    /** @var string content type for API calls */
    protected $defaultContentType = "application/x-www-form-urlencoded";

    /** @var array stored information to connect with provider (secret, etc.) */
    protected $provider = [];

    /** @var array optional additional get parameters to be passed in the authorize_uri */
    protected $authorizeUriParams = [];

    /** @var array optional additional post parameters to be passed in the accessToken request */
    protected $requestAccessTokenParams = [];

    /** @var array optional additional get params to be passed in the request for profile */
    protected $requestProfileParams = [];

    /** @var  @var string optional set the settings view */
    protected $settingsView;

    /**
     * @var string
     */
    protected $clientIDField = self::COLUMN_ASSOCIATION_KEY;

    /**
     * @var SsoUtils
     */
    protected $ssoUtils;

    /**
     * @var \SessionModel
     */
    private $sessionModel;

    /**
     * @var \Garden\Container\Container
     */
    private $container;

    /**
     * @var bool Include `scope` in the Access Token request params.
     */
    protected $sendScopeOnTokenRequest;

    /**
     * Set up OAuth2 access properties.
     *
     * @param string $providerKey Fixed key set in child class.
     * @param bool|string $accessToken Provided by the authentication provider.
     */
    public function __construct($providerKey, $accessToken = false)
    {
        $this->providerKey = $providerKey;
        $this->provider = $this->provider();
        if ($accessToken) {
            // We passed in a connection
            $this->accessToken = $accessToken;
        }
        $this->setSendScopeOnTokenRequest();
    }

    /**
     * Gets the $sendScopOnTokenRequest variable.
     *
     * @return bool
     */
    public function getSendScopeOnTokenRequest(): bool
    {
        return $this->sendScopeOnTokenRequest ?? \Gdn::config()->get("OAuth2.Flags.SendScopeOnTokenRequest", true);
    }

    /**
     * Sets the sendScopeOnTokenRequest variable.
     */
    public function setSendScopeOnTokenRequest(): void
    {
        $this->sendScopeOnTokenRequest = \Gdn::config()->get("OAuth2.Flags.SendScopeOnTokenRequest", true);
    }

    /**
     * Gets the $authenticaitonSchemeAlias variable
     *
     * @return string
     */
    protected function getAuthenticationSchemeAlias(): string
    {
        return $this->authenticationSchemeAlias ?: $this->providerKey;
    }

    /**
     * Sets the $authenticationSchemeAlias variable
     *
     * @param string $alias The AuthenticationSchemeAlias name.
     */
    protected function setAuthenticationSchemeAlias(string $alias): void
    {
        $this->authenticationSchemeAlias = $alias;
    }

    /**
     * Add a query to a URL without checking if there is already a query attached.
     *
     * @param string $uri The URL with or without a query string already attached.
     * @param array $get Array of key/value pairs to be passed as GET params.
     * @return string URL with or without param string attached.
     */
    public static function concatUriQueryString(string $uri, array $get = []): string
    {
        if (!$get) {
            return $uri;
        }
        return $uri . (strpos($uri, "?") !== false ? "&" : "?") . http_build_query($get);
    }

    /**
     * Generate a container key from a provider type.
     *
     * @param string $providerType The provider type (Gdn_AuthenticationProvider.AuthenticationSchemeAlias).
     * @return string
     */
    final protected static function containerKey($providerType): string
    {
        return "@oauth.{$providerType}";
    }

    /**
     * Create the structure in the database.
     */
    public function structure()
    {
        // Clear any locally cached provider and make sure we fetch it fresh.
        $this->provider = null;
        // Make sure we have the OAuth2 provider.
        $provider = $this->provider();
        if (empty($provider) || empty($provider["AuthenticationKey"])) {
            $model = new Gdn_AuthenticationProviderModel();
            $provider = [
                "AuthenticationKey" => $this->providerKey,
                "AuthenticationSchemeAlias" => $this->providerKey,
                "Name" => $this->providerKey,
                "AcceptedScope" => "openid email profile",
                "ProfileKeyEmail" => "email", // Can be overwritten in settings, the key the authenticator uses for email in response.
                "ProfileKeyPhoto" => "picture",
                "ProfileKeyName" => "nickname",
                "ProfileKeyFullName" => "name",
                "ProfileKeyUniqueID" => "sub",
                "ProfileKeyRoles" => "roles",
            ];

            $model->save($provider);
        }
        Gdn::config()->saveToConfig("OAuth2.Flags.SendScopeOnTokenRequest", $this->getSendScopeOnTokenRequest());
    }

    /**
     * Check if there is enough data to connect to an authentication provider.
     *
     * @return bool True if there is a secret and a client_id, false if not.
     */
    public function isConfigured()
    {
        $provider = $this->provider();
        return val("AssociationSecret", $provider) && val(self::COLUMN_ASSOCIATION_KEY, $provider);
    }

    /**
     * Check if the provider is active.
     *
     * @return bool Returns **true** if the provider is active or **false** otherwise.
     */
    final public function isActive()
    {
        $provider = $this->provider();
        return !empty($provider["Active"]);
    }

    /**
     * Check if an access token has been returned from the provider server.
     *
     * @return bool True of there is an accessToken, fals if there is not.
     */
    public function isConnected()
    {
        if (!$this->accessToken) {
            return false;
        }
        return true;
    }

    /**
     * Check authentication provider table to see if this is the default method for logging in.
     *
     * @return bool Return the value of the IsDefault row of GDN_UserAuthenticationProvider .
     */
    public function isDefault()
    {
        $provider = $this->provider();
        return val("IsDefault", $provider);
    }

    /**
     * Renew or return access token.
     *
     * @param bool|string $newValue Pass existing token if it exists.
     * @return bool|string|null String if there is an accessToken passed or found in session, false or null if not.
     */
    public function accessToken($newValue = false)
    {
        if (!$this->isConfigured() && $newValue === false) {
            return false;
        }

        if ($newValue !== false) {
            $this->accessToken = $newValue;
        }

        // If there is no token passed, try to retrieve one from the user's attributes.
        if ($this->accessToken === null && Gdn::session()->UserID) {
            // If this workflow uses a RefreshToken, regenerate the access token using the RefreshToken, otherwise use the stored AccessToken.
            $refreshToken = valr($this->getProviderKey() . ".RefreshToken", Gdn::session()->User->Attributes);
            if ($refreshToken) {
                $response = $this->requestAccessToken($refreshToken, true);
                // save the new refresh_token if there is one and it is different from the existing one.
                if (val("refresh_token", $response) !== $refreshToken) {
                    $userModel = new UserModel();
                    $userModel->saveAttribute(Gdn::session()->UserID, [
                        $this->getProviderKey() => ["RefreshToken" => val("refresh_token", $response)],
                    ]);
                }
                $this->accessToken = val("access_token", $response);
            } else {
                $this->accessToken = valr($this->getProviderKey() . ".AccessToken", Gdn::session()->User->Attributes);
            }
        }

        return $this->accessToken;
    }

    /**
     * Set access token received from provider.
     *
     * @param string $accessToken Retrieved from provider to authenticate communication.
     * @return $this Return this object for chaining purposes.
     */
    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
        return $this;
    }

    /**
     * Set provider key used to access settings stored in GDN_UserAuthenticationProvider.
     *
     * @param string $providerKey Key to retrieve provider data hardcoded into child class.
     * @return $this Return this object for chaining purposes.
     */
    public function setProviderKey($providerKey)
    {
        $this->providerKey = $providerKey;
        return $this;
    }

    /**
     * Set scope to be passed to provider.
     *
     * @param string $scope.
     * @return $this Return this object for chaining purposes.
     */
    public function setScope($scope)
    {
        $this->scope = $scope;
        return $this;
    }

    /**
     * Set additional params to be added to the get string in the AuthorizeUri string.
     *
     * @param string $params.
     * @return $this Return this object for chaining purposes.
     */
    public function setAuthorizeUriParams($params)
    {
        $this->authorizeUriParams = $params;
        return $this;
    }

    /**
     * Set additional params to be to be merged with the default parameters
     * in the access token request.
     *
     * @param array $params Params to add to the access token request.
     *
     * @return $this Return this object for chaining purposes.
     */
    public function setRequestAccessTokenParams($params)
    {
        $this->requestAccessTokenParams = $params;
        return $this;
    }

    /**
     * Set additional params to be to be merged with the default parameters
     * in the getProfile request.
     *
     * @param array $params Params to add to the get profile request.
     *
     * @return $this Return this object for chaining purposes.
     */
    public function setRequestProfileParams(array $params)
    {
        $this->requestProfileParams = $params;
        return $this;
    }

    /**
     * Allow child classes to pass different options to the Token request API call.
     * Valid options are ConnectTimeout, Timeout, Content-Type and Authorization-Header-Message.
     *
     * @return array
     */
    public function getAccessTokenRequestOptions()
    {
        return [];
    }

    /**
     * Allow child classes to pass different options to the Profile request API call.
     * Valid options are ConnectTimeout, Timeout, Content-Type and Authorization-Header-Message.
     *
     * @return array
     */
    public function getProfileRequestOptions()
    {
        return [];
    }

    /** ------------------- Provider Methods --------------------- */

    /**
     *  Return all the information saved in provider table.
     *
     * @return array|bool Stored provider data (secret, client_id, etc.).
     */
    public function provider()
    {
        if (!$this->provider) {
            $this->provider = Gdn_AuthenticationProviderModel::getProviderByScheme($this->providerKey);
        }

        return $this->provider;
    }

    /**
     *  Get provider key.
     *
     * @return string Provider key.
     */
    public function getProviderKey()
    {
        return $this->providerKey;
    }

    /**
     * Register a call back function so that multiple plugins can use it as an entry point on SSO.
     *
     * This endpoint is executed on /entry/[provider] and is used as the redirect after making an
     * initial request to log in to an authentication provider.
     *
     * @param Gdn_PluginManager $sender
     */
    public function gdn_pluginManager_afterStart_handler($sender)
    {
        $sender->registerCallback("entryController_{$this->providerKey}Redirect_create", [
            $this,
            "entryRedirectEndpoint",
        ]);
        $sender->registerCallback("entryController_{$this->providerKey}_create", [$this, "entryEndpoint"]);
        $sender->registerCallback("settingsController_{$this->providerKey}_create", [$this, "settingsEndpoint"]);
    }

    /** ------------------- Settings Related Methods --------------------- */

    /**
     * Allow child class to over-ride or add form fields to settings.
     *
     * @return array Form fields to appear in settings dashboard.
     */
    protected function getSettingsFormFields()
    {
        $promptOptions = [
            "login" => "login",
            "none" => "none",
            "consent" => "consent",
            "consent and login" => "consent and login",
        ];
        $formFields = [
            "RegisterUrl" => [
                "LabelCode" => "Register Url",
                "Description" => "Enter the endpoint to direct a user to register.",
            ],
            "SignOutUrl" => ["LabelCode" => "Sign Out Url", "Description" => "Enter the endpoint to log a user out."],
            "AcceptedScope" => [
                "LabelCode" => "Request Scope",
                "Description" => "Enter the scope to be sent with Token Requests.",
            ],
            "ProfileKeyEmail" => [
                "LabelCode" => "Email",
                "Description" => "The Key in the JSON array to designate Emails",
            ],
            "ProfileKeyPhoto" => [
                "LabelCode" => "Photo",
                "Description" => "The Key in the JSON array to designate Photo.",
            ],
            "ProfileKeyName" => [
                "LabelCode" => "Display Name",
                "Description" => "The Key in the JSON array to designate Display Name.",
            ],
            "ProfileKeyFullName" => [
                "LabelCode" => "Full Name",
                "Description" => "The Key in the JSON array to designate Full Name.",
            ],
            "ProfileKeyUniqueID" => [
                "LabelCode" => "User ID",
                "Description" => "The Key in the JSON array to designate UserID.",
            ],
            "ProfileKeyRoles" => [
                "LabelCode" => "Roles",
                "Description" => "The Key in the JSON array to designate Roles.",
            ],
            "Prompt" => [
                "LabelCode" => "Prompt",
                "Description" => "Prompt Parameter to append to Authorize Url",
                "Control" => "DropDown",
                "Items" => $promptOptions,
            ],
        ];
        return $formFields;
    }

    /**
     * Redirect to the OAuth redirect page with a verification nonce.
     *
     * @param EntryController $sender The controller initiating the request.
     * @param string $state The state to pass along the OAuth2 flow.
     */
    public function entryRedirectEndpoint(\EntryController $sender, $state = "")
    {
        $state = $this->decodeState($state);
        $url = $this->realAuthorizeUri($state);
        static::sendCacheControlHeaders(self::NO_CACHE);
        redirectTo($url, 302, false);
    }

    /**
     * Create a controller to deal with plugin settings in dashboard.
     *
     * @param Gdn_Controller $sender.
     * @param Gdn_Controller $args.
     */
    public function settingsEndpoint($sender, $args)
    {
        $sender->permission("Garden.Settings.Manage");
        $model = new Gdn_AuthenticationProviderModel();

        /* @var Gdn_Form $form */
        $form = new Gdn_Form();
        $form->setModel($model);
        $sender->Form = $form;

        if (!$form->authenticatedPostBack()) {
            $provider = $this->provider();

            // kludge to migrate the client ID to the real key field.
            if ($this->clientIDField !== self::COLUMN_ASSOCIATION_KEY) {
                $provider[self::COLUMN_ASSOCIATION_KEY] = $provider[$this->clientIDField];
            }
            $form->setData($provider);
            $form->addHidden($model->PrimaryKey, $provider[$model->PrimaryKey] ?? "");
        } else {
            $form->setFormValue("AuthenticationSchemeAlias", $this->getAuthenticationSchemeAlias());

            // If this error is triggered then we did something wrong.
            $sender->Form->validateRule(
                $model->PrimaryKey,
                "ValidateRequired",
                "There was an error getting the provider ID."
            );

            $sender->Form->validateRule(
                self::COLUMN_ASSOCIATION_KEY,
                "ValidateRequired",
                "You must provide a unique AccountID."
            );
            $sender->Form->validateRule("AssociationSecret", "ValidateRequired", "You must provide a Secret");
            $sender->Form->validateRule(
                "AuthorizeUrl",
                "isUrl",
                "You must provide a complete URL in the Authorize Url field."
            );
            $sender->Form->validateRule("TokenUrl", "isUrl", "You must provide a complete URL in the Token Url field.");
            $sender->Form->validateRule(
                "ProfileUrl",
                "isUrl",
                "You must provide a complete URL in the Profile Url field."
            );

            if ($this->clientIDField !== self::COLUMN_ASSOCIATION_KEY) {
                $sender->Form->setFormValue($this->clientIDField, $form->getFormValue(self::COLUMN_ASSOCIATION_KEY));
            }

            // To satisfy the AuthenticationProviderModel, create a BaseUrl.
            $baseUrlParts = parse_url($form->getValue("AuthorizeUrl"));
            $baseUrl =
                val("scheme", $baseUrlParts) && val("host", $baseUrlParts)
                    ? val("scheme", $baseUrlParts) . "://" . val("host", $baseUrlParts)
                    : null;
            if ($baseUrl) {
                $form->setFormValue("BaseUrl", $baseUrl);
                $form->setFormValue("SignInUrl", $baseUrl); // kludge for default provider
            }
            // @Todo: please remove this condition and the entire else block once all the SSO implementation is covered
            if (in_array($this->providerKey, ["salesforcesso"])) {
                $authenticatorssController = $this->container->get(AuthenticatorsApiController::class);
                //only post the values if there is no validation error
                if ($form->errorCount() == 0) {
                    $formData = array_merge(
                        ["Name" => $this->providerKey, "AuthenticationSchemeAlias" => $this->providerKey],
                        $form->formValues()
                    );
                    $formData = array_merge(
                        $model->normalizeRow($formData, ["secret" => "AssociationSecret"]),
                        $this->formatAttributes($formData)
                    );
                    $formData["urls"] = (array) $formData["urls"];
                    try {
                        $result = $authenticatorssController->post($formData);
                        if ($result) {
                            $sender->informMessage(t("Saved"));
                        }
                    } catch (Exception $e) {
                        $form->addError($e->getMessage());
                    }
                }
            } else {
                if ($form->save()) {
                    $sender->informMessage(t("Saved"));
                }
            }
        }

        // Set up the form.
        $formFields = [
            self::COLUMN_ASSOCIATION_KEY => [
                "LabelCode" => "Client ID",
                "Description" => "Unique ID of the authentication application.",
            ],
            "AssociationSecret" => [
                "LabelCode" => "Secret",
                "Description" => "Secret provided by the authentication provider.",
            ],
            "AuthorizeUrl" => [
                "LabelCode" => "Authorize Url",
                "Description" => "URL where users sign-in with the authentication provider.",
            ],
            "TokenUrl" => [
                "LabelCode" => "Token Url",
                "Description" => "Endpoint to retrieve the authorization token for a user.",
            ],
            "ProfileUrl" => ["LabelCode" => "Profile Url", "Description" => 'Endpoint to retrieve a user\'s profile.'],
            "BearerToken" => [
                "LabelCode" => "Authorization Code in Header",
                "Description" =>
                    "When requesting the profile, pass the access token in the HTTP header. i.e Authorization: Bearer [accesstoken]",
                "Control" => "checkbox",
            ],
            "BasicAuthToken" => [
                "LabelCode" => "Basic Authorization Code in Header",
                "Description" =>
                    "When requesting the Access Token, pass the basic Auth token in the HTTP header. i.e Authorization: " .
                    '[Authorization =\> Basic base64_encode($rawToken)]',
                "Control" => "checkbox",
            ],
            "PostProfileRequest" => [
                "LabelCode" => "Request Profile Using the POST Method",
                "Description" => "When requesting the profile, use the HTTP POST method (default method is GET).",
                "Control" => "checkbox",
            ],
        ];

        $formFields = $formFields + $this->getSettingsFormFields();

        $formFields["AllowAccessTokens"] = [
            "LabelCode" => "Allow this connection to issue API access tokens.",
            "Control" => "toggle",
        ];
        $formFields["IsDefault"] = [
            "LabelCode" => "Make this connection your default signin method.",
            "Control" => "toggle",
        ];
        $sender->setData("_Form", $formFields);
        $form->addHidden(
            $model->PrimaryKey,
            $provider[$model->PrimaryKey] ?? $form->getValue($model->PrimaryKey, false)
        );
        $sender->setHighlightRoute();
        if (!$sender->data("Title")) {
            $sender->setData("Title", sprintf(t("%s Settings"), "Oauth2 SSO"));
        }

        $view = $this->settingsView ? $this->settingsView : "plugins/oauth2";

        // Create and send the possible redirect URLs that will be required by the authenticating server and display them in the dashboard.
        // Use Gdn::Request instead of convience function so that we can return http and https.
        $redirectUrls = Gdn::request()->url("/entry/" . $this->getProviderKey(), true, true);
        $sender->setData("redirectUrls", $redirectUrls);

        $sender->render("settings", "", $view);
    }

    /** ------------------- Connection Related Methods --------------------- */

    /**
     * Return the URL that sign-in buttons should use.
     *
     * @param array $state Optionally provide an array of variables to be sent to the provider.
     * @return string Returns the sign-in URL.
     */
    public function authorizeUri($state = [])
    {
        $params = empty($state) ? "" : "?" . http_build_query(["state" => $this->encodeState($state)]);
        return url("entry/{$this->providerKey}-redirect{$params}", true);
    }

    /**
     * Return the URL where the browser should be sent with all the necessary params to begin the authorization process.
     *
     * @param array $state Optionally provide an array of variables to be sent to the provider.
     * @return string Returns the sign-in URL.
     */
    final protected function realRegisterUri($state = [])
    {
        $r = $this->generateAuthorizeUriWithStateToken((string) $this->provider()["RegisterUrl"], $state);
        return $r;
    }

    /**
     * Return the URL where the browser should be sent with all the necessary params to begin the registration process.
     *
     * @param array $state Optionally provide an array of variables to be sent to the provider.
     *
     * @return string Endpoint of the provider.
     */
    protected function realAuthorizeUri(array $state = []): string
    {
        $url = $this->provider()["AuthorizeUrl"] ?? null;
        if (empty($url)) {
            throw new Gdn_UserException("The OAuth provider does not have an authorization URL configured.", 400);
        }

        $r = $this->generateAuthorizeUriWithStateToken($url, $state);
        return $r;
    }

    /**
     * Add the state other needed params to the Authorize or Register URL.
     *
     * @param string $uri Either a RegisterURL or an AuthorizeURL.
     * @param array $state Data that will be sent to the provider containing, for example, the target URL.
     * @return string The URI of the provider's registration or authorization page with the state token attached.
     */
    final protected function generateAuthorizeUriWithStateToken(string $uri, array $state): string
    {
        $provider = $this->provider();
        $redirect_uri = "/entry/" . $this->getProviderKey();
        $isOidc = $provider["isOidc"] ?? false;

        $defaultParams = [
            "client_id" => $provider[$this->clientIDField] ?? "not-found",
            "redirect_uri" => url($redirect_uri, true),
            "scope" => val("AcceptedScope", $provider),
        ];
        if ($isOidc) {
            $defaultParams["response_type"] = "code id_token token";
            $defaultParams["response_mode"] = "form_post";
            $nonceModel = new UserAuthenticationNonceModel();
            $nonce = uniqid("oidc_", true);
            $nonceModel->insert(["Nonce" => $nonce, "Token" => "OIDC_Nonce"]);
            $defaultParams["nonce"] = $nonce;
        } else {
            $defaultParams["response_type"] = c("OAuth2.ResponseType", "code");
        }

        // allow child class to overwrite or add to the authorize URI.
        $get = array_merge($defaultParams, $this->authorizeUriParams);

        $state["cid"] = $provider[\Gdn_AuthenticationProviderModel::COLUMN_KEY];
        $state["token"] = $this->ssoUtils->getStateToken();
        $get["state"] = $this->encodeState($state);

        if (array_key_exists("Prompt", $provider) && isset($provider["Prompt"])) {
            $get["prompt"] = $provider["Prompt"];
        }
        return self::concatUriQueryString($uri, $get);
    }

    /**
     * Generic API uses ProxyRequest class to fetch data from remote endpoints.
     *
     * @param string $uri Endpoint on provider's server.
     * @param string $method HTTP method required by provider.
     * @param array $params Query string.
     * @param array $options Configuration options for the request (e.g. Content-Type).
     * @return mixed
     * @throws Gdn_UserException Throws an exception if the server returns an error.
     */
    protected function api($uri, $method = "GET", $params = [], $options = [])
    {
        /** @var \ProxyRequest $proxy */
        $proxy = \Gdn::getContainer()->get(ProxyRequest::class);

        // Create default values of options to be passed to ProxyRequest.
        $defaultOptions["ConnectTimeout"] = 20;
        $defaultOptions["Timeout"] = 20;

        $headers = [];

        // Optionally over-write the content type
        if ($contentType = val("Content-Type", $options, $this->defaultContentType)) {
            $headers["Content-Type"] = $contentType;
        }

        // JSON encode params if the Content-Type is application/json.
        if ($headers["Content-Type"] === "application/json") {
            $params = StringUtils::jsonEncodeChecked($params);
        }

        // Obtionally add proprietary required Authorization headers
        if ($headerAuthorization = val("Authorization-Header-Message", $options, null)) {
            $headers["Authorization"] = $headerAuthorization;
        }
        if ($contentType = val("ProxyUserAuth", $options, null)) {
            $headers["ProxyUserAuth"] = $contentType;
        }

        // Merge the default options with the passed options over-writing default options with passed options.
        $proxyOptions = array_merge($defaultOptions, $options);

        $proxyOptions["URL"] = $uri;
        $proxyOptions["Method"] = $method;

        // Set the sending of cookies in the ProxyRequest to `false`.
        // This is set to `true` in the ProxyRequest class, but it is probably not needed.
        // If setting it to `false` causes any unwanted side effects, set it to `true` in the config.
        // If there are no unwanted side effects, remove this config call and set it to `false`.
        $proxyOptions["Cookies"] = \Gdn::config()->get("OAuth2.Flags.SendCookies", false);

        $this->log("OAuth2 Proxy Request Sent in API", [
            "headers" => $headers,
            "proxyOptions" => $proxyOptions,
            "params" => $params,
        ]);

        $response = $proxy->request($proxyOptions, $params, null, $headers);

        // Extract response only if it arrives as JSON
        if (stripos($proxy->ContentType, "application/json") !== false) {
            $response = json_decode($proxy->ResponseBody, true);
            $this->log("OAuth2 API JSON Response", ["response" => $response]);
        }

        // Return any errors
        if (!$proxy->responseClass("2xx")) {
            if (isset($response["error"])) {
                $message =
                    "Request server says: " . $response["error_description"] . " (code: " . $response["error"] . ")";
            } else {
                $message = "HTTP Error communicating Code: " . $proxy->ResponseStatus;
            }
            $this->log("API Response Error Thrown", ["response" => $proxy->ResponseBody]);
            throw new Gdn_UserException($message, $proxy->ResponseStatus);
        }

        return $response;
    }

    /**
     * Create a controller to handle entry request.
     *
     * @param EntryController $sender
     * @param string $code Retrieved from the response of the authentication provider, used to fetch an authentication token.
     * @param string $state Values passed by us and returned in the response of the authentication provider.
     * @throws Gdn_UserException Throws an exception if there was an error from the provider.
     */
    public function entryEndpoint($sender, $code, $state = "")
    {
        $rawProfile = "";
        if ($sender->Form->isPostBack()) {
            // Get Nonce cookie for validation of the reply
            $nonceModel = new UserAuthenticationNonceModel();
            // get OIDC reply properties.
            $oauthValues = $sender->Form->formValues();
            //if we get a postback error message. We should throw exception with the error
            if (!empty($oauthValues["error"])) {
                $message = $oauthValues["error"] . ": " . val("error_description", $oauthValues, null);
                throw new Gdn_UserException($message);
            }
            $state = val("state", $oauthValues, null);
            $code = val("code", $oauthValues, null);
            $idToken = val("id_token", $oauthValues, null);
            // id_token is jwt encoded.
            $rawProfile = $this->decodeJWT($idToken);
            // Grab the nonce from the session's stash.
            $foundNonce = $nonceModel->getWhere(["Nonce" => $rawProfile["nonce"]])->firstRow(DATASET_TYPE_ARRAY);
            if (!$foundNonce) {
                throw new Gdn_UserException("Potential reply attack, not matching nonce values.");
            }
            $nonceModel->delete(["Nonce" => $rawProfile["nonce"]]);
        }
        if ($error = $sender->Request->get("error")) {
            throw new Gdn_UserException($error);
        }
        if (empty($code)) {
            throw new Gdn_UserException("The code parameter is either not set or empty.");
        }

        $response = $this->requestAccessToken($code);
        if (!$response) {
            throw new Gdn_UserException("The OAuth server did not return a valid response.");
        }

        if (!empty($response["error"])) {
            throw new Gdn_UserException($response["error_description"]);
        } elseif (empty($response["access_token"])) {
            throw new Gdn_UserException("The OAuth server did not return an access token.", 400);
        } else {
            $this->accessToken($response["access_token"]);
        }
        // If we are doing OIDC we already have user profile, no need to make another request.

        if ($rawProfile != "") {
            $this->log("Getting Profile from id_token", []);
            $profile = $this->translateProfileResults($rawProfile);
        } else {
            $this->log("Getting Profile from profile endpoint", []);
            $profile = $this->getProfile();
        }

        $this->log("Profile", $profile);
        if ($state) {
            $state = $this->decodeState($state);
        }

        $suppliedStateToken = $state["token"] ?? "";
        $stashID = $this->ssoUtils->verifyStateToken($this->providerKey, $suppliedStateToken);

        // Save the access token and the profile to the session table, set expiry to 5 minutes.
        $expiryTime = new \DateTimeImmutable("now + 5 minutes");
        $provider = $this->provider();
        $this->sessionModel->update(
            [
                "Attributes" => [
                    Gdn_AuthenticationProviderModel::COLUMN_KEY =>
                        $provider[Gdn_AuthenticationProviderModel::COLUMN_KEY],
                    "AccessToken" => $response["access_token"],
                    "RefreshToken" => $response["refresh_token"],
                    "Profile" => $profile,
                ],
                "DateExpires" => $expiryTime->format(MYSQL_DATE_FORMAT),
            ],
            ["SessionID" => $stashID]
        );
        $url = "/entry/connect/" . $this->getProviderKey();

        // Pass the "sessionID" to in the query so that it can be retrieved.
        $url .= "?" . http_build_query(array_filter(["Target" => $state["target"] ?? "/", "stashID" => $stashID]));
        // Redirect to the connect script.
        redirectTo($url);
    }

    /**
     * Inject into the process of the base connection.
     *
     * @param Gdn_Controller $sender
     * @param array $args
     */
    public function base_connectData_handler($sender, $args)
    {
        if (val(0, $args) != $this->getProviderKey()) {
            return;
        }

        /* @var Gdn_Form $form */
        $form = $sender->Form; //new gdn_Form();

        if ($form->isPostBack()) {
            $stashID = $form->getFormValue("stashID");
        } else {
            $stashID = $sender->Request->get("stashID");
        }

        if (!$stashID) {
            $this->log("Missing stashID", ["POST" => $sender->Request->post(), "GET" => $sender->Request->get()]);
            throw new Gdn_UserException("Missing session, please go back and log in again.", 401);
        }

        $savedProfile = $this->sessionModel->getActiveSession($stashID);
        if ($savedProfile["Attributes"]) {
            $this->log("Base Connect Data Profile Saved in Session", ["profile" => $savedProfile["Attributes"]]);
        } else {
            $this->log("Base Connect Data Profile Not Found in Session", []);
        }

        // Retrieve the profile that was saved to the session in the entryEndPoint.
        $profile = $savedProfile["Attributes"]["Profile"] ?? [];
        $accessToken = val("AccessToken", $savedProfile["Attributes"]);
        $refreshToken = val("RefreshToken", $savedProfile["Attributes"]);

        trace($profile, "Profile");
        trace($accessToken, "Access Token");
        trace($refreshToken, "Refresh Token");

        // Create a form and populate it with values from the profile.
        $originalFormValues = $form->formValues();
        $formValues = array_replace($originalFormValues, $profile);
        $form->formValues($formValues);
        trace($formValues, "Form Values");

        // Save some original data in the attributes of the connection for later API calls.
        $attributes = [];
        $attributes[$this->getProviderKey()] = [
            "AccessToken" => $accessToken,
            "RefreshToken" => $refreshToken,
            "Profile" => $profile,
        ];
        $form->setFormValue("Attributes", $attributes);
        $form->addHidden("stashID", $stashID);
        $sender->EventArguments["Profile"] = $profile;
        $sender->EventArguments["Form"] = $form;

        $this->log("Base Connect Data Before OAuth Event", ["profile" => $profile, "form" => $form]);

        // Throw an event so that other plugins can add/remove stuff from the basic sso.
        $sender->fireEvent("OAuth");

        SpamModel::disabled(true);
        $sender->setData("Trusted", true);
        $sender->setData("Verified", true);
    }

    /**
     * Request access token from provider.
     *
     * @param string $code code returned from initial handshake with provider.
     * @param bool $refresh if we are using the stored RefreshToken to request a new AccessToken
     * @return mixed Result of the API call to the provider, usually JSON.
     */
    public function requestAccessToken($code, $refresh = false)
    {
        $provider = $this->provider();
        $uri = $provider["TokenUrl"];

        //When requesting the AccessToken using the RefreshToken the params are different.
        if ($refresh) {
            $defaultParams = [
                "refresh_token" => $code,
                "grant_type" => "refresh_token",
            ];
        } else {
            $defaultParams = [
                "code" => $code,
                "client_id" => $provider[$this->clientIDField] ?? "not-found",
                "redirect_uri" => url("/entry/" . $this->getProviderKey(), true),
                "client_secret" => $provider["AssociationSecret"],
                "grant_type" => "authorization_code",
            ];
        }

        if ($this->getSendScopeOnTokenRequest()) {
            $defaultParams["scope"] = $provider["AcceptedScope"];
        }

        // Merge any parameters inherited parameters, remove any empty parameters before sending them in the request.
        $post = array_filter(array_merge($defaultParams, $this->requestAccessTokenParams));

        $this->log("Before calling API to request access token", [
            "requestAccessToken" => ["targetURI" => $uri, "post" => $post],
        ]);
        $token = [];
        if (val("BasicAuthToken", $provider)) {
            $token = $this->generateBasicAuthHeader($provider[$this->clientIDField], $provider["AssociationSecret"]);
        }
        $this->accessTokenResponse = $this->api($uri, "POST", $post, $this->getAccessTokenRequestOptions() + $token);

        return $this->accessTokenResponse;
    }

    /**
     * Generate Basic Auth Header.
     *
     * @param string $client_id
     * @param string $secret
     * @return string[]
     */
    public function generateBasicAuthHeader(string $client_id, string $secret): array
    {
        $rawToken = $client_id . ":" . $secret;
        return ["Authorization-Header-Message" => "Basic " . base64_encode($rawToken)];
    }

    /**
     *   Allow the admin to input the keys that their service uses to send data.
     *
     * @param array $rawProfile profile as it is returned from the provider.
     *
     * @return array Profile array transformed by child class or as is.
     */
    public function translateProfileResults($rawProfile = [])
    {
        $provider = $this->provider();
        $markVerified = $provider["markVerified"] ?? false;
        $translatedKeys = [
            $provider["ProfileKeyEmail"] ?? "email" => "Email",
            $provider["ProfileKeyPhoto"] ?? "picture" => "Photo",
            $provider["ProfileKeyName"] ?? "displayname" => "Name",
            $provider["ProfileKeyFullName"] ?? "name" => "FullName",
            $provider["ProfileKeyUniqueID"] ?? "user_id" => "UniqueID",
            $provider["ProfileKeyRoles"] ?? "roles" => "Roles",
        ];

        if ($markVerified) {
            $translatedKeys[$provider["ProfileVerified"] ?? "email_verified"] = "Verified";
            $translatedKeys[$provider["ProfileVerified"] ?? "email_verified"] = "Confirmed";
        } else {
            $translatedKeys[$provider["ProfileVerified"] ?? "verified"] = "Verified";
        }

        $profile = self::translateArrayMulti($rawProfile, $translatedKeys, true);
        if (key_exists("Confirmed", $profile) && $profile["Confirmed"] === null) {
            unset($profile["Confirmed"]);
        }
        if (key_exists("Verified", $profile) && $profile["Verified"] === null) {
            unset($profile["Verified"]);
        }
        $profile["Provider"] = $provider[\Gdn_AuthenticationProviderModel::COLUMN_KEY];

        return $profile;
    }

    /**
     * Get profile data from authentication provider through API.
     *
     * @return array User profile from provider.
     */
    public function getProfile()
    {
        $provider = $this->provider();
        $uri = $this->requireVal("ProfileUrl", $provider, "provider");
        $defaultParams = [];
        $defaultOptions = [];

        // Send the Access Token as an Authorization header, depending on the client workflow.
        if (val("BearerToken", $provider)) {
            $defaultOptions = [
                "Authorization-Header-Message" => "Bearer " . $this->accessToken(),
            ];
        }

        // Merge with any other Header options being set by child classes.
        $requestOptions = array_filter(array_merge($defaultOptions, $this->getProfileRequestOptions()));

        // Send the Access Token is a Get parameter, depending on the client workflow.
        if (!val("BearerToken", $provider)) {
            $defaultParams = [
                "access_token" => $this->accessToken(),
            ];
        }
        // Merge any inherited parameters and remove any empty parameters before sending them in the request.
        $requestParams = array_filter(array_merge($defaultParams, $this->requestProfileParams));

        $requestMethod =
            isset($provider["PostProfileRequest"]) && $provider["PostProfileRequest"] === true ? "POST" : "GET";
        // Request the profile from the Authentication Provider
        $rawProfile = $this->api($uri, $requestMethod, $requestParams, $requestOptions);

        // Translate the keys of the profile sent to match the keys we are looking for.
        $profile = $this->translateProfileResults($rawProfile);

        // Log the results when troubleshooting.
        $this->log("getProfile API call", [
            "ProfileUrl" => $uri,
            "Params" => $requestParams,
            "RawProfile" => $rawProfile,
            "Profile" => $profile,
        ]);

        return $profile;
    }

    /** ------------------- Buttons, linking --------------------- */

    /**
     * Redirect to provider's signin page if this is the default behaviour.
     *
     * @param EntryController $sender
     * @param array $args
     *
     * @return mixed|bool Return null if not configured.
     */
    public function entryController_overrideSignIn_handler($sender, $args)
    {
        $provider = $args["DefaultProvider"];
        if (val("AuthenticationSchemeAlias", $provider) != $this->getProviderKey() || !$this->isConfigured()) {
            return;
        }

        $url = $this->authorizeUri(["target" => $args["Target"]]);
        $args["DefaultProvider"]["SignInUrl"] = $url;
    }

    /**
     * Redirect to provider's signin page if this is the default behaviour.
     *
     * @param EntryController $sender Entry Controller object.
     * @param array $args Array of Event Arguments from the Entry Controller.
     */
    public function entryController_overrideRegister_handler($sender, $args)
    {
        $provider = $args["DefaultProvider"];
        if (val("AuthenticationSchemeAlias", $provider) != $this->getProviderKey() || !$this->isConfigured()) {
            return;
        }

        $url = $this->realRegisterUri(["target" => $args["Target"]]);
        $args["DefaultProvider"]["RegisterUrl"] = $url;
    }

    /**
     * Inject sign-in button into the sign in page.
     *
     * @param EntryController $sender
     * @param array $args
     *
     * @return mixed|bool Return null if not configured
     */
    public function entryController_signIn_handler($sender, $args)
    {
        if (!$this->isConfigured()) {
            return;
        }
        if (isset($sender->Data["Methods"])) {
            // Add the sign in button method to the controller.
            $method = [
                "Name" => $this->getProviderKey(),
                "SignInHtml" => $this->signInButton(),
            ];

            $sender->Data["Methods"][] = $method;
        }
    }

    /**
     * Create signup button specific to this plugin.
     *
     * @param string $type Either button or icon to be output.
     *
     * @return string Resulting HTML element (button).
     */
    public function signInButton($type = "button")
    {
        $target = Gdn::request()->post("Target", Gdn::request()->get("Target", url("", "/")));
        $url = $this->authorizeUri(["target" => $target]);
        $providerName = $this->provider["Name"] ?? "OAuth";
        $linkLabel = sprintf(t("Sign In with %s"), $providerName);
        $result = socialSignInButton($providerName, $url, $type, [
            "rel" => "nofollow",
            "class" => "default",
            "title" => $linkLabel,
        ]);
        return $result;
    }

    /**
     * Create a sign in button for a specific provider row.
     *
     * @param array $provider The provider row from the `GDN_UserAuthenticationProvider` table.
     * @param string|null $target The redirect target or **null** to read the request.
     * @param string $type The type of button.
     * @return string
     */
    public function signInButtonFromProvider(array $provider, ?string $target = null, string $type = "button")
    {
        if ($target === null) {
            $target = Gdn::request()->post("Target", Gdn::request()->get("Target", url("", "/")));
        }
        $url = $this->authorizeUri(["target" => $target]);
        $providerName = $provider["Name"] ?? "OAuth";
        $linkLabel = sprintf(t("Sign In with %s"), $providerName);
        $result = socialSignInButton($providerName, $url, $type, [
            "rel" => "nofollow",
            "class" => "default",
            "title" => $linkLabel,
        ]);

        return $result;
    }

    /**
     * Insert css file for generic styling of signin button/icon.
     *
     * @param \Vanilla\Web\Asset\LegacyAssetModel $sender
     * @param array $args
     */
    public function assetModel_styleCss_handler($sender, $args)
    {
        $sender->addCssFile("oauth2.css", "plugins/oauth2");
    }

    /** ------------------- Helper functions --------------------- */

    /**
     * Extract values from arrays.
     *
     * @param string $key Needle.
     * @param array $arr Haystack.
     * @param string $context Context to make error messages clearer.
     * @return mixed Extracted value from array.
     * @throws \Exception Throws an exception if the key is missing from the array.
     */
    public static function requireVal($key, $arr, $context = null)
    {
        $result = val($key, $arr);
        if (!$result) {
            throw new \Exception("Key {$key} missing from {$context} collection.", 500);
        }
        return $result;
    }

    /**
     * Allow admins to use dot notation to map values from multi-dimensional arrays.
     *
     * @param array $array The array from which we will extract values.
     * @param array $mappings The map of keys where we will find the values in $array and the new keys we will assign to them.
     * @param bool|false $addRemaining Tack on all the unmapped values of $array.
     * @return array An array with the keys passed in $mappings with corresponding values from $array and all the remaining values of $array.
     */
    public static function translateArrayMulti($array, $mappings, $addRemaining = false)
    {
        $array = (array) $array;
        $result = [];
        foreach ($mappings as $index => $value) {
            if (is_numeric($index)) {
                $key = $value;
                $newKey = $value;
            } else {
                $key = $index;
                $newKey = $value;
            }

            if ($newKey === null) {
                unset($array[$key]);
                continue;
            }

            if (valr($key, $array, null) !== null) {
                $result[$newKey] = valr($key, $array);
                if (isset($array[$key])) {
                    unset($array[$key]);
                }
            } else {
                $result[$newKey] = null;
            }
        }

        if ($addRemaining) {
            foreach ($array as $key => $value) {
                if (!isset($result[$key])) {
                    $result[$key] = $value;
                }
            }
        }
        return $result;
    }

    /**
     * Inject dependencies without affecting subclass constructors.
     *
     * Note that injecting the container is an anti-pattern, but is a trade-off in this case since this plugin is
     * constructed every request.
     *
     * @param SsoUtils $ssoUtils Used to generate SSO tokens.
     * @param SessionModel $sessionModel Used to stash keys.
     * @param \Psr\Container\ContainerInterface $container Used to get dependencies that are rarely used.
     */
    public function setDependencies(
        SsoUtils $ssoUtils,
        SessionModel $sessionModel,
        \Garden\Container\Container $container
    ) {
        $this->ssoUtils = $ssoUtils;
        $this->sessionModel = $sessionModel;
        $this->container = $container;
        $this->container->setInstance(static::containerKey($this->providerKey), $this);
    }

    /**
     * Return ssoUtils.
     *
     * @return SsoUtils ssoUtils Member
     */
    public function getSsoUtils(): SsoUtils
    {
        return $this->ssoUtils;
    }

    /**
     * Log a debug message to the general event log.
     *
     * @param string $message
     * @param array $data
     */
    public function log($message, $data)
    {
        AuditLogger::log(new OAuth2AuditEvent("debug", $message, $data));
    }

    /**
     * Encode an array into a state parameter.
     *
     * Some OAuth servers will mess up double URL encoding so use something safe here.
     *
     * @param array $state The state to encode.
     * @return string Returns the encoded state.
     */
    protected function encodeState(array $state): string
    {
        return base64_encode(json_encode($state));
    }

    /**
     * Decode a state string into its original array.
     *
     * If the state cannot be decoded this method returns an empty string so the caller should be responsible for
     * propagating any errors.
     *
     * @param string $state The state to decode.
     * @return array Returns the decoded state.
     */
    protected function decodeState(string $state): array
    {
        if (empty($state)) {
            return [];
        } else {
            $r = json_decode(base64_decode($state), true);
            if (is_array($r)) {
                return $r;
            } else {
                return [];
            }
        }
    }

    /**
     * Decodes ID Token JWT to an array.
     *
     * @param string $idToken JWT encoded token.
     * @return array
     */
    protected function decodeJWT(string $idToken): array
    {
        try {
            $result = json_decode(
                base64_decode(str_replace("_", "/", str_replace("-", "+", explode(".", $idToken)[1]))),
                true
            );
        } catch (Exception $e) {
            throw new ServerException("There was an error decoding id_token.");
        }
        return $result;
    }
    /**
     * Get a schema describing an OAuth2 authentication provider fragment.
     *
     * @return Schema
     */
    protected function providerFragmentSchema(): Schema
    {
        $schema = new UserAuthenticationProviderFragmentSchema();
        $schema->merge(
            Schema::parse([
                "secret:s",
                "urls:o" => [
                    "authorizeUrl:s",
                    "profileUrl:s",
                    "registerUrl:s?" => ["default" => null],
                    "signOutUrl:s?" => ["default" => null],
                    "tokenUrl:s",
                ],
                "authenticationRequest:o?" => [
                    "scope:s?" => ["default" => null],
                    "prompt?" => [
                        "default" => null,
                        "enum" => ["consent", "consent and login", "login", "none"],
                        "type" => "string",
                    ],
                ],
                "useBearerToken:b?" => ["default" => null],
                "useBasicAuthToken:b?" => ["default" => null],
                "postProfileRequest:b?" => ["default" => null],
                "allowAccessTokens:b?" => ["default" => null],
                "userMappings:o?" => [
                    "uniqueID:s?" => ["default" => "user_id"],
                    "email:s?" => ["default" => "email"],
                    "name:s?" => ["default" => "displayname"],
                    "photoUrl:s?" => ["default" => "picture"],
                    "fullName:s?" => ["default" => "name"],
                    "roles:s?" => ["default" => "roles"],
                ],
            ])
        );
        return $schema;
    }

    /**
     * Exchange an OAuth access token for a Vanilla access token.
     *
     * @param TokensApiController $sender
     * @param array $body
     * @return \Garden\Web\Data
     */
    final public function tokensApiController_post_oauth(TokensApiController $sender, array $body): \Garden\Web\Data
    {
        $sender->permission(Permissions::BAN_CSRF);

        $in = $sender->schema(["clientID:s", "oauthAccessToken:s"], "in");

        $valid = $in->validate($body);

        // Look up the specific addon that owns this client ID.
        $instance = $this->getInstanceFromClientID($valid["clientID"]);

        try {
            $result = $instance->issueAccessToken($valid["clientID"], $valid["oauthAccessToken"]);
        } catch (ContainerExceptionInterface $ex) {
            throw new ServerException("There was an error getting the OAuth client instance.");
        }

        return new \Garden\Web\Data($result);
    }

    /**
     * Connect the user payload with a user.
     *
     * @param array $payload The user profile.
     * @param string $providerKey The client ID of the connection.
     * @return int
     * @throws ClientException Throws an exception if the user cannot be connected for some reason.
     * @throws Garden\Schema\ValidationException Throws an exception if the payload doesn't contain the required fields.
     */
    final function sso(array $payload, string $providerKey): int
    {
        unset($payload["UserID"]); // safety precaution due to Gdn_UserModel::connect() behaviour

        /* @var \UserModel $userModel */
        $userModel = $this->container->get(\UserModel::class);

        $userID = $userModel->connect($payload["UniqueID"] ?? "", $providerKey, $payload, ["SyncExisting" => false]);

        if (!$userID) {
            \Vanilla\Utility\ModelUtils::validationResultToValidationException(
                $userModel,
                $this->container->get(\Gdn_Locale::class)
            );
        }

        return (int) $userID;
    }

    /**
     * Get the specific plugin instance from a client ID.
     *
     * This class stores the client ID in the attributes field, which makes it quite difficult to look up. Furthermore,
     * There is no information in the `Gdn_AuthenticationProvider` table that lets us know which class controlls that
     * row.
     *
     * To get around that this method loops through all of the providers to find a match and then gets the instance from
     * the container.
     *
     * @param string $clientID
     * @return $this
     * @throws \Garden\Container\ContainerException Throws an exception when the instance wasn't properly registered in the container.
     */
    final public function getInstanceFromClientID(string $clientID): self
    {
        $type = $this->getProviderTypeFromClientID($clientID);
        $instance = $this->container->get(static::containerKey($type));

        return $instance;
    }

    /**
     * Get the provider type from a client ID.
     *
     * @param string $clientID
     * @return string
     * @throws NotFoundException Throws an exception if there is no provider with that client ID.
     */
    final function getProviderTypeFromClientID(string $clientID): string
    {
        $key = "authenticationPoviderType.clientID.$clientID";

        $cachedType = Gdn::cache()->get($key);

        if ($cachedType === Gdn_Cache::CACHEOP_FAILURE) {
            if ($this->clientIDField === Gdn_AuthenticationProviderModel::COLUMN_KEY) {
                $provider = Gdn_AuthenticationProviderModel::getProviderByKey($clientID);
                if ($provider === false) {
                    throw new NotFoundException("An OAuth client with ID \"$clientID\" could not be found.");
                }
                $cachedType = $provider["AuthenticationSchemeAlias"];
                Gdn::cache()->store($key, $cachedType, [Gdn_Cache::FEATURE_EXPIRY => 300]);
                return $cachedType;
            } else {
                $providers = Gdn_AuthenticationProviderModel::getWhereStatic();

                foreach ($providers as $provider) {
                    if ($clientID === $provider[self::COLUMN_ASSOCIATION_KEY] ?? "") {
                        $cachedType = $provider["AuthenticationSchemeAlias"];
                        Gdn::cache()->store($key, $cachedType, [Gdn_Cache::FEATURE_EXPIRY => 300]);
                        return $cachedType;
                    }
                }

                throw new NotFoundException("An OAuth client with ID \"$clientID\" could not be found.");
            }
        }
        return $cachedType;
    }

    /**
     * Exchange an OAuth access token for a client ID.
     *
     * @param string $clientID The OAuth client ID (AssociationKey in the db).
     * @param string $oauthAccessToken A valid access token for calling the OAuth server.
     * @return array Returns an array with the access token and expiry date.
     * @throws \Garden\Container\ContainerException Throws an exception if the addon instance was improperly registered.
     */
    protected function issueAccessToken(string $clientID, string $oauthAccessToken): array
    {
        $provider = $this->provider();

        if ($clientID !== $provider[$this->clientIDField] ?? null) {
            throw new ClientException("Invalid client ID.", 422);
        }

        if (!$this->isConfigured()) {
            throw new ServerException("The OAuth client has not been configured.", 500);
        }

        if (!$this->isActive()) {
            throw new ServerException("The OAuth client is not active", 500);
        }

        if (!($this->provider()["AllowAccessTokens"] ?? false)) {
            throw new ServerException("The OAuth client is not allowed to issue access tokens.", 500);
        }

        $this->accessToken($oauthAccessToken);
        try {
            $profile = $this->getProfile();
        } catch (\Exception $ex) {
            throw new \Garden\Web\Exception\ForbiddenException($ex->getMessage());
        }

        $userID = $this->sso($profile, $provider[$this->clientIDField]);

        /* @var AccessTokenModel $tokenModel */
        $tokenModel = $this->container->get(AccessTokenModel::class);

        $expires = new DateTimeImmutable("+24 hours");
        $token = $tokenModel->issue($userID, $expires, "tokens/oauth");
        $result = [
            "accessToken" => $token,
            "dateExpires" => $expires,
        ];
        return $result;
    }
}
