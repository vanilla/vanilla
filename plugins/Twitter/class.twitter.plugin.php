<?php
/**
 * Twitter plugin.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Twitter
 */

/**
 * Class TwitterPlugin
 */
class TwitterPlugin extends Gdn_Plugin {

    /** Authentication provider key. */
    const PROVIDER_KEY = 'Twitter';

    /** @var string Twitter's URL. */
    public static $BaseApiUrl = 'https://api.twitter.com/1.1/';

    /** @var string */
    protected $_AccessToken = null;

    /** @var string */
    protected $_RedirectUri = null;

    /**
     * Gets/sets the current oauth access token.
     *
     * @param string $token
     * @param string $secret
     *
     * @return OAuthToken
     */
    public function accessToken($token = null, $secret = null) {
        if (!$this->isConfigured()) {
            return false;
        }

        if (is_object($token)) {
            $this->_AccessToken = $token;
        }
        if ($token !== null && $secret !== null) {
            $this->_AccessToken = new OAuthToken($token, $secret);
        } elseif ($this->_AccessToken == null) {
            if ($token) {
                $this->_AccessToken = $this->getOAuthToken($token);
            } elseif (Gdn::session()->User) {
                $accessToken = valr(self::PROVIDER_KEY.'.AccessToken', Gdn::session()->User->Attributes);

                if (is_array($accessToken)) {
                    $this->_AccessToken = new OAuthToken($accessToken[0], $accessToken[1]);
                }
            }
        }
        return $this->_AccessToken;
    }

    /**
     * Retreieve the URL to start an auth request.
     *
     * @param bool $popup
     *
     * @return string
     */
    protected function _authorizeHref($popup = false) {
        $url = url('/entry/twauthorize', true);
        $urlParts = explode('?', $url);

        parse_str(val(1, $urlParts, ''), $query);
        $path = Gdn::request()->path();

        $target = val('Target', $_GET, $path ? $path : '/');
        if (ltrim($target, '/') == 'entry/signin') {
            $target = '/';
        }
        $query['Target'] = $target;

        if ($popup) {
            $query['display'] = 'popup';
        }
        $result = $urlParts[0].'?'.http_build_query($query);

        return $result;
    }

    /**
     * Add Twitter option to the normal signin page.
     *
     * @param Gdn_Controller $sender
     */
    public function entryController_signIn_handler($sender, $args) {
        if (isset($sender->Data['Methods'])) {
            if (!$this->socialSignIn()) {
                return;
            }

            $url = $this->_authorizeHref();

            // Add the twitter method to the controller.
            $twMethod = [
                'Name' => 'Twitter',
                'SignInHtml' => socialSigninButton('Twitter', $url, 'button', ['class' => 'js-extern'])
            ];

            $sender->Data['Methods'][] = $twMethod;
        }
    }

    /**
     * Add Twitter signin to MeModule.
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
     * Add Twitter signin to GuestModule.
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
     * Add Twitter signin to mobile theme.
     *
     * @param Gdn_Controller $sender
     */
    public function base_beforeSignInLink_handler($sender) {
        if (!$this->socialSignIn()) {
            return;
        }

        if (!Gdn::session()->isValid()) {
            echo "\n".wrap($this->_getButton(), 'li', ['class' => 'Connect TwitterConnect']);
        }
    }

    /**
     * Add an option to share a discussion via Twitter manually.
     *
     * @param Gdn_Controller $sender
     * @param array $args
     */
    public function base_discussionFormOptions_handler($sender, $args) {
        if (!$this->socialSharing() || !$this->accessToken()) {
            return;
        }

        $options =& $args['Options'];
        $options .= ' <li>'.
            $sender->Form->checkBox('ShareTwitter', '@'.sprite('ReactTwitter', 'ReactSprite'), ['value' => '1', 'title' => sprintf(t('Share to %s.'), 'Twitter')]).
            '</li> ';
    }

    /**
     * Add option to share a comment via Twitter as you make it.
     *
     * @param discussionController $sender
     * @param array $args
     */
    public function discussionController_afterBodyField_handler($sender, $args) {
        if (!$this->socialSharing() || !$this->accessToken()) {
            return;
        }

        echo ' '.
            $sender->Form->checkBox('ShareTwitter', '@'.sprite('ReactTwitter', 'ReactSprite'), ['value' => '1', 'title' => sprintf(t('Share to %s.'), 'Twitter')]).
            ' ';
    }

