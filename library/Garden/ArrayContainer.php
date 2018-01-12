<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Garden;

use Garden\Exception\ContainerNotFoundException;
use Interop\Container\ContainerInterface;

/**
 * A basic container that stores its objects in an array.
 */
class ArrayContainer extends \ArrayObject implements ContainerInterface {
    /**
     * @var bool
     */
    private $lazy;

    /**
     * Construct a new instance of the {@link ArrayContainer} class.
     *
     * @param bool $lazy Whether or not to lazy instantiate objects that aren't in the container.
     */
    public function __construct($lazy = false) {
        parent::__construct(null, 0, 'ArrayIterator');
        $this->lazy = $lazy;
    }

    /**
     * Normalize a container entry ID.
     *
     * @param string $id The ID to normalize.
     * @return string Returns a normalized ID as a string.
     */
    private function normalizeID($id) {
        return ltrim($id, '\\');
    }

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
        $id = $this->normalizeID($id);

        if (!isset($this[$id])) {
            if ($this->lazy) {
                $this[$id] = new $id;
            } else {
                throw new ContainerNotFoundException("$id not found.", 404);
            }
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
        $id = $this->normalizeID($id);

        return isset($id) || ($this->lazy && class_exists($id));
    }
}
