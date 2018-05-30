<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Models;

use Exception;
use Garden\Container\Container;
use Garden\Schema\Validation;
use Garden\Schema\ValidationException;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\ServerException;
use Gdn_AuthenticationProviderModel;
use Gdn_Locale;
use Vanilla\AddonManager;
use Vanilla\Authenticator\Authenticator;
use Vanilla\Authenticator\ShimAuthenticator;
use Vanilla\Authenticator\SSOAuthenticator;
use Vanilla\Utility\ModelUtils;

class AuthenticatorModel {

    /** @var Gdn_AuthenticationProviderModel */
    protected $authenticationProviderModel;

    /** @var AddonManager */
    private $addonManager;

    /** @var string[] */
    private $authenticatorClasses = [];

    /** @var Container */
    private $container;

    /** @var \Gdn_Locale */
    private $locale;

    /**
     * Whether to try to load authenticator data from the memory or from the database.
     * Used when saving authenticators to that the instance gets the data that is being saved.
     * @var bool
     */
    private $loadFromMemory = false;

    /** @var array */
    private $memoryData = [];

    private $authenticatorInstances = [];

    /** @var array Map of DB fields to Authenticator fields. */
    private $dbToAuthenticator = [
        'AuthenticationKey' => 'authenticatorID',
        'AuthenticationSchemeAlias' => 'type',
        'Name' => 'name',
        'RegisterUrl' => 'registerUrl',
        'SignInUrl' => 'signInUrl',
        'SignOutUrl' => 'signOutUrl',
        'Attributes' => 'attributes',
        'Active' => 'active',
    ];

    /** @var array Flipped version of $dbToAuthenticator. */
    private $authenticatorToDb;

