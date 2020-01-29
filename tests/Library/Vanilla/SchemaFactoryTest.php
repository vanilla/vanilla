<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla;

use Garden\EventManager;
use Garden\Schema\Schema;
use PHPUnit\Framework\TestCase;
use Vanilla\Models\UserFragmentSchema;
use Vanilla\SchemaFactory;
use VanillaTests\BootstrapTrait;
use VanillaTests\Fixtures\Container;

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
    public function provideGetEvents(): array {
        $parameters = [
            UserFragmentSchema::class => [
                UserFragmentSchema::class,
                "UserFragment",
                "UserFragmentSchema_init",
            ],
        ];
        return $parameters;
    }

    /**
     * Provide type prameters to verify the associated events are properly dispatched.
     *
     * @return array
     */
    public function provideParseEvents(): array {
        $parameters = [
            "array" => [
                "ExampleA",
                "ExampleASchema_init",
            ],
        ];
        return $parameters;
    }

    /**
     * {@inheritDoc}
     */
    public function setUp(): void {
        SchemaFactory::setContainer($this->container());
        SchemaFactory::setEventManager($this->container()->get(EventManager::class));
    }

    /**
     * Verify the expected events are dispatched when the schema is retrieved from the container.
     *
     * @param string $class
     * @param string $id
     * @param string $event
     * @return void
     * @dataProvider provideGetEvents
     */
    public function testGetEventDispatched(string $class, string $id, string $event): void {
        // Start with a clean slate.
        $eventManager = new EventManager($this->container());
        SchemaFactory::setEventManager($eventManager);

        $dispatched = false;
        $eventManager->bind($event, function () use (&$dispatched) {
            $dispatched = true;
        });

        SchemaFactory::get($class, $id);
        $this->assertTrue($dispatched);
    }

    /**
     * Verify the expected events are dispatched when the schema is parsed from an array.
     *
     * @param string $id
     * @param string $event
     * @return void
     * @dataProvider provideParseEvents
     */
    public function testParseEventDispatched(string $id, string $event): void {
        // Start with a clean slate.
        $eventManager = new EventManager($this->container());
        SchemaFactory::setEventManager($eventManager);

        $dispatched = false;
        $eventManager->bind($event, function () use (&$dispatched) {
            $dispatched = true;
        });

        SchemaFactory::parse(["stringField:s"], $id);
        $this->assertTrue($dispatched);
    }

    /**
     * Verify the proper ID is used to dispatch events from the prepare method.
     *
     * @return void
     */
    public function testPrepareEventDispatched(): void {
        $eventManager = new EventManager($this->container());
        SchemaFactory::setEventManager($eventManager);

        $dispatched = false;
        $eventManager->bind("fooSchema_init", function () use (&$dispatched) {
            $dispatched = true;
        });

        $schema = Schema::parse(["stringField:s"]);
        SchemaFactory::prepare($schema, "foo");
        $this->assertTrue($dispatched, "No existing schema ID.");

        $dispatched = false;
        $schema = Schema::parse(["stringField:s"]);
        $schema->setID("bar");
        SchemaFactory::prepare($schema, "foo");
        $this->assertTrue($dispatched, "Overwriting an existing schema ID.");
    }

    /**
     * Verify the container will automatically be retrieved when not explicitly set.
     *
     * @return void
     */
    public function testGetContainerDefault(): void {
        // Unset the existing container.
        SchemaFactory::setContainer(null);
        $this->assertSame(
            $this->container(),
            SchemaFactory::getContainer()
        );
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
     * Test configuring the container dependency.
     *
     * @return void
     */
    public function testSetContainer(): void {
        $container = new Container();
        SchemaFactory::setContainer($container);
        $this->assertSame(
            $container,
            SchemaFactory::getContainer()
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
        $this->assertSame(
            $eventManager,
            SchemaFactory::getEventManager()
        );
    }
}
