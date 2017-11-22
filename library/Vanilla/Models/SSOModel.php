<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

namespace Vanilla\Models;

use Gdn_Configuration;
use Gdn_Session;
use UserModel;
use Vanilla\AddonManager;
use Vanilla\Utility\CapitalCaseScheme;

/**
 * Class SSOModel
 */
class SSOModel {

    /** @var AddonManager */
    private $addonManager;

    /** @var Gdn_Configuration */
    private $config;

    /** @var CapitalCaseScheme */
    private $capitalCaseScheme;

    /** @var  \Gdn_Session */
    private $session;

    /** @var UserModel */
    private $userModel;

    /**
     * SSOModel constructor.
     *
     * @param AddonManager $addonManager
     * @param Gdn_Configuration $config
     * @param Gdn_Session $session
     * @param UserModel $userModel
     */
    public function __construct(
        AddonManager $addonManager,
        Gdn_Configuration $config,
        Gdn_Session $session,
        UserModel $userModel
    ) {
        $this->addonManager = $addonManager;
        $this->capitalCaseScheme = new CapitalCaseScheme();
        $this->config = $config;
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
        $email = $ssoData->coalesce('email');
        $name = $ssoData->coalesce('name');

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

        $email = $ssoData->coalesce('email');
        $name = $ssoData->coalesce('name');
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

        return $this->userModel->getAuthentication($ssoData['uniqueID'], $ssoData['authenticatorID']);
    }

    /**
     * Get a user by the provided SSOData's email.
     *
     * @param SSOData $ssoData
     * @return array|bool User data if found or false otherwise.
     */
    private function getUserByEmail(SSOData $ssoData) {
        $email = $ssoData->coalesce('email', null);
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
     * @param SSOData $ssoData
     * @return array|false The authenticated user info or false.
     */
    public function sso(SSOData $ssoData) {
        $user = $this->getUser($ssoData);

        if (!$user) {
            // Allows registration without an email address.
            $noEmail = $this->config->get('Garden.Registration.NoEmail', false);

            // Specifies whether Emails are unique or not.
            $emailUnique = !$noEmail && $this->config->get('Garden.Registration.EmailUnique', true);

            // Allows SSO connections to link a VanillaUser to a ForeignUser.
            $allowConnect = $this->config->get('Garden.Registration.AllowConnect', true);

            // Will automatically try to link users using the provided Email address if the Provider is "Trusted".
            $autoConnect =
                $emailUnique &&
                (
                    $ssoData['authenticatorIsTrusted']
                    || ($allowConnect && $this->config->get('Garden.Registration.AutoConnect', false))
                )
            ;

            // Let's try to find a matching user.
            if ($autoConnect) {
                $user = $this->getUserByEmail($ssoData);

                // Make sure that the user isn't already linked to another ID.
                if ($user) {
                    $result = $this->userModel->getAuthenticationByUser($user['UserID'], $ssoData['authenticatorID']);
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
                    'Provider' => $ssoData['authenticatorID'],
                    'UniqueID' => $ssoData['uniqueID']
                ]);
            }
        }

        if ($user) {
            $this->session->start($user['UserID']);

            // Allow user's synchronization
            $syncInfo = $this->config->get('Garden.Registration.ConnectSynchronize', true);

            if ($syncInfo) {
                // Synchronize user's roles.
                $syncRoles = $this->config->get('Garden.SSO.SyncRoles', false);

                // Override $syncRoles if the authenticator is trusted.
                if ($ssoData['authenticatorIsTrusted']) {
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
            $userInfo = array_merge($this->capitalCaseScheme->convertArrayKeys((array)$ssoData), $userInfo);

            // Don't overwrite the user photo if the user uploaded a new one.
            $photo = val('Photo', $user);
            if (!val('Photo', $userInfo) || ($photo && !isUrl($photo))) {
                unset($userInfo['Photo']);
            }
        }

        $saveRoles = $syncRoles && array_key_exists('roles', $ssoData);
        if ($saveRoles) {
            if (!empty($ssoData['roles'])) {
                $roles = \RoleModel::getByName($ssoData['roles']);
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

        /*
         * TODO: Add a replacement event for AfterConnectSave.
         * It was added 8 months ago so it is safe to assume that the only usage of it is the CategoryRoles plugin.
         * https://github.com/vanilla/vanilla/commit/1d9ae17652213d888bbd07cac0f682959ca326b9
         */

        return $userID !== false;
    }
}
