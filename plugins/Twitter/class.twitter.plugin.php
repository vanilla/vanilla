<?php
/**
 * Twitter plugin.
 *
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Twitter
 */

/**
 * Class TwitterPlugin
 */
class TwitterPlugin extends Gdn_Plugin {

    /** Authentication provider key. */
    const ProviderKey = 'Twitter';

    /** @var string Twitter's URL. */
    public static $BaseApiUrl = 'https://api.twitter.com/1.1/';

    /** @var string */
    protected $_AccessToken = null;

    /** @var string */
    protected $_RedirectUri = null;

    /**
     * Gets/sets the current oauth access token.
     *
     * @param string $Token
     * @param string $Secret
     *
     * @return OAuthToken
     */
    public function accessToken($Token = null, $Secret = null) {
        if (!$this->isConfigured()) {
            return false;
        }

        if (is_object($Token)) {
            $this->_AccessToken = $Token;
        }
        if ($Token !== null && $Secret !== null) {
            $this->_AccessToken = new OAuthToken($Token, $Secret);
        } elseif ($this->_AccessToken == null) {
            if ($Token) {
                $this->_AccessToken = $this->getOAuthToken($Token);
            } elseif (Gdn::session()->User) {
                $AccessToken = valr(self::ProviderKey.'.AccessToken', Gdn::session()->User->Attributes);

                if (is_array($AccessToken)) {
                    $this->_AccessToken = new OAuthToken($AccessToken[0], $AccessToken[1]);
                }
            }
        }
        return $this->_AccessToken;
    }

    /**
     * Retreieve the URL to start an auth request.
     *
     * @param bool $Popup
     *
     * @return string
     */
    protected function _authorizeHref($Popup = false) {
        $Url = url('/entry/twauthorize', true);
        $UrlParts = explode('?', $Url);

        parse_str(val(1, $UrlParts, ''), $Query);
        $Path = Gdn::request()->path();

        $Target = val('Target', $_GET, $Path ? $Path : '/');
        if (ltrim($Target, '/') == 'entry/signin') {
            $Target = '/';
        }
        $Query['Target'] = $Target;

        if ($Popup) {
            $Query['display'] = 'popup';
        }
        $Result = $UrlParts[0].'?'.http_build_query($Query);

        return $Result;
    }

    /**
     * Add Twitter option to the normal signin page.
     *
     * @param Gdn_Controller $Sender
     */
    public function entryController_signIn_handler($Sender, $Args) {
        if (isset($Sender->Data['Methods'])) {
            if (!$this->socialSignIn()) {
                return;
            }

            $Url = $this->_authorizeHref();

            // Add the twitter method to the controller.
            $TwMethod = [
                'Name' => 'Twitter',
                'SignInHtml' => socialSigninButton('Twitter', $Url, 'button', ['class' => 'js-extern'])
            ];

            $Sender->Data['Methods'][] = $TwMethod;
        }
    }

    /**
     * Add Twitter signin to MeModule.
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
     * Add Twitter signin to GuestModule.
     *
     * @param Gdn_Controller $Sender
     * @param array $Args
     */
    public function base_BeforeSignInButton_handler($Sender, $Args) {
        if (!$this->socialSignIn()) {
            return;
        }

        echo "\n".$this->_getButton();
    }

    /**
     * Add Twitter signin to mobile theme.
     *
     * @param Gdn_Controller $Sender
     */
    public function base_beforeSignInLink_handler($Sender) {
        if (!$this->socialSignIn()) {
            return;
        }

        if (!Gdn::session()->isValid()) {
            echo "\n".Wrap($this->_getButton(), 'li', ['class' => 'Connect TwitterConnect']);
        }
    }

    /**
     * Add an option to share a discussion via Twitter manually.
     *
     * @param Gdn_Controller $Sender
     * @param array $Args
     */
    public function base_discussionFormOptions_handler($Sender, $Args) {
        if (!$this->socialSharing() || !$this->accessToken()) {
            return;
        }

        $Options =& $Args['Options'];
        $Options .= ' <li>'.
            $Sender->Form->checkBox('ShareTwitter', '@'.Sprite('ReactTwitter', 'ReactSprite'), ['value' => '1', 'title' => sprintf(t('Share to %s.'), 'Twitter')]).
            '</li> ';
    }

