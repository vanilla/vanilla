<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

namespace Vanilla\Models;

use Interop\Container\ContainerInterface;
use UserModel;
use Vanilla\AddonManager;
use Vanilla\SSOAuthenticator;

/**
 * Class SSOModel
 */
class SSOModel {

    /** @var AddonManager */
    private $addonManager;

    /** @var Container */
    private $container;

    /** @var UserModel */
    private $userModel;

    /**
     * SSOModel constructor.
     *
     * @param AddonManager $addonManager
     * @param ContainerInterface $container
     * @param UserModel $userModel
     */
    public function __construct(
        AddonManager $addonManager,
        ContainerInterface $container,
        UserModel $userModel
    ) {
        $this->addonManager = $addonManager;
        $this->container = $container;
        $this->userModel = $userModel;
    }

    /**
     * Authenticate a user using a SSOUserInfo object.
     *
     * @throws Exception
     * @param SSOUserInfo $ssoUserInfo
     * @return array The user's data on success or false on failure.
     */
    public function sso(SSOUserInfo $ssoUserInfo) {
        $userData = $this->userModel->getAuthentication($ssoUserInfo['uniqueID'], $ssoUserInfo['authenticatorID']);

        if (empty($userData)) {
            $userData = false;
        }

        return $userData;
    }

    /**
     * Get an SSOAuthenticator
     *
     * @throws Exception
     *
     * @param $authenticatorType
     * @param $authenticatorID
     * @return SSOAuthenticator
     */
    public function getSSOAuthenticator($authenticatorType, $authenticatorID) {
        if (empty($authenticatorType)) {
            throw new NotFoundException();
        }

        $authenticatorClassName = $authenticatorType.'Authenticator';
        $authenticatorClasses = $this->addonManager->findClasses("*/$authenticatorClassName");

        if (empty($authenticatorClasses)) {
            throw new NotFoundException($authenticatorClasses);
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

        /** @var SSOAuthenticator $authenticatorInstance */
        if (!empty($authenticatorID)) {
            $authenticatorInstance = $this->container->getArgs($authenticatorClassName, [$authenticatorID]);
        } else {
            $authenticatorInstance = $this->container->get($authenticatorClassName);
        }

        return $authenticatorInstance;
    }
}
