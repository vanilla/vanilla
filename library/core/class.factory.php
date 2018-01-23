<?php
/**
 * Gdn_Factory.
 *
 * @author Todd Burry <todd@vanillaforums.com>
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

/**
 * Object factory
 *
 * A factory used to create most objects in the core library.
 * If you have your own object that implements some base portion of the library you can install it in the factory
 * make sure your own object has the same properties/methods as the core object and then install it into this factory.
 */
class Gdn_Factory {

    /** @var array The object definitions for the factory. */
    protected $_Objects = [];

    /** @var array The property dependancies for the factory. */
    protected $_Dependencies = [];

    /**
     * @var \Garden\Container\Container The container used to store the class information.
     */
    private $container;

    /**
     *
     */
    public function __construct(Garden\Container\Container $container = null) {
        deprecated('Gdn_Factory', 'Garden\Container\Container');
        register_shutdown_function([$this, 'Cleanup']);

        $this->container = $container !== null ? $container : new Garden\Container\Container();
    }

    /**
     * Checks whether or not a factory alias exists.
     *
     * @param string $alias The alias of the factory to check for.
     * @return boolean Whether or not a factory definintion exists.
     */
    public function exists($alias) {
        return $this->container->hasRule($alias);
    }

    /**
     * Creates an object with mapped to the name.
     *
     * @param string $alias The class code of the object to create.
     * @param array|null $args The arguments to pass to the constructor of the object.
     */
    public function factory($alias, $args = null) {
        try {
            if (!$this->container->has($alias) && $this->container->has("Gdn_$alias")) {
                $alias = "Gdn_$alias";
            }

            $result = $this->container->getArgs($alias, (array)$args);
            return $result;
        } catch (\Garden\Container\NotFoundException $ex) {
            return null;
        }
    }

    /**
     * Install a class to the factory.
     *
     * @param string $alias An alias for the class that will be used to retreive instances of it.
     * @param string $className The actual name of the class.
     * @param string $path The path to the class' file. You can prefix the path with ~ to start at the application root (PATH_ROOT).
     * @param string $factoryType The way objects will be instantiated for the class. One of (Gdn::FactoryInstance, Gdn::FactoryPrototype, Gdn::FactorySingleton).
     * <ul>
     *  <li><b>Gdn::FactoryInstance</b>: A new instance of the class will be created when the factory is called.</li>
     *  <li><b>Gdn::FactoryPrototype</b>: A clone of a prototype will be created when the factory is called.
     *   The prototype must be passed into the $data argument.</li>
     *  <li><b>Gdn::FactorySingleton</b>: A singleton instance, stored in the factory will be returned when the factory is called.
     *   The instance can be passed to the $data argument on installation, or it will be lazy created when first accessed.
     *   You can also pass an array to $data and it will be used as the arguments for the lazy construction.</li>
     * </ul>
     */
    public function install($alias, $className, $path = '', $factoryType = Gdn::FactorySingleton, $data = null) {
        $factoryType = ucfirst($factoryType);
        if (!in_array($factoryType, [Gdn::FactoryInstance, Gdn::FactoryPrototype, Gdn::FactorySingleton, Gdn::FactoryRealSingleton])) {
            throw new Exception(sprintf('$FactoryType must be one of %s, %s, %s, %s.', Gdn::FactoryInstance, Gdn::FactoryPrototype, Gdn::FactorySingleton, Gdn::FactoryRealSingleton));
        }
        $this->container
            ->rule($alias);

        if ($alias !== $className) {
            $this->container->setClass($className);
        }

        // Set the other data of the object.
        switch ($factoryType) {
            case Gdn::FactoryInstance:
                $this->container->setShared(false);
                break;
            case Gdn::FactoryPrototype:
                if (is_null($data)) {
                    throw new Exception('You must supply a prototype object when installing an object of type Prototype.');
                }
                $this->container
                    ->setShared(false)
                    ->setFactory(function () use ($data) {
                        $r = clone $data;
                        return $r;
                    });
                break;
            case Gdn::FactorySingleton:
                $this->container->setShared(true);
                if (is_array($data)) {
                    $this->container->setConstructorArgs($data);
                } elseif ($data !== null) {
                    $this->container->setInstance($alias, $data);
                }
                break;
            case Gdn::FactoryRealSingleton:
                $this->container
                    ->setShared(true)
                    ->setFactory([$className, $data]);
                break;
            default:
                throw exception();
        }
    }

