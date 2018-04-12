<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

use Garden\Schema\Schema;
use Garden\Schema\ValidationField;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\ServerException;
use Garden\Web\RequestInterface;
use Vanilla\ApiUtils;
use Vanilla\Authenticator\Authenticator;
use Vanilla\Authenticator\SSOAuthenticator;
use Vanilla\Exception\PermissionException;
use Vanilla\Models\AuthenticatorModel;
use Vanilla\Models\SSOModel;

/**
 * API Controller for the `/authenticate` resource.
 */
class AuthenticateApiController extends AbstractApiController {

    const SESSION_ID_EXPIRATION = 1200; // 20 minutes

    /** @var AuthenticatorsApiController */
    private $authenticatorApiController;

    /** @var Gdn_Configuration */
    private $config;

    /** @var RequestInterface */
    private $request;

    /** @var SessionModel */
    private $sessionModel;

    /** @var SSOModel */
    private $ssoModel;

    /** @var UserModel */
    private $userModel;

    /**
     * AuthenticationController constructor.
     *
     * @param AuthenticatorsApiController $authenticatorApiController
     * @param Gdn_Configuration $config
     * @param RequestInterface $request
     * @param SessionModel $sessionModel
     * @param SSOModel $ssoModel
     * @param UserModel $userModel
     */
    public function __construct(
        AuthenticatorsApiController $authenticatorApiController,
        Gdn_Configuration $config,
        RequestInterface $request,
        SessionModel $sessionModel,
        SSOModel $ssoModel,
        UserModel $userModel
    ) {
        $this->authenticatorApiController = $authenticatorApiController;
        $this->config = $config;
        $this->request = $request;
        $this->sessionModel = $sessionModel;
        $this->ssoModel = $ssoModel;
        $this->userModel = $userModel;
    }

    /**
     * Store the data and return the associated SessionID to retrieve it.
     *
     * @param array $data The data to store.
     * @return string SessionID
     */
    private function createSession($data) {
        $sessionID = betterRandomString(32, 'aA0');

        $this->sessionModel->insert([
            'SessionID' => $sessionID,
            'UserID' => $this->getSession()->UserID,
            'DateExpires' => date(MYSQL_DATE_FORMAT, time() + self::SESSION_ID_EXPIRATION),
            'Attributes' => $data,
        ]);

        return $sessionID;
    }

    /**
     * Unlink a user from the specified authenticator.
     * If no user is specified it will unlink the current user.
     *
     * @param string $authenticatorType
     * @param string $authenticatorID
     * @param array $query The query string as an array.
     * @throws Exception
     */
    public function delete_authenticators($authenticatorType, $authenticatorID = '', array $query) {
        $this->permission();

        $this->schema([
            'authenticatorType:s' => 'The authenticator type that will be used.',
            'authenticatorID:s?' => 'Authenticator instance\'s identifier.',
        ]);
        $in = $this->schema([
            'userID:i?' => 'UserID to unlink authenticator from. Defaults to the current user\'s id',
        ], 'in')->setDescription('Delete the link between an authenticator and a user.');
        $this->schema([], 'out');

        $in->validate($query);

        if (isset($query['userID']) && $this->getSession()->UserID !== $query['userID']) {
            $this->permission('Garden.Users.Edit');
            $userID = $query['userID'];
        } else {
            $this->permission('Garden.SignIn.Allow');
            $userID = $this->getSession()->UserID;
        }

        $authenticatorInstance = $this->authenticatorApiController->getAuthenticatorModel()->getAuthenticator($authenticatorType, $authenticatorID);

        $data = [];
        $this->userModel->getDelete(
            'UserAuthentication',
            ['UserID' => $userID, 'ProviderKey' => $authenticatorInstance->getID()],
            $data
        );
    }

