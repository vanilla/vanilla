<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler\Auth;

/**
 * Class AdHocAuth412Exception
 */
class AdHocAuth412Exception extends AdHocAuthException {

    /**
     * AdHocAuth412Exception constructor
     *
     * @param string $message
     * @param int $code
     * @param array $context
     */
    public function __construct(
        string $message = 'Precondition Failed',
        int $code = 412,
        array $context = ['description' => 'AdHocAuth - Missing Token Configuration']
    ) {
        parent::__construct($message, $code, $context);
    }
}
