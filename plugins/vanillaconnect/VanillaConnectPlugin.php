<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

namespace Vanilla\VanillaConnect;

use Gdn_AuthenticationProviderModel;
use Gdn_Configuration;
use EntryController;
use Logger;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\RequestInterface;
use Gdn_Plugin;
use Gdn_Session;
use UserAuthenticationNonceModel;
use UserModel;
use Vanilla\Models\SSOModel;

class VanillaConnectPlugin extends Gdn_Plugin {

    /**
     * @var Gdn_AuthenticationProviderModel
     */
    private $authProviderModel;

    /**
     * @var Gdn_Configuration
     */
    private $config;

    /**
     * @var UserAuthenticationNonceModel
     */
    private $nonceModel;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var Gdn_Session
     */
    private $session;

    /**
     * @var SSOModel
     */
    private $ssoModel;

    /**
     * @var UserModel
     */
    private $userModel;

    /**
     * VanillaConnectPlugin constructor.
     *
     * @param Gdn_AuthenticationProviderModel $authProviderModel
     * @param Gdn_Configuration $config
     * @param RequestInterface $request
     * @param Gdn_Session $session
     * @param SSOModel $ssoModel
     * @param UserAuthenticationNonceModel $nonceModel
     * @param UserModel $userModel
     */
    public function __construct(
        Gdn_AuthenticationProviderModel $authProviderModel,
        Gdn_Configuration $config,
        UserAuthenticationNonceModel $nonceModel,
        RequestInterface $request,
        Gdn_Session $session,
        SSOModel $ssoModel,
        UserModel $userModel
    ) {
        parent::__construct();

        $this->authProviderModel = $authProviderModel;
        $this->config = $config;
        $this->nonceModel = $nonceModel;
        $this->request = $request;
        $this->session = $session;
        $this->ssoModel = $ssoModel;
        $this->userModel = $userModel;
    }

    /**
     * Disable Garden.SignIn.Popup is one connection is set as Default.
     * Authenticate a user using the JWT supplied from the query parameter "vc_sso".
     */
    public function gdn_dispatcher_appStartup_handler() {
        if (!$this->session->isValid()) {
            $defaultProvider = $this->authProviderModel->getDefault();
            if ($defaultProvider && $defaultProvider['AuthenticationSchemeAlias'] === VanillaConnect::NAME) {
                // Make sure that we don't use the SignIn popup that does a post request which is incompatible with redirects.
                $this->config->saveToConfig('Garden.SignIn.Popup', false, false);
            }
        }

        $query = $this->request->getQuery();

        $jwt = !empty($query['vc_sso']) ? $query['vc_sso'] : null;
        if ($jwt === null) {
            return;
        }
        unset($query['vc_sso']);

        try {
            $clientID = VanillaConnect::extractClientID($jwt);
            $vanillaConnectAuthenticator = new VanillaConnectAuthenticator(
                $clientID,
                $this->authProviderModel,
                $this->request,
                $this->nonceModel
            );
            $ssoData = $vanillaConnectAuthenticator->validatePushSSOAuthentication($jwt);

            $currentUserID = $this->session->UserID;

            saveToConfig('Garden.Registration.SendConnectEmail', false, false);

            if ($this->ssoModel->sso($ssoData)) {
                if ($this->session->UserID != $currentUserID) {
                    $this->userModel->fireEvent('AfterSignIn');
                }
            } else {
                throw new \Exception('Unable to push connect the user.');
            }
        } catch (\Exception $e) {
            Logger::log(Logger::ERROR, 'vanillaconnect_pushsso_error', [
                'endpoint' => $this->request->getPath(),
                'errorCode' => $e->getCode(),
                'errorMessage' => $e->getMessage(),
                'validationResults' => $this->ssoModel->getValidationResults(),
                'jwt' => $jwt,
            ]);
        }

        // Redirect the request with 'vc_sso' stripped from the query string.
        if ($this->request->getMethod() !== 'POST') {
            $queryString = http_build_query($query);
            redirectTo($this->request->getPath().($queryString ? '?'.$queryString : ''));
            exit();
        }
    }

    /**
     * Make the signIn button work when a VanillaConnect provider is set as the default.
     *
     * @param $sender
     * @param $args
     */
    public function entryController_overrideSignIn_handler($sender, $args) {
        if ($args['DefaultProvider']['AuthenticationSchemeAlias'] !== VanillaConnect::NAME) {
            return;
        }

        $this->entryController_vanillaConnect_create(null, 'signin', $args['DefaultProvider']['AuthenticationKey']);
    }

