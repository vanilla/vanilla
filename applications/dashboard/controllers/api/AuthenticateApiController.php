<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\Schema\Schema;
use Garden\Schema\ValidationField;
use Garden\Web\Data;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\HttpException;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\ServerException;
use Garden\Web\RequestInterface;
use Vanilla\Authenticator\Authenticator;
use Vanilla\Authenticator\SSOAuthenticator;
use Vanilla\Exception\PermissionException;
use Vanilla\Models\SSOData;
use Vanilla\Models\SSOModel;

/**
 * API Controller for the `/authenticate` resource.
 */
class AuthenticateApiController extends AbstractApiController {

    // The feature flag behind which all this functionality is kept.
    const FEATURE_FLAG = 'AuthenticationAPI';

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
        \Vanilla\FeatureFlagHelper::ensureFeature(self::FEATURE_FLAG);
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
     * @param string $authenticatorID
     * @param array $query The query string as an array.
     * @throws Exception
     */
    public function delete_authenticators($authenticatorID = '', array $query) {
        $this->permission();

        $this->schema([
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

        $authenticatorInstance = $this->authenticatorApiController->getAuthenticatorByID($authenticatorID);

        $data = [];
        $this->userModel->getDelete(
            'UserAuthentication',
            ['UserID' => $userID, 'ProviderKey' => $authenticatorInstance->getID()],
            $data
        );
    }

    /**
     * Delete an authentication session.
     *
     * @param string $authSessionID
     *
     * @throws \Garden\Web\Exception\NotFoundException
     */
    public function delete_linkUser(string $authSessionID) {
        $this->schema([
            'authSessionID:s' => 'Identifier of the authentication session.',
        ], 'in')->setDescription('Delete an authentication session.');
        $this->schema([], 'out');

        $sessionData = $this->sessionModel->getID($authSessionID, DATASET_TYPE_ARRAY);
        if (!$sessionData) {
            throw new NotFoundException('AuthSessionID');
        }

        $this->sessionModel->deleteID($authSessionID);
    }

    /**
     * Make sure that the session is started and throw a relevant error message if that's not the case.
     *
     * @throws \Garden\Web\Exception\HttpException
     * @throws \Vanilla\Exception\PermissionException
     */
    private function ensureSessionIsValid() {
        if (!$this->getSession()->isValid()) {
            $this->permission(); // This will throw if there's a ban reason.

            throw HttpException::createFromStatus(401, 'The session could not be started.');
        }
    }

    /**
     * Fill authenticator information into a response.
     *
     * @param array $response
     * @param \Vanilla\Models\SSOData $ssoData
     *
     * @return array
     * @throws \Garden\Schema\ValidationException
     * @throws \Garden\Web\Exception\NotFoundException
     */
    private function fillAuthenticator(array $response, SSOData $ssoData) {
        $response['authenticator'] = $this->authenticatorApiController->normalizeOutput(
            $this->authenticatorApiController->getAuthenticator($ssoData->getAuthenticatorType(), $ssoData->getAuthenticatorID())
        );

        return $response;
    }

    /**
     * Fill configuration information into a response.
     *
     * @param array $response
     *
     * @return array
     */
    private function fillConfig(array $response) {
        $response['config'] = [
            'nameUnique' => $this->config->get('Garden.Registration.NameUnique'),
            'emailUnique' => $this->config->get('Garden.Registration.EmailUnique'),
            'noEmail' => $this->config->get('Garden.Registration.NoEmail'),
        ];

        return $response;
    }

    /**
     * Fill SSO User information into a response.
     *
     * @param $response
     * @param \Vanilla\Models\SSOData $ssoData
     *
     * @return array
     */
    private function fillSSOUser(array $response, SSOData $ssoData) {
        $response['ssoUser'] = array_filter([
            'uniqueID' => $ssoData->getUniqueID(),
            'name' => $ssoData->getUserValue('name'),
            'email' => $ssoData->getUserValue('email'),
            'photoUrl' => $ssoData->getUserValue('photoUrl'),
            'fullName' => $ssoData->getExtraValue('fullName'),
            'defaultName' => $ssoData->getExtraValue('defaultName'),
        ]);

        return $response;
    }

    /**
     * Authenticator schema with whitelisted fields.
     *
     * @return Schema
     */
    public function getAuthenticatorPublicSchema() {
        $ssoSchema = Schema::parse([
            'sso:o?' => [
                'canSignIn' => [
                    'type' => 'boolean',
                    'description' => 'Whether or not the authenticator can be used to sign in.',
                    'default' => true,
                    'x-instance-configurable' => true,
                ],
                'canAutoLinkUser:b' => [
                    'description' => 'Whether or not the authenticator can automatically link the incoming user information to an existing user account by using email address.',
                    'default' => false,
                    'x-instance-configurable' => true,
                ],
            ]
        ]);

        $schema = Schema::parse([
            'authenticatorID' => null,
            'type' => null,
            'isUnique' => null,
            'name' => null,
            'ui' => null,
            'isUserLinked:b?' => 'Whether or not the user is linked to that authenticator.',
        ])
            ->add(SSOAuthenticator::getAuthenticatorSchema())
            ->merge(
                $ssoSchema
            )
        ;

        $schema->setField('properties.ui.properties.url.format', 'uri');
        $schema->setField('properties.ui.properties.photoUrl.format', 'uri');

        return $schema;
    }

    /**
     * Get the linkUser output schema.
     *
     * @return Schema
     */
    public function getLinkUserOutputSchema() {
        return Schema::parse([
            'ssoUser:o' => [
                'uniqueID:s' => 'Unique ID of the user supplied by the provider.',
                'name:s?' => 'The name of the user supplied by the provider.',
                'email:s?' => 'The name of the user supplied by the provider.',
                'photoUrl:s?' => 'The photo url of the user supplied by the provider.',
                'fullName:s?' => 'Field used to help identify the user.',
                'defaultName:s?' => 'Field used to help identify the user.',
            ],
            'authenticator' => $this->getAuthenticatorPublicSchema(),
            'config:o' => [
                'nameUnique:b' => "Whether users' name are unique or not.",
                'emailUnique:b' => "Whether users' email are unique or not.",
                'noEmail:b' => "Whether users are allowed to not have an email or not.",
            ],
            'targetUrl:s?' => 'The sanitized URL to redirect to after a successful authentication.',
        ]);
    }

    /**
     * Get an active authenticator.
     *
     * @param string $authenticatorID
     * @return array
     *
     * @throws ClientException
     * @throws NotFoundException
     * @throws PermissionException
     * @throws \Garden\Schema\ValidationException
     * @throws \Garden\Web\Exception\HttpException
     */
    public function get_authenticators(string $authenticatorID) {
        $this->permission();

        $this->schema(
            Schema::parse(['authenticatorID' => $this->getAuthenticatorPublicSchema()->getField('properties.authenticatorID')]),
            'in'
        )->setDescription('Get an active authenticator.');
        $out = $this->schema($this->getAuthenticatorPublicSchema(), 'out');

        $authenticator = $this->authenticatorApiController->getAuthenticatorByID($authenticatorID);

        if (!$authenticator->isActive()) {
            throw new ClientException('Authenticator is not active.');
        }
        if (is_a($authenticator, SSOAuthenticator::class)) {
            /** @var SSOAuthenticator $authenticator */
            if (!$authenticator->canSignIn()) {
                throw new ClientException('Authenticator does not allow authentication.');
            }
        }

        $result = $this->authenticatorApiController->normalizeOutput($authenticator);

        return $out->validate($result);
    }

    /**
     * Get information about an authentication session.
     *
     * @param string $authSessionID
     *
     * @return mixed
     * @throws \Garden\Schema\ValidationException
     * @throws \Garden\Web\Exception\ClientException
     */
    public function get_linkUser(string $authSessionID) {
        $this->schema([
            'authSessionID:s' => 'Identifier of the authentication session.',
        ], 'in')->setDescription('Get information about an authentication session.');
        $out = $this->schema($this->getLinkUserOutputSchema(), 'out');

        $session = $this->sessionModel->getID($authSessionID, DATASET_TYPE_ARRAY);
        if ($this->sessionModel->isExpired($session)) {
            throw new NotFoundException('AuthenticationSession');
        }
        $sessionData = $session['Attributes'];

        $ssoData = SSOData::fromArray($sessionData['ssoData']);

        $response = $this->fillSSOUser([], $ssoData);
        $response = $this->fillAuthenticator($response, $ssoData);
        $response = $this->fillConfig($response);
        $result = $out->validate($response);
        $result = new Data($result, ['api-allow' => ['email']]);
        return $result;
    }

    /**
     * List authenticators that can be used to authenticate.
     *
     * @throws Exception
     * @param array $query
     * @return Authenticator
     */
    public function index_authenticators(array $query = []) {
        $this->permission();

        $in = $this->schema([
            'isSSO:b?' => 'Filters authenticators depending on if they are SSO authenticators or not.',
            'isUserLinked:b?' => 'Filter authenticators based on whether a user is linked to it or not. Users can only be linked to SSO authenticators.'
        ], 'in')->setDescription('List active authenticators.');
        $out = $this->schema([':a' => $this->getAuthenticatorPublicSchema()], 'out');

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

                if (isset($isUserLinked) && !$ssoAuthenticator->isUserLinked($this->getSession()->UserID)) {
                    return false;
                }
            }

            return true;
        };

        $authenticators = array_filter($this->authenticatorApiController->getAuthenticatorModel()->getAuthenticators(true), $filter);

        $result = array_map([$this->authenticatorApiController, 'normalizeOutput'], $authenticators);

        // Reset keys otherwise the array could be interpreted as an associative array (read object) by $out->validate.
        $result = array_values($result);

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
            'authenticate:o' => [
                'authenticatorType:s' => 'The authenticator type that will be used.',
                'authenticatorID:s' => 'Authenticator instance\'s identifier.',
            ],
            'persist:b' => [
                'default' => false,
                'description' => 'Set the persist option on the cookie when it is set.',
            ],
            'method:s' => [
                'enum' => ['signIn', 'linkUser', 'session'],
                'default' => 'signIn',
                'description' => 'Authentication method.',
            ],
            'targetUrl:s?' => 'The raw URL to redirect to after a successful authentication.',
        ])->setDescription('Authenticate a user using a specific authenticator.');
        $out = $this->schema(Schema::parse([
            'authenticationStep:s' => [
                'description' => 'Tells whether the user is now authenticated or if additional step(s) are required.',
                'enum' => ['authenticated', 'linkUser'],
            ],
            'user?' => $this->getUserFragmentSchema(),
            'authSessionID:s?' => 'Identifier of the authentication session. Returned if more steps are required to complete the authentication.',
            'targetUrl:s?' => 'The sanitized URL to redirect to after a successful authentication.',
        ]), 'out');

        $body = $in->validate($body);

        $authenticatorType = $body['authenticate']['authenticatorType'];
        $authenticatorID = $body['authenticate']['authenticatorID'];

        $authenticatorInstance = $this->authenticatorApiController->getAuthenticator($authenticatorType, $authenticatorID);

        $user = null;
        $ssoData = null;
        $sso = is_a($authenticatorInstance, SSOAuthenticator::class);
        if ($sso) {
            /** @var SSOAuthenticator $authenticatorInstance */
            $ssoData = $authenticatorInstance->validateAuthentication($this->request);

            if (!$ssoData) {
                throw new ServerException("Unknown error while authenticating with $authenticatorType.", 500);
            }

            try {
                 $user = $this->ssoModel->sso($ssoData, [
                    'linkToSession' => $body['method'] === 'session',
                    'setCookie' => $body['method'] !== 'linkUser',
                    'persist' => $body['persist'],
                ]);
            } catch(ClientException $e) {
                if ($e->getCode() === 401) {
                    $this->ensureSessionIsValid(); // This will throw a more relevant error message.
                    throw $e; // Fallback to the original error if the session is valid. Should not happen tho.
                }
            }
        } else {
            $user = $authenticatorInstance->validateAuthentication($this->request);
            if ($user) {
                $this->startSession($user['UserID'], $body['persist']);
            }
        }

        if ($user) {
            $properlyExpanded = ['UserID' => $user['UserID']];
            $this->userModel->expandUsers($properlyExpanded, ['UserID']);

            $response = [
                'authenticationStep' => 'authenticated',
                'user' => $properlyExpanded['User'],
            ];
        // We could not authenticate or autoconnect them so they will need to do a manual connect.
        } else if ($sso) {
            $sessionData = [
                'ssoData' => $ssoData,
                'persist' => $body['persist'],
                'method' => $body['method'],
                'targetUrl' => $body['targetUrl'] ?? null,
            ];

            // Store all the information needed for the next authentication step.
            $sessionData = $this->createSession($sessionData);

            $response = [
                'authenticationStep' => 'linkUser',
                'authSessionID' => $sessionData,
            ];
        } else {
            throw new ServerException('Authentication failed.');
        }

        // Make sure the target has been sanitized.
        if (isset($body['targetUrl'])) {
            $response['targetUrl'] = safeURL($body['targetUrl']);
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
     * @return Data
     */
    public function post_linkUser(array $body) {
        $this->permission();

        // Custom validator
        $validator = function($data, ValidationField $field) {
            if ($field->getValidation()->getErrors()) {
                return true;
            }

            if ($data['method'] === 'register') {
                $agreeToTerms = $data['agreeToTerms'];

                if (!$agreeToTerms) {
                    $field->getValidation()->addError(
                        'agreeToTerms',
                        'You must agree to the terms of service.',
                        ['status' => 400]
                    );
                }
            } else if ($data['method'] === 'password') {
                $hasUsername = (bool)($data['username'] ?? false);
                $hasUserID = (bool)($data['userID'] ?? false);
                $hasPassword = (bool)($data['password'] ?? false);

                if (!$hasUsername && !$hasUserID) {
                    $field->getValidation()->addError(
                        'username',
                        'Username/email is required.',
                        ['status' => 400]
                    );
                }
                if (!$hasPassword) {
                    $field->getValidation()->addError(
                        'password',
                        'Password is required.',
                        ['status' => 400]
                    );
                }
            } else if ($data['method'] === 'session') {
                if (!$this->getSession()->isValid()) {
                    $field->getValidation()->addError(
                        '',
                        'Cannot use method "session" while not signed in.',
                        ['status' => 422]
                    );
                }
            } else {
                throw new Exception('Undefined method.');
            }

            return count($field->getValidation()->getErrors()) === 0;
        };

        $in = $this
            ->schema([
                'authSessionID:s' => 'Identifier of the authentication session.',
                'method:s' => [
                    'enum' => ['register', 'password', 'session'],
                    'description' => 'Link method.',
                ],
                'useAuthSession:b' => [
                    'default' => true,
                    'description' => 'Use the user information from the authSessionID. ie. email, name...',
                ],
                'name:s?' => 'Name of the new user. Override info from useAuthSession is provided. Method: register.',
                'email:s?' => 'Email of the new user. Override info from useAuthSession is provided. Method: register.',
                'agreeToTerms:b?' => 'Agree to terms of service. Method: register.',
                'userID:i?' => 'Identifier of the user to link to. Method: password.',
                'username:s?' => 'The user\'s name or email to link to. Method: password.',
                'password:s?' => 'Password of the user to link to. Method: password.',
                'persist:b' => [
                    'default' => false,
                    'description' => 'Whether the session should persist past the browser closing.',
                ],
                'targetUrl:s?' => 'The sanitized URL to redirect to after a successful authentication.',
            ], 'in')
            ->addValidator('', $validator)
            ->setDescription('Link a user to an authenticator using the authSessionID and some other information. Required: userID + password or name + email + password.')
        ;
        $out = $this->schema(
            Schema::parse([
                'user?' => $this->getUserFragmentSchema(),
                'message:s?' => 'Global error message.',
                'errors:a?' => [
                    'items' => [
                        'type'  => 'object',
                    ],
                    'description' => 'List of errors.',
                ],
                'ssoUser?' => null,
                'authenticator?' => null,
                'config?' => null,
                'targetUrl?' => null,
            ])->merge($this->getLinkUserOutputSchema()),
            'out'
        );

        $response = [];
        $statusCode = 201;
        try {
            $body = $in->validate($body);
        } catch (\Garden\Schema\ValidationException $e) {
            $statusCode = $e->getValidation()->getStatus();
            $response['errors'] = $e->getValidation()->getErrors();
            $response['message'] = $e->getMessage();
        }

        if (($body['userID'] ?? false) && ($body['username'] ?? false)) {
            throw new ClientException('Only one of userID or username should be specified, not both.', 400);
        }

        $session = $this->sessionModel->getID($body['authSessionID'], DATASET_TYPE_ARRAY);
        if ($this->sessionModel->isExpired($session)) {
            throw new ClientException('Your SSO session has expired.', 401);
        }
        $sessionData = $session['Attributes'];

        $ssoData = SSOData::fromArray($sessionData['ssoData']);

        if (!isset($response['errors'])) {
            switch ($body['method']) {
                case 'register':
                    $options = [
                        'useSSOData' => $body['useAuthSession'],
                    ];
                    if (isset($body['name'])) {
                        $options['name'] = $body['name'];
                    }
                    if (isset($body['email'])) {
                        $options['email'] = $body['email'];
                    }
                    $user = $this->ssoModel->createUser($ssoData, $options);
                    break;
                case 'password':
                    $userIdentifierType = null;
                    $userIdentifier = null;
                    if ($body['userID'] ?? false) {
                        $userIdentifierType = 'userID';
                        $userIdentifier = $body['userID'];
                    } else if ($body['username'] ?? false) {
                        $userIdentifier = $body['username'];
                        if (filter_var($userIdentifier, FILTER_VALIDATE_EMAIL)) {
                            $userIdentifierType = 'email';
                        } else {
                            $userIdentifierType = 'name';
                        }
                    }

                    $user = $this->ssoModel->linkUserFromCredentials($ssoData, $userIdentifierType, $userIdentifier, $body['password']);
                    break;
                case 'session':
                    $user = $this->ssoModel->linkUserFromSession($ssoData);
                    break;
                default:
                    throw new Exception('Undefined method.');
                    break;
            }
            $this->userModel->expandUsers($user, ['UserID']);
            $response['user'] = $user['User'];

            if ($body['method'] !== 'session') {
                $this->startSession($user['UserID'], $body['persist']);
            }
        } else {
            $response = $this->fillSSOUser($response, $ssoData);
            $response = $this->fillAuthenticator($response, $ssoData);
            $response = $this->fillConfig($response);
        }

        $result = $out->validate($response);

        return new Data($result, ['status' => $statusCode, 'api-allow' => ['email']]);
    }

    /**
     * Authenticate a user with username/email and password.
     *
     * This endpoint always work even if the password authenticator is "inactive".
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

        // Make sure that the PasswordAuthenticator is "active" for this call.
        $oldConfig = $this->config->get('Garden.SignIn.DisablePassword', false);
        $this->config->set('Garden.SignIn.DisablePassword', false, true, false);

        $result = $this->post([
            'authenticate' => [
                'authenticatorType' => 'password',
                'authenticatorID' => 'password',
            ],
            'persist' => $body['persist'] ?? false,
        ]);

        $this->config->set('Garden.SignIn.DisablePassword', $oldConfig, true, false);

        return $out->validate($result['user']);
    }

    /**
     * Start a session an throw an error if there's a problem.
     *
     * @param $userID
     * @param $persist
     *
     * @throws \Garden\Web\Exception\HttpException
     */
    private function startSession($userID, $persist) {
        $this->getSession()->start($userID, true, $persist);
        $this->userModel->fireEvent('AfterSignIn');

        $this->ensureSessionIsValid();
    }
}
