<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

use Interop\Container\ContainerInterface;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Models\SSOModel;
use Vanilla\Models\SSOUserInfo;
use Vanilla\Utility\CapitalCaseScheme;
use Vanilla\SSOAuthenticator;

/**
 * API Controller for the `/authentication` resource.
 */
class AuthenticationApiController extends AbstractApiController {

    /** @var CapitalCaseScheme */
    private $caseScheme;

    /** @var Container */
    private $container;

    /** @var Gdn_Request */
    private $request;

    /** @var SSOModel */
    private $ssoModel;

    /** @var UserModel */
    private $userModel;

    /**
     * AuthenticationController constructor.
     *
     * @param CapitalCaseScheme $caseScheme
     * @param ContainerInterface $container
     * @param Gdn_Request $request
     * @param SSOModel $ssoModel
     * @param UserModel $userModel
     */
    public function __construct(
        CapitalCaseScheme $caseScheme,
        ContainerInterface $container,
        Gdn_Request $request,
        SSOModel $ssoModel,
        UserModel $userModel
    ) {
        $this->caseScheme = $caseScheme;
        $this->container = $container;
        $this->request = $request;
        $this->ssoModel = $ssoModel;
        $this->userModel = $userModel;
    }

    /**
     * Automatically makes a link in Gdn_UserAuthentication using the email address.
     *
     * @param SSOUserInfo $ssoUserInfo
     * @return array|bool User data if found or false otherwise.
     */
    private function autoConnect(SSOUserInfo $ssoUserInfo) {
        $userData = $this->userModel->getWhere(['Email' => $ssoUserInfo['email']])->firstRow(DATASET_TYPE_ARRAY);
        if ($userData !== false) {
            $this->userModel->saveAuthentication([
                'UserID' => $userData['UserID'],
                'Provider' => $ssoUserInfo['authenticatorID'],
                'UniqueID' => $ssoUserInfo['uniqueID']
            ]);
        }
        return $userData;
    }

    /**
     * Try to find a user matching the provided SSOUserInfo.
     * Email has priority over Name if both are allowed.
     *
     * @param SSOUserInfo $ssoUserInfo SSO provided user's information.
     * @param string $findByEmail Try to find the user by Email.
     * @param string $findByName Try to find the user by Name.
     * @return bool|int UserID that matches the SSOUserInfo or false if nothing.
     */
    private function findExistingUser(SSOUserInfo $ssoUserInfo, $findByEmail, $findByName) {
        $matchingUserID = false;

        if ($findByEmail) {
            $this->userModel->SQL->orWhere(['Email' => $ssoUserInfo['email']]);
        }
        if ($findByName) {
            $this->userModel->SQL->orWhere(['Name' => $ssoUserInfo['name']]);
        }

        $users = $this->userModel->getWhere()->resultArray();
        if (!empty($users)) {
            foreach ($users as $user) {
                if ($findByEmail) {
                    if ($user['Email'] == $ssoUserInfo['email']) {
                        $matchingUserID = $user['UserID'];
                        break;
                    }
                } else {
                    if ($user['Name'] == $ssoUserInfo['name']) {
                        $matchingUserID = $user['UserID'];
                        break;
                    }
                }
            }
        }

        return $matchingUserID;
    }

