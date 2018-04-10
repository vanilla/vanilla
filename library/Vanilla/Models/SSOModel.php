<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

namespace Vanilla\Models;

use Garden\EventManager;
use Garden\Web\Exception\ServerException;
use Gdn_Configuration;
use Gdn_Session;
use UserModel;
use Vanilla\Authenticator\SSOAuthenticator;
use Vanilla\Utility\CapitalCaseScheme;

/**
 * Class SSOModel
 */
class SSOModel {

    /** @var AuthenticatorModel */
    private $authenticatorModel;

    /** @var Gdn_Configuration */
    private $config;

    /** @var CapitalCaseScheme */
    private $capitalCaseScheme;

    /** @var EventManager */
    private $eventManager;

    /** @var  \Gdn_Session */
    private $session;

    /** @var UserModel */
    private $userModel;

    /**
     * SSOModel constructor.
     *
     * @param AuthenticatorModel $authenticatorModel
     * @param Gdn_Configuration $config
     * @param EventManager $eventManager
     * @param Gdn_Session $session
     * @param UserModel $userModel
     */
    public function __construct(
        AuthenticatorModel $authenticatorModel,
        Gdn_Configuration $config,
        EventManager $eventManager,
        Gdn_Session $session,
        UserModel $userModel
    ) {
        $this->authenticatorModel = $authenticatorModel;
        $this->capitalCaseScheme = new CapitalCaseScheme();
        $this->config = $config;
        $this->eventManager = $eventManager;
        $this->session = $session;
        $this->userModel = $userModel;
    }

    /**
     * Create a user from the supplied SSOData.
     *
     * @param SSOData $ssoData
     * @return array|false The user of false on failure.
     */
    public function createUser(SSOData $ssoData) {
        $email = $ssoData->getUserValue('email');
        $name = $ssoData->getUserValue('name');

        if (!$email && !$name) {
            return false;
        }

        $userInfo = [
            'Name' => $name,
            'Email' => $email,
            'Password' => betterRandomString('32', 'Aa0!'),
            'HashMethod' => 'Random',
        ];

        $userID = $this->userModel->register($userInfo, [
            'CheckCaptcha' => false,
            'FixUnique' => false,
            'NoConfirmEmail' => true,
            'ValidateEmail' => false,
        ]);

        $user = false;
        if ($userID) {
            $user = $this->userModel->getID($userID, DATASET_TYPE_ARRAY);
        }

        return $user;
    }

    /**
     * Try to find a user matching the provided SSOData.
     * Email has priority over Name if both are allowed.
     *
     * @param SSOData $ssoData SSO provided user's information.
     * @param string $findByEmail Try to find the user by Email.
     * @param string $findByName Try to find the user by Name.
     * @return array User objects that matches the SSOData.
     */
    public function findMatchingUserIDs(SSOData $ssoData, $findByEmail, $findByName) {
        if (!$findByEmail && !$findByName) {
            return [];
        }

        $email = $ssoData->getUserValue('email');
        $name = $ssoData->getUserValue('name');
        if (!$email && !$name) {
            return [];
        }

        $sql = $this->userModel->SQL;

        $sql->select(['UserID'])->where(['Banned' => 0]);

        $sql->andOp()->beginWhereGroup();
        $previousCondition = false;

        if ($findByEmail && $email) {
            $previousCondition = true;
            $sql->where(['Email' => $email]);
        }

        if ($findByName && $name) {
            if ($previousCondition) {
                $sql->orOp();
            }
            $sql->where(['Name' => $name]);
        }

        $sql->endWhereGroup();

        return $this->userModel->getWhere()->resultArray();
    }

    /**
     * Get a user.
     *
     * @param SSOData $ssoData
     * @return array|false
     */
    public function getUser(SSOData $ssoData) {
        // Will throw a proper exception.
        $ssoData->validate();

        return $this->userModel->getAuthentication($ssoData->getUniqueID(), $ssoData->getAuthenticatorID());
    }

    /**
     * Get a user by the provided SSOData's email.
     *
     * @param SSOData $ssoData
     * @return array|bool User data if found or false otherwise.
     */
    private function getUserByEmail(SSOData $ssoData) {
        $email = $ssoData->getUserValue('email');
        if (!isset($email)) {
            return false;
        }

        return $this->userModel->getWhere(['Email' => $email])->firstRow(DATASET_TYPE_ARRAY);
    }

    /**
     * Get the validation results from the last operation.
     *
     * @return array
     */
    public function getValidationResults() {
        return $this->userModel->validationResults();
    }

