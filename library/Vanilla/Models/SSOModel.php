<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Exception;
use Garden\EventManager;
use Garden\Schema\Validation;
use Garden\Schema\ValidationException;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\ForbiddenException;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\ServerException;
use Gdn_Configuration;
use Gdn_PasswordHash;
use Gdn_Session;
use UserModel;
use Vanilla\Authenticator\SSOAuthenticator;
use Vanilla\Utility\CapitalCaseScheme;

/**
 * Class SSOModel
 */
class SSOModel {
    const IDENTIFIER_TYPE_EMAIL = 'email';
    const IDENTIFIER_TYPE_ID = 'userID';
    const IDENTIFIER_TYPE_NAME = 'name';

    /** @var AuthenticatorModel */
    private $authenticatorModel;

    /** @var Gdn_Configuration */
    private $config;

    /** @var CapitalCaseScheme */
    private $capitalCaseScheme;

    /** @var EventManager */
    private $eventManager;

    /** @var  Gdn_Session */
    private $session;

    /** @var  Gdn_PasswordHash */
    private $passwordHash;

    /** @var UserModel */
    private $userModel;

    /**
     * SSOModel constructor.
     *
     * @param AuthenticatorModel $authenticatorModel
     * @param Gdn_Configuration $config
     * @param EventManager $eventManager
     * @param Gdn_Session $session
     * @param Gdn_PasswordHash $passwordHash
     * @param UserModel $userModel
     */
    public function __construct(
        AuthenticatorModel $authenticatorModel,
        Gdn_Configuration $config,
        EventManager $eventManager,
        Gdn_PasswordHash $passwordHash,
        Gdn_Session $session,
        UserModel $userModel
    ) {
        $this->authenticatorModel = $authenticatorModel;
        $this->capitalCaseScheme = new CapitalCaseScheme();
        $this->config = $config;
        $this->eventManager = $eventManager;
        $this->session = $session;
        $this->passwordHash = $passwordHash;
        $this->userModel = $userModel;
    }

    /**
     * Create a user from the supplied SSOData.
     *
     * @param SSOData $ssoData
     * @param array $options
     *    - useSSOData: Whether or not use the name/email supplied in the SSOData object. Defaults true.
     *    - name: Name to use to try to create the user.
     *    - email: Email to use to try to create the user.
     *
     * @return array $user
     *
     * @throws ClientException
     * @throws ServerException
     * @throws ValidationException
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     * @throws \Garden\Web\Exception\NotFoundException
     */
    public function createUser(SSOData $ssoData, $options = []) {
        // Clear userModel validation results..
        $this->userModel->Validation->reset();

        $ssoAuthenticator = $this->getSSOAuthenticator($ssoData);

        // Used to know if registration should set email as verified or not.
        $userSuppliedEmail = ($options['email'] ?? null) !== null;


        if ($options['useSSOData'] ?? true) {
            $email = $ssoData->getUserValue('email');
            $name = $ssoData->getUserValue('name');
        } else {
            $email = null;
            $name = null;
        }

        $email = $options['email'] ?? $email;
        $name = $options['name'] ?? $name;

        $validation = new Validation();

        if (!$name) {
            $validation->addError(
                'name',
                'Username is required.',
                ['status' => 400]
            );
        } else if (!validateUsername($name)) {
            $validation->addError(
                'name',
                'Username is not valid.',
                ['status' => 422]
            );
        }

        if (!$this->config->get('Garden.Registration.NoEmail') && !$email) {
            $validation->addError(
                'email',
                'Email is required.',
                ['status' => 400]
            );
        }

        if (!$validation->getErrors()) {
            $userInfo = [
                'Name' => $name,
                'Email' => $email,
                'Password' => betterRandomString('32', 'Aa0!'),
                'HashMethod' => 'Random',
            ];

            $emailFromTrustedProvider = !$userSuppliedEmail && $ssoAuthenticator->isTrusted();

            $userID = $this->userModel->save($userInfo, [
                'NoConfirmEmail' => $emailFromTrustedProvider,
                'ValidateEmail' => !$emailFromTrustedProvider,
                'FixUnique' => true,
            ]);

            if (!$userID) {
                // TODO convert old model validation errors to new Validation errors.
                $validationResults = $this->userModel->validationResults();

                $mapping = [
                    'The name you entered is already in use by another member.' => [
                        'code' => 'The username is taken.',
                        'status' => '422',
                    ],
                    'The email you entered is in use by another member.' => [
                        'code' => 'The email is taken.',
                        'status' => '422',
                    ],
                ];

                foreach ($validationResults as $field => $errors) {
                    $field = lcfirst($field);
                    foreach ($errors as $error) {
                        $validation->addError(
                            $field,
                            $mapping[$error]['code'] ?? $error,
                            [
                                'status' => $mapping[$error]['status'] ?? 400,
                            ]
                        );
                    }


                }
            } else {
                return $this->linkUser($ssoData, $userID);
            }
        }

        throw new ValidationException($validation);
    }

