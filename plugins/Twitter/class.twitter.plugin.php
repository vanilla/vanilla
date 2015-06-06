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
    'Version' => '1.1.9',
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
    public function AccessToken($Token = null, $Secret = null) {
        if (!$this->IsConfigured()) {
            return false;
        }

        if (is_object($Token)) {
            $this->_AccessToken = $Token;
        }
        if ($Token !== null && $Secret !== null) {
            $this->_AccessToken = new OAuthToken($Token, $Secret);
//         safeCookie('tw_access_token', $Token, 0, C('Garden.Cookie.Path', '/'), C('Garden.Cookie.Domain', ''));
        } elseif ($this->_AccessToken == null) {
//         $Token = GetValue('tw_access_token', $_COOKIE, NULL);
            if ($Token) {
                $this->_AccessToken = $this->GetOAuthToken($Token);
            } elseif (Gdn::Session()->User) {
                $AccessToken = GetValueR(self::ProviderKey.'.AccessToken', Gdn::Session()->User->Attributes);

                if (is_array($AccessToken)) {
                    $this->_AccessToken = new OAuthToken($AccessToken[0], $AccessToken[1]);
                }
            }
        }
        return $this->_AccessToken;
    }

//   public function AuthenticationController_Render_Before($Sender, $Args) {
//      if (isset($Sender->ChooserList)) {
//         $Sender->ChooserList['twitter'] = 'Twitter';
//      }
//      if (is_array($Sender->Data('AuthenticationConfigureList'))) {
//         $List = $Sender->Data('AuthenticationConfigureList');
//         $List['twitter'] = '/dashboard/settings/twitter';
//         $Sender->SetData('AuthenticationConfigureList', $List);
//      }
//   }

    /**
     *
     *
     * @param bool $Popup
     * @return string
     */
    protected function _AuthorizeHref($Popup = false) {
        $Url = Url('/entry/twauthorize', true);
        $UrlParts = explode('?', $Url);

        parse_str(GetValue(1, $UrlParts, ''), $Query);
        $Path = Gdn::Request()->Path();

        $Target = GetValue('Target', $_GET, $Path ? $Path : '/');
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
    public function EntryController_SignIn_Handler($Sender, $Args) {
        if (isset($Sender->Data['Methods'])) {
            if (!$this->SocialSignIn()) {
                return;
            }

            $Url = $this->_AuthorizeHref();

            // Add the twitter method to the controller.
            $TwMethod = array(
                'Name' => 'Twitter',
                'SignInHtml' => SocialSigninButton('Twitter', $Url, 'button', array('class' => 'js-extern'))
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
    public function Base_SignInIcons_Handler($Sender, $Args) {
        if (!$this->SocialSignIn()) {
            return;
        }

        echo "\n".$this->_GetButton();
    }

    /**
     *
     *
     * @param $Sender
     * @param $Args
     */
    public function Base_BeforeSignInButton_Handler($Sender, $Args) {
        if (!$this->SocialSignIn()) {
            return;
        }

        echo "\n".$this->_GetButton();
    }

    /**
     *
     *
     * @param $Sender
     */
    public function Base_BeforeSignInLink_Handler($Sender) {
        if (!$this->SocialSignIn()) {
            return;
        }

        // if (!IsMobile())
        // 	return;

        if (!Gdn::Session()->IsValid()) {
            echo "\n".Wrap($this->_GetButton(), 'li', array('class' => 'Connect TwitterConnect'));
        }
    }

    /**
     *
     *
     * @param $Sender
     * @param $Args
     */
    public function Base_DiscussionFormOptions_Handler($Sender, $Args) {
        if (!$this->SocialSharing()) {
            return;
        }

        if (!$this->AccessToken()) {
            return;
        }

        $Options =& $Args['Options'];

        $Options .= ' <li>'.
            $Sender->Form->CheckBox('ShareTwitter', '@'.Sprite('ReactTwitter', 'ReactSprite'), array('value' => '1', 'title' => sprintf(T('Share to %s.'), 'Twitter'))).
            '</li> ';
    }

    /**
     *
     *
     * @param $Sender
     * @param $Args
     */
    public function DiscussionController_AfterBodyField_Handler($Sender, $Args) {
        if (!$this->SocialSharing()) {
            return;
        }

        if (!$this->AccessToken()) {
            return;
        }

        echo ' '.
            $Sender->Form->CheckBox('ShareTwitter', '@'.Sprite('ReactTwitter', 'ReactSprite'), array('value' => '1', 'title' => sprintf(T('Share to %s.'), 'Twitter'))).
            ' ';
    }

    /**
     *
     *
     * @param $Sender
     * @param $Args
     * @throws Gdn_UserException
     */
    public function DiscussionModel_AfterSaveDiscussion_Handler($Sender, $Args) {
        if (!$this->SocialSharing()) {
            return;
        }

        if (!$this->AccessToken()) {
            return;
        }

        $Share = GetValueR('FormPostValues.ShareTwitter', $Args);

        if ($Share && $this->AccessToken()) {
            $Row = $Args['Fields'];
            $Url = DiscussionUrl($Row, '', true);
            $Message = SliceTwitter(Gdn_Format::PlainText($Row['Body'], $Row['Format'])).' '.$Url;

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
     * @param $Sender
     * @param $Args
     * @throws Gdn_UserException
     */
    public function CommentModel_AfterSaveComment_Handler($Sender, $Args) {
        if (!$this->SocialSharing()) {
            return;
        }

        if (!$this->AccessToken()) {
            return;
        }

        $Share = GetValueR('FormPostValues.ShareTwitter', $Args);

        if ($Share && $this->AccessToken()) {
            $Row = $Args['FormPostValues'];

            $DiscussionModel = new DiscussionModel();
            $Discussion = $DiscussionModel->GetID(GetValue('DiscussionID', $Row));
            if (!$Discussion) {
                return;
            }

            $Url = DiscussionUrl($Discussion, '', true);
            $Message = SliceTwitter(Gdn_Format::PlainText($Row['Body'], $Row['Format'])).' '.$Url;

            $R = $this->API(
                '/statuses/update.json',
                array(
                'status' => $Message
                ),
                'POST'
            );

//         decho($R);
//         die();
//      } else {
//         die("$Share ".$this->AccessToken());
        }
    }

    /**
     *
     *
     * @return string
     */
    private function _GetButton() {
        $Url = $this->_AuthorizeHref();

        return SocialSigninButton('Twitter', $Url, 'icon', array('class' => 'js-extern', 'rel' => 'nofollow'));
    }

    /**
     *
     *
     * @param bool $Query
     */
    public function Authorize($Query = false) {
        // Aquire the request token.
        $Consumer = new OAuthConsumer(C('Plugins.Twitter.ConsumerKey'), C('Plugins.Twitter.Secret'));
        $RedirectUri = $this->RedirectUri();
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
                $Response = T('The response was not in the correct format.');
            } else {
                // Save the token for later reference.
                $this->SetOAuthToken($Data['oauth_token'], $Data['oauth_token_secret'], 'request');

                // Redirect to twitter's authorization page.
                $Url = "https://api.twitter.com/oauth/authenticate?oauth_token={$Data['oauth_token']}";
                Redirect($Url);
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
    public function EntryController_Twauthorize_Create($Sender, $Dir = '') {
        $Query = ArrayTranslate($Sender->Request->Get(), array('display', 'Target'));
        $Query = http_build_query($Query);

        if ($Dir == 'profile') {
            // This is a profile connection.
            $this->RedirectUri(self::ProfileConnecUrl());
        }

        $this->Authorize($Query);
    }

    /**
     *
     *
     * @param PostController $Sender
     * @param type $RecordType
     * @param type $ID
     * @throws type
     */
    public function PostController_Twitter_Create($Sender, $RecordType, $ID) {
        if (!$this->SocialReactions()) {
            throw PermissionException();
        }

//      if (!Gdn::Request()->IsPostBack())
//         throw PermissionException('Javascript');

        $Row = GetRecord($RecordType, $ID, true);
        if ($Row) {
            // Grab the tweet message.
            switch (strtolower($RecordType)) {
                case 'discussion':
                    $Message = Gdn_Format::PlainText($Row['Name'], 'Text');
                    break;
                case 'comment':
                default:
                    $Message = Gdn_Format::PlainText($Row['Body'], $Row['Format']);
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

            if ($this->AccessToken()) {
                Gdn::Controller()->SetData('Message', $Message);

                $Message .= ' '.$Row['ShareUrl'];
                $R = $this->API(
                    '/statuses/update.json',
                    array(
                    'status' => $Message
                    ),
                    'POST'
                );

                $Sender->SetJson('R', $R);
                $Sender->InformMessage(T('Thanks for sharing!'));
            } else {
                $Get = array(
                    'text' => $Message,
                    'url' => $Row['ShareUrl']
                );
                $Url = "https://twitter.com/share?".http_build_query($Get);
                Redirect($Url);
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
     * @param type $oauth_token
     * @param type $oauth_verifier
     */
    public function ProfileController_TwitterConnect_Create($Sender, $UserReference = '', $Username = '', $oauth_token = '', $oauth_verifier = '') {
        $Sender->Permission('Garden.SignIn.Allow');

        $Sender->GetUserInfo($UserReference, $Username, '', true);

        $Sender->_SetBreadcrumbs(T('Connections'), '/profile/connections');

        // Get the access token.
        Trace('GetAccessToken()');
        $AccessToken = $this->GetAccessToken($oauth_token, $oauth_verifier);
        $this->AccessToken($AccessToken);

        // Get the profile.
        Trace('GetProfile()');
        $Profile = $this->GetProfile();

        // Save the authentication.
        Gdn::UserModel()->SaveAuthentication(array(
            'UserID' => $Sender->User->UserID,
            'Provider' => self::ProviderKey,
            'UniqueID' => $Profile['id']));

        // Save the information as attributes.
        $Attributes = array(
            'AccessToken' => array($AccessToken->key, $AccessToken->secret),
            'Profile' => $Profile
        );
        Gdn::UserModel()->SaveAttribute($Sender->User->UserID, self::ProviderKey, $Attributes);

        $this->EventArguments['Provider'] = self::ProviderKey;
        $this->EventArguments['User'] = $Sender->User;
        $this->FireEvent('AfterConnection');

        Redirect(UserUrl($Sender->User, '', 'connections'));
    }

    /**
     *
     *
     * @param $RequestToken
     * @param $Verifier
     * @return OAuthToken
     * @throws Gdn_UserException
     */
    public function GetAccessToken($RequestToken, $Verifier) {
        if ((!$RequestToken || !$Verifier) && Gdn::Request()->Get('denied')) {
            throw new Gdn_UserException(T('Looks like you denied our request.'), 401);
        }

        // Get the request secret.
        $RequestToken = $this->GetOAuthToken($RequestToken);

        $Consumer = new OAuthConsumer(C('Plugins.Twitter.ConsumerKey'), C('Plugins.Twitter.Secret'));

        $Url = 'https://api.twitter.com/oauth/access_token';
        $Params = array(
            'oauth_verifier' => $Verifier //GetValue('oauth_verifier', $_GET)
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

            $AccessToken = new OAuthToken(GetValue('oauth_token', $Data), GetValue('oauth_token_secret', $Data));

            // Save the access token to the database.
//         $this->SetOAuthToken($AccessToken->key, $AccessToken->secret, 'access');
//         $this->AccessToken($AccessToken->key, $AccessToken->secret);

            // Delete the request token.
            $this->DeleteOAuthToken($RequestToken);

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
    public function Base_ConnectData_Handler($Sender, $Args) {
        if (GetValue(0, $Args) != 'twitter') {
            return;
        }

        $Form = $Sender->Form; //new Gdn_Form();

        $RequestToken = GetValue('oauth_token', $_GET);
        $AccessToken = $Form->GetFormValue('AccessToken');

        if ($AccessToken) {
            $AccessToken = $this->GetOAuthToken($AccessToken);
            $this->AccessToken($AccessToken);
        }

        // Get the access token.
        if ($RequestToken && !$AccessToken) {
            // Get the request secret.
            $RequestToken = $this->GetOAuthToken($RequestToken);

            $Consumer = new OAuthConsumer(C('Plugins.Twitter.ConsumerKey'), C('Plugins.Twitter.Secret'));

            $Url = 'https://api.twitter.com/oauth/access_token';
            $Params = array(
                'oauth_verifier' => GetValue('oauth_verifier', $_GET)
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

                $AccessToken = new OAuthToken(GetValue('oauth_token', $Data), GetValue('oauth_token_secret', $Data));

                // Save the access token to the database.
                $this->SetOAuthToken($AccessToken->key, $AccessToken->secret, 'access');
                $this->AccessToken($AccessToken->key, $AccessToken->secret);

                // Delete the request token.
                $this->DeleteOAuthToken($RequestToken);

            } else {
                // There was some sort of error.
                throw new Exception('There was an error authenticating with twitter.', 400);
            }

            $NewToken = true;
        }

        // Get the profile.
        try {
            $Profile = $this->GetProfile($AccessToken);
        } catch (Exception $Ex) {
            if (!isset($NewToken)) {
                // There was an error getting the profile, which probably means the saved access token is no longer valid. Try and reauthorize.
                if ($Sender->DeliveryType() == DELIVERY_TYPE_ALL) {
                    Redirect($this->_AuthorizeHref());
                } else {
                    $Sender->SetHeader('Content-type', 'application/json');
                    $Sender->DeliveryMethod(DELIVERY_METHOD_JSON);
                    $Sender->RedirectUrl = $this->_AuthorizeHref();
                }
            } else {
                throw $Ex;
            }
        }

        $ID = GetValue('id', $Profile);
        $Form->SetFormValue('UniqueID', $ID);
        $Form->SetFormValue('Provider', self::ProviderKey);
        $Form->SetFormValue('ProviderName', 'Twitter');
        $Form->SetValue('ConnectName', GetValue('screen_name', $Profile));
        $Form->SetFormValue('Name', GetValue('screen_name', $Profile));
        $Form->SetFormValue('FullName', GetValue('name', $Profile));
        $Form->SetFormValue('Photo', GetValue('profile_image_url_https', $Profile));
        $Form->AddHidden('AccessToken', $AccessToken->key);

        // Save some original data in the attributes of the connection for later API calls.
        $Attributes = array(self::ProviderKey => array(
            'AccessToken' => array($AccessToken->key, $AccessToken->secret),
            'Profile' => $Profile
        ));
        $Form->SetFormValue('Attributes', $Attributes);

        $Sender->SetData('Verified', true);
    }

    /**
     *
     *
     * @param $Sender
     * @param $Args
     */
    public function Base_GetConnections_Handler($Sender, $Args) {
        $Profile = GetValueR('User.Attributes.'.self::ProviderKey.'.Profile', $Args);

        $Sender->Data["Connections"][self::ProviderKey] = array(
            'Icon' => $this->GetWebResource('icon.png', '/'),
            'Name' => 'Twitter',
            'ProviderKey' => self::ProviderKey,
            'ConnectUrl' => '/entry/twauthorize/profile',
            'Profile' => array(
                'Name' => '@'.GetValue('screen_name', $Profile),
                'Photo' => GetValue('profile_image_url_https', $Profile)
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
    public function API($Url, $Params = null, $Method = 'GET') {
        if (strpos($Url, '//') === false) {
            $Url = self::$BaseApiUrl.trim($Url, '/');
        }
        $Consumer = new OAuthConsumer(C('Plugins.Twitter.ConsumerKey'), C('Plugins.Twitter.Secret'));

        if ($Method == 'POST') {
            $Post = $Params;
        } else {
            $Post = null;
        }

        $AccessToken = $this->AccessToken();
//      var_dump($AccessToken);

        $Request = OAuthRequest::from_consumer_and_token($Consumer, $AccessToken, $Method, $Url, $Params);

        $SignatureMethod = new OAuthSignatureMethod_HMAC_SHA1();
        $Request->sign_request($SignatureMethod, $Consumer, $AccessToken);

//      print_r($Params);

        $Curl = $this->_Curl($Request, $Post);
        curl_setopt($Curl, CURLINFO_HEADER_OUT, true);
//      curl_setopt($Curl, CURLOPT_VERBOSE, TRUE);
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

        Trace(curl_getinfo($Curl, CURLINFO_HEADER_OUT));

        Trace($Response, 'Response');

//      print_r(curl_getinfo($Curl));
//      die();

        curl_close($Curl);

        Gdn::Controller()->SetJson('Response', $Response);
        if (strpos($Url, '.json') !== false) {
            $Result = @json_decode($Response, true) or $Response;
        } else {
            $Result = $Response;
        }

//      print_r($Result);

        if ($HttpCode == '200') {
            return $Result;
        } else {
            throw new Gdn_UserException(GetValueR('errors.0.message', $Result, $Response), $HttpCode);
        }
    }

    /**
     *
     *
     * @return mixed|string
     * @throws Gdn_UserException
     */
    public function GetProfile() {
        $Profile = $this->API('/account/verify_credentials.json', array('include_entities' => '0', 'skip_status' => '1'));
        return $Profile;
    }

    /**
     *
     *
     * @param $Token
     * @return null|OAuthToken
     */
    public function GetOAuthToken($Token) {
        $Row = Gdn::SQL()->GetWhere('UserAuthenticationToken', array('Token' => $Token, 'ProviderKey' => self::ProviderKey))->FirstRow(DATASET_TYPE_ARRAY);
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
    public function IsConfigured() {
        $Result = C('Plugins.Twitter.ConsumerKey') && C('Plugins.Twitter.Secret');
        return $Result;
    }

    /**
     *
     *
     * @return bool
     */
    public function SocialSharing() {
        return C('Plugins.Twitter.SocialSharing', true) && $this->IsConfigured();
    }

    /**
     *
     *
     * @return bool
     */
    public function SocialReactions() {
        return C('Plugins.Twitter.SocialReactions', true) && $this->IsConfigured();
    }

    /**
     *
     *
     * @return bool
     */
    public function SocialSignIn() {
        return C('Plugins.Twitter.SocialSignIn', true) && $this->IsConfigured();
    }

    /**
     *
     *
     * @param $Token
     * @param null $Secret
     * @param string $Type
     */
    public function SetOAuthToken($Token, $Secret = null, $Type = 'request') {
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
        Gdn::SQL()->Options('Ignore', true)->Insert('UserAuthenticationToken', $Data);
    }

    /**
     *
     *
     * @param $Token
     */
    public function DeleteOAuthToken($Token) {
        if (is_a($Token, 'OAuthToken')) {
            $Token = $Token->key;
        }

        Gdn::SQL()->Delete('UserAuthenticationToken', array('Token' => $Token, 'ProviderKey' => self::ProviderKey));
    }

    /**
     *
     *
     * @param OAuthRequest $Request
     */
    protected function _Curl($Request, $Post = null) {
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
    public static function ProfileConnecUrl() {
        return Url(UserUrl(Gdn::Session()->User, false, 'twitterconnect'), true);
    }

    /**
     *
     *
     * @param null $NewValue
     * @return null|string
     */
    public function RedirectUri($NewValue = null) {
        if ($NewValue !== null) {
            $this->_RedirectUri = $NewValue;
        } elseif ($this->_RedirectUri === null) {
            $RedirectUri = Url('/entry/connect/twitter', true);
            $this->_RedirectUri = $RedirectUri;
        }

        return $this->_RedirectUri;
    }

    /**
     * Add 'Twitter' option to the row.
     */
    public function Base_AfterReactions_Handler($Sender, $Args) {
        if (!$this->SocialReactions()) {
            return;
        }

        echo Gdn_Theme::BulletItem('Share');
        $this->AddReactButton($Sender, $Args);
    }

    /**
     * Output Quote link.
     */
    protected function AddReactButton($Sender, $Args) {
        if ($this->AccessToken()) {
            $Url = Url("post/twitter/{$Args['RecordType']}?id={$Args['RecordID']}", true);
            $CssClass = 'ReactButton Hijack';
        } else {
            $Url = Url("post/twitter/{$Args['RecordType']}?id={$Args['RecordID']}", true);
            $CssClass = 'ReactButton PopupWindow';
        }

        echo Anchor(Sprite('ReactTwitter', 'Sprite ReactSprite', T('Share on Twitter')), $Url, $CssClass);
    }

    /**
     *
     *
     * @param $Sender
     * @param $Args
     */
    public function SocialController_Twitter_Create($Sender, $Args) {
        $Sender->Permission('Garden.Settings.Manage');
        if ($Sender->Form->AuthenticatedPostBack()) {
            $Settings = array(
                'Plugins.Twitter.ConsumerKey' => $Sender->Form->GetFormValue('ConsumerKey'),
                'Plugins.Twitter.Secret' => $Sender->Form->GetFormValue('Secret'),
                'Plugins.Twitter.SocialSignIn' => $Sender->Form->GetFormValue('SocialSignIn'),
                'Plugins.Twitter.SocialReactions' => $Sender->Form->GetFormValue('SocialReactions'),
                'Plugins.Twitter.SocialSharing' => $Sender->Form->GetFormValue('SocialSharing')
            );

            SaveToConfig($Settings);
            $Sender->InformMessage(T("Your settings have been saved."));

        } else {
            $Sender->Form->SetValue('ConsumerKey', C('Plugins.Twitter.ConsumerKey'));
            $Sender->Form->SetValue('Secret', C('Plugins.Twitter.Secret'));
            $Sender->Form->SetValue('SocialSignIn', $this->SocialSignIn());
            $Sender->Form->SetValue('SocialReactions', $this->SocialReactions());
            $Sender->Form->SetValue('SocialSharing', $this->SocialSharing());
        }

        $Sender->AddSideMenu('dashboard/social');
        $Sender->SetData('Title', T('Twitter Settings'));
        $Sender->Render('Settings', '', 'plugins/Twitter');
    }

    /**
     *
     *
     * @throws Gdn_UserException
     */
    public function Setup() {
        // Make sure the user has curl.
        if (!function_exists('curl_exec')) {
            throw new Gdn_UserException('This plugin requires curl.');
        }

        // Save the twitter provider type.
        Gdn::SQL()->Replace(
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
function SliceTwitter($Str) {
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

    $Str = SliceParagraph($Str, $Max);
    if (strlen($Str) > $Max) {
        $Str = substr($Str, 0, $Max - strlen($Elips)).$Elips;
    }

    return $Str;
}
