<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Database\Operation;

use Gdn_Session;
use Vanilla\Database\Operation;
use Vanilla\Utility\ArrayUtils;

/**
 * Database operation processor for including current user ID fields.
 */
class CurrentUserFieldProcessor implements Processor {

    /** @var array */
    private $insertFields = ["InsertUserID"];

    /** @var Gdn_Session */
    private $session;

    /** @var array */
    private $updateFields = ["UpdateUserID"];

    /**
     * CurrentUserFieldProcessor constructor.
     *
     * @param Gdn_Session $session
     */
    public function __construct(Gdn_Session $session) {
        $this->session = $session;
    }

    /**
     * Get the list of fields to be populated with the current user ID when adding a new row.
     *
     * @return array
     */
    public function getInsertFields(): array {
        return $this->insertFields;
    }

    /**
     * Get the list of fields to be populated with the current user ID when updating an existing row.
     *
     * @return array
     */
    public function getUpdateFields(): array {
        return $this->updateFields;
    }

    /**
     * Add current user ID to write operations.
     *
     * @param Operation $operation
     * @param callable $stack
     * @return mixed
     */
    public function handle(Operation $operation, callable $stack) {
        switch ($operation->getType()) {
            case Operation::TYPE_INSERT:
                $fields = $this->getInsertFields();
                break;
            case Operation::TYPE_UPDATE:
                $fields = $this->getUpdateFields();
                break;
            default:
                // Nothing to do here. Shortcut return.
                return $stack($operation);
        }

        foreach ($fields as $field) {
            $fieldExists = $operation->getCaller()->getWriteSchema()->getField("properties.{$field}");
            if ($fieldExists) {
                $set = $operation->getSet();
                if (empty($set[$field] ?? null) || $operation->getMode() === Operation::MODE_DEFAULT) {
                    $set[$field] = $this->session->UserID;
                };
                $operation->setSet($set);
            }
        }

        return $stack($operation);
    }

    /**
     * Get the current user ID that will be applied to fields.
     *
     * @return int
     */
    public function getCurrentUserID(): int {
        return $this->session->UserID;
    }

    /**
     * Set the list of fields to be populated with the current user ID when adding a new row.
     *
     * @param array $insertFields
     * @return self
     */
    public function setInsertFields(array $insertFields): self {
        $this->insertFields = $insertFields;
        return $this;
    }

    /**
     * Set the list of fields to be populated with the current user ID when updating an existing row.
     *
     * @param array $updateFields
     * @return self
     */
    public function setUpdateFields(array $updateFields): self {
        $this->updateFields = $updateFields;
        return $this;
    }

    /**
     * Camel case the default fields.
     *
     * @return $this
     */
    public function camelCase(): self {
        $this->insertFields = array_map('lcfirst', $this->insertFields);
        $this->updateFields = array_map('lcfirst', $this->updateFields);
        return $this;
    }
}
