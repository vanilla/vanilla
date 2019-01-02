<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

namespace Vanilla\VanillaConnect;

use EntryController;
use Garden\Schema\ValidationException;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\RequestInterface;
use Gdn_Configuration;
use Gdn_Plugin;
use Gdn_Session;
use Logger;
use SettingsController;
use sprintft;
use UserModel;
use Vanilla\Models\AuthenticatorModel;
use Vanilla\Models\SSOModel;

class VanillaConnectPlugin extends Gdn_Plugin {

    /** @var \Vanilla\Models\AuthenticatorModel  */
    private $authenticatorModel;

    /** @var Gdn_Configuration */
    private $config;

    /** @var RequestInterface */
    private $request;

    /** @var Gdn_Session */
    private $session;

    /** @var SSOModel */
    private $ssoModel;

    /** @var UserModel */
    private $userModel;

    /**
     * VanillaConnectPlugin constructor.
     *
     * @param \Vanilla\Models\AuthenticatorModel
     * @param Gdn_Configuration $config
     * @param RequestInterface $request
     * @param Gdn_Session $session
     * @param SSOModel $ssoModel
     * @param UserModel $userModel
     */
    public function __construct(
        AuthenticatorModel $authenticatorModel,
        Gdn_Configuration $config,
        RequestInterface $request,
        Gdn_Session $session,
        SSOModel $ssoModel,
        UserModel $userModel
    ) {
        parent::__construct();

        $this->authenticatorModel = $authenticatorModel;
        $this->config = $config;
        $this->request = $request;
        $this->session = $session;
        $this->ssoModel = $ssoModel;
        $this->userModel = $userModel;
    }

