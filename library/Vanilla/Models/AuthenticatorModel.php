<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Models;

use Exception;
use Gdn_AuthenticationProviderModel;
use Gdn_Session;
use Garden\Container\Container;
use Garden\Web\Exception\ServerException;
use Garden\Web\Exception\NotFoundException;
use Vanilla\AddonManager;
use Vanilla\Authenticator\Authenticator;
use Vanilla\Authenticator\ShimAuthenticator;

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
    public function __construct(
        AddonManager $addonManager,
        Gdn_AuthenticationProviderModel $authenticationProviderModel,
        Container $container
    ) {
        $this->addonManager = $addonManager;
        $this->authenticationProviderModel = $authenticationProviderModel;
        $this->container = $container;
    }

    /**
     * Register an authenticator class.
     * Necessary only for authenticators that are not in an addon.
     *
     * @throws Exception
     * @param string $authenticatorClass
     * @return self
     */
    public function registerAuthenticatorClass(string $authenticatorClass): self {
        if (!is_a($authenticatorClass, Authenticator::class, true)) {
            throw new Exception($authenticatorClass.' is not an Authenticator.');
        }

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
     *
     * @throws NotFoundException
     * @throws ServerException
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     */
    public function getAuthenticator(string $authenticatorType, string $authenticatorID) {
        if (empty($authenticatorType)) {
            throw new NotFoundException('Authenticator does not exist.');
        }

        $authenticatorClassName = $authenticatorType.'Authenticator';

        /** @var Authenticator $authenticatorInstance */
        $authenticatorInstance = null;

        // Get Authenticator classes.
        $authenticatorClasses = array_filter($this->getAuthenticatorClasses(true), function($class) use ($authenticatorClassName) {
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
     * Get an Authenticator by its ID.
     * Unique authenticators will always be returned first if there is a conflict.
     *
     * @param string $authenticatorID
     * @return Authenticator
     *
     * @throws NotFoundException
     * @throws ServerException
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     */
    public function getAuthenticatorByID(string $authenticatorID) {
        $uniqueAuthenticators = $this->getUniqueAuthenticatorIDs(true);

        // Unique authenticators have type === id
        if (in_array($authenticatorID, $uniqueAuthenticators)) {
            $type = $authenticatorID;
        } else {
            $authenticatorData = $this->authenticationProviderModel->getID($authenticatorID, DATASET_TYPE_ARRAY);
            $type = $authenticatorData['AuthenticationSchemeAlias'] ?? null;
        }

        return $this->getAuthenticator($type, $authenticatorID);
    }

    /**
     * Get Authenticator instances.
     *
     * @param bool $includeShims
     * @return Authenticator[]
     */
    public function getAuthenticators($includeShims = false): array {
        $authenticatorClasses = $this->getAuthenticatorClasses($includeShims);
        $authenticators = [];
        foreach ($authenticatorClasses as $authenticatorClass) {
            try {
                $authenticatorsInfo = $this->authenticationProviderModel->getProvidersByScheme($authenticatorClass::getType());
            } catch (Exception $e) {}

            /** @var Authenticator $authenticatorInstance */
            $authenticatorInstance = null;

            if ($authenticatorsInfo) {
                foreach ($authenticatorsInfo as $authenticatorInfo) {
                    // Check if the container can find the Authenticator.
                    try {
                        $authenticatorInstance = $this->getAuthenticator($authenticatorInfo['AuthenticationSchemeAlias'], $authenticatorInfo['AuthenticationKey']);
                        $authenticators[] = $authenticatorInstance;
                    } catch (Exception $e) {
                        throw new ServerException('Error while trying to instanciate authenticator '.$authenticatorClass, 500, ['AuthenticatorError' => $e]);
                    }
                    $authenticatorInstance = null;
                }
            } else {
                try {
                    $authenticatorInstance = $this->container->get($authenticatorClass);
                    $authenticators[] = $authenticatorInstance;
                } catch (Exception $e) {
                    throw new ServerException('Error while trying to instanciate authenticator '.$authenticatorClass, 500, ['AuthenticatorError' => $e]);
                }

            }
        }

        return $authenticators;
    }

    /**
     * Get the list of ID of unique authenticators.
     *
     * @param bool $includeShims
     * @return array
     */
    public function getUniqueAuthenticatorIDs($includeShims = false): array {
        $ids = [];
        foreach ($this->getAuthenticatorClasses($includeShims) as $class) {
            /** @var Authenticator $class */
            if ($class::isUnique()) {
                $ids[] = $class::getType();
            }
        }

        return $ids;
    }

    /**
     * Get available Authenticator classes.
     *
     * @param bool $includeShims
     * @return array
     */
    public function getAuthenticatorClasses($includeShims = false): array {
        $authenticatorClasses = array_unique(
            array_merge($this->addonManager->findClasses('*Authenticator'), array_keys($this->authenticatorClasses))
        );

        return array_filter($authenticatorClasses, function($authenticatorClass) use ($includeShims) {
            if (!$includeShims && is_subclass_of($authenticatorClass, ShimAuthenticator::class, true)) {
                return false;
            }

            $result = is_subclass_of($authenticatorClass, Authenticator::class, true);
            return $result;
        });
    }
}