    /**
     * Share the discussion you just started to Twitter if you chose to.
     *
     * @param discussionModel $sender
     * @param array $args
     *
     * @throws Gdn_UserException
     */
    public function discussionModel_afterSaveDiscussion_handler($sender, $args) {
        if (!$this->socialSharing() || !$this->accessToken()) {
            return;
        }

        $share = valr('FormPostValues.ShareTwitter', $args);

        if ($share && $this->accessToken()) {
            $row = $args['Fields'];
            $url = discussionUrl($row, '', true);
            $message = sliceTwitter(Gdn_Format::plainText($row['Body'], $row['Format'])).' '.$url;

            $r = $this->api(
                '/statuses/update.json',
                [
                'status' => $message
                ],
                'POST'
            );
        }
    }

    /**
     * Share the comment you just made to Twitter if you chose to.
     *
     * @param commentModel $sender
     * @param array $args
     *
     * @throws Gdn_UserException
     */
    public function commentModel_afterSaveComment_handler($sender, $args) {
        if (!$this->socialSharing() || !$this->accessToken()) {
            return;
        }

        $share = valr('FormPostValues.ShareTwitter', $args);

        if ($share && $this->accessToken()) {
            $row = $args['FormPostValues'];

            $discussionModel = new DiscussionModel();
            $discussion = $discussionModel->getID(val('DiscussionID', $row));
            if (!$discussion) {
                return;
            }

            $url = discussionUrl($discussion, '', true);
            $message = sliceTwitter(Gdn_Format::plainText($row['Body'], $row['Format'])).' '.$url;

            $r = $this->aPI(
                '/statuses/update.json',
                [
                'status' => $message
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
        $url = $this->_authorizeHref();

        return socialSigninButton('Twitter', $url, 'icon', ['class' => 'js-extern', 'rel' => 'nofollow']);
    }

    /**
     * Authorize the current user against Twitter's OAuth.
     *
     * @param bool $query
     */
    public function authorize($query = false) {
        // Acquire the request token.
        $consumer = new OAuthConsumer(c('Plugins.Twitter.ConsumerKey'), c('Plugins.Twitter.Secret'));
        $redirectUri = $this->redirectUri();
        if ($query) {
            $redirectUri .= (strpos($redirectUri, '?') === false ? '?' : '&').$query;
        }

        $params = ['oauth_callback' => $redirectUri];

        $url = 'https://api.twitter.com/oauth/request_token';
        $request = OAuthRequest::from_consumer_and_token($consumer, null, 'POST', $url, $params);
        $signatureMethod = new OAuthSignatureMethod_HMAC_SHA1();
        $request->sign_request($signatureMethod, $consumer, null);

        $curl = $this->_Curl($request, $params);
        $response = curl_exec($curl);
        if ($response === false) {
            $response = curl_error($curl);
        }

        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($httpCode == '200') {
            // Parse the reponse.
            $data = OAuthUtil::parse_parameters($response);

            if (!isset($data['oauth_token']) || !isset($data['oauth_token_secret'])) {
                $response = t('The response was not in the correct format.');
            } else {
                // Save the token for later reference.
                $this->setOAuthToken($data['oauth_token'], $data['oauth_token_secret'], 'request');

                // Redirect to twitter's authorization page.
                $url = "https://api.twitter.com/oauth/authenticate?oauth_token={$data['oauth_token']}";
                redirectTo($url, 302, false);
            }
        }

        // There was an error. Echo the error.
        echo $response;
    }

    /**
     * Send user to the OAuth authorization page via cleverly-named endpoint.
     *
     * See, because it's Twitter...
     *
     * @param $sender
     * @param string $dir
     */
    public function entryController_twauthorize_create($sender, $dir = '') {
        $query = arrayTranslate($sender->Request->get(), ['display', 'Target']);
        $query = http_build_query($query);

        if ($dir == 'profile') {
            // This is a profile connection.
            $this->redirectUri(self::profileConnecUrl());
        }

        $this->authorize($query);
    }

    /**
     * Post to Twitter.
     *
     * @param PostController $sender
     * @param string $recordType
     * @param int $iD
     *
     * @throws Gdn_UserException
     */
    public function postController_twitter_create($sender, $recordType, $iD) {
        if (!$this->socialReactions()) {
            throw permissionException();
        }

        $row = getRecord($recordType, $iD, true);
        if ($row) {
            // Grab the tweet message.
            switch (strtolower($recordType)) {
                case 'discussion':
                    $message = Gdn_Format::plainText($row['Name'], 'Text');
                    break;
                case 'comment':
                default:
                    $message = Gdn_Format::plainText($row['Body'], $row['Format']);
            }

            // WHY ARE WE REPEATING THE `sliceTwitter()` FUNCTION BELOW?
            // Dammit, fellas, ima hang y'uns out to DRY.
            $elips = '...';
            $message = preg_replace('`\s+`', ' ', $message);

            $max = 140;
            $linkLen = 22;
            $max -= $linkLen;

            $message = sliceParagraph($message, $max);
            if (strlen($message) > $max) {
                $message = substr($message, 0, $max - strlen($elips)).$elips;
            }

            if ($this->accessToken()) {
                Gdn::controller()->setData('Message', $message);

                $message .= ' '.$row['ShareUrl'];
                $r = $this->api(
                    '/statuses/update.json',
                    [
                    'status' => $message
                    ],
                    'POST'
                );

                $sender->setJson('R', $r);
                $sender->informMessage(t('Thanks for sharing!'));
            } else {
                $get = [
                    'text' => $message,
                    'url' => $row['ShareUrl']
                ];
                $url = "https://twitter.com/share?".http_build_query($get);
                redirectTo($url, 302, false);
            }
        }

        $sender->render('Blank', 'Utility', 'Dashboard');
    }

    /**
     * Endpoint to connect to Twitter via user profile.
     *
     * @param ProfileController $sender
     * @param mixed $userReference
     * @param string $username
     * @param string $oauth_token
     * @param string $oauth_verifier
     */
    public function profileController_twitterConnect_create($sender, $userReference = '', $username = '', $oauth_token = '', $oauth_verifier = '') {
        $sender->permission('Garden.SignIn.Allow');

        $sender->getUserInfo($userReference, $username, '', true);

        $sender->_setBreadcrumbs(t('Connections'), '/profile/connections');

        // Get the access token.
        trace('GetAccessToken()');
        $accessToken = $this->getAccessToken($oauth_token, $oauth_verifier);
        $this->accessToken($accessToken);

        // Get the profile.
        trace('GetProfile()');
        $profile = $this->getProfile();

        // Save the authentication.
        Gdn::userModel()->saveAuthentication([
            'UserID' => $sender->User->UserID,
            'Provider' => self::PROVIDER_KEY,
            'UniqueID' => $profile['id']]);

        // Save the information as attributes.
        $attributes = [
            'AccessToken' => [$accessToken->key, $accessToken->secret],
            'Profile' => $profile
        ];
        Gdn::userModel()->saveAttribute($sender->User->UserID, self::PROVIDER_KEY, $attributes);

        $this->EventArguments['Provider'] = self::PROVIDER_KEY;
        $this->EventArguments['User'] = $sender->User;
        $this->fireEvent('AfterConnection');

        redirectTo(userUrl($sender->User, '', 'connections'));
    }

    /**
     * Get an access token from Twitter.
     *
     * @param string $requestToken
     * @param string $verifier
     *
     * @return string OAuthToken
     * @throws Gdn_UserException
     */
    public function getAccessToken($requestToken, $verifier) {
        if ((!$requestToken || !$verifier) && Gdn::request()->get('denied')) {
            throw new Gdn_UserException(t('Looks like you denied our request.'), 401);
        }

        // Get the request secret.
        $requestToken = $this->getOAuthToken($requestToken);
        if (!$requestToken) {
            throw new Gdn_UserException('Token was not found or is invalid for the current action.');
        }

        $consumer = new OAuthConsumer(c('Plugins.Twitter.ConsumerKey'), c('Plugins.Twitter.Secret'));

        $url = 'https://api.twitter.com/oauth/access_token';
        $params = [
            'oauth_verifier' => $verifier //GetValue('oauth_verifier', $_GET)
        ];
        $request = OAuthRequest::from_consumer_and_token($consumer, $requestToken, 'POST', $url, $params);

        $signatureMethod = new OAuthSignatureMethod_HMAC_SHA1();
        $request->sign_request($signatureMethod, $consumer, $requestToken);
        $post = $request->to_postdata();

        $curl = $this->_curl($request);
        $response = curl_exec($curl);
        if ($response === false) {
            $response = curl_error($curl);
        }
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($httpCode == '200') {
            $data = OAuthUtil::parse_parameters($response);

            $accessToken = new OAuthToken(val('oauth_token', $data), val('oauth_token_secret', $data));

            // Delete the request token.
            $this->deleteOAuthToken($requestToken);

        } else {
            // There was some sort of error.
            throw new Gdn_UserException('There was an error authenticating with twitter. '.$response, $httpCode);
        }

        return $accessToken;
    }

    /**
     * Generic SSO hook into Vanilla for authorization and data transfer.
     *
     * @param Gdn_Controller $sender
     * @param array $args
     */
    public function base_connectData_handler($sender, $args) {
        if (val(0, $args) != 'twitter') {
            return;
        }

        /** @var Gdn_Form $form */
        $form = $sender->Form;

        $requestToken = val('oauth_token', $_GET);
        $accessToken = $form->getFormValue('AccessToken');

        if ($accessToken) {
            $accessToken = $this->getOAuthToken($accessToken);
            $this->accessToken($accessToken);
        }

        // Get the access token.
        if ($requestToken && !$accessToken) {
            // Get the request secret.
            $requestToken = $this->getOAuthToken($requestToken);

            $consumer = new OAuthConsumer(c('Plugins.Twitter.ConsumerKey'), c('Plugins.Twitter.Secret'));

            $url = 'https://api.twitter.com/oauth/access_token';
            $params = [
                'oauth_verifier' => val('oauth_verifier', $_GET)
            ];
            $request = OAuthRequest::from_consumer_and_token($consumer, $requestToken, 'POST', $url, $params);

            $signatureMethod = new OAuthSignatureMethod_HMAC_SHA1();
            $request->sign_request($signatureMethod, $consumer, $requestToken);
            $post = $request->to_postdata();

            $curl = $this->_Curl($request);
            $response = curl_exec($curl);
            if ($response === false) {
                $response = curl_error($curl);
            }
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            if ($httpCode == '200') {
                $data = OAuthUtil::parse_parameters($response);

                $accessToken = new OAuthToken(val('oauth_token', $data), val('oauth_token_secret', $data));

                // Save the access token to the database.
                $this->setOAuthToken($accessToken->key, $accessToken->secret, 'access');
                $this->accessToken($accessToken->key, $accessToken->secret);

                // Delete the request token.
                $this->deleteOAuthToken($requestToken);

            } else {
                // There was some sort of error.
                throw new Exception('There was an error authenticating with twitter.', 400);
            }

            $newToken = true;
        }

        // Get the profile.
        try {
            $profile = $this->getProfile($accessToken);
        } catch (Exception $ex) {
            if (!isset($newToken)) {
                // There was an error getting the profile, which probably means the saved access token is no longer valid. Try and reauthorize.
                if ($sender->deliveryType() == DELIVERY_TYPE_ALL) {
                    redirectTo($this->_AuthorizeHref(), 302, false);
                } else {
                    $sender->setHeader('Content-type', 'application/json');
                    $sender->deliveryMethod(DELIVERY_METHOD_JSON);
                    $sender->setRedirectTo($this->_authorizeHref(), false);
                }
            } else {
                throw $ex;
            }
        }

        // This isn't a trusted connection. Don't allow it to automatically connect a user account.
        saveToConfig('Garden.Registration.AutoConnect', false, false);

        $iD = val('id', $profile);
        $form->setFormValue('UniqueID', $iD);
        $form->setFormValue('Provider', self::PROVIDER_KEY);
        $form->setFormValue('ProviderName', 'Twitter');
        $form->setValue('ConnectName', val('screen_name', $profile));
        $form->setFormValue('Name', val('screen_name', $profile));
        $form->setFormValue('FullName', val('name', $profile));
        $form->setFormValue('Photo', val('profile_image_url_https', $profile));
        $form->addHidden('AccessToken', $accessToken->key);

        // Save some original data in the attributes of the connection for later API calls.
        $attributes = [self::PROVIDER_KEY => [
            'AccessToken' => [$accessToken->key, $accessToken->secret],
            'Profile' => $profile
        ]];
        $form->setFormValue('Attributes', $attributes);

        $sender->setData('Verified', true);
    }

    /**
     * Make Twitter available as an SSO provider.
     *
     * @param $sender
     * @param $args
     */
    public function base_getConnections_handler($sender, $args) {
        $profile = valr('User.Attributes.'.self::PROVIDER_KEY.'.Profile', $args);

        $sender->Data["Connections"][self::PROVIDER_KEY] = [
            'Icon' => $this->getWebResource('icon.png', '/'),
            'Name' => 'Twitter',
            'ProviderKey' => self::PROVIDER_KEY,
            'ConnectUrl' => '/entry/twauthorize/profile',
            'Profile' => [
                'Name' => '@'.getValue('screen_name', $profile),
                'Photo' => val('profile_image_url_https', $profile)
            ]
        ];
    }

    /**
     * Make an API request to Twitter.
     *
     * @param string $url
     * @param array|null $params
     * @param string $method GET or POST.
     *
     * @return mixed Response from the API.
     * @throws Gdn_UserException
     */
    public function api($url, $params = null, $method = 'GET') {
        if (strpos($url, '//') === false) {
            $url = self::$BaseApiUrl.trim($url, '/');
        }
        $consumer = new OAuthConsumer(c('Plugins.Twitter.ConsumerKey'), c('Plugins.Twitter.Secret'));

        if ($method == 'POST') {
            $post = $params;
        } else {
            $post = null;
        }

        $accessToken = $this->accessToken();
        $request = OAuthRequest::from_consumer_and_token($consumer, $accessToken, $method, $url, $params);

        $signatureMethod = new OAuthSignatureMethod_HMAC_SHA1();
        $request->sign_request($signatureMethod, $consumer, $accessToken);

        $curl = $this->_curl($request, $post);
        curl_setopt($curl, CURLINFO_HEADER_OUT, true);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if ($response == false) {
            $response = curl_error($curl);
        }

        trace(curl_getinfo($curl, CURLINFO_HEADER_OUT));
        trace($response, 'Response');

        curl_close($curl);

        Gdn::controller()->setJson('Response', $response);

        if (strpos($url, '.json') !== false) {
            $result = @json_decode($response, true) or $response;
        } else {
            $result = $response;
        }

        if ($httpCode == '200') {
            return $result;
        } else {
            throw new Gdn_UserException(valr('errors.0.message', $result, $response), $httpCode);
        }
    }

    /**
     * Retrieve user's Twitter profile via API.
     *
     * @return mixed Profile data.
     * @throws Gdn_UserException
     */
    public function getProfile() {
        $profile = $this->api('/account/verify_credentials.json', ['include_entities' => '0', 'skip_status' => '1']);
        return $profile;
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
            'ProviderKey' => self::PROVIDER_KEY
        ])->firstRow(DATASET_TYPE_ARRAY);

        if ($row) {
            $canUseToken = false;
            if (!empty($row['ForeignUserKey'])) {
                if (Gdn::session()->isValid() && $row['ForeignUserKey'] == Gdn::session()->UserID) {
                    $canUseToken = true;
                }
            } else {
                $canUseToken = true;
            }

            if ($canUseToken) {
                $result = new OAuthToken($row['Token'], $row['TokenSecret']);
            }
        }
        return $result;
    }

    /**
     * Whether this addon had enough config done to work.
     *
     * @return bool
     */
    public function isConfigured() {
        $result = c('Plugins.Twitter.ConsumerKey') && c('Plugins.Twitter.Secret');
        return $result;
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
            'ForeignUserKey' => Gdn::session()->isValid() ? Gdn::session()->UserID : 0,
            'Authorized' => 0,
            'Lifetime' => 60 * 5
        ];
        $where = [
            'Token' => $token,
            'ProviderKey' => self::PROVIDER_KEY
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
            'ProviderKey' => self::PROVIDER_KEY
        ]);
    }

    /**
     * Configure a cURL request.
     *
     * @param OAuthRequest $request
     * @param $post Deprecated
     */
    protected function _curl($request, $post = null) {
        $c = curl_init();
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
        switch ($request->get_normalized_http_method()) {
            case 'POST':
                curl_setopt($c, CURLOPT_URL, $request->get_normalized_http_url());
                curl_setopt($c, CURLOPT_POST, true);
                curl_setopt($c, CURLOPT_POSTFIELDS, $request->to_postdata());
                break;
            default:
                curl_setopt($c, CURLOPT_URL, $request->to_url());
        }
        return $c;
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
     * @param null $newValue
     * @return null|string
     */
    public function redirectUri($newValue = null) {
        if ($newValue !== null) {
            $this->_RedirectUri = $newValue;
        } elseif ($this->_RedirectUri === null) {
            $redirectUri = url('/entry/connect/twitter', true);
            $this->_RedirectUri = $redirectUri;
        }

        return $this->_RedirectUri;
    }

    /**
     * Add 'Twitter' option to the reactions row for users.
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
     * Output Quote link for sharing on Twitter.
     *
     * @param Gdn_Controller $sender
     * @param array $args
     */
    protected function addReactButton($sender, $args) {
        if ($this->accessToken()) {
            $url = url("post/twitter/{$args['RecordType']}?id={$args['RecordID']}", true);
            $cssClass = 'ReactButton Hijack';
        } else {
            $url = url("post/twitter/{$args['RecordType']}?id={$args['RecordID']}", true);
            $cssClass = 'ReactButton PopupWindow';
        }

        echo anchor(sprite('ReactTwitter', 'Sprite ReactSprite', t('Share on Twitter')), $url, $cssClass);
    }

    /**
     * Endpoint for configuring this addon.
     *
     * @param socialController $sender
     * @param array $args
     */
    public function socialController_twitter_create($sender, $args) {
        $sender->permission('Garden.Settings.Manage');
        if ($sender->Form->authenticatedPostBack()) {
            $settings = [
                'Plugins.Twitter.ConsumerKey' => trim($sender->Form->getFormValue('ConsumerKey')),
                'Plugins.Twitter.Secret' => trim($sender->Form->getFormValue('Secret')),
                'Plugins.Twitter.SocialSignIn' => $sender->Form->getFormValue('SocialSignIn'),
                'Plugins.Twitter.SocialReactions' => $sender->Form->getFormValue('SocialReactions'),
                'Plugins.Twitter.SocialSharing' => $sender->Form->getFormValue('SocialSharing')
            ];

            saveToConfig($settings);
            $sender->informMessage(t("Your settings have been saved."));

        } else {
            $sender->Form->setValue('ConsumerKey', c('Plugins.Twitter.ConsumerKey'));
            $sender->Form->setValue('Secret', c('Plugins.Twitter.Secret'));
            $sender->Form->setValue('SocialSignIn', $this->socialSignIn());
            $sender->Form->setValue('SocialReactions', $this->socialReactions());
            $sender->Form->setValue('SocialSharing', $this->socialSharing());
        }

        $sender->setHighlightRoute('dashboard/social');
        $sender->setData('Title', t('Twitter Settings'));
        $sender->render('Settings', '', 'plugins/Twitter');
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
            ['AuthenticationKey' => self::PROVIDER_KEY],
            true
        );
    }
}

/**
 * Truncate a message to appropriate Twitter length.
 *
 * @param string $str Input message to be truncated.
 * @return string Resulting message.
 */
function sliceTwitter($str) {

    $elips = '...';
    $str = preg_replace('`\s+`', ' ', $str);

    $max = 140;
    $linkLen = 22;
    $max -= $linkLen;

    $str = sliceParagraph($str, $max);
    if (strlen($str) > $max) {
        $str = substr($str, 0, $max - strlen($elips)).$elips;
    }

    return $str;
}
