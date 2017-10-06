<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

use Garden\Web\RequestInterface;
use Vanilla\Models\SSOModel;
use Vanilla\Models\SSOInfo;

/**
 * Create /authenticate endpoint.
 */
class AuthenticateController extends Gdn_Controller {

    /**
     * @var AuthenticateApiController
     */
    private $authenticateApiController;

    /**
     * @var Gdn_Form
     */
    private $form;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var SessionModel
     */
    private $sessionModel;

    /**
     * @var SSOModel
     */
    private $ssoModel;

    /**
     * @var UserModel
     */
    private $userModel;

    /**
     * AuthenticateController constructor.
     *
     * @param AuthenticateApiController $authenticateApiController
     * @param RequestInterface $request
     * @param SessionModel $sessionModel
     * @param SSOModel $ssoModel
     * @param UserModel $userModel
     */
    public function __construct(
        AuthenticateApiController $authenticateApiController,
        RequestInterface $request,
        SessionModel $sessionModel,
        SSOModel $ssoModel,
        UserModel $userModel
    ) {
        parent::__construct();

        $this->authenticateApiController = $authenticateApiController;
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
     * @param string $authenticator The authenticator's name.
     * @param string $authenticatorID The authenticator's instance ID.
     * @throws Exception
     */
    public function index($authenticator = '', $authenticatorID = '') {
        try {
            $response = $this->authenticateApiController->post([
                'authenticator' => $authenticator,
                'authenticatorID' => $authenticatorID,
            ]);
        } catch(Exception $e) {
            if (debug()) {
                throw $e;
            }
            throw notFoundException();
        }

        if ($response['authenticationStep'] === 'authenticated') {
            redirectTo(val('target', $this->request->getQuery(), '/'));
        } else {
            $target = val('target', $this->request->getQuery());
            if ($target) {
                $target = '&target='.$target;
            }
            redirectTo("/authenticate/connectuser?authSessionID={$response['authSessionID']}{$target}");
        }
    }

    /**
     * Create a connection between a provider and a vanilla user.
     *
     * @param $authSessionID The authentication session ID.
     * @throws Gdn_UserException
     */
    public function connectUser($authSessionID) {
        $session = $this->authenticateApiController->get_session($authSessionID, ['expand' => true]);
        if (!isset($session['attributes']['linkuser'])) {
            throw new Gdn_UserException('Invalid session.');
        }

        if (isset($session['attributes']['linkuser']['existingUsers'])) {
            $this->setData('existingUsers', $session['attributes']['linkuser']['existingUsers']);
        }

        $ssoInfo = new SSOInfo($session['attributes']['ssoInfo']);
        $this->setData('ssoInfo', $ssoInfo);

        $connectSuccess = false;
        if ($this->form->isPostBack()) {
            $connectUserID = false;
            $this->form->validateRule('connectOption', 'ValidateRequired');

            if ($this->form->errorCount() === 0) {
                $connectOption = $this->form->getFormValue('connectOption');
                if ($connectOption === 'linkuser') {
                    $connectUserID = $this->linkUser($authSessionID);
                } else if ($connectOption === 'createuser') {
                    $connectUserID = $this->createUser($ssoInfo, $authSessionID);
                } else {
                    $this->form->addError(t('Invalid connectOption.'));
                }

                // Set connect option so that the SSOModel knows how to properly sync the user's roles.
                $ssoInfo['connectOption'] = $connectOption;

                if ($connectUserID) {
                    // This will effectively sync the user info / roles if there is a need for it.
                    $connectSuccess = (bool)$this->ssoModel->sso($ssoInfo);
                } else {
                    $this->form->addError(t('Unable to connect user.'));
                }
            }

        } else {
            $this->form->setValue('createUserName', $ssoInfo->coalesce('name'));
            $this->form->setValue('createUserEmail', $ssoInfo->coalesce('email'));
        }

        if ($connectSuccess) {
            redirectTo(val('Target', $this->request->getQuery(), '/'));
        }

        $this->render();
    }

    /**
     * Validate "linkuser" connect form fields.
     *
     * @param string $authSessionID
     * @return int|false
     */
    private function linkUser($authSessionID) {
        $this->form->validateRule('linkUserID', 'ValidateRequired');
        $this->form->validateRule('linkUserID', 'ValidateInteger');
        $this->form->validateRule('linkUserPassword', 'ValidateRequired', t('Password is required'));

        $userID = false;

        $body = [];
        if ($this->form->errorCount() === 0) {
            $body['password'] = $this->form->getFormValue('linkUserPassword');

            $linkUserID = $this->form->getFormValue('linkUserID');

            if ($linkUserID === '-1') {
                $this->form->validateRule('linkUserName', 'ValidateRequired', t('Username is required.'));
                $this->form->validateRule('linkUserEmail', 'ValidateRequired', t('Email is required'));

                if ($this->form->errorCount() === 0) {
                    $body['name'] = $this->form->getFormValue('linkUserName');
                    $body['email'] = $this->form->getFormValue('linkUserEmail');
                }
            } else {
                $body['userID'] = $this->form->getFormValue('linkUserID');
            }
        }

        if (!empty($body)) {
            try {
                $userFragment = $this->authenticateApiController->post_linkUser($authSessionID, $body);
                $userID = $userFragment['UserID'];
            } catch(Exception $e) {
                $this->form->addError($e->getMessage());
            }
        }

        return $userID;
    }

    /**
     * Create a new user using the "createuser" connect form fields.
     *
     * @param SSOInfo $ssoInfo
     * @param string $authSessionID
     * @return int|false
     */
    private function createUser(SSOInfo $ssoInfo, $authSessionID) {
        $userID = false;

        $this->form->validateRule('createUserName', 'ValidateRequired', t('Username is required.'));
        $this->form->validateRule('createUserName', 'ValidateUsername'); // Default message is perfect.
        $this->form->validateRule('createUserEmail', 'ValidateRequired', t('Email is required'));
        $this->form->validateRule('createUserEmail', 'ValidateEmail', t('Email is invalid.'));

        if ($this->form->errorCount() === 0) {
            $ssoInfo['name'] = $this->form->getFormValue('createUserName');
            $ssoInfo['email'] = $this->form->getFormValue('createUserEmail');
            $user = $this->ssoModel->createUser($ssoInfo);

            if ($user) {
                $userID = $user['UserID'];

                $this->userModel->saveAuthentication([
                    'UserID' => $userID,
                    'Provider' => $ssoInfo['authenticatorID'],
                    'UniqueID' => $ssoInfo['uniqueID']
                ]);
                // Clean the session.
                $this->sessionModel->deleteID($authSessionID);
            } else {
                $this->form->setValidationResults($this->ssoModel->getValidationResults());
            }
        }

        return $userID;
    }
}
