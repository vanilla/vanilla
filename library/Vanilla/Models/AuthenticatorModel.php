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

    /** @var string[] */
    private $authenticatorClasses = [];

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
     * Register an authenticator class.
     * Necessary only for authenticators that are not in an addon.
     *
     * @param string $authenticatorClass
     * @return self
     */
    public function registerAuthenticatorClass(string $authenticatorClass): self {
        $this->authenticatorClasses[$authenticatorClass] = true;

        return $this;
    }

    /**
     * Un-register an authenticator class.
     *
     * @param string $authenticatorClass
     * @return self
     */
    public function unregisterAuthenticatorClass(string $authenticatorClass): self {
        unset($this->authenticatorClasses[$authenticatorClass]);

        return $this;
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

        // Get authenticator classes.
        $authenticatorClasses = array_filter($this->getAuthenticatorClasses(), function($class) use ($authenticatorClassName) {
            return preg_match("/(?:^|\\\\)$authenticatorClassName$/i", $class);
        });

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

        $fullyQualifiedAuthenticationClass = reset($authenticatorClasses);

        if (!is_a($fullyQualifiedAuthenticationClass, Authenticator::class, true)) {
            throw new ServerException("$fullyQualifiedAuthenticationClass is not an ".Authenticator::class);
        }

        try {
            $authenticatorInstance = $this->container->getArgs($fullyQualifiedAuthenticationClass, [$authenticatorID]);
        } catch (Exception $e) {
            $authenticatorInstance = $this->container->get($fullyQualifiedAuthenticationClass);
        }

        return $authenticatorInstance;
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
                    $authenticatorInstance = $this->container->get($authenticatorClass);
                    $authenticators[] = $authenticatorInstance;
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
        $authenticatorClasses = array_unique(
            $this->addonManager->findClasses('*Authenticator') + array_keys($this->authenticatorClasses)
        );

        return array_filter($authenticatorClasses, function($authenticatorClass) {
            $result = is_subclass_of($authenticatorClass, Authenticator::class, true);
            return $result;
        });
    }
}
