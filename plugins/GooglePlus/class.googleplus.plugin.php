<?php
/**
 * GooglePlus Plugin.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package GooglePlus
 */

// Define the plugin:
$PluginInfo['GooglePlus'] = array(
    'Name' => 'Google+ Social Connect',
    'Description' => 'Users may sign into your site using their Google Plus account.',
    'Version' => '1.1.0',
    'RequiredApplications' => array('Vanilla' => '2.2'),
    'MobileFriendly' => true,
    'Author' => 'Todd Burry',
    'AuthorEmail' => 'todd@vanillaforums.com',
    'AuthorUrl' => 'http://www.vanillaforums.org/profile/todd',
    'SettingsUrl' => '/dashboard/social/googleplus',
    'SettingsPermission' => 'Garden.Settings.Manage',
    'Hidden' => false,
    'SocialConnect' => true,
    'RequiresRegistration' => false
);

/**
 * Class GooglePlusPlugin
 */
class GooglePlusPlugin extends Gdn_Plugin {

    /** Authentication Provider key. */
    const ProviderKey = 'GooglePlus';

    /** Google's URL. */
    const APIUrl = 'https://www.googleapis.com/oauth2/v1';

    /** @var string */
    protected $_AccessToken = null;

    /**
     *
     *
     * @param bool $NewValue
     * @return bool|mixed|null
     */
    public function accessToken($NewValue = false) {
        if (!$this->isConfigured()) {
            return false;
        }

        if ($NewValue !== false) {
            $this->_AccessToken = $NewValue;
        }

        if ($this->_AccessToken === null) {
            $this->_AccessToken = valr(self::ProviderKey.'.AccessToken', Gdn::session()->User->Attributes);
        }

        return $this->_AccessToken;
    }

    /**
     *
     *
     * @param $Path
     * @param array $Post
     * @return mixed
     * @throws Gdn_UserException
     */
    public function api($Path, $Post = array()) {
        $Url = self::APIUrl.'/'.ltrim($Path, '/');
        if (strpos($Url, '?') === false) {
            $Url .= '?';
        } else {
            $Url .= '&';
        }
        $Url .= 'access_token='.urlencode($this->accessToken());

        $Result = $this->Curl($Url, empty($Post) ? 'GET' : 'POST', $Post);
        return $Result;
    }

    /**
     *
     *
     * @param array $State
     * @return string
     */
    public function authorizeUri($State = array()) {
        $Url = 'https://accounts.google.com/o/oauth2/auth';
        $Get = array(
            'response_type' => 'code',
            'client_id' => c('Plugins.GooglePlus.ClientID'),
            'redirect_uri' => url('/entry/googleplus', true),
            'scope' => 'https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email'
        );

        if (is_array($State)) {
            $Get['state'] = http_build_query($State);
        }

        return $Url.'?'.http_build_query($Get);
    }

    /**
     *
     *
     * @param $Code
     * @return mixed
     * @throws Gdn_UserException
     */
    public function getAccessToken($Code) {
        $Url = 'https://accounts.google.com/o/oauth2/token';
        $Post = array(
            'code' => $Code,
            'client_id' => c('Plugins.GooglePlus.ClientID'),
            'client_secret' => c('Plugins.GooglePlus.Secret'),
            'redirect_uri' => url('/entry/googleplus', true),
            'grant_type' => 'authorization_code'
        );

        $Data = self::curl($Url, 'POST', $Post);
        $AccessToken = $Data['access_token'];
        return $AccessToken;
    }

    /**
     *
     *
     * @return bool
     */
    public function isConfigured() {
        $Result = c('Plugins.GooglePlus.ClientID') && c('Plugins.GooglePlus.Secret');
        return $Result;
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
     *
     *
     * @return mixed
     */
    public function socialSharing() {
        return c('Plugins.GooglePlus.SocialSharing', true);
    }

    /**
     *
     *
     * @return mixed
     */
    public function socialReactions() {
        return c('Plugins.GooglePlus.SocialReactions', true);
    }

    /**
     *
     *
     * @param $Url
     * @param string $Method
     * @param array $Data
     * @return mixed
     * @throws Gdn_UserException
     */
    public static function curl($Url, $Method = 'GET', $Data = array()) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_URL, $Url);

