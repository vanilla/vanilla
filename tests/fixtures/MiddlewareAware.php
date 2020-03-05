<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures;

use Garden\Web\MiddlewareAwareTrait;

/**
 * Class MiddlewareAware
 */
class MiddlewareAware {
    use MiddlewareAwareTrait;

    /**
     * Set the innermost request handler.
     *
     * @param callable $callback
     */
    public function setHandler(callable $callback) {
        $this->middleware = $callback;
    }
}
