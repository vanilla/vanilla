<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Fixtures;


use Psr\Container\ContainerInterface;
use Psr\Container\Exception\ContainerException;
use Psr\Container\Exception\NotFoundException;

/**
 * A basic container for unit testing.
 */
class Container extends \ArrayObject implements ContainerInterface {
    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @throws NotFoundException  No entry was found for this identifier.
     * @throws ContainerException Error while retrieving the entry.
     *
     * @return mixed Entry.
     */
    public function get($id) {
        if (!isset($this[$id])) {
            $this[$id] = new $id;
        }

        return $this[$id];
    }

    /**
     * Returns true if the container can return an entry for the given identifier. Returns false otherwise.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @return boolean
     */
    public function has($id) {
        return class_exists($id);
    }
}