    /**
     * Do an authentication using the provided SSOData.
     *
     * @throws ServerException
     * @param SSOData $ssoData
     * @param array $options
     *   - setCookie:b Set session cookie on success. Default: true
     *   - persist:b Set the persist option on the cookie when it is set. Default: false
     * @return array|false The authenticated user info or false.
     */
    public function sso(SSOData $ssoData, $options) {
        $user = $this->getUser($ssoData);

        /** @var SSOAuthenticator $ssoAuthenticator */
        $ssoAuthenticator = $this->authenticatorModel->getAuthenticator($ssoData->getAuthenticatorType(), $ssoData->getAuthenticatorID());
        if (!is_a($ssoAuthenticator, SSOAuthenticator::class)) {
            throw new ServerException('Expected an SSOAuthenticator');
        }
        if (!$ssoAuthenticator->isActive()) {
            throw new ServerException('The authenticator is not active.');
        }

        if (!$user) {
            // Allows registration without an email address.
            $noEmail = $this->config->get('Garden.Registration.NoEmail', false);

            // Specifies whether Emails are unique or not.
            $emailUnique = !$noEmail && $this->config->get('Garden.Registration.EmailUnique', true);

            // Allows SSO connections to link a VanillaUser to a ForeignUser.
            $allowConnect = $emailUnique && $this->config->get('Garden.Registration.AllowConnect', true);

            // Will automatically try to link users using the provided Email address if the Provider is "Trusted".
            $autoConnect = $allowConnect && $ssoAuthenticator->canAutoLinkUser();

            // Let's try to find a matching user.
            if ($autoConnect) {
                $user = $this->getUserByEmail($ssoData);

                // Make sure that the user isn't already linked to another ID.
                if ($user) {
                    $result = $this->userModel->getAuthenticationByUser($user['UserID'], $ssoData->getAuthenticatorID());
                    if ($result) {
                        // TODO: We should probably add some sort of warning about this.
                        $user = false;
                    }
                }
            }

            // Try to create a new user since none are matching.
            if (!$user) {
                $user = $this->createUser($ssoData);
            }

            // Yay!
            if ($user !== false) {
                $this->userModel->saveAuthentication([
                    'UserID' => $user['UserID'],
                    'Provider' => $ssoData->getAuthenticatorID(),
                    'UniqueID' => $ssoData->getUniqueID(),
                ]);
            }
        }

        if ($user) {
            $this->session->start($user['UserID'], $options['setCookie'] ?? true, $options['persistCookie'] ?? false);
            $this->userModel->fireEvent('AfterSignIn');

            // Allow user's synchronization
            $syncInfo = $this->config->get('Garden.Registration.ConnectSynchronize', true);

            if ($syncInfo) {
                // Synchronize user's roles.
                $syncRoles = $this->config->get('Garden.SSO.SyncRoles', false);

                // Override $syncRoles if the authenticator is trusted.
                if ($ssoAuthenticator->isTrusted()) {
                    // Synchronize user's roles only on registration.
                    $syncRolesOnlyRegistration = $this->config->get('Garden.SSO.SyncRolesOnRegistrationOnly', false);

                    // This coupling (connectOption put in $ssoData) sucks but I feel like that's the best way to accommodate the config!
                    if ($syncRolesOnlyRegistration && val('connectOption', $ssoData) !== 'createuser') {
                        $syncRoles = false;
                    } else {
                        $syncRoles = true;
                    }
                }

                if (!$this->syncUser($ssoData, $user, $syncInfo, $syncRoles)) {
                    throw new ServerException(
                        "User synchronization failed",
                        500,
                        [
                            'validationResults' => $this->userModel->validationResults()
                        ]
                    );
                }
            }
        } else {
            $user = false;
        }

        return $user;
    }

    /**
     * Synchronize a user using the provided data.
     *
     * @param SSOData $ssoData SSO provided user data.
     * @param array $user Current user's data.
     * @param bool $syncInfo Synchronize the user's information.
     * @param bool $syncRoles Synchronize the user's roles.
     * @return bool If the synchronisation was a success ot not.
     */
    private function syncUser(SSOData $ssoData, $user, $syncInfo, $syncRoles) {
        if (!$syncInfo && !$syncRoles) {
            return true;
        }

        $userInfo = [
            'UserID' => $user['UserID']
        ];

        if ($syncInfo) {
            $userInfo = array_merge($this->capitalCaseScheme->convertArrayKeys($ssoData->getUser()), $userInfo);

            // Don't overwrite the user photo if the user uploaded a new one.
            $photo = val('Photo', $user);
            if (!val('Photo', $userInfo) || ($photo && !isUrl($photo))) {
                unset($userInfo['Photo']);
            }
        }

        $ssoDataRole = $ssoData->getUserValue('roles');
        $saveRoles = $syncRoles && $ssoDataRole !== null;
        if ($saveRoles) {
            if (!empty($ssoDataRole)) {
                $roles = \RoleModel::getByName($ssoDataRole);
                $roleIDs = array_keys($roles);
            }

            // Ensure user has at least one role.
            if (empty($roleIDs)) {
                $roleIDs = $this->userModel->newUserRoleIDs();
            }

            $userInfo['RoleID'] = $roleIDs;
        }

        $userID = $this->userModel->save($userInfo, [
            'NoConfirmEmail' => true,
            'FixUnique' => true,
            'SaveRoles' => $saveRoles,
        ]);

        $this->eventManager->fireArray('afterUserSync', [$userID, $ssoData->getUser(), $ssoData->getExtra()]);

        return $userID !== false;
    }
}
