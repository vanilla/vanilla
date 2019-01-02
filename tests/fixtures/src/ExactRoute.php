<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures;

use Garden\Web\RequestInterface;
use Garden\Web\Route;

/**
 * A route that matches exact paths and
 */
class ExactRoute extends Route {
    /**
     * @var string
     */
    private $path;

    /**
     * @var callable
     */
    private $callback;

    /**
     * ExactRoute constructor.
     *
     * @param string $path The path to match.
     * @param callable $callback The callback to execute on the match.
     */
    public function __construct(string $path, callable $callback) {
        $this
            ->setPath($path)
            ->setCallback($callback);
    }

    /**
     * Match the route to a request.
     *
     * @param RequestInterface $request The request to match against.
     * @return mixed Returns match information or **null** if the route doesn't match.
     */
    public function match(RequestInterface $request) {
        if ($request->getPath() === $this->getPath()) {
            return function () use ($request) {
                return call_user_func($this->getCallback(), $request);
            };
        }
    }

    /**
     * Get the path.
     *
     * @return string Returns the path.
     */
    public function getPath(): string {
        return $this->path;
    }

    /**
     * Set the path.
     *
     * @param string $path
     * @return $this
     */
    public function setPath(string $path) {
        $this->path = '/'.ltrim($path, '/');
        return $this;
    }

    /**
     * Get the callback.
     *
     * @return callable Returns the callback.
     */
    public function getCallback(): callable {
        return $this->callback;
    }

    /**
     * Set the callback.
     *
     * @param callable $callback
     * @return $this
     */
    public function setCallback(callable $callback) {
        $this->callback = $callback;
        return $this;
    }
}
