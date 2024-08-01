<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Database\Operation;

use Vanilla\CurrentTimeStamp;
use Vanilla\Database\Operation;
use Vanilla\Utility\ArrayUtils;

/**
 * Database operation processor for including current date fields.
 */
class CurrentDateFieldProcessor implements Processor
{
    private array $insertFields;
    private array $updateFields;
    private bool $precise;

    /**
     * @param array|string[] $insertFields
     * @param array|string[] $updateFields
     * @param bool $precise Use microsecond date precision.
     */
    public function __construct(
        array $insertFields = ["DateInserted"],
        array $updateFields = ["DateUpdated"],
        bool $precise = false
    ) {
        $this->insertFields = $insertFields;
        $this->updateFields = $updateFields;
        $this->precise = $precise;
    }

    /**
     * Get the list of fields to be populated with the current user ID when adding a new row.
     *
     * @return array
     */
    public function getInsertFields(): array
    {
        return $this->insertFields;
    }

    /**
     * Get the list of fields to be populated with the current user ID when updating an existing row.
     *
     * @return array
     */
    public function getUpdateFields(): array
    {
        return $this->updateFields;
    }

    /**
     * Camel case the default fields.
     *
     * @return $this
     */
    public function camelCase(): self
    {
        $this->insertFields = array_map("lcfirst", $this->insertFields);
        $this->updateFields = array_map("lcfirst", $this->updateFields);
        return $this;
    }

    /**
     * Add current date to write operations.
     *
     * @param Operation $operation
     * @param callable $stack
     * @return mixed
     */
    public function handle(Operation $operation, callable $stack)
    {
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
            $fieldExists = $operation
                ->getCaller()
                ->getWriteSchema()
                ->getField("properties.{$field}");
            if ($fieldExists) {
                $set = $operation->getSet();
                if (empty($set[$field] ?? null) || $operation->getMode() === Operation::MODE_DEFAULT) {
                    $set[$field] = CurrentTimeStamp::getMySQL($this->precise);
                } else {
                    if ($set[$field] instanceof \DateTimeImmutable) {
                        $set[$field] = $set[$field]->format(
                            $this->precise
                                ? CurrentTimeStamp::MYSQL_DATE_FORMAT_PRECISE
                                : CurrentTimeStamp::MYSQL_DATE_FORMAT
                        );
                    }
                }
                $operation->setSet($set);
            }
        }

        return $stack($operation);
    }

    /**
     * Set the list of fields to be populated with the current user ID when adding a new row.
     *
     * @param array $insertFields
     * @return self
     */
    public function setInsertFields(array $insertFields): self
    {
        $this->insertFields = $insertFields;
        return $this;
    }

    /**
     * Set the list of fields to be populated with the current user ID when updating an existing row.
     *
     * @param array $updateFields
     * @return self
     */
    public function setUpdateFields(array $updateFields): self
    {
        $this->updateFields = $updateFields;
        return $this;
    }
}
