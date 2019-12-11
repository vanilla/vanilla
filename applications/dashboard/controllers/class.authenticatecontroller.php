<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\Web\Data;
use Garden\Web\RequestInterface;
use Vanilla\Models\AuthenticatorModel;
use Vanilla\Models\SSOModel;
use Vanilla\Web\JsInterpop\ReduxAction;

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
        \Vanilla\FeatureFlagHelper::ensureFeature(AuthenticateApiController::FEATURE_FLAG);
        parent::__construct();

        $this->authenticateApiController = $authenticateApiController;
        $this->authenticatorModel = $authenticatorModel;
        $this->form = new Gdn_Form();
        $this->request = $request;
        $this->sessionModel = $sessionModel;
        $this->ssoModel = $ssoModel;
        $this->userModel = $userModel;

        $this->setHeader('Cache-Control', \Vanilla\Web\CacheControlMiddleware::NO_CACHE);
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
        $this->addJsFile('jquery.popup.js');
        $this->addJsFile('jquery.popin.js');
        $this->addJsFile('global.js');

        $this->addCssFile('style.css');
        $this->addCssFile('vanillicon.css', 'static');

        parent::initialize();
    }

    /**
     * Do an authentication using the specified authenticator.
     *
     * @param string $authenticatorType The authenticator's type.
     * @param string $authenticatorID The authenticator's instance ID.
     * @throws Exception Connect user feature is not implemented.
     */
    public function index($authenticatorType = '', $authenticatorID = '') {
        if ($authenticatorType === '') {
            redirectTo('authenticate/signin');
        }
        $persist = $this->request->getBody()['persist'] ?? ($this->request->getQuery()['persist'] ?? false);

        $response = $this->authenticateApiController->post([
            'authenticate' => [
                'authenticatorType' => $authenticatorType,
                'authenticatorID' => $authenticatorID,
            ],
            'persist' => $persist,
        ]);

        if ($response['authenticationStep'] === 'authenticated') {
            $redirectURL = (val('target', $this->request->getQuery(), '/'));
        } else {
            throw new Exception('Connect user feature is not implemented.', 500);
//            $target = val('target', $this->request->getQuery());
//            if ($target) {
//                $target = '&target='.$target;
//            }
//            $redirectURL = "/authenticate/connectuser?authSessionID={$response['authSessionID']}{$target}";
        }

        if ($this->deliveryMethod() === DELIVERY_METHOD_JSON) {
            $this->setRedirectTo($redirectURL);
        } else {
            redirectTo($redirectURL);
        }
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

        $this->addReduxAction(
            new ReduxAction(
                '@@auth/GET_ALL_AUTHENTICATORS_DONE',
                Data::box($this->authenticateApiController->index_authenticators()),
                []
            )
        );

        $this->renderReact();
    }

    /**
     * Password Page
     */
    public function password() {
        $this->renderReact();
    }
}
