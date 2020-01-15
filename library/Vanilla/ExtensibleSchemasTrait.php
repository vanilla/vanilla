<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla;

use Garden\EventManager;
use Garden\Schema\Schema;

trait ExtensibleSchemasTrait {

    /**
     * Create a schema, allowing for extension via events.
     *
     * @param array|Schema $schema
     * @param string|array $type
     * @return Schema
     */
    public function extensibleSchema($schema, $type): Schema {
        /** @var EventManager */
        $eventManager = \Gdn::getContainer()->get(EventManager::class);

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

            $eventManager->fire("{$id}Schema_init", $schema);
        }

        return $schema;
    }
}
