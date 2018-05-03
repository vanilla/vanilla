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
use Vanilla\Authenticator\Authenticator;
use Vanilla\Authenticator\SSOAuthenticator;
use Vanilla\Exception\PermissionException;
use Vanilla\Models\SSOData;
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
                Schema::parse([
                    'sso?' => [
                        'canSignIn' => null,
                        'canAutoLinkUser' => null,
                    ]
                ])->add(SSOAuthenticator::getAuthenticatorSchema()->getField('properties.sso'))
            )
        ;

        return $schema;
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

                if (isset($isUserLinked) && !$authenticator->isUserLinked($this->getSession()->UserID)) {
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
            'authenticate' => [
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

        $sso = is_a($authenticatorInstance, SSOAuthenticator::class);
        if ($sso) {
            /** @var SSOAuthenticator $authenticatorInstance */
            $ssoData = $authenticatorInstance->validateAuthentication($this->request);

            if (!$ssoData) {
                throw new ServerException("Unknown error while authenticating with $authenticatorType.", 500);
            }

            $user = $this->ssoModel->sso($ssoData, [
                'linkToSession' => $body['method'] === 'session',
                'setCookie' => $body['method'] !== 'linkUser',
                'persist' => $body['persist'],
            ]);
        } else {
            $user = $authenticatorInstance->validateAuthentication($this->request);
            if ($user) {
                $this->getSession()->start($user['UserID'], true, $body['persist']);
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
                'description' => 'Whether the session should Garden.SignIn.Allowpersist past the browser closing.',
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
}
