<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Exception;

use Throwable;

/**
 * This is an exception meant to replace calls to `die()` or `exit()` inside controller methods.
 *
 * If a method deep inside a controller needs to exit it can throw this exception which will be caught up in the
 * dispatcher where the application will actually exit unless we are in debug mode.
 */
class ExitException extends \Exception
{
    /**
     * Constructor.
     *
     * @param int $code
     */
    public function __construct($code = 0)
    {
        parent::__construct("Exit", $code);
    }
}