    /**
     * Add option to share a comment via Twitter as you make it.
     *
     * @param discussionController $Sender
     * @param array $Args
     */
    public function discussionController_afterBodyField_handler($Sender, $Args) {
        if (!$this->socialSharing() || !$this->accessToken()) {
            return;
        }

        echo ' '.
            $Sender->Form->checkBox('ShareTwitter', '@'.Sprite('ReactTwitter', 'ReactSprite'), ['value' => '1', 'title' => sprintf(t('Share to %s.'), 'Twitter')]).
            ' ';
    }

    /**
     * Share the discussion you just started to Twitter if you chose to.
     *
     * @param discussionModel $Sender
     * @param array $Args
     *
     * @throws Gdn_UserException
     */
    public function discussionModel_afterSaveDiscussion_handler($Sender, $Args) {
        if (!$this->socialSharing() || !$this->accessToken()) {
            return;
        }

        $Share = valr('FormPostValues.ShareTwitter', $Args);

        if ($Share && $this->accessToken()) {
            $Row = $Args['Fields'];
            $Url = discussionUrl($Row, '', true);
            $Message = sliceTwitter(Gdn_Format::plainText($Row['Body'], $Row['Format'])).' '.$Url;

            $R = $this->api(
                '/statuses/update.json',
                [
                'status' => $Message
                ],
                'POST'
            );
        }
    }

    /**
     * Share the comment you just made to Twitter if you chose to.
     *
     * @param commentModel $Sender
     * @param array $Args
     *
     * @throws Gdn_UserException
     */
    public function commentModel_afterSaveComment_handler($Sender, $Args) {
        if (!$this->socialSharing() || !$this->accessToken()) {
            return;
        }

        $Share = valr('FormPostValues.ShareTwitter', $Args);

        if ($Share && $this->accessToken()) {
            $Row = $Args['FormPostValues'];

            $DiscussionModel = new DiscussionModel();
            $Discussion = $DiscussionModel->getID(val('DiscussionID', $Row));
            if (!$Discussion) {
                return;
            }

            $Url = DiscussionUrl($Discussion, '', true);
            $Message = SliceTwitter(Gdn_Format::plainText($Row['Body'], $Row['Format'])).' '.$Url;

            $R = $this->API(
                '/statuses/update.json',
                [
                'status' => $Message
                ],
                'POST'
            );
        }
    }

    /**
     * Gimme button!
     *
     * @return string
     */
    private function _getButton() {
        $Url = $this->_authorizeHref();

        return socialSigninButton('Twitter', $Url, 'icon', ['class' => 'js-extern', 'rel' => 'nofollow']);
    }

    /**
     * Authorize the current user against Twitter's OAuth.
     *
     * @param bool $Query
     */
    public function authorize($Query = false) {
        // Acquire the request token.
        $Consumer = new OAuthConsumer(c('Plugins.Twitter.ConsumerKey'), c('Plugins.Twitter.Secret'));
        $RedirectUri = $this->redirectUri();
        if ($Query) {
            $RedirectUri .= (strpos($RedirectUri, '?') === false ? '?' : '&').$Query;
        }

        $Params = ['oauth_callback' => $RedirectUri];

        $Url = 'https://api.twitter.com/oauth/request_token';
        $Request = OAuthRequest::from_consumer_and_token($Consumer, null, 'POST', $Url, $Params);
        $SignatureMethod = new OAuthSignatureMethod_HMAC_SHA1();
        $Request->sign_request($SignatureMethod, $Consumer, null);

        $Curl = $this->_Curl($Request, $Params);
        $Response = curl_exec($Curl);
        if ($Response === false) {
            $Response = curl_error($Curl);
        }

        $HttpCode = curl_getinfo($Curl, CURLINFO_HTTP_CODE);
        curl_close($Curl);

        if ($HttpCode == '200') {
            // Parse the reponse.
            $Data = OAuthUtil::parse_parameters($Response);

            if (!isset($Data['oauth_token']) || !isset($Data['oauth_token_secret'])) {
                $Response = t('The response was not in the correct format.');
            } else {
                // Save the token for later reference.
                $this->setOAuthToken($Data['oauth_token'], $Data['oauth_token_secret'], 'request');

                // Redirect to twitter's authorization page.
                $Url = "https://api.twitter.com/oauth/authenticate?oauth_token={$Data['oauth_token']}";
                redirectTo($Url, 302, false);
            }
        }

        // There was an error. Echo the error.
        echo $Response;
    }

