<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Database\Operation;

use Vanilla\Database\Operation;
use Vanilla\Logging\LoggerUtils;

/**
 * Database operation processor for translating int fields to boolean values.
 */
class BooleanFieldProcessor implements Processor
{
    /** @var array */
    private $fields = [];

    /**
     * Setup the processor.
     *
     * @param array $fields
     */
    public function __construct(array $fields = [])
    {
        $this->setFields($fields);
    }

    /**
     * Get the list of fields to be packed and unpacked.
     *
     * @return array
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * Translate integers to booleans, or vice versa, based on the database operation type.
     *
     * @param Operation $operation
     * @param callable $stack
     * @return mixed
     */
    public function handle(Operation $operation, callable $stack)
    {
        if (in_array($operation->getType(), [Operation::TYPE_INSERT, Operation::TYPE_UPDATE])) {
            $this->translateInput($operation);
            return $stack($operation);
        } elseif ($operation->getType() === Operation::TYPE_SELECT) {
            $result = $stack($operation);
            return $this->translateOutput($result);
        } else {
            return $stack($operation);
        }
    }

    /**
     * Translate boolean fields to integer values.
     *
     * @param Operation $operation
     */
    private function translateInput(Operation $operation)
    {
        $set = $operation->getSet();
        foreach ($this->getFields() as $field) {
            if (array_key_exists($field, $set)) {
                $set[$field] = intval($set[$field]);
            }
        }
        $operation->setSet($set);
    }

    /**
     * Set the list of fields to be translated.
     *
     * @param array $fields
     * @return self
     */
    public function setFields(array $fields): self
    {
        $this->fields = $fields;
        return $this;
    }

    /**
     * Translate integer fields to boolean values.
     *
     * @param array $results
     * @return array
     */
    private function translateOutput(array $results): array
    {
        $fields = $this->getFields();
        foreach ($results as &$row) {
            foreach ($fields as $field) {
                if (array_key_exists($field, $row)) {
                    $row[$field] = boolval($row[$field]);
                }
            }
        }
        return $results;
    }
}