    /**
     * Allow user to be logged in when hitting the /sso endpoint.
     *
     * @param $sender
     * @param $args
     */
    public function rootController_sso_handler($sender, $args) {
        if ($args['DefaultProvider']['AuthenticationSchemeAlias'] !== VanillaConnect::NAME) {
            return;
        }

        $this->entryController_vanillaConnect_create(null, 'signin', $args['DefaultProvider']['AuthenticationKey']);
    }

    /**
     * Put SignIn buttons in the guest box.
     */
    public function base_beforeSignInButton_handler() {
        $providers = $this->authProviderModel->getProvidersByScheme(VanillaConnect::NAME);
        foreach ($providers as $provider) {
            echo "\n".$this->connectButton($provider);
        }
    }

    /**
     * Put SignIn buttons in mobile theme.
     */
    public function base_beforeSignInLink_handler() {
        if ($this->session->isValid()) {
            return;
        }

        $providers = $this->authProviderModel->getProvidersByScheme(VanillaConnect::NAME);
        foreach ($providers as $provider) {
            echo "\n".wrap($this->connectButton($provider, ['NoRegister' => true]), 'li', ['class' => 'Connect']);
        }
    }

    /**
     * Put SignIn buttons on /entry/signin
     *
     * @param EntryController $sender
     */
    public function entryController_signIn_handler($sender) {
        $providers = $this->authProviderModel->getProvidersByScheme(VanillaConnect::NAME);

        $methods = $sender->data('Methods', []);

        foreach ($providers as $provider) {
            $methods[] = [
                'Name' => $provider['Name'],
                'SignInHtml' => $this->connectButton($provider),
            ];
        }

        $sender->setData('Methods', $methods);
    }

    /**
     * Generate the connect button for a specific provider.
     *
     * @param array $provider
     * @param array $options
     * @return string
     */
    private function connectButton($provider, $options = []) {
        if ($provider['IsDefault']) {
            return '';
        }

        // Redirect to /authenticate/vanillaconnect which will redirect to the proper URL with the JWT.
        $signInUrl = '/entry/vanillaconnect/signin/'.rawurlencode($provider['AuthenticationKey']).'?target='.rawurlencode($this->request->getPath());
        $registerUrl = '/entry/vanillaconnect/register/'.rawurlencode($provider['AuthenticationKey']).'?target='.rawurlencode($this->request->getPath());

        $result = '<div class="vanilla-connect">';
        $result .= anchor(sprintf(t('Sign In with %s'), $provider['Name']), $signInUrl, 'Button Primary SignInLink');
        if (!val('NoRegister', $options, false) && $registerUrl) {
            $result .= ' '.anchor(sprintf(t('Register with %s', 'Register'), $provider['Name']), $registerUrl, 'Button RegisterLink');
        }
        $result .= '</div>';

        return $result;
    }

    /**
     * Create the entry/vanillaconnect endpoint.
     *
     * @param EntryController $sender
     * @param string $action
     * @param string $clientID
     * @throws NotFoundException
     */
    public function entryController_vanillaConnect_create($sender, $action = '', $clientID = '') {
        if (!in_array($action, ['signin', 'register'])) {
            throw new NotFoundException();
        }

        $vanillaConnectAuthenticator = new VanillaConnectAuthenticator(
            $clientID,
            $this->authProviderModel,
            $this->request,
            $this->nonceModel
        );

        if ($action === 'signin') {
            $url = $vanillaConnectAuthenticator->signInURL();
        } else {
            $url = $vanillaConnectAuthenticator->registrationURL();
        }

        redirectTo($url, 302, false);
    }

    /**
     * Add VanillaConnect to the side menu.
     *
     * @param Gdn_Controller $sender
     */
    public function base_getAppSettingsMenuItems_handler($sender) {
        $menu = $sender->EventArguments['SideMenu'];
        $menu->addLink('connect', 'VanillaConnect', 'settings/vanillaconnect', 'Garden.Settings.Manage', ['class' => 'nav-jsconnect']);
    }

    /**
     * Dispatch settings/vanillaconnect/{action}
     *
     * @param SettingsController $sender
     * @param array $args
     */
    public function settingsController_vanillaconnect_create($sender, $args = []) {
        $sender->permission('Garden.Settings.Manage');
        $sender->setHighlightRoute();

        switch (strtolower(val(0, $args))) {
            case 'addedit':
                $this->settings_addEdit($sender);
                break;
            case 'delete':
                $this->settings_delete($sender);
                break;
            default:
                $this->settings_index($sender);
                break;
        }
    }

