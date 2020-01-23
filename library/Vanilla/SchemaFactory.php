<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla;

use Garden\EventManager;
use Garden\Schema\Schema;
use Gdn;
use Psr\Container\ContainerInterface;

/**
 * Factory for schema objects.
 */
final class SchemaFactory {

    /** @var ContainerInterface */
    private static $container;

    /** @var EventManager */
    private static $eventManager;

    /**
     * Get an instance of a schema object by its class name.
     *
     * @param string $schema
     * @param string|null $id
     * @return Schema
     */
    public static function get(string $schema, ?string $id = null): Schema {
        /** @var Schema */
        $schema = self::getContainer()->get($schema);
        if ($id) {
            $schema->setID($id);
        }
        $schema = self::prepare($schema);
        return $schema;
    }

    /**
     * Get the configured container.
     *
     * @return ContainerInterface
     */
    public static function getContainer(): ContainerInterface {
        if (!isset(self::$container)) {
            self::$container = Gdn::getContainer();
        }
        return self::$container;
    }

    /**
     * Get the configured event manager instance.
     *
     * @return EventManager
     */
    public static function getEventManager(): EventManager {
        if (!isset(self::$eventManager)) {
            /** @var EventManager */
            $eventManager = self::getContainer()->get(EventManager::class);
            self::setEventManager($eventManager);
        }
        return self::$eventManager;
    }

    /**
     * Create a schema object from an array.
     *
     * @param array $schema
     * @param string|null $id
     * @return Schema
     */
    public static function parse(array $schema, ?string $id = null): Schema {
        $result = Schema::parse($schema);
        if ($id) {
            $result->setID($id);
        }
        $result = self::prepare($result);
        return $result;
    }

    /**
     * Final preparations on a schema object before usage.
     *
     * @param Schema $schema
     * @param string|null $id
     * @return Schema
     * @internal This method should only be used in this class. The weaker visibility is a BC kludge.
     */
    public static function prepare(Schema $schema, ?string $id = null): Schema {
        $result = clone $schema;

        // Allow the schema ID to be set or overwritten.
        if (!empty($id)) {
            $result->setID($id);
        }

        if ($schemaID = $schema->getID()) {
            // Fire an event for schema modification.
            self::getEventManager()->fire("{$schemaID}Schema_init", $result);
        }

        return $result;
    }

    /**
     * Set the container used for creating instances.
     *
     * @param ContainerInterface $container
     * @return void
     */
    public static function setContainer(?ContainerInterface $container): void {
        self::$container = $container;
    }

    /**
     * Set the event manager instance.
     *
     * @param EventManager $eventManager
     * @return void
     */
    public static function setEventManager(?EventManager $eventManager): void {
        self::$eventManager = $eventManager;
    }
}