    /**
     * Authenticate a user using the JWT supplied from the query parameter "vc_sso".
     */
    public function gdn_dispatcher_appStartup_handler() {
        $query = $this->request->getQuery();

        $jwt = !empty($query['vc_sso']) ? $query['vc_sso'] : null;
        if ($jwt === null) {
            return;
        }
        unset($query['vc_sso']);

        try {
            $authenticatorID = VanillaConnect::extractItemFromClaim('authenticatorID');
            $vanillaConnectAuthenticator = $this->authenticatorModel->getAuthenticator(VanillaConnectAuthenticator::getType(), $authenticatorID);
            $ssoData = $vanillaConnectAuthenticator->validatePushSSOAuthentication($jwt);

            saveToConfig('Garden.Registration.SendConnectEmail', false, false);

            if (!$this->ssoModel->sso($ssoData)) {
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

//
//    Authenticators do not currently support the "default" option.
//
//    /**
//     * Make the signIn button work when a VanillaConnect provider is set as the default.
//     *
//     * @param $sender
//     * @param $args
//     *
//     * @throws \Garden\Container\ContainerException
//     * @throws \Garden\Container\NotFoundException
//     * @throws \Garden\Web\Exception\NotFoundException
//     */
//    public function entryController_overrideSignIn_handler($sender, $args) {
//        if ($args['DefaultProvider']['AuthenticationSchemeAlias'] !== VanillaConnect::NAME) {
//            return;
//        }
//
//        $this->entryController_vanillaConnect_create(null, 'signin', $args['DefaultProvider']['AuthenticationKey']);
//    }
//
//    /**
//     * Allow user to be logged in when hitting the /sso endpoint.
//     *
//     * @param $sender
//     * @param $args
//     *
//     * @throws \Garden\Container\ContainerException
//     * @throws \Garden\Container\NotFoundException
//     * @throws \Garden\Web\Exception\NotFoundException
//     */
//    public function rootController_sso_handler($sender, $args) {
//        if ($args['DefaultProvider']['AuthenticationSchemeAlias'] !== VanillaConnect::NAME) {
//            return;
//        }
//
//        $this->entryController_vanillaConnect_create(null, 'signin', $args['DefaultProvider']['AuthenticationKey']);
//    }

    /**
     * Put SignIn buttons in the guest box.
     */
    public function base_beforeSignInButton_handler() {
        $authenticators = $this->authenticatorModel->getAuthenticatorsByClass(VanillaConnectAuthenticator::class);
        foreach ($authenticators as $authenticator) {
            echo "\n".$this->connectButton($authenticator);
        }
    }

    /**
     * Put SignIn buttons in mobile theme.
     */
    public function base_beforeSignInLink_handler() {
        if ($this->session->isValid()) {
            return;
        }

        $authenticators = $this->authenticatorModel->getAuthenticatorsByClass(VanillaConnectAuthenticator::class);
        foreach ($authenticators as $authenticator) {
            echo "\n".wrap($this->connectButton($authenticator, ['NoRegister' => true]), 'li', ['class' => 'Connect']);
        }
    }

    /**
     * Put SignIn buttons on /entry/signin
     *
     * @param EntryController $sender
     *
     * @throws \Garden\Web\Exception\ServerException
     */
    public function entryController_signIn_handler($sender) {
        $authenticators = $this->authenticatorModel->getAuthenticatorsByClass(VanillaConnectAuthenticator::class);

        $methods = $sender->data('Methods', []);

        foreach ($authenticators as $authenticator) {
            $methods[] = [
                'Name' => $authenticator->getName(),
                'SignInHtml' => $this->connectButton($authenticator),
            ];
        }

        $sender->setData('Methods', $methods);
    }

    /**
     * Generate the connect button for a specific provider.
     *
     * @param VanillaConnectAuthenticator $authenticator
     * @param array $options
     * @return string
     */
    private function connectButton(VanillaConnectAuthenticator $authenticator, $options = []) {
        // Redirect to /authenticate/vanillaconnect which will redirect to the proper URL with the JWT.
        $signInUrl = '/entry/vanillaconnect/signin/'.rawurlencode($authenticator->getID()).'?target='.rawurlencode($this->request->getPath());
        $registerUrl = '/entry/vanillaconnect/register/'.rawurlencode($authenticator->getID()).'?target='.rawurlencode($this->request->getPath());

        $result = '<div class="vanilla-connect">';
        $result .= anchor(sprintf(t('Sign In with %s'), $authenticator->getName()), $signInUrl, 'Button Primary SignInLink');
        if (!val('NoRegister', $options, false) && $registerUrl) {
            $result .= ' '.anchor(sprintf(t('Register with %s', 'Register'), $authenticator->getName()), $registerUrl, 'Button RegisterLink');
        }
        $result .= '</div>';

        return $result;
    }

    /**
     * Create the entry/vanillaconnect endpoint.
     *
     * @param EntryController $sender
     * @param string $action
     * @param string $authenticatorID
     *
     * @throws \Garden\Web\Exception\ClientException
     * @throws \Garden\Web\Exception\NotFoundException
     * @throws \Garden\Web\Exception\ServerException
     */
    public function entryController_vanillaConnect_create($sender, $action = '', $authenticatorID = '') {
        if (!in_array($action, ['signin', 'register'])) {
            throw new NotFoundException();
        }

        /** @var \Vanilla\VanillaConnect\VanillaConnectAuthenticator $authenticator */
        $authenticator = $this->authenticatorModel->getAuthenticator(VanillaConnectAuthenticator::getType(), $authenticatorID);

        if ($action === 'signin') {
            $url = $authenticator->initSSOAuthentication();
        } else {
            $url = $authenticator->getRegisterUrl();
            redirectTo($url, 302, false);
        }

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
     *
     * @throws \Gdn_UserException
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
     *
     * @throws \Gdn_UserException
     */
    protected function settings_addEdit($sender) {
        /* @var \Gdn_Form $form */
        $form = $sender->Form;

        $authenticatorID = $sender->Request->get('authenticatorID');
        if ($authenticatorID) {
            /** @var \Vanilla\VanillaConnect\VanillaConnectAuthenticator $authenticator */
            $authenticator = $this->authenticatorModel->getAuthenticatorByID($authenticatorID);
        } else {
            $authenticator = null;
        }


        if ($form->authenticatedPostBack()) {
            $form->validateRule('vanillaConnect.clientID', 'ValidateRequired', sprintft('%s is required.', 'Client ID'));
            $form->validateRule('vanillaConnect.clientID', 'regex:`^[a-z0-9_-]+$`i', sprintft('%s must contain only letters, numbers and dashes.', 'Client ID'));
            $form->validateRule('vanillaConnect.secret', 'ValidateRequired', sprintft('%s is required.', 'Secret'));
            $form->validateRule('name', 'ValidateRequired', sprintft('%s is required.', 'Provider Name'));
            $form->validateRule('signInUrl', 'ValidateRequired', sprintft('%s is required.', 'Sign In URL'));
            $form->validateRule('signInUrl', 'ValidateUrl');
            $form->validateRule('registerUrl', 'ValidateUrl');
            $form->validateRule('signOutUrl', 'ValidateUrl');

            if (!$form->validationResults()) {
                $fields = VanillaConnectAuthenticator::getConfigurableFields();
                $nonEmptyData = array_filter($form->formValues(), function($value) {
                    return $value !== '';
                });
                $data = unflattenArray('.', array_intersect_key($nonEmptyData, array_flip($fields)));

                try {
                    if ($authenticator) {
                        $authenticator->updateAuthenticatorInfo($data);
                    } else {
                        $data['authenticatorID'] = 'vc_'.$data['vanillaConnect']['clientID'];
                        $data['type'] = VanillaConnectAuthenticator::getType();
                        $data['isActive'] = true;
                        $this->authenticatorModel->createSSOAuthenticatorInstance($data);
                    }
                } catch(ValidationException $e) {
                    $form->setValidationResults(['errors' => array_column($e->getValidation()->getErrors(), 'message')]);
                }
            }
            if (!$form->validationResults()) {
                $sender->setRedirectTo('/settings/vanillaconnect');
            }
        } else {
            if ($authenticator) {
                $form->setData(flattenArray('.', $authenticator->getAuthenticatorInfo()));
            }
        }

        $configurableFields = VanillaConnectAuthenticator::getConfigurableFields();

        $controls = [
            'vanillaConnect.clientID' => [
                'LabelCode' => t('Client ID'),
                'Description' => t('The client ID uniquely identifies the site.'),
            ],
            'vanillaConnect.secret' => [
                'LabelCode' => t('Secret'),
                'Description' => t('The secret secures the sign in process.', 'The secret secures the sign in process. Do <b>NOT</b> give the secret out to anyone.'),
            ],
            'name' => [
                'LabelCode' => t('Provider Name'),
                'Description' => t('Enter a short name that identifies this provider.').' '.t('This is displayed on the sign in buttons.'),
            ],
            'signInUrl' => [
                'LabelCode' => t('Sign In URL'),
                'Description' => t('The url that users use to sign in.').' '.t('Use target={target} to redirect users to the page they were on or target=WhateverYouWant to redirect to a specific url.'),
            ],
            'registerUrl' => [
                'LabelCode' => t('Registration URL'),
                'Description' => t('The url that users use to register for a new account.'),
            ],
            'signOutUrl' => [
                'LabelCode' => t('Sign Out URL'),
                'Description' => t('The url that users use to sign out of your site.').' '.t('Only used if the provider is set to be used as the default sign in method.'),
            ],
            'sso.isTrusted' => [
                'LabelCode' => t('Trusted'),
                'Control' => 'toggle',
                'Description' => t('This is a trusted provider and it can sync user\'s information, roles & permissions.'),
            ],
            'sso.canAutoLinkUser' => [
                'LabelCode' => t('Link User'),
                'Control' => 'toggle',
                'Description' => t('This provider can link users to existing forum user by using the provided email address.'),
            ],
        ];
        $sender->setData('_Controls', $controls);
        $sender->setData('Title', sprintf(t($authenticatorID ? 'Edit %s' : 'Add %s'), t('Provider')));

        $sender->render('settings_addedit', '', 'plugins/vanillaconnect');
    }

    /**
     * Delete a VanillaConnect provider.
     *
     * @param SettingsController $sender
     *
     * @throws \Gdn_UserException
     */
    public function settings_delete($sender) {
        $authenticatorID = $sender->Request->get('authenticatorID');
        if ($sender->Form->authenticatedPostBack()) {
            $this->authenticatorModel->deleteSSOAuthenticator(VanillaConnectAuthenticator::getType(), $authenticatorID);
            $sender->setRedirectTo('/settings/vanillaconnect');
            $sender->render('blank', 'utility', 'dashboard');
        }
    }

    /**
     * Display the VanillaConnect settings page.
     *
     * @param SettingsController $sender
     *
     * @throws \Garden\Web\Exception\ServerException
     */
    protected function settings_index($sender) {
        $authenticators = $this->authenticatorModel->getAuthenticatorsByClass(VanillaConnectAuthenticator::class);
        $sender->setData('authenticators', $authenticators);
        $sender->render('settings', '', 'plugins/vanillaconnect');
    }
}