    /**
     * Send user to the OAuth authorization page via cleverly-named endpoint.
     *
     * See, because it's Twitter...
     *
     * @param $Sender
     * @param string $Dir
     */
    public function entryController_twauthorize_create($Sender, $Dir = '') {
        $Query = arrayTranslate($Sender->Request->get(), ['display', 'Target']);
        $Query = http_build_query($Query);

        if ($Dir == 'profile') {
            // This is a profile connection.
            $this->redirectUri(self::profileConnecUrl());
        }

        $this->authorize($Query);
    }

    /**
     * Post to Twitter.
     *
     * @param PostController $Sender
     * @param string $RecordType
     * @param int $ID
     *
     * @throws Gdn_UserException
     */
    public function postController_twitter_create($Sender, $RecordType, $ID) {
        if (!$this->socialReactions()) {
            throw permissionException();
        }

        $Row = GetRecord($RecordType, $ID, true);
        if ($Row) {
            // Grab the tweet message.
            switch (strtolower($RecordType)) {
                case 'discussion':
                    $Message = Gdn_Format::plainText($Row['Name'], 'Text');
                    break;
                case 'comment':
                default:
                    $Message = Gdn_Format::plainText($Row['Body'], $Row['Format']);
            }

            // WHY ARE WE REPEATING THE `sliceTwitter()` FUNCTION BELOW?
            // Dammit, fellas, ima hang y'uns out to DRY.
            $Elips = '...';
            $Message = preg_replace('`\s+`', ' ', $Message);

            $Max = 140;
            $LinkLen = 22;
            $Max -= $LinkLen;

            $Message = SliceParagraph($Message, $Max);
            if (strlen($Message) > $Max) {
                $Message = substr($Message, 0, $Max - strlen($Elips)).$Elips;
            }

            if ($this->accessToken()) {
                Gdn::controller()->setData('Message', $Message);

                $Message .= ' '.$Row['ShareUrl'];
                $R = $this->api(
                    '/statuses/update.json',
                    [
                    'status' => $Message
                    ],
                    'POST'
                );

                $Sender->setJson('R', $R);
                $Sender->informMessage(t('Thanks for sharing!'));
            } else {
                $Get = [
                    'text' => $Message,
                    'url' => $Row['ShareUrl']
                ];
                $Url = "https://twitter.com/share?".http_build_query($Get);
                redirectTo($Url, 302, false);
            }
        }

        $Sender->render('Blank', 'Utility', 'Dashboard');
    }

    /**
     * Endpoint to connect to Twitter via user profile.
     *
     * @param ProfileController $Sender
     * @param mixed $UserReference
     * @param string $Username
     * @param string $oauth_token
     * @param string $oauth_verifier
     */
    public function profileController_twitterConnect_create($Sender, $UserReference = '', $Username = '', $oauth_token = '', $oauth_verifier = '') {
        $Sender->permission('Garden.SignIn.Allow');

        $Sender->getUserInfo($UserReference, $Username, '', true);

        $Sender->_setBreadcrumbs(t('Connections'), '/profile/connections');

        // Get the access token.
        trace('GetAccessToken()');
        $AccessToken = $this->getAccessToken($oauth_token, $oauth_verifier);
        $this->accessToken($AccessToken);

        // Get the profile.
        trace('GetProfile()');
        $Profile = $this->getProfile();

        // Save the authentication.
        Gdn::userModel()->saveAuthentication([
            'UserID' => $Sender->User->UserID,
            'Provider' => self::ProviderKey,
            'UniqueID' => $Profile['id']]);

        // Save the information as attributes.
        $Attributes = [
            'AccessToken' => [$AccessToken->key, $AccessToken->secret],
            'Profile' => $Profile
        ];
        Gdn::userModel()->saveAttribute($Sender->User->UserID, self::ProviderKey, $Attributes);

        $this->EventArguments['Provider'] = self::ProviderKey;
        $this->EventArguments['User'] = $Sender->User;
        $this->fireEvent('AfterConnection');

        redirectTo(userUrl($Sender->User, '', 'connections'));
    }

