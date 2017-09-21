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

    public function getForm() {
        return $this->form;
    }

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

    public function index($authenticator, $authenticatorID = '') {
        try {
            $response = $this->authenticateApiController->get_auth($authenticator, $authenticatorID);
        } catch(Exception $e) {
            if (debug()) {
                throw $e;
            }
            // Render errors
            $this->setData('exception', $e);
            $this->render();
        }

        if ($response['authenticationStep'] === 'authenticated') {
            redirectTo(val('Target', $this->request->getQuery(), '/'));
        } else {
            $target = val('Target', $this->request->getQuery());
            if ($target) {
                $target = '&Target='.$target;
            }
            redirectTo("/authenticate/connectuser?authSessionID={$response['authSessionID']}{$target}");
        }
    }

    /**
     * @param $authSessionID
     * @throws Gdn_UserException
     */
    public function connectUser($authSessionID) {
        $session = $this->authenticateApiController->get_session($authSessionID, ['expand' => true]);
        if (!isset($session['attributes']['connectuser'])) {
            throw new Gdn_UserException('Invalid session.');
        }

        if (isset($session['attributes']['connectuser']['existingUsers'])) {
            $this->setData('existingUsers', $session['attributes']['connectuser']['existingUsers']);
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
                    $connectUserID = $this->getLinkedUserID();
                } else if ($connectOption === 'createuser') {
                    $connectUserID = $this->createUser();
                } else {
                    $this->form->addError(t('Invalid connectOption.'));
                }

                // Set connect option so that the SSOModel knows how to properly sync the user's roles.
                $ssoInfo['connectOption'] = $connectOption;

                if ($connectUserID) {
                    $this->userModel->saveAuthentication([
                        'UserID' => $connectUserID,
                        'Provider' => $ssoInfo['authenticatorID'],
                        'UniqueID' => $ssoInfo['uniqueID']
                    ]);

                    // This will effectively sync the user info / roles if there is a need for it.
                    $connectSuccess = (bool)$this->ssoModel->sso($ssoInfo);
                } else {
                    $this->form->addError(t('Unable to connect user.'));
                }
            }

        } else {
            $this->form->setValue('name', $ssoInfo['name']);
            $this->form->setValue('email', $ssoInfo['email']);
        }

        if ($connectSuccess) {
            $this->authenticateApiController->delete_session($authSessionID);

            redirectTo(val('Target', $this->request->getQuery(), '/'));
        }

        $this->render();
    }

    /**
     * Validate "linkuser" connect form fields.
     *
     * @return int|false
     */
    private function getLinkedUserID() {
        $this->form->validateRule('linkUserID', 'ValidateRequired');
        $this->form->validateRule('linkUserID', 'ValidateInteger');

        $this->form->validateRule('linkUserPassword', 'ValidateRequired', t('Password is required'));

        $user = false;
        if ($this->form->errorCount() === 0) {
            $linkUserID = $this->form->getFormValue('linkUserID');

            $userResults = [];
            if ($linkUserID === '-1') {
                $this->form->validateRule('linkUserName', 'ValidateRequired', t('Username is required.'));
                $this->form->validateRule('linkUserEmail', 'ValidateRequired', t('Email is required'));

                if ($this->form->errorCount() === 0) {
                    $userResults = $this->userModel->getWhere([
                        'Name' => $this->form->getFormValue('linkUserName'),
                        'Email' => $this->form->getFormValue('linkUserEmail'),
                    ])->resultArray();
                }
            } else {
                $userResults = $this->userModel->getIDs([$linkUserID]);
            }

            if (count($userResults) > 1) {
                $this->form->addError('More than one user has the same Email and Name combination.');
            } else if (count($userResults) === 0) {
                $this->form->addError('No user was found with the supplied information.');
            } else {
                $user = array_pop($userResults);
            }
        }

        $linkValid = false;
        if ($user) {
            $this->form->validateRule('linkUserPassword', 'ValidateRequired', t('Password is required.'));

            if ($this->form->errorCount() === 0) {
                $password = $this->form->getFormValue('linkUserPassword');
                $passwordHash = new Gdn_PasswordHash();

                $linkValid = $passwordHash->checkPassword($password, $user['Password'], $user['HashMethod']);
            }
        }

        return $linkValid ? $user['UserID'] : false;
    }

    /**
     * Create a new user using the "createuser" connect form fields.
     *
     * @return int|false
     */
    private function createUser() {
        $userID = false;

        $this->form->validateRule('createUserName', 'ValidateRequired', t('Username is required.'));
        $this->form->validateRule('createUserName', 'ValidateUsername'); // Default message is perfect.
        $this->form->validateRule('createUserEmail', 'ValidateRequired', t('Email is required'));
        $this->form->validateRule('createUserEmail', 'ValidateEmail', t('Email is invalid.'));

        if ($this->form->errorCount() === 0) {
            $user = [];
            $user['Name'] = $this->form->getFormValue('createUserName');
            $user['Email'] = $this->form->getFormValue('createUserEmail');
            $user['Password'] = randomString(16); // Required field.
            $user['HashMethod'] = 'Random';
            $createdUserID = $this->userModel->register($user, [
                'CheckCaptcha' => false,
                'NoConfirmEmail' => true,
            ]);

            if ($createdUserID) {
                $userID = $createdUserID;
            } else {
                $this->form->setValidationResults($this->userModel->validationResults());
            }
        }

        return $userID;
    }
}
