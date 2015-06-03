<?php if (!defined('APPLICATION')) exit();
/**
 * OpenID Plugin.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package OpenID
 */

// Define the plugin:
$PluginInfo['OpenID'] = array(
    'Name' => 'OpenID',
    'Description' => 'Allows users to sign in with OpenID. Must be enabled before using &lsquo;Google Sign In&rsquo; and &lsquo;Steam&rsquo; plugins.',
    'Version' => '1.2.0',
    'RequiredApplications' => array('Vanilla' => '2.2'),
    'MobileFriendly' => TRUE,
    'SettingsUrl' => '/settings/openid',
    'SettingsPermission' => 'Garden.Settings.Manage',
    'Author' => "Todd Burry",
    'AuthorEmail' => 'todd@vanillaforums.com',
    'AuthorUrl' => 'http://www.vanillaforums.org/profile/todd'
);

// 0.2 - Remove redundant enable toggle (2012-03-08 Lincoln)

/**
 * Class OpenIDPlugin
 */
class OpenIDPlugin extends Gdn_Plugin {

    /** @var string  */
    public static $ProviderKey = 'OpenID';

    /**
     *
     *
     * @param bool $Popup
     * @return string
     */
    protected function _AuthorizeHref($Popup = FALSE) {
        $Url = Url('/entry/openid', TRUE);
        $UrlParts = explode('?', $Url);
        parse_str(GetValue(1, $UrlParts, ''), $Query);

        $Path = '/'.Gdn::Request()->Path();
        $Query['Target'] = GetValue('Target', $_GET, $Path ? $Path : '/');

        if (isset($_GET['Target']))
            $Query['Target'] = $_GET['Target'];
        if ($Popup)
            $Query['display'] = 'popup';

        $Result = $UrlParts[0].'?'.http_build_query($Query);
        return $Result;
    }

    /**
     *
     *
     * @return LightOpenID
     */
    public function GetOpenID() {
        if (get_magic_quotes_gpc()) {
            foreach ($_GET as $Name => $Value) {
                $_GET[$Name] = stripslashes($Value);
            }
        }

        $OpenID = new LightOpenID();

        if ($url = Gdn::Request()->Get('url')) {
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                throw new Gdn_UserException(sprintf(T('ValidateUrl'), 'OpenID'), 400);
            }

            // Don't allow open ID on a non-standard scheme.
            $scheme = parse_url($url, PHP_URL_SCHEME);
            if (!in_array($scheme, array('http', 'https'))) {
                throw new Gdn_UserException(sprintf(T('ValidateUrl'), 'OpenID'), 400);
            }

            // Don't allow open ID on a non-standard port.
            $port = parse_url($url, PHP_URL_PORT);
            if ($port && !in_array($port, array(80, 8080, 443))) {
                throw new Gdn_UserException(T('OpenID is not allowed on non-standard ports.'));
            }

            $OpenID->identity = $url;
        }

        $Url = Url('/entry/connect/openid', TRUE);
        $UrlParts = explode('?', $Url);
        parse_str(GetValue(1, $UrlParts, ''), $Query);
        $Query = array_merge($Query, ArrayTranslate($_GET, array('display', 'Target')));

        $OpenID->returnUrl = $UrlParts[0].'?'.http_build_query($Query);
        $OpenID->required = array('contact/email', 'namePerson/first', 'namePerson/last', 'pref/language');

        $this->EventArguments['OpenID'] = $OpenID;
        $this->FireEvent('GetOpenID');

        return $OpenID;
    }

    /**
     * Act as a mini dispatcher for API requests to the plugin app
     */
//   public function PluginController_OpenID_Create($Sender) {
//      $Sender->Permission('Garden.Settings.Manage');
//		$this->Dispatch($Sender, $Sender->RequestArgs);
//   }

//   public function Controller_Toggle($Sender) {
//      $this->AutoToggle($Sender);
//   }

