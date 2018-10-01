<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Database\Operation;

use Gdn_Session;
use Vanilla\Database\Operation;

/**
 * Database operation processor for including current user ID fields.
 */
class CurrentUserFieldProcessor implements Processor {

    /** @var Gdn_Session */
    private $session;

    /**
     * CurrentUserFieldProcessor constructor.
     *
     * @param Gdn_Session $session
     */
    public function __construct(Gdn_Session $session) {
        $this->session = $session;
    }

    /**
     * Add current user ID to write operations.
     *
     * @param Operation $databaseOperation
     * @param callable $stack
     * @return mixed
     */
    public function handle(Operation $databaseOperation, callable $stack) {
        switch ($databaseOperation->getType()) {
            case Operation::TYPE_INSERT:
                $field = "InsertUserID";
                break;
            case Operation::TYPE_UPDATE:
                $field = "UpdateUserID";
                break;
            default:
                // Nothing to do here. Shortcut return.
                return $stack($databaseOperation);
        }

        $fieldExists = $databaseOperation->getCaller()->getWriteSchema()->getField("properties.{$field}");
        if ($fieldExists) {
            $set = $databaseOperation->getSet();
            $set[$field] = $this->session->UserID;
            $databaseOperation->setSet($set);
        }

        return $stack($databaseOperation);
    }
}
