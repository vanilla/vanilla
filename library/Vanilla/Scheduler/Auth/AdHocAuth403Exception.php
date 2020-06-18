<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler\Auth;

/**
 * Class AdHocAuth403Exception
 */
class AdHocAuth403Exception extends AdHocAuthException {

    /**
     * AdHocAuth403Exception constructor
     *
     * @param string $message
     * @param int $code
     * @param array $context
     */
    public function __construct(
        string $message = 'Forbidden Error',
        int $code = 403,
        array $context = ['description' => 'AdHocAuth - Missing Token']
    ) {
        parent::__construct($message, $code, $context);
    }
}
