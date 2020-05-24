<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Garden\Events;

use PHPUnit\Framework\TestCase;
use VanillaTests\Fixtures\Events\TestResourceEvent;

/**
 * Test capabilities of ResourceEvent data class.
 */
class ResourceEventTest extends TestCase {

    /**
     * Test setting the action.
     *
     * @return void
     */
    public function testSetAction(): void {
        $resourceEvent = new TestResourceEvent(__FUNCTION__, []);
        $this->assertEquals(__FUNCTION__, $resourceEvent->getAction());
    }

    /**
     * Test setting the event payload.
     *
     * @return void
     */
    public function testSetPayload(): void {
        $payload = ["foo" => "bar"];
        $resourceEvent = new TestResourceEvent(__FUNCTION__, $payload);
        $this->assertEquals($payload, $resourceEvent->getPayload());
    }

    /**
     * Test deriving the type from the class name.
     *
     * @return void
     */
    public function testSetType(): void {
        $resourceEvent = new TestResourceEvent(__FUNCTION__, []);
        $this->assertEquals("testResource", $resourceEvent->getType());
    }

    /**
     * Events should have their type and action in their full event name.
     */
    public function testFullEventName(): void {
        $resourceEvent = new TestResourceEvent(__FUNCTION__, []);
        $this->assertStringContainsString(__FUNCTION__, $resourceEvent->getFullEventName());
        $this->assertStringContainsString($resourceEvent->getType(), $resourceEvent->getFullEventName());
    }

    /**
     * The sender should be set unchanged.
     */
    public function testGetSender(): void {
        $expected = ['foo' => 'bar'];
        $resourceEvent = new TestResourceEvent(__FUNCTION__, [], $expected);
        $this->assertSame($expected, $resourceEvent->getSender());
    }
}
