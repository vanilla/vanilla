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

    /**
     * Get an instance of a schema object by its class name.
     *
     * @param string $schema
     * @param string|null $id
     * @return Schema
     */
    public static function get(string $schema, ?string $id = null): Schema {
        /** @var Schema */
        $schema = Gdn::getContainer()->get($schema);
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
     * @deprecated
     */
    public static function getContainer(): ContainerInterface {
        return Gdn::getContainer();
    }

    /**
     * Get the configured event manager instance.
     *
     * @return EventManager
     * @deprecated
     */
    public static function getEventManager(): EventManager {
        return Gdn::getContainer()->get(EventManager::class);
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
        } elseif ($schemaID = $schema->getID()) {
            $id = $schemaID;
        }

        if ($id) {
            // Fire an event for schema modification.
            Gdn::getContainer()->get(EventManager::class)->fire("{$id}Schema_init", $result);
        }

        return $result;
    }

    /**
     * Set the container used for creating instances.
     *
     * @param ContainerInterface $container
     * @return void
     * @deprecated
     */
    public static function setContainer(?ContainerInterface $container): void {
        return;  // noop
    }

    /**
     * Set the event manager instance.
     *
     * @param EventManager $eventManager
     * @return void
     * @deprecated
     */
    public static function setEventManager(?EventManager $eventManager): void {
        return; // noop
    }
}