    /**
     * Authenticate a user using the specified authenticatorType.
     *
     * @throws Exception If the authentication process fails
     * @throws NotFoundException If the $authenticatorType is not found.
     * @param $authenticatorType
     * @return array
     */
    public function post_auth($authenticatorType) {
        $in = $this->schema([
            'authenticatorType:s' => 'The type of authenticator that will be used.',
            'authenticatorID:s?' => 'Authenticator instance\'s identifier',
        ])->setDescription('Authenticate a using using a specific Authenticator.');
        $out = $this->schema(Schema::parse([
            'AuthenticationStep:s' => 'Next step of the authentication process',
            'UserID:i' => 'UserID of user.',
        ]), 'out');

        if (empty($authenticatorType)) {
            throw new NotFoundException();
        }

        $authenticatorClass = $authenticatorType.'Authenticator';

        /** @var SSOAuthenticator $authenticator */
        $authenticator = null;
        if (class_exists($authenticatorClass)) {
            try {
                $authenticator = $this->container->get($authenticatorClass);
            } catch (Exception $ex) {
                if (debug()) {
                    return $ex;
                } else {
                    throw new NotFoundException($authenticatorClass);
                }
            }
        }

        // The authenticator should throw an appropriate error message on error.
        $ssoUserInfo = $authenticator->sso($this->request);

        if (!$ssoUserInfo) {
            throw new Exception("Unknown error while authenticating with $authenticatorType.");
        }

        // Allows registration without an email address.
        $noEmail = c('Garden.Registration.NoEmail', false);

        // Specifies whether Emails are unique or not.
        $emailUnique = !$noEmail && c('Garden.Registration.EmailUnique', true);

        // Specifies whether Names are unique or not.
        $nameUnique = c('Garden.Registration.NameUnique', true);

        // Allows SSO connections to link a VanillaUser to a ForeignUser.
        $allowConnect = c('Garden.Registration.AllowConnect', true);

        // Will automatically try to link users using the provided Email address if the Provider is "Trusted".
        $autoConnect = $allowConnect && $emailUnique&& c('Garden.Registration.AutoConnect', false);

        // Synchronize user's data.
        $syncUser = c('Garden.Registration.ConnectSynchronize', true);

        // Synchronize user's roles only on registration.
        $syncRolesOnlyRegistration = c('Garden.SSO.SyncRolesOnRegistrationOnly', false);

        // Synchronize user's roles.
        $syncRoles = !$syncRolesOnlyRegistration && c('Garden.SSO.SyncRoles', false);

        $user = $this->ssoModel->sso($ssoUserInfo);

        // Let's try to find a matching user.
        if (!$user && $autoConnect) {
            $user = $this->autoConnect($authenticator);
        }

        if ($user) {
            if (!$this->syncUser($syncUser, $syncRoles, $user, $ssoUserInfo)) {
                throw new Exception(
                    "User synchronization failed because of the following problems:\n"
                    .print_r($this->userModel->validationResults(), true)
                );
            }

            $response = array_merge(['AuthenticationStep' => 'Authenticated'], $user);
        } else {
            // We could not authenticate or autoconnect but it may be possible to do a manual connect.
            // If that is the case we should state so in the response.
            if ($allowConnect && ($emailUnique || $nameUnique)) {
                $existingUserID = $this->findExistingUser($ssoUserInfo, $emailUnique, $nameUnique);
                if ($existingUserID) {
                    // TODO: Determine what we need to do when we have to manually link a user.
                    // Should we use JWT to pass information along to the linkuser endpoint or use session stash...
                    // If possible using JWT to
                    $response = [
                        'AuthenticationStep' => 'linkuser',
                        'UserID' => $existingUserID,
                    ];
                }
            }
        }

        if (!isset($response)) {
            throw new Exception('Authentication failed.');
        }

        return $out->validate($response);
    }

    /**
     * Link a user.
     * WIP
     */
    public function post_linkuser() {
        // TODO: Implement.
    }

    /**
     * Synchronize the user using the provided data.
     *
     * @param bool $syncUser Synchronize the user's data.
     * @param bool $syncRoles Synchronize the user's roles.
     * @param array $user Current user's data.
     * @param SSOUserInfo $ssoUserInfo SSO provided user data.
     * @return bool If the synchronisation was a success ot not.
     */
    private function syncUser($syncUser, $syncRoles, $user, SSOUserInfo $ssoUserInfo) {
        if (!$syncUser && !$syncRoles) {
            return true;
        }

        $userInfo = [
            'UserID' => $user['UserID']
        ];

        if ($syncUser) {
            $userInfo = array_merge($this->caseScheme->convertArrayKeys($ssoUserInfo), $userInfo);

            // Don't overwrite the user photo if the user uploaded a new one.
            $photo = val('Photo', $user);
            if (!val('Photo', $userInfo) || ($photo && !isUrl($photo))) {
                unset($userInfo['Photo']);
            }
        }

        $saveRoles = $syncRoles && array_key_exists('roles', $ssoUserInfo);
        if ($saveRoles) {
            if (!empty($ssoUserInfo['roles'])) {
                $roles = RoleModel::getByName($roles);
                $roleIDs = array_keys($roles);
            }

            // Ensure user has at least one role.
            if (empty($roleIDs)) {
                $roleIDs = $this->UserModel->newUserRoleIDs();
            }

            $userInfo['RoleID'] = $roleIDs;
        }

        $userID = $this->userModel->save($userInfo, [
            'NoConfirmEmail' => true,
            'FixUnique' => true,
            'SaveRoles' => $saveRoles,
        ]);

        /*
         * TODO: Add a replacement event for AfterConnectSave.
         * It was added 8 months ago so it is safe to assume that the only usage of it is the CategoryRoles plugin.
         * https://github.com/vanilla/vanilla/commit/1d9ae17652213d888bbd07cac0f682959ca326b9
         */

        return $userID !== false;
    }
}