    /**
     * Get an access token from Twitter.
     *
     * @param string $RequestToken
     * @param string $Verifier
     *
     * @return string OAuthToken
     * @throws Gdn_UserException
     */
    public function getAccessToken($RequestToken, $Verifier) {
        if ((!$RequestToken || !$Verifier) && Gdn::request()->get('denied')) {
            throw new Gdn_UserException(t('Looks like you denied our request.'), 401);
        }

        // Get the request secret.
        $RequestToken = $this->getOAuthToken($RequestToken);

        $Consumer = new OAuthConsumer(c('Plugins.Twitter.ConsumerKey'), c('Plugins.Twitter.Secret'));

        $Url = 'https://api.twitter.com/oauth/access_token';
        $Params = [
            'oauth_verifier' => $Verifier //GetValue('oauth_verifier', $_GET)
        ];
        $Request = OAuthRequest::from_consumer_and_token($Consumer, $RequestToken, 'POST', $Url, $Params);

        $SignatureMethod = new OAuthSignatureMethod_HMAC_SHA1();
        $Request->sign_request($SignatureMethod, $Consumer, $RequestToken);
        $Post = $Request->to_postdata();

        $Curl = $this->_curl($Request);
        $Response = curl_exec($Curl);
        if ($Response === false) {
            $Response = curl_error($Curl);
        }
        $HttpCode = curl_getinfo($Curl, CURLINFO_HTTP_CODE);
        curl_close($Curl);

        if ($HttpCode == '200') {
            $Data = OAuthUtil::parse_parameters($Response);

            $AccessToken = new OAuthToken(val('oauth_token', $Data), val('oauth_token_secret', $Data));

            // Delete the request token.
            $this->deleteOAuthToken($RequestToken);

        } else {
            // There was some sort of error.
            throw new Gdn_UserException('There was an error authenticating with twitter. '.$Response, $HttpCode);
        }

        return $AccessToken;
    }

    /**
     * Generic SSO hook into Vanilla for authorization and data transfer.
     *
     * @param Gdn_Controller $Sender
     * @param array $Args
     */
    public function base_connectData_handler($Sender, $Args) {
        if (val(0, $Args) != 'twitter') {
            return;
        }

        $Form = $Sender->Form; //new Gdn_Form();

        $RequestToken = val('oauth_token', $_GET);
        $AccessToken = $Form->getFormValue('AccessToken');

        if ($AccessToken) {
            $AccessToken = $this->getOAuthToken($AccessToken);
            $this->accessToken($AccessToken);
        }

        // Get the access token.
        if ($RequestToken && !$AccessToken) {
            // Get the request secret.
            $RequestToken = $this->getOAuthToken($RequestToken);

            $Consumer = new OAuthConsumer(c('Plugins.Twitter.ConsumerKey'), c('Plugins.Twitter.Secret'));

            $Url = 'https://api.twitter.com/oauth/access_token';
            $Params = [
                'oauth_verifier' => val('oauth_verifier', $_GET)
            ];
            $Request = OAuthRequest::from_consumer_and_token($Consumer, $RequestToken, 'POST', $Url, $Params);

            $SignatureMethod = new OAuthSignatureMethod_HMAC_SHA1();
            $Request->sign_request($SignatureMethod, $Consumer, $RequestToken);
            $Post = $Request->to_postdata();

            $Curl = $this->_Curl($Request);
            $Response = curl_exec($Curl);
            if ($Response === false) {
                $Response = curl_error($Curl);
            }
            $HttpCode = curl_getinfo($Curl, CURLINFO_HTTP_CODE);
            curl_close($Curl);

            if ($HttpCode == '200') {
                $Data = OAuthUtil::parse_parameters($Response);

                $AccessToken = new OAuthToken(val('oauth_token', $Data), val('oauth_token_secret', $Data));

                // Save the access token to the database.
                $this->setOAuthToken($AccessToken->key, $AccessToken->secret, 'access');
                $this->accessToken($AccessToken->key, $AccessToken->secret);

                // Delete the request token.
                $this->deleteOAuthToken($RequestToken);

            } else {
                // There was some sort of error.
                throw new Exception('There was an error authenticating with twitter.', 400);
            }

            $NewToken = true;
        }

        // Get the profile.
        try {
            $Profile = $this->getProfile($AccessToken);
        } catch (Exception $Ex) {
            if (!isset($NewToken)) {
                // There was an error getting the profile, which probably means the saved access token is no longer valid. Try and reauthorize.
                if ($Sender->deliveryType() == DELIVERY_TYPE_ALL) {
                    redirectTo($this->_AuthorizeHref(), 302, false);
                } else {
                    $Sender->setHeader('Content-type', 'application/json');
                    $Sender->deliveryMethod(DELIVERY_METHOD_JSON);
                    $Sender->setRedirectTo($this->_authorizeHref(), false);
                }
            } else {
                throw $Ex;
            }
        }

        // This isn't a trusted connection. Don't allow it to automatically connect a user account.
        saveToConfig('Garden.Registration.AutoConnect', false, false);

        $ID = val('id', $Profile);
        $Form->setFormValue('UniqueID', $ID);
        $Form->setFormValue('Provider', self::ProviderKey);
        $Form->setFormValue('ProviderName', 'Twitter');
        $Form->setValue('ConnectName', val('screen_name', $Profile));
        $Form->setFormValue('Name', val('screen_name', $Profile));
        $Form->setFormValue('FullName', val('name', $Profile));
        $Form->setFormValue('Photo', val('profile_image_url_https', $Profile));
        $Form->addHidden('AccessToken', $AccessToken->key);

        // Save some original data in the attributes of the connection for later API calls.
        $Attributes = [self::ProviderKey => [
            'AccessToken' => [$AccessToken->key, $AccessToken->secret],
            'Profile' => $Profile
        ]];
        $Form->setFormValue('Attributes', $Attributes);

        $Sender->setData('Verified', true);
    }