    /**
     * Get an active authenticator.
     *
     * @throws NotFoundException
     * @param string $authenticatorID
     * @return array
     */
    public function get_authenticators(string $authenticatorID) {
        $this->permission();

        $this->schema(
            Schema::parse(['authenticatorID'])->merge($this->getAuthenticatorPublicSchema()),
            'in'
        )->setDescription('Get an active authenticator.');
        $out = $this->schema($this->getAuthenticatorPublicSchema(), 'out');

        $authenticator = $this->authenticatorApiController->getAuthenticatorByID($authenticatorID);

        if (!$authenticator->isActive()) {
            throw new ClientException('Authenticator is not active.');
        }
        if (is_a($authenticator, SSOAuthenticator::class) && !$authenticator->canSignIn()) {
            throw new ClientException('Authenticator does not allow authentication.');
        }

        $result = $this->authenticatorApiController->normalizeOutput($authenticator);

        return $out->validate($result);
    }

    /**
     * Authenticator schema with whitelisted fields.
     *
     * @return Schema
     */
    public function getAuthenticatorPublicSchema() {
        return Schema::parse([
            'authenticatorID' => null,
            'type' => null,
            'name' => null,
            'ui' => null,
            'isUserLinked:b?' => 'Whether or not the user is linked to that authenticator.',
        ])->merge(SSOAuthenticator::getAuthenticatorSchema());
    }

    /**
     * List authenticators that can be used to authenticate.
     *
     * @throws Exception
     * @param array $query
     * @return Authenticator
     */
    public function index_authenticators(array $query) {
        $this->permission();

        $in = $this->schema([
            'isSSO:b?' => 'Filters authenticators depending on if they are SSO authenticators or not.',
            'isUserLinked:b?' => 'Filter authenticators based on whether a user is linked to it or not. Users can only be linked to SSO authenticators.'
        ], 'in')->setDescription('List active authenticators.');
        $out = $this->schema([':a', $this->getAuthenticatorPublicSchema()], 'out');

        $query = $in->validate($query);

        $isUserLinked = $query['isUserLinked'] ?? null;
        $isSSO = $query['isSSO'] ?? null;

        $filter = function($authenticator) use ($isUserLinked, $isSSO) {
            /** @var Authenticator $authenticator */
            // Must be active!
            if (!$authenticator->isActive()) {
                return false;
            }

            $ssoAuthenticator = null;
            if (is_a($authenticator, SSOAuthenticator::class)) {
                /** @var SSOAuthenticator $ssoAuthenticator */
                $ssoAuthenticator = $authenticator;

                if ($isSSO === false) {
                    return false;
                }

                // Must be able to sign in with the SSO plugin!
                if (!$ssoAuthenticator->canSignIn()) {
                    return false;
                }

                if (isset($isUserLinked) && !$authenticator->isUserLinked($this->getSession()->UserID)) {
                    return false;
                }
            }

            return true;
        };

        $authenticators = array_filter($this->authenticatorApiController->getAuthenticatorModel()->getAuthenticators(), $filter);

        $result = array_map([$this->authenticatorApiController, 'normalizeOutput'], $authenticators);

        return $out->validate($result);
    }

