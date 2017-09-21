<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

use Garden\Schema\Schema;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\ServerException;
use Garden\Web\RequestInterface;
use Interop\Container\ContainerInterface;
use Vanilla\AddonManager;
use Vanilla\Authenticator\Authenticator;
use Vanilla\Authenticator\SSOAuthenticator;
use Vanilla\Models\SSOModel;
use Vanilla\Models\SSOInfo;
use Vanilla\Utility\CamelCaseScheme;
use Vanilla\Utility\CapitalCaseScheme;

/**
 * API Controller for the `/authenticate` resource.
 */
class AuthenticateApiController extends AbstractApiController {

    const SESSION_ID_EXPIRATION = 1200; // 20 minutes

    /** @var AddonManager */
    private $addonManager;

    /** @var CamelCaseScheme */
    private $camelCaseScheme;

//    /** @var CapitalCaseScheme */
//    private $capitalCaseScheme;

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
        $this->camelCaseScheme = new CamelCaseScheme();
//        $this->capitalCaseScheme = new CapitalCaseScheme();
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
            'DateExpire' => date(MYSQL_DATE_FORMAT, time() + self::SESSION_ID_EXPIRATION),
            'Attributes' => $data,
        ]);

        return $sessionID;
    }

    /**
     * Unlink a user from the specified authenticator.
     * If no user is specified it will unlink the current user.
     *
     * @throws Exception
     *
     * @param $authenticator
     * @param string $authenticatorID
     * @param array $query The query string as an array.
     */
    public function delete($authenticator, $authenticatorID = '', array $query) {
        $in = $this->schema([
            'authenticator:s' => 'The authenticator that will be used.',
            'authenticatorID:s?' => 'Authenticator instance\'s identifier.',
            'userID:i?' => 'UserID to unlink authenticator from.',
        ])->setDescription('Authenticate a user using a specific authenticator.');
        $this->schema([], 'out');

        $in->validate($query, true);

        if (isset($query['UserID'])) {
            $this->permission('Garden.Users.Edit');
            $userID = $query['UserID'];
        } else {
            $this->permission('Garden.SignIn.Allow');
            $userID = $this->getSession()->UserID;
        }

        $authenticatorInstance = $this->getSSOAuthenticator($authenticator, $authenticatorID);

        $data = [];
        $this->userModel->getDelete(
            'UserAuthentication',
            ['UserID' => $userID, 'ProviderKey' => $authenticatorInstance->getID()],
            $data
        );
    }

    public function delete_session($authSessionID) {
        $this->schema([
            'authSessionID:s' => 'Identifier of the authentication session.',
        ], 'in')->setDescription('Delete an authenticate session.');
        $this->schema([], 'out');

        $this->sessionModel->deleteID($authSessionID);
    }

    /**
     * Authenticate a user using the specified authenticator.
     * We allow get requests because some authenticators need that.
     *
     * @throws Exception If the authentication process fails
     * @throws NotFoundException If the $authenticatorType is not found.
     * @param string $authenticator
     * @param string $authenticatorID
     * @return array
     */
    public function get_auth($authenticator, $authenticatorID = '') {
        return $this->post_auth($authenticator, $authenticatorID);
    }

    public function get_session($authSessionID, array $query) {
        $this->schema([
            'authSessionID:s' => 'Identifier of the authentication session.',
        ], 'in');
        $in = $this->schema([
            'expand:b?' => 'Expand associated records.',
        ], 'in')->setDescription('Get the content of an authentication session.');
        $out = $this->schema([
            'authSessionID:s' => 'Identifier of the authentication session.',
            'dateInserted:dt' => 'When the session was created.',
            'dateExpire:dt' => 'When the session expires.',
            'attributes' => Schema::parse([
                'ssoInfo:o' => $this->ssoInfoSchema(), // This should do a sparse validation
                'connectuser:o?' => Schema::parse([
                    'existingUsers:a' => Schema::parse([
                        'userID:i' => 'The userID of the participant.',
                        'user:o?' => $this->getUserFragmentSchema(),
                    ])->setDescription('User that matches the SSOInfo and can be used to connect the user.'),
                ])->setDescription('Information needed for the "connectuser" step.'),
            ]),
        ], 'out');

        $query = $in->validate($query);

        $sessionData = $this->sessionModel->getID($authSessionID, DATASET_TYPE_ARRAY);
        if ($this->sessionModel->isExpired($sessionData)) {
            throw new Exception('The session has expired.');
        }

        if (!empty($query['expand']) && isset($sessionData['Attributes']['connectuser']['existingUsers'])) {
            $this->userModel->expandUsers($sessionData['Attributes']['connectuser']['existingUsers'], ['UserID']);
        }

        $sessionData['authSessionID'] = $sessionData['SessionID'];

        $cleanedSessionData = $out->validate($sessionData);

        // We need to add back extra information that were stripped during the clean process.
        if (isset($cleanedSessionData['attributes']['ssoInfo']['extraInfo'])) {
            $extraInfo = $this->camelCaseScheme->convertArrayKeys($sessionData['Attributes']['ssoInfo']['extraInfo']);
            $cleanedSessionData['attributes']['ssoInfo']['extraInfo'] += $extraInfo;
        }

        return $cleanedSessionData;
    }

    /**
     * Get an Authenticator
     *
     * @throws Exception
     *
     * @param $authenticatorType
     * @param $authenticatorID
     * @return Authenticator
     */
    public function getAuthenticator($authenticatorType, $authenticatorID) {
        if (empty($authenticatorType)) {
            throw new NotFoundException();
        }

        $authenticatorClassName = $authenticatorType.'Authenticator';
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

        /** @var Authenticator $authenticatorInstance */
        $authenticatorInstance = $this->container->getArgs($fqnAuthenticationClass, [$authenticatorID]);

        return $authenticatorInstance;
    }

    /**
     * Authenticate a user using the specified authenticator.
     *
     * @throws Exception If the authentication process fails
     * @throws NotFoundException If the $authenticatorType is not found.
     *
     * @param string $authenticator
     * @param string $authenticatorID
     * @return array
     */
    public function post_auth($authenticator, $authenticatorID = '') {
        $this->schema([
            'authenticator:s' => 'The authenticator that will be used.',
            'authenticatorID:s?' => 'Authenticator instance\'s identifier.',
        ])->setDescription('Authenticate a user using a specific authenticator.');
        $out = $this->schema(Schema::parse([
            'authenticationStep:s' => 'Tells whether the user is now authenticated or if additional step(s) are required.',
            'userID:i?' => 'Identifier of the authenticated user.',
            'authSessionID:s?' => 'Identifier of the authentication session. Returned if more steps are required to complete the authentication.',
        ]), 'out');

        if ($this->getSession()->isValid()) {
            throw new ClientException("Cannot authenticate while already logged in.", 403);
        }

        $authenticatorInstance = $this->getAuthenticator($authenticator, $authenticatorID);

        if (is_a($authenticatorInstance, SSOAuthenticator::class)) {

            /** @var SSOAuthenticator $authenticatorInstance */
            $ssoInfo = $authenticatorInstance->authenticate($this->request);

            if (!$ssoInfo) {
                throw new ServerException("Unknown error while authenticating with $authenticatorType.", 500);
            }

            $user = $this->ssoModel->sso($ssoInfo, false);
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
            'ssoInfo' => $ssoInfo,
        ];

        if ($user) {
            $response = array_merge(['authenticationStep' => 'authenticated'], $this->camelCaseScheme->convertArrayKeys($user));
        } else {
            // We could not authenticate or autoconnect but it may be possible to do a manual connect.
            // If that is the case we should state so in the response.
            if ($allowConnect && ($emailUnique || $nameUnique)) {
                $existingUserIDs = $this->ssoModel->findMatchingUserIDs($ssoInfo, $emailUnique, $nameUnique);
                if (!empty($existingUserIDs)) {
                    $sessionData['connectuser'] = [
                        'existingUsers' => $existingUserIDs,
                    ];
                    $response = [
                        'authenticationStep' => 'connectuser',
                    ];
                }
            }
        }

        if (!isset($response)) {
            throw new ClientException('Authentication failed.');
        }

        if ($response['authenticationStep'] === 'connectuser') {
            // Store all the information needed for the next authentication step.
            $response['authSessionID'] = $this->createSession($sessionData);
        }

        return $out->validate($response);
    }

    /**
     *
     *
     * @return Schema
     */
    public function ssoInfoSchema() {
        static $ssoInfoSchema;

        if ($ssoInfoSchema === null) {
            $ssoInfoSchema = $this->schema([
                'authenticatorName:s' => 'Name of the authenticator that was used to create this object.',
                'authenticatorID:s' => 'ID of the authenticator instance that was used to create this object.',
                'authenticatorIsTrusted:b' => 'If the authenticator is trusted to sync user\'s information.',
                'uniqueID:s' => 'Unique ID of the user supplied by the provider.',
                'extraInfo?' => Schema::parse([
                        'email:s?' => 'Email of the user.',
                        'name:s?' => 'Name of the user.',
                        'roles:a?' => [
                            'description' => 'One or more role name.',
                            'items' => ['type' => 'string'],
                            'style' => 'form',
                        ],
                        '...:s?' => 'Any other information.',
                    ])
                    ->setDescription('Any extra information returned by the provider. Usually name, email and possibly roles.')
            ], 'SSOInfo')->setDescription('SSOAuthenticator\'s supplied information.');
        }

        return $ssoInfoSchema;
    }
}
