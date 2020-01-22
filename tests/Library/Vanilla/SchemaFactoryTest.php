<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla;

use Garden\EventManager;
use PHPUnit\Framework\TestCase;
use Vanilla\SchemaFactory;
use VanillaTests\BootstrapTrait;

/**
 * Verify basic capabilities of SchemaFactory.
 */
class SchemaFactoryTest extends TestCase {

    use BootstrapTrait;

    /**
     * Provide type prameters to verify the associated events are properly dispatched.
     *
     * @return array
     */
    public function provideTypesForEvents(): array {
        $types = [
            "array" => [
                ["ExampleA", "in"],
                "ExampleASchema_init",
            ],
            "string" => [
                "ExampleB",
                "ExampleBSchema_init",
            ],
        ];
        return $types;
    }

    /**
     * Verify the expected events are dispatched when the schema is created.
     *
     * @param array|string $type
     * @param string $event
     * @return void
     * @dataProvider provideTypesForEvents
     */
    public function testEventDispatched($type, string $event): void {
        // Start with a clean slate.
        $eventManager = new EventManager($this->container());
        SchemaFactory::setEventManager($eventManager);

        $dispatched = false;
        $eventManager->bind($event, function () use (&$dispatched) {
            $dispatched = true;
        });

        SchemaFactory::parse(["stringField:s"], $type);
        $this->assertTrue($dispatched);
    }

    /**
     * Verify the event manager will automatically be retrieved when not explicitly set.
     *
     * @return void
     */
    public function testGetEventManagerDefault(): void {
        // Unset the existing event manager.
        SchemaFactory::setEventManager(null);
        $this->assertSame(
            $this->container()->get(EventManager::class),
            SchemaFactory::getEventManager()
        );
    }

    /**
     * Test configuring the event manager dependency.
     *
     * @return void
     */
    public function testSetEventManager(): void {
        $eventManager = new EventManager($this->container());
        SchemaFactory::setEventManager($eventManager);
        $this->assertSame($eventManager, SchemaFactory::getEventManager());
    }
}
