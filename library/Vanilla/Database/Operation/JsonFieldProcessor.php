<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Database\Operation;

use Vanilla\Database\Operation;
use Vanilla\Logging\LoggerUtils;

/**
 * Database operation processor for packing and unpacking JSON fields.
 */
class JsonFieldProcessor implements Processor {

    /** @var array */
    private $fields = [];

    /**
     * JsonFieldProcessor constructor.
     *
     * @param array $fields
     */
    public function __construct(array $fields = []) {
        $this->setFields($fields);
    }

    /**
     * Get the list of fields to be packed and unpacked.
     *
     * @return array
     */
    public function getFields(): array {
        return $this->fields;
    }

    /**
     * Pack or unpack JSON fields, based on the database operation type.
     *
     * @param Operation $operation
     * @param callable $stack
     * @return mixed
     */
    public function handle(Operation $operation, callable $stack) {
        if (in_array($operation->getType(), [Operation::TYPE_INSERT, Operation::TYPE_UPDATE])) {
            $this->packFields($operation);
            return $stack($operation);
        } elseif ($operation->getType() === Operation::TYPE_SELECT) {
            $result = $stack($operation);
            return $this->unpackFields($result);
        } else {
            return $stack($operation);
        }
    }

    /**
     * Pack JSON fields for write operations.
     *
     * @param Operation $operation
     * @throws \Exception If JSON field data is not valid JSON.
     */
    private function packFields(Operation $operation) {
        $set = $operation->getSet();
        foreach ($this->getFields() as $field) {
            if (array_key_exists($field, $set)) {
                $json = $set[$field];
                if (is_array($json)) {
                    $json = LoggerUtils::stringifyDates($json);
                }
                $json = json_encode($json, JSON_FORCE_OBJECT);
                if ($json === false) {
                    throw new \InvalidArgumentException("Unable to encode field as JSON.", 400);
                }
                $set[$field] = $json;
            }
        }
        $operation->setSet($set);
    }

    /**
     * Set the list of fields to be packed and unpacked.
     *
     * @param array $fields
     * @return self
     */
    public function setFields(array $fields): self {
        $this->fields = $fields;
        return $this;
    }

    /**
     * Unpack JSON fields for read operations.
     *
     * @param array $results
     * @return array
     */
    private function unpackFields(array $results): array {
        $fields = $this->getFields();
        foreach ($results as &$row) {
            foreach ($fields as $field) {
                if (array_key_exists($field, $row)) {
                    $row[$field] = json_decode($row[$field], true);
                }
            }
        }
        return $results;
    }
}
