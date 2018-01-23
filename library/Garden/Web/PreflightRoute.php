<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Garden\Web;

use Garden\Web\Exception\NotFoundException;

/**
 * A route that handles all **OPTIONS** requests to aid [CORS](https://developer.mozilla.org/en-US/docs/Web/HTTP/Access_control_CORS) support.
 */
class PreflightRoute extends Route {

    /**
     * @var string
     */
    private $basePath;

    /**
     * @var bool Whether or not a not found error is thrown on non-options requests.
     */
    private $throwNotFound;

    public function __construct($basePath = '/', $throwNotFound = false) {
        $this->setBasePath($basePath);
        $this->setThrowNotFound($throwNotFound);
    }

    /**
     * Match the route to a request.
     *
     * @param RequestInterface $request The request to match against.
     * @return mixed Returns match information or **null** if the route doesn't match.
     */
    public function match(RequestInterface $request) {
        $path = $request->getPath();

        // First check and strip the base path.
        if (stripos($path, $this->basePath) === 0) {
            $pathPart = substr($path, strlen($this->basePath));
        } else {
            return null;
        }

        if (strcasecmp($request->getMethod(), 'OPTIONS') === 0) {
            return [$this, 'dispatch'];
        }

        if ($this->getThrowNotFound()) {
            throw new NotFoundException($path);
        }
    }

    /**
     * Handle the pre-flight request.
     *
     * This method doesn't do anything because the dispatcher should handle the origin headers.
     *
     * @return null Returns **null** for a "no content" response.
     */
    public function dispatch() {
        return null;
    }

    /**
     * Get the base path.
     *
     * @return string Returns the basePath.
     */
    public function getBasePath() {
        return $this->basePath;
    }

    /**
     * Set the base path.
     *
     * @param string $basePath The new base path.
     * @return $this
     */
    public function setBasePath($basePath) {
        $this->basePath = '/'.ltrim($basePath, '/');
        return $this;
    }

    /**
     * Get the throwNotFound.
     *
     * @return mixed Returns the throwNotFound.
     */
    public function getThrowNotFound() {
        return $this->throwNotFound;
    }

    /**
     * Set the throwNotFound.
     *
     * @param mixed $throwNotFound
     * @return $this
     */
    public function setThrowNotFound($throwNotFound) {
        $this->throwNotFound = $throwNotFound;
        return $this;
    }
}
