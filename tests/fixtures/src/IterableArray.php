<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures;

/**
 * An array object that is also iterable.
 */
class IterableArray extends DumbArray implements \IteratorAggregate {

    /**
     * @inheritDoc
     */
    public function getIterator() {
        return new \ArrayIterator($this->arr);
    }
}
