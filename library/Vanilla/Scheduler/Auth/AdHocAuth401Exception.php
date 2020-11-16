<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler\Auth;

/**
 * Class AdHocAuth401Exception
 */
class AdHocAuth401Exception extends AdHocAuthException {

    /**
     * AdHocAuth401Exception constructor
     *
     * @param string $message
     * @param int $code
     * @param array $context
     */
    public function __construct(
        string $message = 'Invalid Token',
        int $code = 401,
        array $context = ['description' => 'AdHocAuth - Invalid Token']
    ) {
        parent::__construct($message, $code, $context);
    }
}
