<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Garden;

/**
 * Use this interface to mark this as a class with autobound PSR event handlers.
 */
interface PsrEventHandlersInterface extends EventHandlersInterface
{
    /**
     * Return an array of methods on this class that are event handlers.
     *
     * @return string[]
     */
    public static function getPsrEventHandlerMethods(): array;
}