//   public function AuthenticationController_Render_Before($Sender, $Args) {
//      if (isset($Sender->ChooserList)) {
//         $Sender->ChooserList['openid'] = 'OpenID';
//      }
//      if (is_array($Sender->Data('AuthenticationConfigureList'))) {
//         $List = $Sender->Data('AuthenticationConfigureList');
//         $List['openid'] = '/dashboard/plugin/openid';
//         $Sender->SetData('AuthenticationConfigureList', $List);
//      }
//   }

    /**
     * @throws Gdn_UserException
     */
    public function Setup() {
        if (!ini_get('allow_url_fopen')) {
            throw new Gdn_UserException('This plugin requires the allow_url_fopen php.ini setting.');
        }
    }

    /**
     *
     *
     * @param $Sender
     * @param $Args
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function Base_ConnectData_Handler($Sender, $Args) {
        if (GetValue(0, $Args) != 'openid')
            return;

        $Mode = $Sender->Request->Get('openid_mode');
        if ($Mode != 'id_res')
            return; // this will error out

        $this->EventArguments = $Args;

        // Check session before retrieving
        $Session = Gdn::Session();
        $OpenID = $Session->Stash('OpenID', '', FALSE);
        if (!$OpenID)
            $OpenID = $this->GetOpenID();

        if ($Session->Stash('OpenID', '', FALSE) || $OpenID->validate()) {
            $Attr = $OpenID->getAttributes();

            $Form = $Sender->Form; //new Gdn_Form();
            $ID = $OpenID->identity;
            $Form->SetFormValue('UniqueID', $ID);
            $Form->SetFormValue('Provider', self::$ProviderKey);
            $Form->SetFormValue('ProviderName', 'OpenID');

            $Form->SetFormValue('FullName', trim(val('namePerson/first', $Attr).' '.val('namePerson/last', $Attr)));

            if ($Email = GetValue('contact/email', $Attr)) {
                $Form->SetFormValue('Email', $Email);
            }

            $Sender->SetData('Verified', TRUE);
            $Session->Stash('OpenID', $OpenID);

            $this->EventArguments['OpenID'] = $OpenID;
            $this->EventArguments['Form'] = $Form;
            $this->FireEvent('AfterConnectData');

        }
    }

    /**
     *
     *
     * @param EntryController $Sender
     * @param array $Args
     */
    public function EntryController_OpenID_Create($Sender, $Args) {
        $this->EventArguments = $Args;
        $Sender->Form->InputPrefix = '';

        try {
            $OpenID = $this->GetOpenID();
        } catch (Gdn_UserException $ex) {
            $Sender->Form->AddError('@'.$ex->getMessage());
            $Sender->Render('Url', '', 'plugins/OpenID');
        }

        $Mode = $Sender->Request->Get('openid_mode');
        switch ($Mode) {
            case 'cancel':
                $Sender->Render('Cancel', '', 'plugins/OpenID');
                break;
            case 'id_res':
                if ($OpenID->validate()) {
                    $Attributes = $OpenID->getAttributes();
                    print_r($_GET);
                }

                break;
            default:
                if (!$OpenID->identity) {
                    $Sender->CssClass = 'Dashboard Entry connect';
                    $Sender->SetData('Title', T('Sign In with OpenID'));
                    $Sender->Render('Url', '', 'plugins/OpenID');
                } else {
                    try {
                        $Url = $OpenID->authUrl();
                        Redirect($Url);
                    } catch (Exception $Ex) {
                        $Sender->Form->AddError($Ex);
                        $Sender->Render('Url', '', 'plugins/OpenID');
                    }
                }
                break;
        }
    }

    /**
     *
     *
     * @param Gdn_Controller $Sender
     */
    public function EntryController_SignIn_Handler($Sender, $Args) {
//      if (!$this->IsEnabled()) return;

        if (isset($Sender->Data['Methods']) && $this->SignInAllowed()) {
            $Url = $this->_AuthorizeHref();

            // Add the OpenID method to the controller.
            $Method = array(
                'Name' => 'OpenID',
                'SignInHtml' => SocialSigninButton('OpenID', $Url, 'button', array('class' => 'js-extern'))
            );

            $Sender->Data['Methods'][] = $Method;
        }
    }

    /**
     *
     *
     * @return bool
     */
    public function SignInAllowed() {
        return !C('Plugins.OpenID.DisableSignIn', FALSE);
    }

    /**
     *
     *
     * @param $Sender
     * @param $Args
     */
    public function Base_SignInIcons_Handler($Sender, $Args) {
        if ($this->SignInAllowed()) {
            echo "\n".$this->_GetButton();
        }
    }

    /**
     *
     *
     * @param $Sender
     * @param $Args
     */
    public function Base_BeforeSignInButton_Handler($Sender, $Args) {
        if ($this->SignInAllowed()) {
            echo "\n".$this->_GetButton();
        }
    }

    /**
     *
     *
     * @return string
     */
    private function _GetButton() {
        if ($this->SignInAllowed()) {
            $Url = $this->_AuthorizeHref();
            return SocialSigninButton('OpenID', $Url, 'icon', array('class' => 'js-extern', 'rel' => 'nofollow'));
        }
    }

    /**
     *
     *
     * @param $Sender
     */
    public function Base_BeforeSignInLink_Handler($Sender) {
//      if (!$this->IsEnabled())
//			return;

        // if (!IsMobile())
        // 	return;

        if (!Gdn::Session()->IsValid() && $this->SignInAllowed())
            echo "\n".Wrap($this->_GetButton(), 'li', array('class' => 'Connect OpenIDConnect'));
    }

    /**
     * This OpenID plugin is requisite for some other sso plugins, but we may not always want the OpenID sso option.
     *
     * Let's allow users to remove the ability to sign in with OpenID.
     */
    public function SettingsController_OpenID_Create($Sender) {
        $Sender->Permission('Garden.Settings.Manage');

        $Conf = new ConfigurationModule($Sender);
        $Conf->Initialize(array(
            'Plugins.OpenID.DisableSignIn' => array('Control' => 'Checkbox', 'LabelCode' => 'Disable OpenID sign in', 'Default' => FALSE)
        ));

        $Sender->AddSideMenu();
        $Sender->SetData('Title', sprintf(T('%s Settings'), T('OpenID')));
        $Sender->ConfigurationModule = $Conf;
        $Conf->RenderAll();
    }
}