    /**
     * Add/Edit VanillaConnect providers.
     *
     * @param SettingsController $sender
     */
    protected function settings_addEdit($sender) {
        $sender->addJsFile('jsconnect-settings.js', 'plugins/jsconnect');

        /* @var \Gdn_Form $form */
        $form = $sender->Form;
        $form->setModel($this->authProviderModel);

        $clientID = $sender->Request->get('client_id');

        if ($form->authenticatedPostBack()) {
            $form->validateRule('AuthenticationKey', 'ValidateRequired', sprintf(t('%s is required.'), t('Client ID')));
            $form->validateRule('AuthenticationKey', 'regex:`^[a-z0-9_-]+$`i', t('The client id must contain only letters, numbers and dashes.'));
            $form->validateRule('AssociationSecret', 'ValidateRequired', sprintf(t('%s is required.'), t('Secret')));
            $form->validateRule('SignInUrl', 'ValidateRequired', sprintf(t('%s is required.'), t('Sign In URL')));
            $form->validateRule('SignInUrl', 'ValidateUrl');
            $form->validateRule('RegisterUrl', 'ValidateUrl');
            $form->validateRule('SignOutUrl', 'ValidateUrl');

            $form->setFormValue('AuthenticationSchemeAlias', VanillaConnect::NAME);

            if ($form->save(['ID' => $clientID])) {
                $sender->setRedirectTo('/settings/vanillaconnect');
            }
        } else {
            if ($clientID) {
                $provider = $this->authProviderModel->getID($clientID, DATASET_TYPE_ARRAY);
                $provider['Trusted'] = valr('Attributes.Trusted', $provider, false);
            } else {
                $provider = [];
            }
            $form->setData($provider);
        }

        $controls = [
            'AuthenticationKey' => [
                'LabelCode' => 'Client ID',
                'Description' => t('The client ID uniquely identifies the site.', 'The client ID uniquely identifies the site. You can generate a new ID with the button at the bottom of this page.')
            ],
            'AssociationSecret' => [
                'LabelCode' => 'Secret',
                'Description' => t('The secret secures the sign in process.', 'The secret secures the sign in process. Do <b>NOT</b> give the secret out to anyone.')
            ],
            'Name' => [
                'LabelCode' => 'Provider Name',
                'Description' => t('Enter a short name that identifies this provider.').' '.t('This is displayed on the sign in buttons.')
            ],
            'SignInUrl' => [
                'LabelCode' => 'Sign In URL',
                'Description' => t('The url that users use to sign in.').' '.t('Use target={target} to redirect users to the page they were on or target=WhateverYouWant to redirect to a specific url.')
            ],
            'RegisterUrl' => [
                'LabelCode' => 'Registration URL',
                'Description' => t('The url that users use to register for a new account.')
            ],
            'SignOutUrl' => [
                'LabelCode' => 'Sign Out URL',
                'Description' => t('The url that users use to sign out of your site.').' '.t('Only used if the provider is set to be used as the default sign in method.')
            ],
            'Trusted' => [
                'Control' => 'toggle',
                'LabelCode' => 'This is a trusted provider and it can sync user\'s information, roles & permissions.'
            ],
            'IsDefault' => [
                'Control' => 'toggle',
                'LabelCode' => 'Make this provider your default sign in method.'
            ],
        ];
        $sender->setData('_Controls', $controls);
        $sender->setData('Title', sprintf(t($clientID ? 'Edit %s' : 'Add %s'), t('Provider')));

        $sender->render('settings_addedit', '', 'plugins/vanillaconnect');
    }

    /**
     * Delete a VanillaConnect provider.
     *
     * @param SettingsController $sender
     */
    public function settings_delete($sender) {
        $clientID = $sender->Request->get('client_id');
        if ($sender->Form->authenticatedPostBack()) {
            $this->authProviderModel->delete(['AuthenticationKey' => $clientID]);
            $sender->setRedirectTo('/settings/vanillaconnect');
            $sender->render('blank', 'utility', 'dashboard');
        }
    }

    /**
     * Display the VanillaConnect settings page.
     *
     * @param SettingsController $sender
     */
    protected function settings_index($sender) {
        $validation = new \Gdn_Validation();
        $configurationModel = new \Gdn_ConfigurationModel($validation);
        $configurationModel->setField([
            'Garden.Registration.AutoConnect',
        ]);
        $sender->Form->setModel($configurationModel);
        if ($sender->Form->authenticatedPostback()) {
            if ($sender->Form->save() !== false) {
                $sender->informMessage(t('Your settings have been saved.'));
            }
        } else {
            $sender->Form->setData($configurationModel->Data);
        }

        $providers = $this->authProviderModel->getProvidersByScheme(VanillaConnect::NAME);
        $sender->setData('providers', $providers);
        $sender->render('settings', '', 'plugins/vanillaconnect');
    }
}
