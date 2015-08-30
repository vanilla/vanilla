<?php
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
    'MobileFriendly' => true,
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
    protected function _AuthorizeHref($Popup = false) {
        $Url = url('/entry/openid', true);
        $UrlParts = explode('?', $Url);
        parse_str(val(1, $UrlParts, ''), $Query);

        $Path = '/'.Gdn::request()->path();
        $Query['Target'] = val('Target', $_GET, $Path ? $Path : '/');

        if (isset($_GET['Target'])) {
            $Query['Target'] = $_GET['Target'];
        }
        if ($Popup) {
            $Query['display'] = 'popup';
        }

        $Result = $UrlParts[0].'?'.http_build_query($Query);
        return $Result;
    }

    /**
     *
     *
     * @return LightOpenID
     */
    public function getOpenID() {
        if (get_magic_quotes_gpc()) {
            foreach ($_GET as $Name => $Value) {
                $_GET[$Name] = stripslashes($Value);
            }
        }

        $OpenID = new LightOpenID();

        if ($url = Gdn::request()->get('url')) {
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                throw new Gdn_UserException(sprintf(t('ValidateUrl'), 'OpenID'), 400);
            }

            // Don't allow open ID on a non-standard scheme.
            $scheme = parse_url($url, PHP_URL_SCHEME);
            if (!in_array($scheme, array('http', 'https'))) {
                throw new Gdn_UserException(sprintf(t('ValidateUrl'), 'OpenID'), 400);
            }

            // Don't allow open ID on a non-standard port.
            $port = parse_url($url, PHP_URL_PORT);
            if ($port && !in_array($port, array(80, 8080, 443))) {
                throw new Gdn_UserException(t('OpenID is not allowed on non-standard ports.'));
            }

            $OpenID->identity = $url;
        }

        $Url = url('/entry/connect/openid', true);
        $UrlParts = explode('?', $Url);
        parse_str(val(1, $UrlParts, ''), $Query);
        $Query = array_merge($Query, arrayTranslate($_GET, array('display', 'Target')));

        $OpenID->returnUrl = $UrlParts[0].'?'.http_build_query($Query);
        $OpenID->required = array('contact/email', 'namePerson/first', 'namePerson/last', 'pref/language');

        $this->EventArguments['OpenID'] = $OpenID;
        $this->fireEvent('GetOpenID');

        return $OpenID;
    }

    /**
     * Act as a mini dispatcher for API requests to the plugin app
     */
//   public function pluginController_OpenID_create($Sender) {
//      $Sender->permission('Garden.Settings.Manage');
//		$this->dispatch($Sender, $Sender->RequestArgs);
//   }

//   public function Controller_Toggle($Sender) {
//      $this->AutoToggle($Sender);
//   }

