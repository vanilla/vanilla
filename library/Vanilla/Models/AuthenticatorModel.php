<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Models;


use Exception;
use Gdn_AuthenticationProviderModel;
use Garden\Container\Container;
use Garden\Web\Exception\ServerException;
use Garden\Web\Exception\NotFoundException;
use Vanilla\AddonManager;
use Vanilla\Authenticator\Authenticator;

class AuthenticatorModel {

    /** @var Container */
    private $container;

    /** @var Gdn_AuthenticationProviderModel */
    private $authenticationProviderModel;

    /** @var AddonManager */
    private $addonManager;

    /**
     * AuthenticatorModel constructor.
     *
     * @param AddonManager $addonManager
     * @param Gdn_AuthenticationProviderModel $authenticationProviderModel
     * @param Container $container
     */
    public function __construct(AddonManager $addonManager, Gdn_AuthenticationProviderModel $authenticationProviderModel, Container $container) {
        $this->addonManager = $addonManager;
        $this->authenticationProviderModel = $authenticationProviderModel;
        $this->container = $container;
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
    public function getAuthenticator(string $authenticatorType, string $authenticatorID) {
        if (empty($authenticatorType)) {
            throw new NotFoundException('Authenticator does not exist.');
        }

        $authenticatorClassName = $authenticatorType.'Authenticator';

        /** @var Authenticator $authenticatorInstance */
        $authenticatorInstance = null;

        // Check if the container can find the authenticator.
        try {
            $authenticatorInstance = $this->container->getArgs($authenticatorClassName, [$authenticatorID]);

            if (!is_a($authenticatorInstance, Authenticator::class, true)) {
                throw new ServerException(
                    "\"$authenticatorClassName\" is not an ".Authenticator::class,
                    500
                );
            }

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
     * Get an authenticator.
     *
     * @param string $authenticatorID
     * @return Authenticator
     */
    public function getAuthenticatorByID(string $authenticatorID) {
        $authenticatorData = $this->authenticationProviderModel->getID($authenticatorID, DATASET_TYPE_ARRAY);

        return $this->getAuthenticator($authenticatorData['AuthenticationSchemeAlias'] ?? null, $authenticatorID);
    }

    /**
     * Get authenticator instances.
     *
     * @return Authenticator[]
     */
    public function getAuthenticators() {
        $authenticatorClasses = $this->getAuthenticatorClasses();
        $authenticators = [];
        foreach ($authenticatorClasses as $authenticatorClass) {
            try {
                $authenticatorsInfo = $this->authenticationProviderModel->getProvidersByScheme($authenticatorClass::getType());
            } catch (Exception $e) {}

            /** @var Authenticator $authenticatorInstance */
            $authenticatorInstance = null;

            if ($authenticatorsInfo) {
                foreach ($authenticatorsInfo as $authenticatorInfo) {
                    // Check if the container can find the authenticator.
                    try {
                        $authenticatorInstance = $this->getAuthenticator($authenticatorInfo['AuthenticationSchemeAlias'], $authenticatorInfo['AuthenticationKey']);
                        $authenticators[] = $authenticatorInstance;
                    } catch (Exception $e) {}
                    $authenticatorInstance = null;
                }
            } else {
                try {
                    $this->container->get($authenticatorClassName);
                } catch (Exception $e) {}
            }
        }

        return $authenticators;
    }

    /**
     * Get available authenticator classes.
     *
     * @return array
     */
    public function getAuthenticatorClasses() {
        $authenticatorClasses = $this->addonManager->findClasses('*Authenticator');

        return array_filter($authenticatorClasses, function($authenticatorClass) {
            return is_a($authenticatorClass, Authenticator::class, true);
        });
    }
}
