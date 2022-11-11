<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures\Events;

use Garden\PsrEventHandlersInterface;

/**
 * Test psr event handler.
 */
class TestPsrEventHandler implements PsrEventHandlersInterface
{
    /**
     * @inheritdoc
     */
    public static function getPsrEventHandlerMethods(): array
    {
        return ["handleResourceEvent"];
    }

    /**
     * Does nothing.
     *
     * @param TestResourceEvent $event
     */
    public function handleResourceEvent(TestResourceEvent $event)
    {
        $event->wasHandled = true;
    }
}