        if ($Method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($Data));
            trace("  POST $Url");
        } else {
            trace("  GET  $Url");
        }

        $Response = curl_exec($ch);

        $HttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $ContentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        $Result = @json_decode($Response, true);
        if (!$Result) {
            $Result = $Response;
        }

        if ($HttpCode != 200) {
            $Error = val('error', $Result, $Response);

            throw new Gdn_UserException($Error, $HttpCode);
        }

        return $Result;
    }

    /**
     *
     */
    public function setup() {
        $this->structure();
    }

    /**
     *
     *
     * @param string $type
     * @return string
     */
    public function signInButton($type = 'button') {
        $Target = Gdn::request()->post('Target', Gdn::request()->get('Target', url('', '/')));
        $Url = $this->authorizeUri(array('target' => $Target));

        $Result = socialSignInButton('Google', $Url, $type, array('rel' => 'nofollow'));
        return $Result;
    }

    /**
     *
     */
    public function structure() {
        Gdn::sql()->put('UserAuthenticationProvider', array('AuthenticationSchemeAlias' => self::ProviderKey), array('AuthenticationSchemeAlias' => 'Google+'));

        // Save the google+ provider type.
        Gdn::sql()->replace(
            'UserAuthenticationProvider',
            array('AuthenticationSchemeAlias' => self::ProviderKey, 'URL' => '', 'AssociationSecret' => '', 'AssociationHashMethod' => '...'),
            array('AuthenticationKey' => self::ProviderKey),
            true
        );
    }

    /**
     * Calculate the final sign in and register urls for google+.
     *
     * @param object $sender Not used.
     * @param array $args Contains the provider and
     */
    public function authenticationProviderModel_calculateGooglePlus_handler($sender, $args) {
        $provider =& $args['Provider'];
        $target = val('Target', null);

        if (!$target) {
            $target = Gdn::request()->post('Target', Gdn::request()->get('Target', url('', '/')));
        }

        $provider['SignInUrlFinal'] = $this->authorizeUri(array('target' => $target));
//      $provider['RegisterUrlFinal'] = static::getRegisterUrl($provider, $target);
    }

    /**
     * Add 'Google+' option to the row.
     */
    public function base_AfterReactions_handler($Sender, $Args) {
        if (!$this->socialReactions()) {
            return;
        }
        echo Gdn_Theme::bulletItem('Share');
//      if ($this->AccessToken()) {
//         $Url = url("post/twitter/{$Args['RecordType']}?id={$Args['RecordID']}", true);
//         $CssClass = 'ReactButton Hijack';
//      } else {
        $Url = url("post/googleplus/{$Args['RecordType']}?id={$Args['RecordID']}", true);
        $CssClass = 'ReactButton PopupWindow';
//      }

        echo ' '.anchor(sprite('ReactGooglePlus', 'ReactSprite'), $Url, $CssClass).' ';
    }

    /**
     *
     *
     * @param EntryController $Sender
     * @param array $Args
     */
    public function base_connectData_handler($Sender, $Args) {
        if (val(0, $Args) != 'googleplus') {
            return;
        }

        // Grab the google plus profile from the session staff.
        $GooglePlus = Gdn::session()->stash(self::ProviderKey, '', false);
        $AccessToken = val('AccessToken', $GooglePlus);
        $Profile = val('Profile', $GooglePlus);

        $Form = $Sender->Form;
        $Form->setFormValue('UniqueID', val('id', $Profile));
        $Form->setFormValue('Provider', self::ProviderKey);
        $Form->setFormValue('ProviderName', 'Google+');
        $Form->setFormValue('FullName', val('name', $Profile));
        $Form->setFormValue('Email', val('email', $Profile));
        if (c('Plugins.GooglePlus.UseAvatars', true)) {
            $Form->setFormValue('Photo', val('picture', $Profile));
        }

        if (c('Plugins.GooglePlus.UseFullNames')) {
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

        $this->EventArguments['Form'] = $Form;
        $this->fireEvent('AfterConnectData');
    }

    /**
     *
     *
     * @param $Sender
     * @param $Args
     */
    public function base_signInIcons_handler($Sender, $Args) {
        if (!$this->isDefault()) {
            echo ' '.$this->signInButton('icon').' ';
        }
    }

    /**
     *
     *
     * @param $Sender
     * @param $Args
     */
    public function base_beforeSignInButton_handler($Sender, $Args) {
        if (!$this->isConfigured()) {
            return;
        }
        if (!$this->isDefault()) {
            echo ' '.$this->signInButton('icon').' ';
        }
    }

    /**
     *
     *
     * @param $Sender
     * @param $Args
     */
    public function base_getConnections_handler($Sender, $Args) {
        $GPlus = valr('User.Attributes.'.self::ProviderKey, $Args);
        $Profile = valr('User.Attributes.'.self::ProviderKey.'.Profile', $Args);

        $Sender->Data['Connections'][self::ProviderKey] = array(
            'Icon' => $this->getWebResource('icon.png'),
            'Name' => 'Google+',
            'ProviderKey' => self::ProviderKey,
            'ConnectUrl' => $this->authorizeUri(array('r' => 'profile', 'uid' => Gdn::session()->UserID)),
            'Profile' => array(
                'Name' => val('name', $Profile),
                'Photo' => val('picture', $Profile)
            )
        );

        trace(val('AccessToken', $GPlus), 'google+ access token');
    }

    /**
     *
     *
     * @param EntryController $Sender
     * @param string $Code
     * @param string $State
     * @throws Gdn_UserException
     */
    public function entryController_googlePlus_create($Sender, $Code = false, $State = false) {
        if ($Error = $Sender->Request->get('error')) {
            throw new Gdn_UserException($Error);
        }

        // Get an access token.
        Gdn::session()->stash(self::ProviderKey); // remove any old google plus.
        $AccessToken = $this->getAccessToken($Code);
        $this->accessToken($AccessToken);

        // Get the user's information.
        $Profile = $this->api('/userinfo');

        if ($State) {
            parse_str($State, $State);
        } else {
            $State = array('r' => 'entry', 'uid' => null);
        }

        switch ($State['r']) {
            case 'profile':
                // This is a connect request from the user's profile.

                $User = Gdn::userModel()->getID($State['uid']);
                if (!$User) {
                    throw notFoundException('User');
                }
                // Save the authentication.
                Gdn::userModel()->saveAuthentication(array(
                    'UserID' => $User->UserID,
                    'Provider' => self::ProviderKey,
                    'UniqueID' => $Profile['id']));

                // Save the information as attributes.
                $Attributes = array(
                    'AccessToken' => $AccessToken,
                    'Profile' => $Profile
                );
                Gdn::userModel()->saveAttribute($User->UserID, self::ProviderKey, $Attributes);

                $this->EventArguments['Provider'] = self::ProviderKey;
                $this->EventArguments['User'] = $Sender->User;
                $this->fireEvent('AfterConnection');

                redirect(userUrl($User, '', 'connections'));
                break;
            case 'entry':
            default:
                // This is an sso request, we need to redispatch to /entry/connect/googleplus
                Gdn::session()->stash(self::ProviderKey, array('AccessToken' => $AccessToken, 'Profile' => $Profile));
                $url = '/entry/connect/googleplus';

                if ($target = val('target', $State)) {
                    $url .= '?Target='.urlencode($target);
                }
                redirect($url);
                break;
        }
    }

    /**
     *
     *
     * @param Gdn_Controller $Sender
     */
    public function entryController_signIn_handler($Sender, $Args) {
//      if (!$this->IsEnabled()) return;

        if (isset($Sender->Data['Methods'])) {
            $Url = $this->authorizeUri();

            // Add the Google method to the controller.
            $Method = array(
                'Name' => 'Google',
                'SignInHtml' => $this->signInButton() //SocialSigninButton('Google', $Url, 'button', array('class' => 'js-extern', 'rel' => 'nofollow'))
            );

            $Sender->Data['Methods'][] = $Method;
        }
    }

    /**
     * Override the sign in if Google+ is the default sign-in method.
     *
     * @param EntryController $Sender
     * @param array $Args
     */
    public function entryController_overrideSignIn_handler($Sender, $Args) {
        if (valr('DefaultProvider.AuthenticationKey', $Args) !== self::ProviderKey || !$this->isConfigured()) {
            return;
        }

        $Url = $this->authorizeUri(array('target' => $Args['Target']));
        $Args['DefaultProvider']['SignInUrl'] = $Url;

//      redirect($Url);
    }

    /**
     *
     *
     * @param PostController $Sender
     * @param type $RecordType
     * @param type $ID
     * @throws type
     */
    public function postController_googlePlus_create($Sender, $RecordType, $ID) {
        $Row = GetRecord($RecordType, $ID);
        if ($Row) {
            $Message = SliceParagraph(Gdn_Format::plainText($Row['Body'], $Row['Format']), 160);

            $Get = array(
                'url' => $Row['ShareUrl']
            );

            $Url = 'https://plus.google.com/share?'.http_build_query($Get);
            redirect($Url);
        }

        $Sender->render('Blank', 'Utility', 'Dashboard');
    }

    /**
     *
     *
     * @param $Sender
     * @param $Args
     */
    public function socialController_googlePlus_create($Sender, $Args) {
        $Sender->permission('Garden.Settings.Manage');

        $Conf = new ConfigurationModule($Sender);
        $Conf->initialize(array(
            'Plugins.GooglePlus.ClientID' => array('LabelCode' => 'Client ID', 'Options' => array('class' => 'InputBox BigInput')),
            'Plugins.GooglePlus.Secret' => array('LabelCode' => 'Client secret', 'Options' => array('class' => 'InputBox BigInput')),
            'Plugins.GooglePlus.SocialReactions' => array('Control' => 'checkbox', 'Default' => true),
            'Plugins.GooglePlus.SocialSharing' => array('Control' => 'checkbox', 'Default' => true),
            'Plugins.GooglePlus.UseAvatars' => array('Control' => 'checkbox', 'Default' => true),
            'Plugins.GooglePlus.Default' => array('Control' => 'checkbox', 'LabelCode' => 'Make this connection your default signin method.')
        ));

        if (Gdn::request()->isAuthenticatedPostBack()) {
            $Model = new Gdn_AuthenticationProviderModel();
            $Model->save(array('AuthenticationKey' => self::ProviderKey, 'IsDefault' => c('Plugins.GooglePlus.Default')));
        }

        $Sender->addSideMenu('dashboard/social');
        $Sender->setData('Title', sprintf(t('%s Settings'), 'Google+'));
        $Sender->ConfigurationModule = $Conf;
        $Sender->render('Settings', '', 'plugins/GooglePlus');
    }
}