    /**
     * Authenticate a user using the specified authenticator.
     *
     * @throws Exception If the authentication process fails.
     * @throws NotFoundException If the $authenticatorType is not found.
     * @param array $body
     * @return array
     */
    public function post(array $body) {
        $this->permission();

        $in = $this->schema([
            'authenticate' => [
                'authenticatorType:s' => 'The authenticator type that will be used.',
                'authenticatorID:s' => 'Authenticator instance\'s identifier.',
            ],
            'persist:b' => [
                'default' => false,
                'description' => 'Set the persist option on the cookie when it is set.',
            ],
        ])->setDescription('Authenticate a user using a specific authenticator.');
        $out = $this->schema(Schema::parse([
            'authenticationStep:s' => [
                'description' => 'Tells whether the user is now authenticated or if additional step(s) are required.',
                'enum' => ['authenticated', 'linkUser'],
            ],
            'user?' => $this->getUserFragmentSchema(),
            'authSessionID:s?' => 'Identifier of the authentication session. Returned if more steps are required to complete the authentication.',
        ]), 'out');

        $body = $in->validate($body);

        $authenticatorType = $body['authenticate']['authenticatorType'];
        $authenticatorID = $body['authenticate']['authenticatorID'];

        if ($this->getSession()->isValid()) {
            throw new ClientException('Cannot authenticate while already logged in.', 403);
        }

        $authenticatorInstance = $this->authenticatorApiController->getAuthenticator($authenticatorType, $authenticatorID);

        $sso = is_a($authenticatorInstance, SSOAuthenticator::class);
        if ($sso) {
            /** @var SSOAuthenticator $authenticatorInstance */
            $ssoData = $authenticatorInstance->validateAuthentication($this->request);

            if (!$ssoData) {
                throw new ServerException("Unknown error while authenticating with $authenticatorType.", 500);
            }

            $user = $this->ssoModel->sso($ssoData, [
                'persist' => $body['persist'],
            ]);

            // Allows registration without an email address.
            $noEmail = $this->config->get('Garden.Registration.NoEmail', false);

            // Specifies whether Emails are unique or not.
            $emailUnique = !$noEmail && $this->config->get('Garden.Registration.EmailUnique', true);

            // Specifies whether Names are unique or not.
            $nameUnique = $this->config->get('Garden.Registration.NameUnique', true);

            // Allows SSO connections to link a VanillaUser to a ForeignUser.
            $allowConnect = $this->config->get('Garden.Registration.AllowConnect', true);

            $sessionData = [
                'ssoData' => $ssoData,
            ];
        } else {
            $user = $authenticatorInstance->validateAuthentication($this->request);

            $this->getSession()->start($user['UserID'], true, $body['persist']);
        }

        if ($user) {
            $properlyExpanded = ['UserID' => $user['UserID']];
            $this->userModel->expandUsers($properlyExpanded, ['UserID']);

            $response = [
                'authenticationStep' => 'authenticated',
                'user' => $properlyExpanded['User'],
            ];
        // We could not authenticate or autoconnect so they will need to do a manual connect.
        } else if ($sso) {
            if ($allowConnect) {
                $existingUserIDs = $this->ssoModel->findMatchingUserIDs($ssoData, $emailUnique, $nameUnique);
                if (!empty($existingUserIDs)) {
                    $sessionData['linkUser'] = [
                        'existingUsers' => $existingUserIDs,
                    ];
                }
            }
            $response = [
                'authenticationStep' => 'linkUser',
            ];
        }

        if ($response['authenticationStep'] === 'linkUser') {
            // Store all the information needed for the next authentication step.
            $response['authSessionID'] = $this->createSession($sessionData);
        }

        return $out->validate($response);
    }

