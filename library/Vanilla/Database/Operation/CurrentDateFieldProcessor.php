<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Database\Operation;

use Vanilla\Database\Operation;

/**
 * Database operation processor for including current date fields.
 */
class CurrentDateFieldProcessor implements Processor {

    /**
     * Add current date to write operations.
     *
     * @param Operation $databaseOperation
     * @param callable $stack
     * @return mixed
     */
    public function handle(Operation $databaseOperation, callable $stack) {
        switch ($databaseOperation->getType()) {
            case Operation::TYPE_INSERT:
                $field = "DateInserted";
                break;
            case Operation::TYPE_UPDATE:
                $field = "DateUpdated";
                break;
            default:
                // Nothing to do here. Shortcut return.
                return $stack($databaseOperation);
        }

        $fieldExists = $databaseOperation->getCaller()->getWriteSchema()->getField("properties.{$field}");
        if ($fieldExists) {
            $set = $databaseOperation->getSet();
            $set[$field] = date("Y-m-d H:i:s");
            $databaseOperation->setSet($set);
        }

        return $stack($databaseOperation);
    }
}
