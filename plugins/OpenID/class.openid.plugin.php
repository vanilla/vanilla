<?php
/**
 * OpenID Plugin.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package OpenID
 */

/**
 * Class OpenIDPlugin
 */
class OpenIDPlugin extends Gdn_Plugin {

    /** @var string  */
    public static $ProviderKey = 'OpenID';

    /**
     *
     *
     * @param bool $popup
     * @return string
     */
    protected function _AuthorizeHref($popup = false) {
        $url = url('/entry/openid', true);
        $urlParts = explode('?', $url);
        parse_str(val(1, $urlParts, ''), $query);

        $path = '/'.Gdn::request()->path();
        $query['Target'] = val('Target', $_GET, $path ? $path : '/');

        if (isset($_GET['Target'])) {
            $query['Target'] = $_GET['Target'];
        }
        if ($popup) {
            $query['display'] = 'popup';
        }

        $result = $urlParts[0].'?'.http_build_query($query);
        return $result;
    }

    /**
     *
     *
     * @return LightOpenID
     */
    public function getOpenID() {
        $OpenID = new LightOpenID();

        if ($url = Gdn::request()->get('url')) {
            if (filter_var($url, FILTER_VALIDATE_URL) === false) {
                throw new Gdn_UserException(sprintf(t('ValidateUrl'), 'OpenID'), 400);
            }

            // Don't allow open ID on a non-standard scheme.
            $scheme = parse_url($url, PHP_URL_SCHEME);
            if (!in_array($scheme, ['http', 'https'])) {
                throw new Gdn_UserException(sprintf(t('ValidateUrl'), 'OpenID'), 400);
            }

            // Make sure the host is not an ip.
            $host = parse_url($url, PHP_URL_HOST);
            if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
                throw new Gdn_UserException(sprintf(t('ValidateUrl').' '.t('The hostname cannot be an IP address.'), 'OpenID'), 400);
            }

            // Don't allow open ID on a non-standard port.
            $port = parse_url($url, PHP_URL_PORT);
            if ($port && !in_array($port, [80, 8080, 443])) {
                throw new Gdn_UserException(t('OpenID is not allowed on non-standard ports.'));
            }

            $OpenID->identity = $url;
        }

        $Url = url('/entry/connect/openid', true);
        $UrlParts = explode('?', $Url);
        parse_str(val(1, $UrlParts, ''), $Query);
        $Query = array_merge($Query, arrayTranslate($_GET, ['display', 'Target']));

        $OpenID->returnUrl = $UrlParts[0].'?'.http_build_query($Query);
        $OpenID->required = ['contact/email', 'namePerson/first', 'namePerson/last', 'pref/language'];

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

//   public function controller_Toggle($Sender) {
//      $this->autoToggle($Sender);
//   }

//   public function authenticationController_render_before($Sender, $Args) {
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
     * @param $sender
     * @param $args
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function base_connectData_handler($sender, $args) {
        if (val(0, $args) != 'openid') {
            return;
        }

        $mode = $sender->Request->get('openid_mode');
        if ($mode != 'id_res') {
            return; // this will error out
        }
        $this->EventArguments = $args;

        // Check session before retrieving
        $session = Gdn::session();
        $openID = $this->getOpenID();
        $sessionData = $session->stash('OpenID', '', false);

        /**
         * Save a successful validation and do not attempt to validate again. If a nonce is used as part of the request,
         * and the user has to enter additional information to register (e.g. email), a second authentication attempt
         * after they've submitted the requested information would fail.
         */
        if ($sessionData) {
            $validated = true;
            $openID->setData($sessionData['data']);
            $openID->identity = $sessionData['identity'];
        } elseif ($openID->validate()) {
            $validated = true;
            $session->stash('OpenID', [
                'data' => $openID->getData(),
                'identity' => $openID->identity
            ]);
        } else {
            $validated = false;
        }

        if ($validated) {
            $attr = $openID->getAttributes();

            // This isn't a trusted connection. Don't allow it to automatically connect a user account.
            saveToConfig('Garden.Registration.AutoConnect', false, false);

            $form = $sender->Form; //new gdn_Form();
            $iD = $openID->identity;
            $form->setFormValue('UniqueID', $iD);
            $form->setFormValue('Provider', self::$ProviderKey);
            $form->setFormValue('ProviderName', 'OpenID');

            $form->setFormValue('FullName', trim(val('namePerson/first', $attr).' '.val('namePerson/last', $attr)));

            if ($email = val('contact/email', $attr)) {
                $form->setFormValue('Email', $email);
            }

            $sender->setData('Verified', true);

            $this->EventArguments['OpenID'] = $openID;
            $this->EventArguments['Form'] = $form;
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

        try {
            $OpenID = $this->getOpenID();
        } catch (Gdn_UserException $ex) {
            $Sender->Form->addError('@'.$ex->getMessage());
        }

        $Mode = $Sender->Request->get('openid_mode');
        switch ($Mode) {
            case 'cancel':
                redirectTo(Gdn::router()->getDestination('DefaultController'));
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
                        redirectTo($Url, 302, false);
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
     * @param Gdn_Controller $sender
     */
    public function entryController_signIn_handler($sender, $args) {
        if (isset($sender->Data['Methods']) && $this->signInAllowed()) {
            $url = $this->_authorizeHref();

            // Add the OpenID method to the controller.
            $method = [
                'Name' => 'OpenID',
                'SignInHtml' => socialSigninButton('OpenID', $url, 'button', ['class' => 'js-extern'])
            ];

            $sender->Data['Methods'][] = $method;
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
     * @param $sender
     * @param $args
     */
    public function base_signInIcons_handler($sender, $args) {
        if ($this->signInAllowed()) {
            echo "\n".$this->_getButton();
        }
    }

    /**
     *
     *
     * @param $sender
     * @param $args
     */
    public function base_beforeSignInButton_handler($sender, $args) {
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
            $url = $this->_authorizeHref();
            return socialSigninButton('OpenID', $url, 'icon', ['class' => 'js-extern', 'rel' => 'nofollow']);
        }
    }

    /**
     *
     *
     * @param $sender
     */
    public function base_beforeSignInLink_handler($sender) {
        if (!Gdn::session()->isValid() && $this->signInAllowed()) {
            echo "\n".wrap($this->_getButton(), 'li', ['class' => 'Connect OpenIDConnect']);
        }
    }

    /**
     * This OpenID plugin is requisite for some other sso plugins, but we may not always want the OpenID sso option.
     *
     * Let's allow users to remove the ability to sign in with OpenID.
     */
    public function settingsController_openID_create($sender) {
        $sender->permission('Garden.Settings.Manage');

        $conf = new ConfigurationModule($sender);
        $conf->initialize([
            'Plugins.OpenID.DisableSignIn' => ['Control' => 'Toggle', 'LabelCode' => 'Disable OpenID sign in', 'Default' => false]
        ]);


        $sender->setData('Title', sprintf(t('%s Settings'), t('OpenID')));
        $sender->ConfigurationModule = $conf;
        $conf->renderAll();
    }
}
