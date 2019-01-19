<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Garden;

use Gdn;

/**
 * Trait for fetching a value from the container and statically caching the result.
 *
 * __When not to use this__
 * Don't use this in an instance based class. Use dependency injection instead.
 *
 * __When to use this__
 * This trait is ideal for refactoring static classes to call out to instance based classes.
 * You don't necessarily want to make all of the instance classes singletons, but you don't want to make a new instance
 * at every usage site.
 */
trait StaticCacheContainerTrait {

    private static $cachedIntances = [];

    /**
     * Calculates value for particular key (overwrite f() of StaticCache trait)
     *
     * @param string $className Key to store
     *
     * @return mixed An instance of the selected class.
     */
    protected static function getCachedInstance(string $className) {
        $cachedInstance = self::$cachedIntances[$className] ?? null;
        if (!$cachedInstance) {
            $cachedInstance = Gdn::getContainer()->get($className);
            self::$cachedIntances[$className] = $cachedInstance;
        }

        return $cachedInstance;
    }
}
