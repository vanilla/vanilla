<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler\Auth;

use Garden\Web\Exception\ClientException;

/**
 * Class AdHocAuthException
 */
class AdHocAuthException extends ClientException {

    /**
     * AdHocAuthException constructor
     *
     * @param string $message
     * @param int $code
     * @param array $context
     */
    public function __construct(string $message, int $code, array $context) {
        parent::__construct($message, $code, $context);
    }
}
