<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Database\Operation;

use Vanilla\Database\Operation;
use Vanilla\Utility\ArrayUtils;

/**
 * A processor that adds the current IP address to fields in the model.
 */
class CurrentIPAddressProcessor implements Processor {
    /** @var array */
    private $insertFields = ["InsertIPAddress"];

    /** @var \Gdn_Request */
    private $request;

    /** @var array */
    private $updateFields = ["UpdateIPAddress"];

    /**
     * CurrentUserFieldProcessor constructor.
     *
     * @param \Gdn_Request $request
     */
    public function __construct(\Gdn_Request $request) {
        $this->request = $request;
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
            case Operation::TYPE_SELECT:
                $result = $stack($operation);
                $fields = array_merge($this->getInsertFields(), $this->getUpdateFields());
                foreach ($result as &$row) {
                    foreach ($fields as $field) {
                        if (!empty($row[$field])) {
                            $row[$field] = ipDecode($row[$field]);
                        }
                    }
                }
                return $result;
            default:
                // Nothing to do here. Shortcut return.
                return $stack($operation);
        }

        foreach ($fields as $field) {
            $fieldExists = $operation->getCaller()->getWriteSchema()->getField("properties.{$field}");
            if ($fieldExists) {
                $set = $operation->getSet();
                if (empty($set[$field]) || $operation->getMode() === Operation::MODE_DEFAULT) {
                    $set[$field] = ipEncode($this->getCurrentIPAddress());
                };
                $operation->setSet($set);
            }
        }

        return $stack($operation);
    }

    /**
     * Get the current IP address used for field updates.
     *
     * @return string|null
     */
    public function getCurrentIPAddress(): ?string {
        return $this->request->getIP();
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
     * Camel case the default fields.
     *
     * @return $this
     */
    public function camelCase(): self {
        $this->insertFields = array_map('lcfirst', $this->insertFields);
        $this->updateFields = array_map('lcfirst', $this->updateFields);
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
}