    /**
     * Link to a user using credentials.
     *
     * @param \Vanilla\Models\SSOData $ssoData
     * @param $identifierType (self::IDENTIFIER_TYPE_ID || self::IDENTIFIER_TYPE_EMAIL || self::IDENTIFIER_TYPE_NAME)
     * @param $identifier value of the specified $identifierType
     * @param $password
     *
     * @return array User
     * @throws \Garden\Schema\ValidationException
     * @throws \Garden\Web\Exception\ClientException
     * @throws \Garden\Web\Exception\NotFoundException
     * @throws \Garden\Web\Exception\ServerException
     */
    public function linkUserFromCredentials(SSOData $ssoData, $identifierType, $identifier, $password) {
        if (!$this->config->get('Garden.Registration.AllowConnect', true)) {
            throw new ServerException('Liking user is not allowed.');
        }

        if (!isset($password)) {
            throw new ClientException('Password is required');
        }

        if ($identifierType === self::IDENTIFIER_TYPE_ID) {
            $user = $this->getUserById($identifier);
        } else if ($identifierType === self::IDENTIFIER_TYPE_EMAIL) {
            $user = $this->getUserByEmail($identifier);
        } else if ($identifierType === self::IDENTIFIER_TYPE_NAME) {
            $user = $this->getUserByName($identifier);
        } else {
            throw new ServerException('Invalid identifier type "'.$identifierType.'".');
        }

        try {
            $passwordValid = $this->passwordHash->checkPassword($password, $user['Password'], $user['HashMethod']);
        } catch (Exception $exception) {
            $validation = new Validation();
            $validation->addError(
                'password',
                $exception->getMessage(),
                ['status' => 400]
            );
            throw new ValidationException($validation);
        }

        if (!$passwordValid) {
            throw new ClientException('The password validation failed.');
        }

        return $this->linkUser($ssoData, $user['UserID']);
    }

    /**
     *
     *
     * @param \Vanilla\Models\SSOData $ssoData
     *
     * @return array User.
     * @throws \Garden\Web\Exception\ForbiddenException
     * @throws \Garden\Web\Exception\NotFoundException
     * @throws \Garden\Web\Exception\ServerException
     */
    public function linkUserFromSession(SSOData $ssoData) {
        if (!$this->config->get('Garden.Registration.AllowConnect', true)) {
            throw new ServerException('Liking user is not allowed.');
        }

        if (!$this->session->isValid()) {
            throw new ForbiddenException('Cannot link user from session while not signed in.');
        }

        return $this->linkUser($ssoData, $this->session->UserID);
    }

    /**
     *
     *
     * @param \Vanilla\Models\SSOData $ssoData
     * @param $userID
     *
     * @return array User.
     * @throws \Garden\Web\Exception\NotFoundException
     * @throws \Garden\Web\Exception\ServerException
     */
    protected function linkUser(SSOData $ssoData, $userID) {
        if (!$this->config->get('Garden.Registration.AllowConnect', true)) {
            throw new ServerException('Liking user is not allowed.');
        }

        $user = $this->getUserById($userID);

        $this->userModel->saveAuthentication([
            'UserID' => $userID,
            'Provider' => $ssoData->getAuthenticatorID(),
            'UniqueID' => $ssoData->getUniqueID(),
        ]);

        return $user;
    }

    /**
     * Get the user linked to the specified authenticator.
     *
     * @param \Vanilla\Models\SSOData $ssoData
     *
     * @return array
     * @throws \Garden\Web\Exception\NotFoundException
     */
    protected function getLinkedUser(SSOData $ssoData) {
        $data = $this->userModel->getAuthentication($ssoData->getUniqueID(), $ssoData->getAuthenticatorID());

        if (!$data) {
            throw new NotFoundException('UserLink');
        }

        return $data;
    }

    /**
     *
     *
     * @param $email
     *
     * @return array
     * @throws \Garden\Web\Exception\ClientException
     * @throws \Garden\Web\Exception\NotFoundException
     * @throws \Garden\Web\Exception\ServerException
     */
    protected function getUserByEmail($email) {
        // Allows registration without an email address.
        $noEmail = $this->config->get('Garden.Registration.NoEmail', false);

        // Specifies whether Emails are unique or not.
        $emailUnique = !$noEmail && $this->config->get('Garden.Registration.EmailUnique', true);

        if (!$emailUnique) {
            throw new ClientException('Cannot get user by email due to current configurations.');
        }

        if (!$email) {
            throw new ClientException('Email is required.');
        }

        $results = $this->userModel->getWhere(['Email' => $email])->resultArray();

        if (!$results) {
            throw new NotFoundException('User');
        } else if (count($results) > 1) {
            throw new ServerException('Multiple users found with the same email.');
        }

        return reset($results);
    }

