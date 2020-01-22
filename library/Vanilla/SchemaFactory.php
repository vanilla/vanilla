<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla;

use Garden\EventManager;
use Garden\Schema\Schema;
use Gdn;

/**
 * Factory for schema objects.
 */
class SchemaFactory {

    /** @var EventManager */
    private static $eventManager;

    /**
     * Get the configured event manager instance.
     *
     * @return EventManager
     */
    private static function getEventManager(): EventManager {
        if (!isset(self::$eventManager)) {
            /** @var EventManager */
            $eventManager = Gdn::getContainer()->get(EventManager::class);
            self::setEventManager($eventManager);
        }
        return self::$eventManager;
    }

    /**
     * Create a schema, allowing for extension via events.
     *
     * @param array|Schema $schema
     * @param string|array $type
     * @return Schema
     */
    public static function parse($schema, $type): Schema {
        $id = '';
        if (is_array($type)) {
            $origType = $type;
            list($id, $type) = $origType;
        } elseif (!in_array($type, ['in', 'out'], true)) {
            $id = $type;
            $type = '';
        }

        // Figure out the name.
        if (is_array($schema)) {
            $schema = Schema::parse($schema);
        } elseif ($schema instanceof Schema) {
            $schema = clone $schema;
        }

        // Fire an event for schema modification.
        if (!empty($id)) {
            // The type is a specific type of schema.
            $schema->setID($id);

            self::getEventManager()->fire("{$id}Schema_init", $schema);
        }

        return $schema;
    }

    /**
     * Set the event manager instance.
     *
     * @param EventManager $eventManager
     * @return void
     */
    private static function setEventManager(EventManager $eventManager): void {
        self::$eventManager = $eventManager;
    }
}