//   public function AuthenticationController_render_before($Sender, $Args) {
//      if (isset($Sender->ChooserList)) {
//         $Sender->ChooserList['openid'] = 'OpenID';
//      }
//      if (is_array($Sender->data('AuthenticationConfigureList'))) {
//         $List = $Sender->data('AuthenticationConfigureList');
//         $List['openid'] = '/dashboard/plugin/openid';
//         $Sender->setData('AuthenticationConfigureList', $List);
//      }
//   }

    /**
     * @throws Gdn_UserException
     */
    public function setup() {
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
    public function base_connectData_handler($Sender, $Args) {
        if (val(0, $Args) != 'openid') {
            return;
        }

        $Mode = $Sender->Request->get('openid_mode');
        if ($Mode != 'id_res') {
            return; // this will error out
        }
        $this->EventArguments = $Args;

        // Check session before retrieving
        $Session = Gdn::session();
        $OpenID = $Session->stash('OpenID', '', false);
        if (!$OpenID) {
            $OpenID = $this->GetOpenID();
        }

        if ($Session->Stash('OpenID', '', false) || $OpenID->validate()) {
            $Attr = $OpenID->getAttributes();

            $Form = $Sender->Form; //new Gdn_Form();
            $ID = $OpenID->identity;
            $Form->setFormValue('UniqueID', $ID);
            $Form->setFormValue('Provider', self::$ProviderKey);
            $Form->setFormValue('ProviderName', 'OpenID');

            $Form->setFormValue('FullName', trim(val('namePerson/first', $Attr).' '.val('namePerson/last', $Attr)));

            if ($Email = val('contact/email', $Attr)) {
                $Form->setFormValue('Email', $Email);
            }

            $Sender->setData('Verified', true);
            $Session->stash('OpenID', $OpenID);

            $this->EventArguments['OpenID'] = $OpenID;
            $this->EventArguments['Form'] = $Form;
            $this->fireEvent('AfterConnectData');

        }
    }

    /**
     *
     *
     * @param EntryController $Sender
     * @param array $Args
     */
    public function entryController_openID_create($Sender, $Args) {
        $this->EventArguments = $Args;
        $Sender->Form->InputPrefix = '';

        try {
            $OpenID = $this->getOpenID();
        } catch (Gdn_UserException $ex) {
            $Sender->Form->addError('@'.$ex->getMessage());
            $Sender->render('Url', '', 'plugins/OpenID');
        }

        $Mode = $Sender->Request->get('openid_mode');
        switch ($Mode) {
            case 'cancel':
                $Sender->render('Cancel', '', 'plugins/OpenID');
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
                    $Sender->setData('Title', t('Sign In with OpenID'));
                    $Sender->render('Url', '', 'plugins/OpenID');
                } else {
                    try {
                        $Url = $OpenID->authUrl();
                        redirect($Url);
                    } catch (Exception $Ex) {
                        $Sender->Form->addError($Ex);
                        $Sender->render('Url', '', 'plugins/OpenID');
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
    public function entryController_signIn_handler($Sender, $Args) {
        if (isset($Sender->Data['Methods']) && $this->signInAllowed()) {
            $Url = $this->_authorizeHref();

            // Add the OpenID method to the controller.
            $Method = array(
                'Name' => 'OpenID',
                'SignInHtml' => socialSigninButton('OpenID', $Url, 'button', array('class' => 'js-extern'))
            );

            $Sender->Data['Methods'][] = $Method;
        }
    }

    /**
     *
     *
     * @return bool
     */
    public function signInAllowed() {
        return !c('Plugins.OpenID.DisableSignIn', false);
    }

    /**
     *
     *
     * @param $Sender
     * @param $Args
     */
    public function base_SignInIcons_handler($Sender, $Args) {
        if ($this->signInAllowed()) {
            echo "\n".$this->_getButton();
        }
    }

    /**
     *
     *
     * @param $Sender
     * @param $Args
     */
    public function base_beforeSignInButton_handler($Sender, $Args) {
        if ($this->signInAllowed()) {
            echo "\n".$this->_getButton();
        }
    }

    /**
     *
     *
     * @return string
     */
    private function _getButton() {
        if ($this->signInAllowed()) {
            $Url = $this->_authorizeHref();
            return socialSigninButton('OpenID', $Url, 'icon', array('class' => 'js-extern', 'rel' => 'nofollow'));
        }
    }

    /**
     *
     *
     * @param $Sender
     */
    public function base_beforeSignInLink_handler($Sender) {
        if (!Gdn::session()->isValid() && $this->signInAllowed()) {
            echo "\n".wrap($this->_getButton(), 'li', array('class' => 'Connect OpenIDConnect'));
        }
    }

    /**
     * This OpenID plugin is requisite for some other sso plugins, but we may not always want the OpenID sso option.
     *
     * Let's allow users to remove the ability to sign in with OpenID.
     */
    public function settingsController_openID_create($Sender) {
        $Sender->permission('Garden.Settings.Manage');

        $Conf = new ConfigurationModule($Sender);
        $Conf->initialize(array(
            'Plugins.OpenID.DisableSignIn' => array('Control' => 'Checkbox', 'LabelCode' => 'Disable OpenID sign in', 'Default' => false)
        ));

        $Sender->addSideMenu();
        $Sender->setData('Title', sprintf(t('%s Settings'), t('OpenID')));
        $Sender->ConfigurationModule = $Conf;
        $Conf->renderAll();
    }
}
