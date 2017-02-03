<?php
/**
 * Gdn_Factory.
 *
 * @author Todd Burry <todd@vanillaforums.com>
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
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
    protected $_Objects = array();

    /** @var array The property dependancies for the factory. */
    protected $_Dependencies = array();

    /**
     * @var \Garden\Container\Container The container used to store the class information.
     */
    private $container;

    /**
     *
     */
    public function __construct(Garden\Container\Container $container = null) {
        deprecated('Gdn_Factory', 'Garden\Container\Container');
        register_shutdown_function(array($this, 'Cleanup'));

        $this->container = $container !== null ? $container : new Garden\Container\Container();
    }

    /**
     * Checks whether or not a factory alias exists.
     *
     * @param string $Alias The alias of the factory to check for.
     * @return boolean Whether or not a factory definintion exists.
     */
    public function exists($Alias) {
        return $this->container->hasRule($Alias);
    }

    /**
     * Creates an object with mapped to the name.
     *
     * @param string $Alias The class code of the object to create.
     * @param array|null $Args The arguments to pass to the constructor of the object.
     */
    public function factory($Alias, $Args = null) {
        try {
            if (!$this->container->has($Alias) && $this->container->has("Gdn_$Alias")) {
                $Alias = "Gdn_$Alias";
            }

            $result = $this->container->getArgs($Alias, (array)$Args);
            return $result;
        } catch (\Garden\Container\NotFoundException $ex) {
            return null;
        }
    }

    /**
     * Install a class to the factory.
     *
     * @param string $Alias An alias for the class that will be used to retreive instances of it.
     * @param string $ClassName The actual name of the class.
     * @param string $Path The path to the class' file. You can prefix the path with ~ to start at the application root (PATH_ROOT).
     * @param string $FactoryType The way objects will be instantiated for the class. One of (Gdn::FactoryInstance, Gdn::FactoryPrototype, Gdn::FactorySingleton).
     * <ul>
     *  <li><b>Gdn::FactoryInstance</b>: A new instance of the class will be created when the factory is called.</li>
     *  <li><b>Gdn::FactoryPrototype</b>: A clone of a prototype will be created when the factory is called.
     *   The prototype must be passed into the $Data argument.</li>
     *  <li><b>Gdn::FactorySingleton</b>: A singleton instance, stored in the factory will be returned when the factory is called.
     *   The instance can be passed to the $Data argument on installation, or it will be lazy created when first accessed.
     *   You can also pass an array to $Data and it will be used as the arguments for the lazy construction.</li>
     * </ul>
     */
    public function install($Alias, $ClassName, $Path = '', $FactoryType = Gdn::FactorySingleton, $Data = null) {
        $FactoryType = ucfirst($FactoryType);
        if (!in_array($FactoryType, array(Gdn::FactoryInstance, Gdn::FactoryPrototype, Gdn::FactorySingleton, Gdn::FactoryRealSingleton))) {
            throw new Exception(sprintf('$FactoryType must be one of %s, %s, %s, %s.', Gdn::FactoryInstance, Gdn::FactoryPrototype, Gdn::FactorySingleton, Gdn::FactoryRealSingleton));
        }
        $this->container
            ->rule($Alias);

        if ($Alias !== $ClassName) {
            $this->container->setClass($ClassName);
        }

        // Set the other data of the object.
        switch ($FactoryType) {
            case Gdn::FactoryInstance:
                $this->container->setShared(false);
                break;
            case Gdn::FactoryPrototype:
                if (is_null($Data)) {
                    throw new Exception('You must supply a prototype object when installing an object of type Prototype.');
                }
                $this->container
                    ->setShared(false)
                    ->setFactory(function () use ($Data) {
                        $r = clone $Data;
                        return $r;
                    });
                break;
            case Gdn::FactorySingleton:
                if (is_array($Data)) {
                    $this->container
                        ->setShared(true)
                        ->setConstructorArgs($Data);
                } elseif ($Data !== null) {
                    $this->container->setInstance($Alias, $Data);
                }
                break;
            case Gdn::FactoryRealSingleton:
                $this->container
                    ->setShared(true)
                    ->setFactory([$ClassName, $Data]);
                break;
            default:
                throw Exception();
        }
    }

    /**
     * Install a dependency for the factory.
     *
     * This method provides support for simple dependency injection.
     * When an object with dependencies is created then the factory will call inline{@link Gdn_Factory::Factory()}
     * for each dependency and set the object properties before returning it.
     * Those dependencies can also have their own dependencies which will all be set when the object is returned.
     *
     * @param string $Alias The alias of the class that will have the dependency.
     * @param string $PropertyName The name of the property on the class that will have the dependency.
     * @param string $SourceAlias The alias of the class that will provide the value of the property when objects are instantiated.
     *
     */
    public function installDependency($Alias, $PropertyName, $SourceAlias) {
        if (!array_key_exists($Alias, $this->_Dependencies)) {
            $this->_Dependencies[$Alias] = array($PropertyName => $SourceAlias);
        } else {
            $this->_Dependencies[$Alias][$PropertyName] = $SourceAlias;
        }
    }

    /**
     *
     *
     * @param $Alias
     * @param $Object
     * @throws Exception
     */
    private function setDependancies($Alias, $Object) {
        // Set any dependancies for the object.
        if (array_key_exists($Alias, $this->_Dependencies)) {
            $Dependencies = $this->_Dependencies[$Alias];
            foreach ($Dependencies as $PropertyName => $SourceAlias) {
                $PropertyValue = $this->factory($SourceAlias);
                $Object->$PropertyName = $PropertyValue;
            }
        }
    }

    /**
     * Uninstall a factory definition.
     *
     * @param string $Alias The object alias to uninstall.
     */
    public function uninstall($Alias) {
        if (array_key_exists($Alias, $this->_Objects)) {
            unset($this->_Objects[$Alias]);
        }
    }

    /**
     * Clean up the factory's objects
     *
     * Also calls 'Cleanup' on compatible instances.
     */
    public function cleanup() {
        foreach ($this->_Objects as $FactoryInstanceName => &$FactoryInstance) {
            if (!is_array($FactoryInstance)) {
                continue;
            }
            $FactoryType = $FactoryInstance['FactoryType'];

            if (!array_key_exists($FactoryType, $FactoryInstance)) {
                continue;
            }
            $FactoryObject = &$FactoryInstance[$FactoryType];

            if (method_exists($FactoryObject, 'Cleanup')) {
                $FactoryObject->cleanup();
            }

            unset($FactoryInstance);
        }
    }

    /**
     * Uninstall a dependency definition.
     *
     * @param string $Alias The object alias to uninstall the dependency for.
     * @param string $PropertyName The name of the property dependency to uninstall.
     * Note: If $PropertyName is null then all of the dependencies will be uninstalled for $Alias.
     */
    public function uninstallDependency($Alias, $PropertyName = null) {
        if (array_key_exists($Alias, $this->_Dependencies)) {
            if (is_null($PropertyName)) {
                unset($this->_Dependencies[$Alias]);
            } elseif (array_key_exists($PropertyName, $this->_Dependencies[$Alias])) {
                unset($this->_Dependencies[$Alias][$PropertyName]);
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
