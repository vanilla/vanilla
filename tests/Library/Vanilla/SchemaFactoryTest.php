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

    /** @var EventManager */
    private $eventManager;

    /**
     * This method is called before each test.
     */
    public function setUp(): void {
        parent::setUp();
        $this->eventManager = $this->container()->get(EventManager::class);
    }

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
     * Verify the expected events are dispatched when the schema is retrieved from the container.
     *
     * @param string $class
     * @param string $id
     * @param string $event
     * @return void
     * @dataProvider provideGetEvents
     */
    public function testGetEventDispatched(string $class, string $id, string $event): void {
        $dispatched = false;
        $this->eventManager->bind($event, function () use (&$dispatched) {
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
        $dispatched = false;
        $this->eventManager->bind($event, function () use (&$dispatched) {
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
        $dispatched = false;
        $this->eventManager->bind("fooSchema_init", function () use (&$dispatched) {
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
}
