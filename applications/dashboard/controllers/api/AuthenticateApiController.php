<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

use Garden\Schema\Schema;
use Garden\Schema\ValidationField;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\ServerException;
use Garden\Web\RequestInterface;
use Interop\Container\ContainerInterface;
use Vanilla\AddonManager;
use Vanilla\Authenticator\Authenticator;
use Vanilla\Authenticator\SSOAuthenticator;
use Vanilla\Exception\PermissionException;
use Vanilla\Models\SSOModel;
use Vanilla\ApiUtils;

/**
 * API Controller for the `/authenticate` resource.
 */
class AuthenticateApiController extends AbstractApiController {

    const SESSION_ID_EXPIRATION = 1200; // 20 minutes

    /** @var AddonManager */
    private $addonManager;

    /** @var Gdn_Configuration */
    private $config;

    /** @var ContainerInterface */
    private $container;

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
     * @param AddonManager $addonManager
     * @param Gdn_Configuration $config
     * @param ContainerInterface $container,
     * @param RequestInterface $request
     * @param SessionModel $sessionModel
     * @param SSOModel $ssoModel
     * @param UserModel $userModel
     */
    public function __construct(
        AddonManager $addonManager,
        Gdn_Configuration $config,
        ContainerInterface $container,
        RequestInterface $request,
        SessionModel $sessionModel,
        SSOModel $ssoModel,
        UserModel $userModel
    ) {
        $this->addonManager = $addonManager;
        $this->config = $config;
        $this->container = $container;
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
     * @param string $authenticator
     * @param string $authenticatorID
     * @param array $query The query string as an array.
     * @throws Exception
     */
    public function delete($authenticator, $authenticatorID = '', array $query) {
        $this->permission();

        $this->schema([
            'authenticator:s' => 'The authenticator that will be used.',
            'authenticatorID:s?' => 'Authenticator instance\'s identifier.',
        ]);
        $in = $this->schema([
            'userID:i?' => 'UserID to unlink authenticator from.',
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

        $authenticatorInstance = $this->getAuthenticator($authenticator, $authenticatorID);

        $data = [];
        $this->userModel->getDelete(
            'UserAuthentication',
            ['UserID' => $userID, 'ProviderKey' => $authenticatorInstance->getID()],
            $data
        );
    }

    /**
     * Delete a session.
     *
     * @param string $authSessionID
     */
    public function delete_session($authSessionID) {
        $this->permission();

        $this->schema([
            'authSessionID:s' => 'Identifier of the authentication session.',
        ], 'in')->setDescription('Delete an authentication session.');
        $this->schema([], 'out');

        $this->sessionModel->deleteID($authSessionID);
    }

    /**
     * Tell if a user is linked to an authenticator or not.
     *
     * @param string $authenticator
     * @param string $authenticatorID
     * @param array $query
     * @return bool
     */
    public function get($authenticator, $authenticatorID = '', array $query) {
        $this->permission();

        $this->schema([
            'authenticator:s' => 'The authenticator that will be used.',
            'authenticatorID:s?' => 'Authenticator instance\'s identifier.',
        ], 'in');
        $in = $this->schema([
            'userID:i?' => 'UserID of the user to check against the authenticator. Defaults to the current user.',
        ])->setDescription('Tells whether the user is linked to the authenticator or not.');
        $out = $this->schema(['linked:b' => 'Whether the user is linked to the authenticator or not.'], 'out');

        $in->validate($query);

        if (isset($query['UserID'])) {
            $this->permission('Garden.Users.Edit');
            $userID = $query['UserID'];
        } else {
            $this->permission('Garden.SignIn.Allow');
            $userID = $this->getSession()->UserID;
        }

        $authenticatorInstance = $this->getAuthenticator($authenticator, $authenticatorID);

        return $out->validate([
            'linked' => (bool)$this->userModel->getAuthenticationByUser($userID, $authenticatorInstance->getID())
        ]);
    }

    /**
     * Get a session.
     *
     * @param string $authSessionID
     * @param array $query
     * @return array
     * @throws Exception
     */
    public function get_session($authSessionID, array $query) {
        $this->permission();

        $this->schema([
            'authSessionID:s' => 'Identifier of the authentication session.',
        ], 'in');
        $in = $this->schema([
            'expand:b?' => 'Expand associated records.',
        ], 'in')->setDescription('Get the content of an authentication session.');
        $out = $this->schema([
            'authSessionID:s' => 'Identifier of the authentication session.',
            'dateInserted:dt' => 'When the session was created.',
            'dateExpires:dt' => 'When the session expires.',
            'attributes' => Schema::parse([
                'ssoData:o' => $this->ssoDataSchema(), // This should do a sparse validation
                'linkUser:o?' => Schema::parse([
                    'existingUsers:a' => Schema::parse([
                        'userID:i' => 'The userID of the participant.',
                        'user:o?' => $this->getUserFragmentSchema(),
                    ])->setDescription('User that matches the SSOData and can be used to connect the user.'),
                ])->setDescription('Information needed for the "linkUser" step.'),
            ]),
        ], 'out');

        $query = $in->validate($query);

        $sessionData = $this->sessionModel->getID($authSessionID, DATASET_TYPE_ARRAY);
        if ($this->sessionModel->isExpired($sessionData)) {
            throw new ClientException('The session has expired.');
        }

        if (!empty($query['expand']) && isset($sessionData['Attributes']['linkUser']['existingUsers'])) {
            $this->userModel->expandUsers($sessionData['Attributes']['linkUser']['existingUsers'], ['UserID']);
        }

        $sessionData['authSessionID'] = $sessionData['SessionID'];

        $cleanedSessionData = $out->validate($sessionData);

        // We need to add back the ssoData since it was cleaned and we want to preserve any extra information.
        if (isset($cleanedSessionData['attributes']['ssoData'])) {
            $ssoData = ApiUtils::convertOutputKeys($sessionData['Attributes']['ssoData']);
            $cleanedSessionData['attributes']['ssoData'] = $ssoData;
        }

        return $cleanedSessionData;
    }

    /**
     * Get an authenticator.
     *
     * @param string $authenticatorType
     * @param string $authenticatorID
     * @return Authenticator
     * @throws NotFoundException
     * @throws ServerException
     */
    private function getAuthenticator($authenticatorType, $authenticatorID) {
        if (empty($authenticatorType)) {
            throw new NotFoundException();
        }

        $authenticatorClassName = $authenticatorType.'Authenticator';

        /** @var Authenticator $authenticatorInstance */
        $authenticatorInstance = null;

        // Check if the container can find the authenticator.
        try {
            $authenticatorInstance = $this->container->getArgs($authenticatorClassName, [$authenticatorID]);
            return $authenticatorInstance;
        } catch (Exception $e) {}

        // Use the addonManager to find the class.
        $authenticatorClasses = $this->addonManager->findClasses("*\\$authenticatorClassName");

        if (empty($authenticatorClasses)) {
            throw new NotFoundException($authenticatorClassName);
        }

        // Throw an exception if there are multiple authenticators with that type.
        // We are not handling authenticators with the same name in different namespaces for now.
        if (count($authenticatorClasses) > 1) {
            throw new ServerException(
                "Multiple class named \"$authenticatorClasses\" have been found.",
                500,
                ['classes' => $authenticatorClasses]
            );
        }

        $fqnAuthenticationClass = $authenticatorClasses[0];

        if (!is_a($fqnAuthenticationClass, Authenticator::class, true)) {
            throw new ServerException(
                "\"$fqnAuthenticationClass\" is not an ".Authenticator::class,
                500
            );
        }

        $authenticatorInstance = $this->container->getArgs($fqnAuthenticationClass, [$authenticatorID]);

        return $authenticatorInstance;
    }

    /**
     * Authenticate a user using the specified authenticator.
     *
     * @param array $body
     * @throws Exception If the authentication process fails
     * @throws NotFoundException If the $authenticatorType is not found.
     * @return array
     */
    public function post(array $body) {
        $this->permission();

        $in = $this->schema([
            'authenticator:s' => 'The authenticator that will be used.',
            'authenticatorID:s?' => 'Authenticator instance\'s identifier.',
        ])->setDescription('Authenticate a user using a specific authenticator.');
        $out = $this->schema(Schema::parse([
            'authenticationStep:s' => [
                'description' => 'Tells whether the user is now authenticated or if additional step(s) are required.',
                'enum' => ['authenticated', 'linkUser'],
            ],
            'userID:i?' => 'Identifier of the authenticated user.',
            'authSessionID:s?' => 'Identifier of the authentication session. Returned if more steps are required to complete the authentication.',
        ]), 'out');

        $in->validate($body);

        $authenticator = $body['authenticator'];
        $authenticatorID = isset($body['authenticatorID']) ? $body['authenticatorID'] : null;

        if ($this->getSession()->isValid()) {
            throw new ClientException("Cannot authenticate while already logged in.", 403);
        }

        $authenticatorInstance = $this->getAuthenticator($authenticator, $authenticatorID);

        if (is_a($authenticatorInstance, SSOAuthenticator::class)) {

            /** @var SSOAuthenticator $authenticatorInstance */
            $ssoData = $authenticatorInstance->validateAuthentication($this->request);

            if (!$ssoData) {
                throw new ServerException("Unknown error while authenticating with $authenticatorType.", 500);
            }

            $user = $this->ssoModel->sso($ssoData, false);
        } else {
            throw new ServerException(get_class($authenticatorInstance).' is not a supported authenticator yet.', 500);
        }

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

        if ($user) {
            $response = array_merge(['authenticationStep' => 'authenticated'], ApiUtils::convertOutputKeys($user));
        // We could not authenticate or autoconnect so they will need to do a manual connect.
        } else {
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
     * Get the SSOData schema.
     *
     * @return Schema
     */
    public function ssoDataSchema() {
        static $ssoDataSchema;

        if ($ssoDataSchema === null) {
            $ssoDataSchema = $this->schema([
                'authenticatorName:s' => 'Name of the authenticator that was used to create this object.',
                'authenticatorID:s' => 'ID of the authenticator instance that was used to create this object.',
                'authenticatorIsTrusted:b' => 'If the authenticator is trusted to sync user\'s information.',
                'uniqueID:s' => 'Unique ID of the user supplied by the provider.',
                'email:s?' => 'Email of the user.',
                'name:s?' => 'Name of the user.',
                'roles:a?' => [
                    'description' => 'One or more role name.',
                    'items' => ['type' => 'string'],
                    'style' => 'form',
                ],
                '...:s?' => 'Any other information.',
            ], 'SSOData')->setDescription('SSOAuthenticator\'s supplied information.');
        }

        return $ssoDataSchema;
    }
}