    /**
     * Make Twitter available as an SSO provider.
     *
     * @param $Sender
     * @param $Args
     */
    public function base_getConnections_handler($Sender, $Args) {
        $Profile = valr('User.Attributes.'.self::ProviderKey.'.Profile', $Args);

        $Sender->Data["Connections"][self::ProviderKey] = [
            'Icon' => $this->getWebResource('icon.png', '/'),
            'Name' => 'Twitter',
            'ProviderKey' => self::ProviderKey,
            'ConnectUrl' => '/entry/twauthorize/profile',
            'Profile' => [
                'Name' => '@'.GetValue('screen_name', $Profile),
                'Photo' => val('profile_image_url_https', $Profile)
            ]
        ];
    }

    /**
     * Make an API request to Twitter.
     *
     * @param string $Url
     * @param array|null $Params
     * @param string $Method GET or POST.
     *
     * @return mixed Response from the API.
     * @throws Gdn_UserException
     */
    public function api($Url, $Params = null, $Method = 'GET') {
        if (strpos($Url, '//') === false) {
            $Url = self::$BaseApiUrl.trim($Url, '/');
        }
        $Consumer = new OAuthConsumer(c('Plugins.Twitter.ConsumerKey'), c('Plugins.Twitter.Secret'));

        if ($Method == 'POST') {
            $Post = $Params;
        } else {
            $Post = null;
        }

        $AccessToken = $this->accessToken();
        $Request = OAuthRequest::from_consumer_and_token($Consumer, $AccessToken, $Method, $Url, $Params);

        $SignatureMethod = new OAuthSignatureMethod_HMAC_SHA1();
        $Request->sign_request($SignatureMethod, $Consumer, $AccessToken);

        $Curl = $this->_curl($Request, $Post);
        curl_setopt($Curl, CURLINFO_HEADER_OUT, true);

        $Response = curl_exec($Curl);
        $HttpCode = curl_getinfo($Curl, CURLINFO_HTTP_CODE);

        if ($Response == false) {
            $Response = curl_error($Curl);
        }

        trace(curl_getinfo($Curl, CURLINFO_HEADER_OUT));
        trace($Response, 'Response');

        curl_close($Curl);

        Gdn::controller()->setJson('Response', $Response);

        if (strpos($Url, '.json') !== false) {
            $Result = @json_decode($Response, true) or $Response;
        } else {
            $Result = $Response;
        }

        if ($HttpCode == '200') {
            return $Result;
        } else {
            throw new Gdn_UserException(valr('errors.0.message', $Result, $Response), $HttpCode);
        }
    }

