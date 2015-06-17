<?php
/**
 * Twitter plugin.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Twitter
 */

// Define the plugin:
$PluginInfo['Twitter'] = array(
    'Name' => 'Twitter Social Connect',
    'Description' => 'Users may sign into your site using their Twitter account.',
    'Version' => '1.1.10',
    'RequiredApplications' => array('Vanilla' => '2.2'),
    'MobileFriendly' => true,
    'SettingsUrl' => '/dashboard/social/twitter',
    'SettingsPermission' => 'Garden.Settings.Manage',
    'HasLocale' => true,
    'Author' => "Todd Burry",
    'AuthorEmail' => 'todd@vanillaforums.com',
    'AuthorUrl' => 'http://www.vanillaforums.org/profile/todd',
    'SocialConnect' => true,
    'RequiresRegistration' => true
);

require_once PATH_LIBRARY.'/vendors/oauth/OAuth.php';

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
//         safeCookie('tw_access_token', $Token, 0, c('Garden.Cookie.Path', '/'), c('Garden.Cookie.Domain', ''));
        } elseif ($this->_AccessToken == null) {
//         $Token = val('tw_access_token', $_COOKIE, null);
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

//   public function AuthenticationController_render_before($Sender, $Args) {
//      if (isset($Sender->ChooserList)) {
//         $Sender->ChooserList['twitter'] = 'Twitter';
//      }
//      if (is_array($Sender->data('AuthenticationConfigureList'))) {
//         $List = $Sender->data('AuthenticationConfigureList');
//         $List['twitter'] = '/dashboard/settings/twitter';
//         $Sender->setData('AuthenticationConfigureList', $List);
//      }
//   }

    /**
     *
     *
     * @param bool $Popup
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
     *
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
            $TwMethod = array(
                'Name' => 'Twitter',
                'SignInHtml' => socialSigninButton('Twitter', $Url, 'button', array('class' => 'js-extern'))
            );

            $Sender->Data['Methods'][] = $TwMethod;
        }
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
    public function base_BeforeSignInButton_handler($Sender, $Args) {
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

        // if (!IsMobile())
        // 	return;

        if (!Gdn::session()->isValid()) {
            echo "\n".Wrap($this->_getButton(), 'li', array('class' => 'Connect TwitterConnect'));
        }
    }

    /**
     *
     *
     * @param $Sender
     * @param $Args
     */
    public function base_discussionFormOptions_handler($Sender, $Args) {
        if (!$this->socialSharing() || !$this->accessToken()) {
            return;
        }

        $Options =& $Args['Options'];
        $Options .= ' <li>'.
            $Sender->Form->checkBox('ShareTwitter', '@'.Sprite('ReactTwitter', 'ReactSprite'), array('value' => '1', 'title' => sprintf(t('Share to %s.'), 'Twitter'))).
            '</li> ';
    }

    /**
     *
     *
     * @param $Sender
     * @param $Args
     */
    public function discussionController_afterBodyField_handler($Sender, $Args) {
        if (!$this->socialSharing() || !$this->accessToken()) {
            return;
        }

        echo ' '.
            $Sender->Form->checkBox('ShareTwitter', '@'.Sprite('ReactTwitter', 'ReactSprite'), array('value' => '1', 'title' => sprintf(t('Share to %s.'), 'Twitter'))).
            ' ';
    }

    /**
     *
     *
     * @param $Sender
     * @param $Args
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
                array(
                'status' => $Message
                ),
                'POST'
            );
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
                array(
                'status' => $Message
                ),
                'POST'
            );
        }
    }

    /**
     *
     *
     * @return string
     */
    private function _getButton() {
        $Url = $this->_authorizeHref();

        return socialSigninButton('Twitter', $Url, 'icon', array('class' => 'js-extern', 'rel' => 'nofollow'));
    }

    /**
     *
     *
     * @param bool $Query
     */
    public function authorize($Query = false) {
        // Aquire the request token.
        $Consumer = new OAuthConsumer(c('Plugins.Twitter.ConsumerKey'), c('Plugins.Twitter.Secret'));
        $RedirectUri = $this->redirectUri();
        if ($Query) {
            $RedirectUri .= (strpos($RedirectUri, '?') === false ? '?' : '&').$Query;
        }

        $Params = array('oauth_callback' => $RedirectUri);

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
                redirect($Url);
            }
        }

        // There was an error. Echo the error.
        echo $Response;
    }

    /**
     *
     *
     * @param $Sender
     * @param string $Dir
     */
    public function entryController_twauthorize_create($Sender, $Dir = '') {
        $Query = arrayTranslate($Sender->Request->get(), array('display', 'Target'));
        $Query = http_build_query($Query);

        if ($Dir == 'profile') {
            // This is a profile connection.
            $this->redirectUri(self::profileConnecUrl());
        }

        $this->authorize($Query);
    }

    /**
     *
     *
     * @param PostController $Sender
     * @param type $RecordType
     * @param type $ID
     * @throws type
     */
    public function postController_twitter_create($Sender, $RecordType, $ID) {
        if (!$this->socialReactions()) {
            throw permissionException();
        }

//      if (!Gdn::request()->isPostBack())
//         throw permissionException('Javascript');

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

            $Elips = '...';

            $Message = preg_replace('`\s+`', ' ', $Message);

//         if (function_exists('normalizer_is_normalized')) {
//            // Slice the string to 119 characters (21 reservered for the url.
//            if (!normalizer_is_normalized($Message))
//               $Message = Normalizer::normalize($Message, Normalizer::FORM_D);
//            $Elips = Normalizer::normalize($Elips, Normalizer::FORM_D);
//         }

            $Max = 140;
            $LinkLen = 22;

            $Max -= $LinkLen;

            $Message = SliceParagraph($Message, $Max);
            if (strlen($Message) > $Max) {
                $Message = substr($Message, 0, $Max - strlen($Elips)).$Elips;
            }

//         echo $Message.strlen($Message);

            if ($this->accessToken()) {
                Gdn::controller()->setData('Message', $Message);

                $Message .= ' '.$Row['ShareUrl'];
                $R = $this->api(
                    '/statuses/update.json',
                    array(
                    'status' => $Message
                    ),
                    'POST'
                );

                $Sender->setJson('R', $R);
                $Sender->informMessage(t('Thanks for sharing!'));
            } else {
                $Get = array(
                    'text' => $Message,
                    'url' => $Row['ShareUrl']
                );
                $Url = "https://twitter.com/share?".http_build_query($Get);
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
     * @param type $oauth_token
     * @param type $oauth_verifier
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
        Gdn::userModel()->saveAuthentication(array(
            'UserID' => $Sender->User->UserID,
            'Provider' => self::ProviderKey,
            'UniqueID' => $Profile['id']));

        // Save the information as attributes.
        $Attributes = array(
            'AccessToken' => array($AccessToken->key, $AccessToken->secret),
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
     * @param $RequestToken
     * @param $Verifier
     * @return OAuthToken
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
        $Params = array(
            'oauth_verifier' => $Verifier //GetValue('oauth_verifier', $_GET)
        );
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

            // Save the access token to the database.
//         $this->SetOAuthToken($AccessToken->key, $AccessToken->secret, 'access');
//         $this->AccessToken($AccessToken->key, $AccessToken->secret);

            // Delete the request token.
            $this->deleteOAuthToken($RequestToken);

        } else {
            // There was some sort of error.
            throw new Gdn_UserException('There was an error authenticating with twitter. '.$Response, $HttpCode);
        }

        return $AccessToken;
    }

    /**
     *
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
            $Params = array(
                'oauth_verifier' => val('oauth_verifier', $_GET)
            );
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
                    redirect($this->_AuthorizeHref());
                } else {
                    $Sender->setHeader('Content-type', 'application/json');
                    $Sender->deliveryMethod(DELIVERY_METHOD_JSON);
                    $Sender->RedirectUrl = $this->_authorizeHref();
                }
            } else {
                throw $Ex;
            }
        }

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
        $Attributes = array(self::ProviderKey => array(
            'AccessToken' => array($AccessToken->key, $AccessToken->secret),
            'Profile' => $Profile
        ));
        $Form->setFormValue('Attributes', $Attributes);

        $Sender->setData('Verified', true);
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
            'Name' => 'Twitter',
            'ProviderKey' => self::ProviderKey,
            'ConnectUrl' => '/entry/twauthorize/profile',
            'Profile' => array(
                'Name' => '@'.GetValue('screen_name', $Profile),
                'Photo' => val('profile_image_url_https', $Profile)
            )
        );
    }

    /**
     *
     *
     * @param $Url
     * @param null $Params
     * @param string $Method
     * @return mixed|string
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
//      var_dump($AccessToken);

        $Request = OAuthRequest::from_consumer_and_token($Consumer, $AccessToken, $Method, $Url, $Params);

        $SignatureMethod = new OAuthSignatureMethod_HMAC_SHA1();
        $Request->sign_request($SignatureMethod, $Consumer, $AccessToken);

//      print_r($Params);

        $Curl = $this->_curl($Request, $Post);
        curl_setopt($Curl, CURLINFO_HEADER_OUT, true);
//      curl_setopt($Curl, CURLOPT_VERBOSE, true);
//      $fp = fopen("php://stdout", 'w');
//      curl_setopt($Curl, CURLOPT_STDERR, $fp);
        $Response = curl_exec($Curl);
        $HttpCode = curl_getinfo($Curl, CURLINFO_HTTP_CODE);

        if ($Response == false) {
            $Response = curl_error($Curl);
        }

//      echo curl_getinfo($Curl, CURLINFO_HEADER_OUT);
//
//      echo($Request->to_postdata());
//      echo "\n\n";

        trace(curl_getinfo($Curl, CURLINFO_HEADER_OUT));

        trace($Response, 'Response');

//      print_r(curl_getinfo($Curl));
//      die();

        curl_close($Curl);

        Gdn::controller()->setJson('Response', $Response);
        if (strpos($Url, '.json') !== false) {
            $Result = @json_decode($Response, true) or $Response;
        } else {
            $Result = $Response;
        }

//      print_r($Result);

        if ($HttpCode == '200') {
            return $Result;
        } else {
            throw new Gdn_UserException(valr('errors.0.message', $Result, $Response), $HttpCode);
        }
    }

    /**
     *
     *
     * @return mixed|string
     * @throws Gdn_UserException
     */
    public function getProfile() {
        $Profile = $this->api('/account/verify_credentials.json', array('include_entities' => '0', 'skip_status' => '1'));
        return $Profile;
    }

    /**
     *
     *
     * @param $Token
     * @return null|OAuthToken
     */
    public function getOAuthToken($Token) {
        $Row = Gdn::sql()->getWhere('UserAuthenticationToken', array('Token' => $Token, 'ProviderKey' => self::ProviderKey))->firstRow(DATASET_TYPE_ARRAY);
        if ($Row) {
            return new OAuthToken($Row['Token'], $Row['TokenSecret']);
        } else {
            return null;
        }
    }

    /**
     *
     *
     * @return bool
     */
    public function isConfigured() {
        $Result = c('Plugins.Twitter.ConsumerKey') && c('Plugins.Twitter.Secret');
        return $Result;
    }

    /**
     *
     *
     * @return bool
     */
    public function socialSharing() {
        return c('Plugins.Twitter.SocialSharing', true) && $this->isConfigured();
    }

    /**
     *
     *
     * @return bool
     */
    public function socialReactions() {
        return c('Plugins.Twitter.SocialReactions', true) && $this->isConfigured();
    }

    /**
     *
     *
     * @return bool
     */
    public function socialSignIn() {
        return c('Plugins.Twitter.SocialSignIn', true) && $this->isConfigured();
    }

    /**
     *
     *
     * @param $Token
     * @param null $Secret
     * @param string $Type
     */
    public function setOAuthToken($Token, $Secret = null, $Type = 'request') {
        if (is_a($Token, 'OAuthToken')) {
            $Secret = $Token->secret;
            $Token = $Token->key;
        }

        // Insert the token.
        $Data = array(
            'Token' => $Token,
            'ProviderKey' => self::ProviderKey,
            'TokenSecret' => $Secret,
            'TokenType' => $Type,
            'Authorized' => false,
            'Lifetime' => 60 * 5);
        Gdn::sql()->options('Ignore', true)->insert('UserAuthenticationToken', $Data);
    }

    /**
     *
     *
     * @param $Token
     */
    public function deleteOAuthToken($Token) {
        if (is_a($Token, 'OAuthToken')) {
            $Token = $Token->key;
        }

        Gdn::sql()->delete('UserAuthenticationToken', array('Token' => $Token, 'ProviderKey' => self::ProviderKey));
    }

    /**
     *
     *
     * @param OAuthRequest $Request
     */
    protected function _curl($Request, $Post = null) {
        $C = curl_init();
        curl_setopt($C, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($C, CURLOPT_SSL_VERIFYPEER, false);
        switch ($Request->get_normalized_http_method()) {
            case 'POST':
//            echo $Request->get_normalized_http_url();
//            echo "\n\n";
//            echo $Request->to_postdata();

                curl_setopt($C, CURLOPT_URL, $Request->get_normalized_http_url());
//            curl_setopt($C, CURLOPT_HTTPHEADER, array('Authorization' => $Request->to_header()));
                curl_setopt($C, CURLOPT_POST, true);
                curl_setopt($C, CURLOPT_POSTFIELDS, $Request->to_postdata());
                break;
            default:
                curl_setopt($C, CURLOPT_URL, $Request->to_url());
        }
        return $C;
    }

    /**
     *
     *
     * @return string
     */
    public static function profileConnecUrl() {
        return url(userUrl(Gdn::session()->User, false, 'twitterconnect'), true);
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
            $RedirectUri = url('/entry/connect/twitter', true);
            $this->_RedirectUri = $RedirectUri;
        }

        return $this->_RedirectUri;
    }

    /**
     * Add 'Twitter' option to the row.
     */
    public function base_AfterReactions_handler($Sender, $Args) {
        if (!$this->socialReactions()) {
            return;
        }

        echo Gdn_Theme::bulletItem('Share');
        $this->addReactButton($Sender, $Args);
    }

    /**
     * Output Quote link.
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
     *
     *
     * @param $Sender
     * @param $Args
     */
    public function socialController_twitter_create($Sender, $Args) {
        $Sender->permission('Garden.Settings.Manage');
        if ($Sender->Form->authenticatedPostBack()) {
            $Settings = array(
                'Plugins.Twitter.ConsumerKey' => $Sender->Form->getFormValue('ConsumerKey'),
                'Plugins.Twitter.Secret' => $Sender->Form->getFormValue('Secret'),
                'Plugins.Twitter.SocialSignIn' => $Sender->Form->getFormValue('SocialSignIn'),
                'Plugins.Twitter.SocialReactions' => $Sender->Form->getFormValue('SocialReactions'),
                'Plugins.Twitter.SocialSharing' => $Sender->Form->getFormValue('SocialSharing')
            );

            saveToConfig($Settings);
            $Sender->informMessage(t("Your settings have been saved."));

        } else {
            $Sender->Form->setValue('ConsumerKey', c('Plugins.Twitter.ConsumerKey'));
            $Sender->Form->setValue('Secret', c('Plugins.Twitter.Secret'));
            $Sender->Form->setValue('SocialSignIn', $this->SocialSignIn());
            $Sender->Form->setValue('SocialReactions', $this->SocialReactions());
            $Sender->Form->setValue('SocialSharing', $this->SocialSharing());
        }

        $Sender->addSideMenu('dashboard/social');
        $Sender->setData('Title', t('Twitter Settings'));
        $Sender->render('Settings', '', 'plugins/Twitter');
    }

    /**
     *
     *
     * @throws Gdn_UserException
     */
    public function setup() {
        // Make sure the user has curl.
        if (!function_exists('curl_exec')) {
            throw new Gdn_UserException('This plugin requires curl.');
        }

        // Save the twitter provider type.
        Gdn::sql()->replace(
            'UserAuthenticationProvider',
            array('AuthenticationSchemeAlias' => 'twitter', 'URL' => '...', 'AssociationSecret' => '...', 'AssociationHashMethod' => '...'),
            array('AuthenticationKey' => self::ProviderKey)
        );
    }
}

/**
 *
 *
 * @param $Str
 * @return mixed|string
 */
function sliceTwitter($Str) {
    $Elips = '...';

    $Str = preg_replace('`\s+`', ' ', $Str);

//         if (function_exists('normalizer_is_normalized')) {
//            // Slice the string to 119 characters (21 reservered for the url.
//            if (!normalizer_is_normalized($Message))
//               $Message = Normalizer::normalize($Message, Normalizer::FORM_D);
//            $Elips = Normalizer::normalize($Elips, Normalizer::FORM_D);
//         }

    $Max = 140;
    $LinkLen = 22;

    $Max -= $LinkLen;

    $Str = sliceParagraph($Str, $Max);
    if (strlen($Str) > $Max) {
        $Str = substr($Str, 0, $Max - strlen($Elips)).$Elips;
    }

    return $Str;
}
