<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

/**
 * Signal that this class has opted into System job calls.
 */
interface SystemCallableInterface
{
    /**
     * Get the array of system callable method names.
     *
     * @return string[]
     */
    public static function getSystemCallableMethods(): array;
}