    /**
     * AuthenticatorModel constructor.
     *
     * @param AddonManager $addonManager
     * @param Gdn_Locale $locale
     * @param Gdn_AuthenticationProviderModel $authenticationProviderModel
     * @param Container $container
     */
    public function __construct(
        AddonManager $addonManager,
        Gdn_Locale $locale,
        Gdn_AuthenticationProviderModel $authenticationProviderModel,
        Container $container
    ) {
        $this->authenticatorToDb = array_flip($this->dbToAuthenticator);

        $this->addonManager = $addonManager;
        $this->authenticationProviderModel = $authenticationProviderModel;
        $this->container = $container;
        $this->locale = $locale;
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
     *
     * @return Authenticator
     *
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     * @throws \Garden\Web\Exception\ClientException
     * @throws \Garden\Web\Exception\NotFoundException
     * @throws \Garden\Web\Exception\ServerException
     */
    public function getAuthenticator(string $authenticatorType, string $authenticatorID) {
        if (empty($authenticatorType)) {
            throw new NotFoundException('Authenticator does not exist.');
        }

        if (isset($this->authenticatorInstances[$authenticatorType][$authenticatorID])) {
            return $this->authenticatorInstances[$authenticatorType][$authenticatorID];
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
            throw new ClientException(
                "Multiple authenticator found with the same type ($authenticatorType).",
                409,
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

        $this->authenticatorInstances[$authenticatorType][$authenticatorID] = $authenticatorInstance;

        return $authenticatorInstance;
    }

    /**
     * Get an Authenticator by its ID.
     * Unique authenticators will always be returned first if there is a conflict.
     *
     * @param string $authenticatorID
     *
     * @return Authenticator
     *
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     * @throws \Garden\Web\Exception\ClientException
     * @throws \Garden\Web\Exception\NotFoundException
     * @throws \Garden\Web\Exception\ServerException
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
     *
     * @return Authenticator[]
     * @throws \Garden\Web\Exception\ServerException
     */
    public function getAuthenticators($includeShims = false): array {
        $authenticatorClasses = $this->getAuthenticatorClasses($includeShims);
        $authenticators = [];
        foreach ($authenticatorClasses as $authenticatorClass) {
            $authenticatorsInfo = null;
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
                // Only try this for non SSOAuthenticator since we should have info in the DB for these.
                if (!is_a($authenticatorClass, SSOAuthenticator::class, true)) {
                    try {
                        $authenticatorInstance = $this->getAuthenticator($authenticatorClass::getType(), $authenticatorClass::getType());
                        $authenticators[] = $authenticatorInstance;
                    } catch (Exception $e) {
                        throw new ServerException('Error while trying to instanciate authenticator '.$authenticatorClass, 500, ['AuthenticatorError' => $e]);
                    }
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

    /**
     * @param string $authenticatorType
     *
     * @return string|bool
     */
    private function getAuthenticatorClassFromType(string $authenticatorType) {
        foreach ($this->getAuthenticatorClasses() as $currentAuthenticatorClass) {
            /** @var $currentAuthenticatorClass Authenticator */
            if (strtolower($currentAuthenticatorClass::getType()) === strtolower($authenticatorType)) {
                return $currentAuthenticatorClass;
            }
        }

        return false;
    }

    /**
     * Create an SSOAuthenticator instance.
     *
     * @param array $data Data matching a specific {@link \Authenticator::getAuthenticatorSchema()}
     *
     * @return bool|\Vanilla\Authenticator\SSOAuthenticator
     *
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     * @throws \Garden\Schema\ValidationException
     * @throws \Garden\Web\Exception\ClientException
     * @throws \Garden\Web\Exception\NotFoundException
     * @throws \Garden\Web\Exception\ServerException
     */
    public function createSSOAuthenticatorInstance(array $data) {
        $validation = new Validation();

        $authenticatorClass = null;
        $authenticatorType = $data['type'] ?? false;
        if (!$authenticatorType) {
            $validation->addError('type', '{field} is required.', ['status' => 422]);
        } else {
            $authenticatorClass = $this->getAuthenticatorClassFromType($authenticatorType);
        }

        if (!is_a($authenticatorClass, SSOAuthenticator::class, true)) {
            throw new Exception($authenticatorClass.' is not an '.SSOAuthenticator::class);
        }

        $authenticatorID = $data['authenticatorID'] ?? false;
        if (!$authenticatorID) {
            $validation->addError('authenticatorID', '{field} is required.', ['status' => 422]);
        }

        if (!$validation->getErrorCount()) {
            $authenticatorData = $this->authenticationProviderModel->getWhere([
                'AuthenticationKey' => $authenticatorID
            ])->firstRow(DATASET_TYPE_ARRAY);

            if ($authenticatorData) {
                $validation->addError('authenticatorID', '{field} already exists.', ['status' => 422]);
            }
        }

        if ($validation->getErrorCount()) {
            throw new ValidationException($validation);
        }


        $this->loadFromMemory = true;
        $this->memoryData[$authenticatorID] = $data;
        try {
            // The authenticator will load the data assigned to memoryData instead of querying the database!
            $authenticator = $this->getAuthenticator($authenticatorType, $authenticatorID);
        } finally {
            unset($this->memoryData[$authenticatorID]);
            $this->loadFromMemory = false;
        }

        // Force a save at this point.
        return $this->saveSSOAuthenticatorData($authenticator) ? $authenticator : false;
    }

    /**
     * Get an SSOAuthenticator database data.
     *
     * @param string $authenticatorType
     * @param string $authenticatorID
     *
     * @return bool|array Authenticator's data or false on failure.
     *
     * @throws \Garden\Schema\ValidationException
     * @throws \Garden\Web\Exception\NotFoundException
     */
    public function getSSOAuthenticatorData(string $authenticatorType, string $authenticatorID) {
        $authenticatorClass = $this->getAuthenticatorClassFromType($authenticatorType);

        if (!$authenticatorClass) {
            throw new NotFoundException('Authenticator class was not found.');
        }

        /** @var \Garden\Schema\Schema $authenticatorSchema */
        $authenticatorSchema = $authenticatorClass::getAuthenticatorSchema();
        $schemaProperties = $authenticatorSchema->getSchemaArray()['properties'];

        if ($this->loadFromMemory) {
            if ($this->memoryData[$authenticatorID]) {
                $authenticatorData = $this->memoryData[$authenticatorID];
                $authenticatorData['attributes'] = $authenticatorData['attributes'] ?? [];

                // Move properties into attributes
                $diffs = array_diff_key($authenticatorData, $schemaProperties);
                foreach($diffs as $key => $value) {
                    $authenticatorData['attributes'][$key] = $value;
                    unset($authenticatorData[$key]);
                }
            } else {
                $authenticatorData = false;
            }
        } else {
            $authenticatorData = $this->authenticationProviderModel->getWhere([
                'AuthenticationKey' => $authenticatorID,
                'AuthenticationSchemeAlias' => $authenticatorType,
            ])->firstRow(DATASET_TYPE_ARRAY);

            if ($authenticatorData) {
                $authenticatorData['Attributes'] = dbdecode($authenticatorData['Attributes']);

                // Filter out unused fields.
                $authenticatorData = array_intersect_key($authenticatorData, $this->dbToAuthenticator);

                // Convert array keys.
                foreach($schemaProperties as $name => $definition) {
                    if (array_key_exists($name, $this->authenticatorToDb) && array_key_exists($this->authenticatorToDb[$name], $authenticatorData)) {
                        $authenticatorData[$name] = $authenticatorData[$this->authenticatorToDb[$name]];
                        unset($authenticatorData[$this->authenticatorToDb[$name]]);
                    }
                }

                // Move property out of attributes.
                foreach ($schemaProperties as $name => $definition) {
                     if (!array_key_exists($name, $authenticatorData) && array_key_exists($name, $authenticatorData['attributes'])) {
                        $authenticatorData[$name] = $authenticatorData['attributes'][$name];
                        unset($authenticatorData['attributes'][$name]);
                    }
                }
            } else {
                $authenticatorData = false;
            }
        }

        if (!$authenticatorData) {
            return false;
        }

        return $authenticatorSchema->validate($authenticatorData, true);
    }

    /**
     * Save an SSOAuthenticator's data into the database.
     * Note that SSOAuthenticators save their data into the DB themselves (By using this function).
     *
     * @param \Vanilla\Authenticator\SSOAuthenticator $authenticator
     *
     * @return bool
     * @throws \Garden\Schema\ValidationException
     */
    public function saveSSOAuthenticatorData(SSOAuthenticator $authenticator) {
        $data = $authenticator->getAuthenticatorInfo();

        foreach ($this->authenticatorToDb as $from => $to) {
            if (array_key_exists($from, $data)) {
                $data[$to] = $data[$from];
                unset($data[$from]);
            }
        }


        // Move everything out of attributes since AuthenticationProviderModel will put anything not matching the schema back in Attributes.
        // (It's gonna overwrite it)
        $data = array_merge($data, $data['Attributes']);
        unset($data['Attributes']);

        if ($this->authenticationProviderModel->save($data) === false) {
            ModelUtils::validationResultToValidationException($this->authenticationProviderModel, $this->locale);
        }

        return true;
    }

    /**
     * Delete an SSOAuthenticator instance.
     *
     * @param \Vanilla\Authenticator\SSOAuthenticator $authenticator
     */
    public function deleteSSOAuthenticatorInstance(SSOAuthenticator $authenticator) {
        $this->authenticationProviderModel->delete([
            'AuthenticationKey' => $authenticator->getID(),
            'AuthenticationSchemeAlias' => $authenticator::getType(),
        ]);

        unset($this->authenticatorInstances[$authenticator->getType()][$authenticator->getID()]);
    }
}