    /**
     * Retrieve user's Twitter profile via API.
     *
     * @return mixed Profile data.
     * @throws Gdn_UserException
     */
    public function getProfile() {
        $Profile = $this->api('/account/verify_credentials.json', ['include_entities' => '0', 'skip_status' => '1']);
        return $Profile;
    }

    /**
     * Retrieve our stored OAuth token.
     *
     * @param $token
     * @return null|OAuthToken
     */
    public function getOAuthToken($token) {
        $uatModel = new UserAuthenticationTokenModel();
        $result = null;
        $row = $uatModel->getWhere([
            'Token' => $token,
            'ProviderKey' => self::ProviderKey
        ])->firstRow(DATASET_TYPE_ARRAY);

        if ($row) {
            $result = new OAuthToken($row['Token'], $row['TokenSecret']);
        }
        return $result;
    }

    /**
     * Whether this addon had enough config done to work.
     *
     * @return bool
     */
    public function isConfigured() {
        $Result = c('Plugins.Twitter.ConsumerKey') && c('Plugins.Twitter.Secret');
        return $Result;
    }

    /**
     * Whether social sharing is enabled & ready.
     *
     * @return bool
     */
    public function socialSharing() {
        return c('Plugins.Twitter.SocialSharing', true) && $this->isConfigured();
    }

    /**
     * Whether social reactions are enabled & ready.
     *
     * @return bool
     */
    public function socialReactions() {
        return c('Plugins.Twitter.SocialReactions', true) && $this->isConfigured();
    }

    /**
     * Whether social signin is enabled & ready.
     *
     * @return bool
     */
    public function socialSignIn() {
        return c('Plugins.Twitter.SocialSignIn', true) && $this->isConfigured();
    }

    /**
     * Save an OAuth token for use.
     *
     * @param $token
     * @param null $secret
     * @param string $type
     * @return bool
     */
    public function setOAuthToken($token, $secret = null, $type = 'request') {
        $uatModel = new UserAuthenticationTokenModel();
        $result = false;

        if (is_a($token, 'OAuthToken')) {
            $secret = $token->secret;
            $token = $token->key;
        }

        $set = [
            'TokenSecret' => $secret,
            'TokenType' => $type,
            'Authorized' => 0,
            'Lifetime' => 60 * 5
        ];
        $where = [
            'Token' => $token,
            'ProviderKey' => self::ProviderKey
        ];
        $row = $uatModel->getWhere($where, '', '', 1)->firstRow();

        if ($row === false) {
            $result = $uatModel->insert(array_merge($set, $where));
        }

        return $result;
    }

    /**
     * Remove an OAuth token from the database.
     *
     * @param string $token
     */
    public function deleteOAuthToken($token) {
        $uatModel = new UserAuthenticationTokenModel();

        if (is_a($token, 'OAuthToken')) {
            $token = $token->key;
        }

        $uatModel->delete([
            'Token' => $token,
            'ProviderKey' => self::ProviderKey
        ]);
    }