    /**
     * Link a user to an authenticator using an authSessionID.
     *
     * @throws ClientException
     * @throws Exception
     *
     * @param array $body
     * @return array
     */
    public function post_linkUser(array $body) {
        $this->permission();

        if (!$this->config->get('Garden.Registration.AllowConnect', true)) {
            throw new PermissionException('Garden.Registration.AllowConnect');
        }

        // Custom validator
        $validator = function ($data, ValidationField $field) {
            $hasPassword = !empty($data['password']);

            if ($hasPassword) {
                $valid = !empty($data['userID']);
            }
            if (!$valid && $hasPassword) {
                $valid = !empty($data['name']) && !empty($data['email']);
            }

            if (!$valid) {
                $field->addError('missingField', [
                    'messageCode' => 'You must specify either userID + password or name + email + password.',
                    'required' => true,
                ]);
            }

            return $valid;
        };

        $in = $this->schema([
                'authSessionID:s' => 'Identifier of the authentication session.',
                'password:s' => 'Password of the user.',
                'userID:i?' => 'Identifier of the user.',
                'name:s?' => 'User name.',
                'email:s?' => 'User email.',
            ], 'in')
            ->addValidator('', $validator)
            ->setDescription('Link a user to an authenticator using the authSessionID and some other information. Required: userID + password or name + email + password.');
        $out = $this->schema($this->getUserFragmentSchema(), 'out');

        $in->validate($body);

        $sessionData = $this->sessionModel->getID($body['authSessionID'], DATASET_TYPE_ARRAY);
        if ($this->sessionModel->isExpired($sessionData)) {
            throw new Exception('The session has expired.');
        }

        if (!empty($body['userID'])) {
            $userID = $body['userID'];
        } else {
            $this->userModel->SQL->select('UserID');
            $userResults = $this->userModel->getWhere([
                'Name' => $body['name'],
                'Email' => $body['email'],
            ])->resultArray();

            if (count($userResults) > 1) {
                throw new ClientException('More than one user has the same Email and Name combination.');
            } else if (count($userResults) === 0) {
                throw new ClientException('No user was found with the supplied information.');
            }

            $userID = $userResults[0]['UserID'];
        }

        $tmp = ['UserID' => $userID];
        $this->userModel->expandUsers($tmp, ['UserID']);
        $user = $tmp['User'];

        $passwordHash = new Gdn_PasswordHash();
        $linkValid = $passwordHash->checkPassword($body['password'], $user['Password'], $user['HashMethod']);
        if (!$linkValid) {
            throw new ClientException('The password verification failed.');
        }

        $this->userModel->saveAuthentication([
            'UserID' => $user['UserID'],
            'Provider' => $sessionData['Attributes']['ssoData']['authenticatorID'],
            'UniqueID' => $sessionData['Attributes']['ssoData']['uniqueID'],
        ]);
        // Clean the session.
        $this->sessionModel->deleteID($sessionData['SessionID']);

        return $out->validate($user);
    }

    /**
     * Authenticate a user with username/email and password.
     *
     * @param array $body The username/password to sign in.
     * @return array Returns the signed in user.
     * @throws \Exception Throws all exceptions to be dispatched as error responses.
     */
    public function post_password(array $body): array {
        $this->permission();

        $in = $this->schema([
            'username:s' => 'The user\'s username or email address.',
            'password:s' => 'The user\'s password.',
            'persist:b' => [
                'description' => 'Whether the session should persist past the browser closing.',
                'default' => false,
            ],
        ])->setDescription('Authenticate a user with username/email and password.');
        $out = $this->schema($this->getUserFragmentSchema(), 'out');

        $body = $in->validate($body);

        $result = $this->post([
            'authenticate' => [
                'authenticatorType' => 'password',
                'authenticatorID' => 'password',
            ],
            'persist' => $body['persist'] ?? false,
        ]);

        return $out->validate($result['user']);
    }

    /**
     * Get the SSOData schema.
     *
     * @return Schema
     */
    public function ssoDataSchema() {
        static $ssoDataSchema;

        if ($ssoDataSchema === null) {
            $ssoDataSchema = $this->schema([
                'authenticatorType:s' => 'Name of the authenticator that was used to create this object.',
                'authenticatorID:s' => 'ID of the authenticator instance that was used to create this object.',
                'uniqueID:s' => 'Unique ID of the user supplied by the provider.',
                'user:o' => [
                    'email:s?' => 'Email of the user.',
                    'name:s?' => 'Name of the user.',
                    'photo:s?' => 'Photo of the user.',
                    'roles:a?' => [
                        'description' => 'One or more role name.',
                        'items' => ['type' => 'string'],
                        'style' => 'form',
                    ],
                ],
                'extra:o' => 'Any other information.',
            ], 'SSOData')->setDescription('SSOAuthenticator\'s supplied information.');
        }

        return $ssoDataSchema;
    }
}
