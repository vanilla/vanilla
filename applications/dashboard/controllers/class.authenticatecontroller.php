<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

use Garden\Web\RequestInterface;
use Vanilla\Models\AuthenticatorModel;
use Vanilla\Models\SSOModel;

/**
 * Create /authenticate endpoint.
 */
class AuthenticateController extends Gdn_Controller {

    /** @var AuthenticateApiController */
    private $authenticateApiController;

    /** @var AuthenticatorModel */
    private $authenticatorModel;

    /** @var Gdn_Form */
    private $form;

    /** @var RequestInterface */
    private $request;

    /** @var SessionModel */
    private $sessionModel;

    /** @var SSOModel */
    private $ssoModel;

    /** @var UserModel */
    private $userModel;

    /**
     * AuthenticateController constructor.
     *
     * @param AuthenticateApiController $authenticateApiController
     * @param AuthenticatorModel $authenticatorModel
     * @param RequestInterface $request
     * @param SessionModel $sessionModel
     * @param SSOModel $ssoModel
     * @param UserModel $userModel
     */
    public function __construct(
        AuthenticateApiController $authenticateApiController,
        AuthenticatorModel $authenticatorModel,
        RequestInterface $request,
        SessionModel $sessionModel,
        SSOModel $ssoModel,
        UserModel $userModel
    ) {
        parent::__construct();

        $this->authenticateApiController = $authenticateApiController;
        $this->authenticatorModel = $authenticatorModel;
        $this->form = new Gdn_Form();
        $this->request = $request;
        $this->sessionModel = $sessionModel;
        $this->ssoModel = $ssoModel;
        $this->userModel = $userModel;
    }

    /**
     * Get the controller's form.
     *
     * @return Gdn_Form
     */
    public function getForm() {
        return $this->form;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize() {
        Gdn::session()->ensureTransientKey();

        // Set up head
        $this->Head = new HeadModule($this);

        $this->addJsFile('jquery.js');
        $this->addJsFile('jquery.form.js');
        $this->addJsFile('jquery.popup.js');
        $this->addJsFile('jquery.popin.js');
        $this->addJsFile('jquery.gardenhandleajaxform.js');
        $this->addJsFile('jquery.atwho.js');
        $this->addJsFile('global.js');

        $this->addJsFile('authenticate.js');

        $this->addCssFile('style.css');
        $this->addCssFile('vanillicon.css', 'static');
        $this->addCssFile('authenticate.css');

        parent::initialize();
    }

    /**
     * Do an authentication using the specified authenticator.
     *
     * @param string $authenticatorType The authenticator's type.
     * @param string $authenticatorID The authenticator's instance ID.
     * @throws Exception Connect user feature is not implemented.
     */
    public function connect($authenticatorType = '', $authenticatorID = '') {
        $persist = $this->request->getBody()['persist'] ?? ($this->request->getQuery()['persist'] ?? false);
        $agreeToTerms = $this->request->getBody()['agreeToTerms'] ?? ($this->request->getQuery()['agreeToTerms'] ?? false);

        $state = [];

        try {
            $response = $this->authenticateApiController->post([
                'authenticate' => [
                    'authenticatorType' => $authenticatorType,
                    'authenticatorID' => $authenticatorID,
                ],
                'method' => 'signIn',
                'persist' => $persist,
                'agreeToTerms' => $agreeToTerms,
            ]);



//            trace($response);

            switch ($response['authenticationStep']) {
                case 'authenticated':
                    // The user successfully authenticated and the cookie is set. Redirect.
                    redirectTo($respone['targetUrl'] ?? '/');
                    break;
                case 'linkUser':
                    // The user has to be manually linked.
                    $state['step'] = 'linkUser';
                    $state['authSessionID'] = $response['authSessionID'];
                    $state['linkUser'] = $this->authenticateApiController->get_linkUser($response['authSessionID']);

                    if (!valr('linkUser.ssoUser.photoUrl', $state)) {
                        setvalr('linkUser.ssoUser.photoUrl', $state, url('/applications/dashboard/design/images/authenticators/user.svg'));
                    }

                    // Terms of Service
                    $termsOfServiceUrl = c('Garden.TermsOfService', '#');
                    $termsOfServiceText = sprintf(t('I agree to the <a id="TermsOfService" class="Popup" target="terms" href="%s">terms of service</a>'), url($termsOfServiceUrl));
                    setvalr('linkUser.authenticator.ui.termsOfServiceLabel', $state, $termsOfServiceText);

                    break;
                // case 'addInfo':
                //     // The user successfully authenticated, but has to accept terms of service.
                default:
                    $state['step'] = 'error';
                    $state['error'] = 'Unknown authentication step: '.$response['authenticationStep'];
            }
        } catch (\Exception $ex) {
            $state['step'] = 'error';
            $state['error'] = $ex->getMessage();
        }

        // Render the component routed to /authenticate/connect. It will get its data
//        trace($state);
        $this->addDefinition('state', ['authenticate' => $state]);
        $this->renderReact();
    }

    /**
     *
     */
    public function recoverPassword() {
        $this->renderReact();
    }

    /**
     * Sign In Page
     *
     * @param string $authenticatorType
     * @param string $authenticatorID
     *
     * @throws \Garden\Web\Exception\ClientException
     * @throws \Garden\Web\Exception\NotFoundException
     * @throws \Garden\Web\Exception\ServerException
     */
    public function signin($authenticatorType = '', $authenticatorID = '') {
        if ($authenticatorType && $authenticatorID) {
            $authenticator = $this->authenticatorModel->getAuthenticator($authenticatorType, $authenticatorID);
            if (is_a($authenticator, \Vanilla\Authenticator\SSOAuthenticator::class)) {
                $authenticator->initiateAuthentication();
                return;
            }
        }

        $this->renderReact();
    }

    /**
     * Password Page
     */
    public function password() {
        $this->renderReact();
    }
}
