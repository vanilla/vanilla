<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler;

/**
 * Throw this into a long-running generator to tell it that it has run out of memory
 * and should return a LongRunnerNextArg.
 */
class LongRunnerTimeoutException extends \Exception
{
}