    /**
     * Configure a cURL request.
     *
     * @param OAuthRequest $Request
     * @param $Post Deprecated
     */
    protected function _curl($Request, $Post = null) {
        $C = curl_init();
        curl_setopt($C, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($C, CURLOPT_SSL_VERIFYPEER, false);
        switch ($Request->get_normalized_http_method()) {
            case 'POST':
                curl_setopt($C, CURLOPT_URL, $Request->get_normalized_http_url());
                curl_setopt($C, CURLOPT_POST, true);
                curl_setopt($C, CURLOPT_POSTFIELDS, $Request->to_postdata());
                break;
            default:
                curl_setopt($C, CURLOPT_URL, $Request->to_url());
        }
        return $C;
    }

    /**
     * Get the URL for connecting on your profile.
     *
     * @return string
     */
    public static function profileConnecUrl() {
        return url(userUrl(Gdn::session()->User, false, 'twitterconnect'), true);
    }

    /**
     * Where to redirect a user after authorization.
     *
     * @param null $NewValue
     * @return null|string
     */
    public function redirectUri($NewValue = null) {
        if ($NewValue !== null) {
            $this->_RedirectUri = $NewValue;
        } elseif ($this->_RedirectUri === null) {
            $RedirectUri = url('/entry/connect/twitter', true);
            $this->_RedirectUri = $RedirectUri;
        }

        return $this->_RedirectUri;
    }

    /**
     * Add 'Twitter' option to the reactions row for users.
     *
     * @param Gdn_Controller $Sender
     * @param array $Args
     */
    public function base_AfterReactions_handler($Sender, $Args) {
        if (!$this->socialReactions()) {
            return;
        }

        echo Gdn_Theme::bulletItem('Share');
        $this->addReactButton($Sender, $Args);
    }

    /**
     * Output Quote link for sharing on Twitter.
     *
     * @param Gdn_Controller $Sender
     * @param array $Args
     */
    protected function addReactButton($Sender, $Args) {
        if ($this->accessToken()) {
            $Url = url("post/twitter/{$Args['RecordType']}?id={$Args['RecordID']}", true);
            $CssClass = 'ReactButton Hijack';
        } else {
            $Url = url("post/twitter/{$Args['RecordType']}?id={$Args['RecordID']}", true);
            $CssClass = 'ReactButton PopupWindow';
        }

        echo anchor(sprite('ReactTwitter', 'Sprite ReactSprite', t('Share on Twitter')), $Url, $CssClass);
    }

    /**
     * Endpoint for configuring this addon.
     *
     * @param socialController $Sender
     * @param array $Args
     */
    public function socialController_twitter_create($Sender, $Args) {
        $Sender->permission('Garden.Settings.Manage');
        if ($Sender->Form->authenticatedPostBack()) {
            $Settings = [
                'Plugins.Twitter.ConsumerKey' => trim($Sender->Form->getFormValue('ConsumerKey')),
                'Plugins.Twitter.Secret' => trim($Sender->Form->getFormValue('Secret')),
                'Plugins.Twitter.SocialSignIn' => $Sender->Form->getFormValue('SocialSignIn'),
                'Plugins.Twitter.SocialReactions' => $Sender->Form->getFormValue('SocialReactions'),
                'Plugins.Twitter.SocialSharing' => $Sender->Form->getFormValue('SocialSharing')
            ];

            saveToConfig($Settings);
            $Sender->informMessage(t("Your settings have been saved."));

        } else {
            $Sender->Form->setValue('ConsumerKey', c('Plugins.Twitter.ConsumerKey'));
            $Sender->Form->setValue('Secret', c('Plugins.Twitter.Secret'));
            $Sender->Form->setValue('SocialSignIn', $this->SocialSignIn());
            $Sender->Form->setValue('SocialReactions', $this->SocialReactions());
            $Sender->Form->setValue('SocialSharing', $this->SocialSharing());
        }

        $Sender->setHighlightRoute('dashboard/social');
        $Sender->setData('Title', t('Twitter Settings'));
        $Sender->render('Settings', '', 'plugins/Twitter');
    }

    /**
     * Run once on enable.
     *
     * @throws Gdn_UserException
     */
    public function setup() {
        // Make sure the user has curl.
        if (!function_exists('curl_exec')) {
            throw new Gdn_UserException('This plugin requires cURL for PHP.');
        }

        $this->structure();
    }

    /**
     * Perform any necessary database or configuration updates.
     */
    public function structure() {
        // Save the twitter provider type.
        Gdn::sql()->replace(
            'UserAuthenticationProvider',
            ['AuthenticationSchemeAlias' => 'twitter', 'URL' => '...', 'AssociationSecret' => '...', 'AssociationHashMethod' => '...'],
            ['AuthenticationKey' => self::ProviderKey],
            true
        );
    }
}

/**
 * Truncate a message to appropriate Twitter length.
 *
 * @param string $Str Input message to be truncated.
 * @return string Resulting message.
 */
function sliceTwitter($Str) {

    $Elips = '...';
    $Str = preg_replace('`\s+`', ' ', $Str);

    $Max = 140;
    $LinkLen = 22;
    $Max -= $LinkLen;

    $Str = sliceParagraph($Str, $Max);
    if (strlen($Str) > $Max) {
        $Str = substr($Str, 0, $Max - strlen($Elips)).$Elips;
    }

    return $Str;
}
