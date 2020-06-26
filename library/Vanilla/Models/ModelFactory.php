<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Garden\Container\Container;
use Garden\Container\NotFoundException;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Vanilla\AddonManager;

/**
 * Class ModelFactory
 * @package Vanilla\Models
 */
class ModelFactory implements ContainerInterface {
    private const KEY_INDEX = '@models.index';

    /**
     * @var Container
     */
    private $container;

    /**
     * ModelFactory constructor.
     *
     * @param Container $container
     */
    public function __construct(
        Container $container
    ) {
        $this->container = $container;
    }

    /**
     * Grab the factory instance from the container.
     *
     * This is a useful method to get helpful IDE support.
     *
     * @param ContainerInterface $container
     * @return ModelFactory
     */
    public static function fromContainer(ContainerInterface $container): self {
        return $container->get(static::class);
    }

    /**
     * Get the model associated with a record type.
     *
     * @param string $recordType The record type or alias to lookup.
     * @return \Gdn_Model|Model
     */
    public function get($recordType) {
        $r = $this->container->get(self::key($recordType));
        return $r;
    }

    /**
     * Register a model for lookup.
     *
     * @param string $recordType
     * @param string $className
     * @param string $alias
     * @return self
     */
    public function addModel(string $recordType, string $className, string $alias = ''): self {
        $this
            ->container
            ->rule($className)
            ->addAlias(self::key($recordType));


        if ($alias !== '') {
            $this->container->addAlias(self::key($alias));
        }

        $allModels = $this->getRecordTypes();
        $allModels[$className] = $recordType;
        return $this;
    }

    /**
     * Get the record type for a reference with the proper casing.
     *
     * @param string $ref On of the following: The record type (case insensitive), an alias, or the model class name.
     * @return string Returns the name of the record type.
     * @throws NotFoundException Throws an exception when the ref isn't found.
     */
    public function getRecordType(string $ref): string {
        $r = $this->getRecordTypes()[$ref] ?? null;
        if ($r !== null) {
            return $r;
        }

        $key = self::key($ref);
        if ($this->container->has($key)) {
            $className = $this->container->rule($key)->getAliasOf();

            $r = $this->getRecordTypes()[$className] ?? null;
            if ($r !== null) {
                return $r;
            }
        }
        throw new NotFoundException("Record type not found for: $ref", 404);
    }

    /**
     * Add a record type alias.
     *
     * @param string $recordType
     * @param string $alias
     * @return $this
     */
    public function addAlias(string $recordType, string $alias): self {
        $key = self::key($recordType);

        if ($this->container->hasRule($key)) {
            $this->container->rule($key);

            $this->container->rule($this->container->getAliasOf())->addAlias(self::key($alias));
            return $this;
        } else {
            throw new NotFoundException("Record type was not found: $recordType", 404);
        }
    }

    /**
     * Canonicalize a key that is being registered.
     *
     * @param string $key
     * @return string
     */
    private static function key(string $key): string {
        return '@models.'.strtolower($key);
    }

    /**
     * Check whether or not the record type is recognized.
     *
     * @param string $recordType
     * @return bool
     */
    public function has($recordType) {
        return $this->container->has(self::key($recordType));
    }

    /**
     * Get the index of registered models.
     *
     * @return \ArrayObject
     */
    private function getRecordTypes(): \ArrayObject {
        if ($this->container->has(self::KEY_INDEX)) {
            return $this->container->get(self::KEY_INDEX);
        } else {
            $r = new \ArrayObject();
            $this->container->setInstance(self::KEY_INDEX, $r);
            return $r;
        }
    }

    /**
     * Get all of the models that have been registered.
     *
     * @return array
     */
    public function getAll(): array {
        $index = $this->getRecordTypes();

        $result = [];
        foreach ($index as $className => $recordType) {
            $result[$recordType] = $this->container->get(self::key($recordType));
        }
        return $result;
    }

    /**
     * Get all of the modes that implement an interface or don't implement it.
     *
     * @param string $interface The name of the interface. Start with a "-" for negation.
     * @param bool $include Whether to include (true) or exclude (false) models that implement the interface.
     * @return array
     */
    public function getAllByInterface(string $interface, bool $include = true): array {
        $index = $this->getRecordTypes();

        $result = [];
        foreach ($index as $className => $recordType) {
            $key = self::key($recordType);
            $className = $this->container->rule($key)->getAliasOf();
            if (is_a($className, $interface, true) === $include) {
                $result[$recordType] = $this->container->get(self::key($recordType));
            }
        }
        return $result;
    }
}
