<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla;

/**
 * A trait for providing mapping between classes and there aliases.
 *
 * Due to this needing to be static for performance reasons this was implemented as a trait instead of a base class.
 *
 * Provided:
 * - mapping of aliases to classes (many to one).
 * - mapping of classes to aliases (one to many).
 * - an autoloader for usage with spl_autoload_register to autoload the original class for an alias.
 * - a utility for creating alias from the mapping defined here.
 */
trait AliasProviderTrait {

    /** @var array An array of className => AliasName[] */
    /**
     * @inheritdoc
     */
    private static $classToAliases = null;

    /** @var array An array of alias name => class name. Lazily populated */
    private static $aliasesToClasses = null;

    /**
     * A method to provide the aliases for the trait to work with.
     *
     * There can be multiple aliases for one class name.
     *
     * @return array A mapping of Class Name => AliasName[]
     */
    abstract protected static function provideAliases(): array;

    /**
     * Get the map of class => alias[]
     *
     * @return array
     */
    private static function getClassToAliases(): array {
        if (self::$classToAliases === null) {
            self::$classToAliases = static::provideAliases();
        }

        return self::$classToAliases;
    }

    /**
     * Get the map of alias => class.
     *
     * @return array
     */
    private static function getAliasesToClasses(): array {
        if (self::$aliasesToClasses === null) {
            self::$aliasesToClasses = [];
            foreach (static::getClassToAliases() as $className => $aliases) {
                foreach ($aliases as $alias) {
                    self::$aliasesToClasses[$alias] = $className;
                }
            }
        }

        return self::$aliasesToClasses;
    }

    /**
     * Create the class alias for one of the class registered here.
     *
     * @param string $className
     * @throws \Exception When attempting to create an alias that has no mappings.
     */
    public static function createAliases(string $className) {
        foreach (static::getAliases($className) as $alias) {
            class_alias($className, $alias);
        }
    }

    /**
     * Get the aliases defined for a given class.
     *
     * @param string $className
     * @return array
     * @throws \Exception If you try and fetch aliases for a class that is not listed.
     */
    public static function getAliases(string $className): array {
        $classes = static::getClassToAliases();
        if (!isset($classes[$className])) {
            throw new \Exception("Could not find any registered aliases for class $className");
        }

        return $classes[$className];
    }

    /**
     * An autoload function for use with spl_autoload_register.
     *
     * This loads the original class for an alias defined here.
     * The class will have the responsibility of declaring the alias.
     * That should happen immediately after the class declaration.
     *
     * @param string $aliasName the class name to try and load.
     */
    public static function autoload(string $aliasName) {
        $aliases = static::getAliasesToClasses();
        if (isset($aliases[$aliasName])) {
            $newName = $aliases[$aliasName];
            trigger_error("The className $aliasName has been renamed. Use $newName instead", E_USER_DEPRECATED);
            class_exists($newName, true);
        }
    }
}