    /**
     *
     *
     * @param $name
     *
     * @return array
     * @throws \Garden\Web\Exception\ClientException
     * @throws \Garden\Web\Exception\NotFoundException
     * @throws \Garden\Web\Exception\ServerException
     */
    protected function getUserByName($name) {
        if (!$this->config->get('Garden.Registration.NameUnique', true)) {
            throw new ClientException('Cannot get user by name due to current configurations.');
        }

        if (!isset($name) || $name === '') {
            throw new ClientException('Name is required.');
        }

        $results = $this->userModel->getWhere(['Name' => $name])->resultArray();

        if (!$results) {
            throw new NotFoundException('User');
        } else if (count($results) > 1) {
            throw new ServerException('Multiple users found with the same name.');
        }

        return reset($results);
    }

    /**
     *
     *
     * @param $userID
     *
     * @return array
     * @throws \Garden\Web\Exception\NotFoundException
     */
    protected function getUserByID($userID) {
        $user = $this->userModel->getID($userID, DATASET_TYPE_ARRAY);

        if (!$user) {
            throw new NotFoundException('User');
        }

        return $user;
    }

    /**
     * Do an authentication using the provided SSOData.
     *
     * @param SSOData $ssoData
     * @param array $options
     *   - linkToSession:b Link the authentication to the currently logged user. Default: false
     *   - setCookie:b Set session cookie on success. Default: true
     *   - persist:b Set the persist option on the cookie when it is set. Default: false
     *
     * @return array|bool The authenticated user info or false.
     *
     * @throws ServerException
     * @throws \Exception
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     * @throws \Garden\Web\Exception\NotFoundException
     */
    public function sso(SSOData $ssoData, $options = []) {
        // Clear userModel validation results..
        $this->userModel->Validation->reset();

        // Will throw a proper exception.
        $ssoData->validate();

        /** @var SSOAuthenticator $ssoAuthenticator */
        $ssoAuthenticator = $this->authenticatorModel->getAuthenticator($ssoData->getAuthenticatorType(), $ssoData->getAuthenticatorID());
        if (!is_a($ssoAuthenticator, SSOAuthenticator::class)) {
            throw new ServerException('Expected an SSOAuthenticator');
        }

        $user = false;
        try {
            $user = $this->getLinkedUser($ssoData);
        } catch (Exception $e) {}

        if (!$user) {
            // Allows SSO connections to link a VanillaUser to a ForeignUser.
            $allowConnect = $this->config->get('Garden.Registration.AllowConnect', true);

            // We want to link to the currently logged user.
            if ($options['linkToSession'] ?? false) {
                if (!$this->session->isValid()) {
                    throw new ClientException('Cannot link user to session while not signed in.', 401);
                }
                if (!$allowConnect) {
                    throw new ForbiddenException('This site is not configured to allow user linking.');
                }
            }

            // Allows registration without an email address.
            $noEmail = $this->config->get('Garden.Registration.NoEmail', false);

            // Specifies whether Emails are unique or not.
            $emailUnique = !$noEmail && $this->config->get('Garden.Registration.EmailUnique', true);

            // Will automatically try to link users using the provided Email address if the Provider is "Trusted".
            $autoConnect = $emailUnique && $allowConnect && $ssoAuthenticator->canAutoLinkUser();

            // Let's try to find a matching user.
            if ($autoConnect) {
                $user = $this->getUserByEmail($ssoData->getUserValue('email'));

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
                try {
                    $user = $this->createUser($ssoData);
                } catch(Exception $e) {}
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

            if (!$this->session->isValid()) {
                throw new ClientException('The session could not be started.', 401);
            }

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
     * @param \Vanilla\Models\SSOData $ssoData SSO provided user data.
     * @param array $user Current user's data.
     * @param bool $syncInfo Synchronize the user's information.
     * @param bool $syncRoles Synchronize the user's roles.
     *
     * @return bool If the synchronisation was a success ot not.
     * @throws \Garden\Web\Exception\ServerException
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
                try {
                    $roleIDs = $this->userModel->newUserRoleIDs();
                } catch (Exception $e) {
                    throw new ServerException('Unable to synchronize user info.');
                }
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

    /**
     *
     *
     * @param SSOData $ssoData
     *
     * @return \Vanilla\Authenticator\SSOAuthenticator
     * @throws ClientException
     * @throws ServerException
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     * @throws \Garden\Web\Exception\NotFoundException
     */
    protected function getSSOAuthenticator(SSOData $ssoData) {
        $ssoAuthenticator = $this->authenticatorModel->getAuthenticator($ssoData->getAuthenticatorType(), $ssoData->getAuthenticatorID());
        if (!is_a($ssoAuthenticator, SSOAuthenticator::class)) {
            throw new ServerException('Expected an SSOAuthenticator');
        }

        if (!$ssoAuthenticator->isActive()) {
            throw new ClientException('Authenticator is not active.', 422);
        }

        return $ssoAuthenticator;
    }
}
