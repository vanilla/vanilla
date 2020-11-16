<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler\Auth;

use Garden\Web\RequestInterface;
use Vanilla\Contracts\ConfigurationInterface;

/**
 * AdHocAuth
 */
class AdHocAuth {

    /** @var RequestInterface */
    protected $request;

    /** @var string */
    protected $cronToken;

    /**
     * AdHocAuth constructor
     *
     * @param ConfigurationInterface $config
     * @param RequestInterface $request
     */
    public function __construct(ConfigurationInterface $config, RequestInterface $request) {
        $this->cronToken = $config->get('Garden.Scheduler.Token', null);
        $this->request = $request;
    }

    /**
     * Validate a bearer token
     *
     * @return bool
     * @throws AdHocAuthException When Validation has failed.
     */
    public function validateToken() {
        if ($this->cronToken === null) {
            throw new AdHocAuth412Exception();
        }

        $authHeader = $this->request->getHeader('Authorization') ?? '';
        if ($authHeader === '') {
            throw new AdHocAuth403Exception();
        }

        $parts = explode(' ', $authHeader);

        if (count($parts) === 2 && strtolower($parts[0]) === "bearer") {
            if (hash_equals($parts[1], $this->cronToken)) {
                return true;
            }
        }

        throw new AdHocAuth401Exception();
    }
}
