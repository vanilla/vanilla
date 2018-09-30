<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Garden;

use Garden\EventManager;
use PHPUnit\Framework\TestCase;
use VanillaTests\Fixtures\EventManager\FireEventTraitModel;

/**
 * Tests for the {@link EventManager} class.
 */
class FireEventTraitTest extends TestCase {

    /**
     * Verify the trait fires events.
     */
    public function testFireEvent() {
        $eventManager = new EventManager();
        $eventFired = false;
        $eventManager->bind("fireEventTrait_test", function () use (&$eventFired) {
            $eventFired = true;
        });

        $model = new FireEventTraitModel($eventManager);
        $this->assertFalse($eventFired);
        $model->fireTestEvent();
        $this->assertTrue($eventFired);
    }
}