    /**
     * Install a dependency for the factory.
     *
     * This method provides support for simple dependency injection.
     * When an object with dependencies is created then the factory will call inline{@link Gdn_Factory::factory()}
     * for each dependency and set the object properties before returning it.
     * Those dependencies can also have their own dependencies which will all be set when the object is returned.
     *
     * @param string $alias The alias of the class that will have the dependency.
     * @param string $propertyName The name of the property on the class that will have the dependency.
     * @param string $sourceAlias The alias of the class that will provide the value of the property when objects are instantiated.
     *
     */
    public function installDependency($alias, $propertyName, $sourceAlias) {
        if (!array_key_exists($alias, $this->_Dependencies)) {
            $this->_Dependencies[$alias] = [$propertyName => $sourceAlias];
        } else {
            $this->_Dependencies[$alias][$propertyName] = $sourceAlias;
        }
    }

    /**
     *
     *
     * @param $alias
     * @param $object
     * @throws Exception
     */
    private function setDependancies($alias, $object) {
        // Set any dependancies for the object.
        if (array_key_exists($alias, $this->_Dependencies)) {
            $dependencies = $this->_Dependencies[$alias];
            foreach ($dependencies as $propertyName => $sourceAlias) {
                $propertyValue = $this->factory($sourceAlias);
                $object->$propertyName = $propertyValue;
            }
        }
    }

    /**
     * Uninstall a factory definition.
     *
     * @param string $alias The object alias to uninstall.
     */
    public function uninstall($alias) {
        if (array_key_exists($alias, $this->_Objects)) {
            unset($this->_Objects[$alias]);
        }
    }

    /**
     * Clean up the factory's objects
     *
     * Also calls 'Cleanup' on compatible instances.
     */
    public function cleanup() {
        foreach ($this->_Objects as $factoryInstanceName => &$factoryInstance) {
            if (!is_array($factoryInstance)) {
                continue;
            }
            $factoryType = $factoryInstance['FactoryType'];

            if (!array_key_exists($factoryType, $factoryInstance)) {
                continue;
            }
            $factoryObject = &$factoryInstance[$factoryType];

            if (method_exists($factoryObject, 'Cleanup')) {
                $factoryObject->cleanup();
            }

            unset($factoryInstance);
        }
    }

    /**
     * Uninstall a dependency definition.
     *
     * @param string $alias The object alias to uninstall the dependency for.
     * @param string $propertyName The name of the property dependency to uninstall.
     * Note: If $propertyName is null then all of the dependencies will be uninstalled for $alias.
     */
    public function uninstallDependency($alias, $propertyName = null) {
        if (array_key_exists($alias, $this->_Dependencies)) {
            if (is_null($propertyName)) {
                unset($this->_Dependencies[$alias]);
            } elseif (array_key_exists($propertyName, $this->_Dependencies[$alias])) {
                unset($this->_Dependencies[$alias][$propertyName]);
            }
        }
    }

    /**
     * Get all currently defined factories
     *
     * @return array
     */
    public function all() {
        return $this->_Objects;
    }

    /**
     * Search installed factories by fnmatch
     *
     * @param string $search fnmatch-compatible search string
     * @return array list of matching definitions
     */
    public function search($search) {
        $arr = array_map(function ($ak, $av) use ($search) {
            return fnmatch($search, $ak, FNM_CASEFOLD) ? [$ak => $av] : null;
        }, array_keys($this->_Objects), array_values($this->_Objects));

        $arr = array_filter($arr);
        $arr = array_reduce($arr, function ($carry, $av) {
            $keys = array_keys($av);
            $key = array_pop($keys);
            $carry[$key] = $av[$key];
            return $carry;
        }, []);

        return $arr;
    }
}
