<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla;

use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Garden\Schema\ValidationField;

/**
 * Basic model class.
 */
class Model {

    /** @var \Gdn_Database */
    private $database;

    /** @var Schema */
    private $readSchema;

    /** @var string */
    private $table;

    /** @var Schema */
    private $writeSchema;

    /**
     * Basic model constructor.
     *
     * @param string $table
     * @param \Gdn_Database $database
     */
    public function __construct(string $table, \Gdn_Database $database) {
        $this->table = $table;
        $this->database = $database;

        $schema = $this->database->simpleSchema($table);
        $this->setReadSchema(clone $schema)
            ->setWriteSchema(clone $schema);
    }

    /**
     * Attempt to decode a encoded string value into a more complex type.
     *
     * @param mixed $value Raw database value.
     * @param ValidationField $field An object representing the Garden Schema field.
     * @return mixed Unpacked attributes field value.
     */
    private function filterValueDecode($value, ValidationField $field) {
        if ($value === null || $value === '') {
            $value = null;
        } elseif (is_string($value)) {
            $value = json_decode($value, true);
            if ($value === null) {
                $value = unserialize($value);
                if ($value === false) {
                    $value = null;
                }
            }
        }

        return $value;
    }

    /**
     * Attempt to decode a encoded string value into a more complex type.
     *
     * @param mixed $value Raw database value.
     * @param ValidationField $field An object representing the Garden Schema field.
     * @return mixed Unpacked attributes field value.
     * @throws \Exception If there was an error encoding the value.
     */
    private function filterValueEncode($value, ValidationField $field) {
        if ($value === null || $value === '') {
            $value = null;
        } else {
            $value = json_encode($value, JSON_UNESCAPED_SLASHES);
            $jsonError = json_last_error();
            if ($jsonError !== JSON_ERROR_NONE) {
                throw new \Exception("An error was encountered while encoding the JSON value ({$jsonError}).");
            }
        }
        return $value;
    }

    /**
     * Get resource rows from a database table.
     *
     * @param array $where Conditions for the select query.
     * @param array $options Options for the select query.
     * @return array Rows matching the conditions and within the parameters specified in the options.
     * @throws ValidationException If a row fails to validate against the schema.
     */
    public function get(array $where = [], array $options = []): array {
        $orderFields = $options['orderFields'] ?? '';
        $orderDirection = $options['orderDirection'] ?? 'asc';
        $limit = $options['limit'] ?? false;
        $offset = $options['offset'] ?? 0;

        $result = $this->sql()
            ->getWhere($this->table, $where, $orderFields, $orderDirection, $limit, $offset)
            ->resultArray();

        $schema = Schema::parse([':a' => $this->readSchema]);
        $result = $schema->validate($result);

        return $result;
    }

    /**
     * Add a resource row.
     *
     * @param array $set Field values to set.
     * @return mixed ID of the inserted row.
     * @throws \Exception If an error is encountered while performing the query.
     */
    public function insert(array $set) {
        $set = $this->writeSchema->validate($set);
        $result = $this->sql()->insert($this->table, $set);
        if ($result === false) {
            throw new \Exception('An unknown error was encountered while inserting the row.');
        }
        return $result;
    }

    /**
     * Set the read/output schema for the model.
     *
     * @param Schema $schema Schema representing the resource's database table.
     * @return Model Current instance for fluent calls.
     */
    private function setReadSchema(Schema $schema): Model {
        // Transform Attributes field values, if available.
        $attributes = $schema->getField('properties.Attributes');
        if ($attributes) {
            $attributeTypes = $schema->getField('properties.Attributes.type', []);
            if (!empty($attributeTypes)) {
                $attributeTypes = array_unique(array_merge($attributeTypes, ['array', 'object']));
                $schema->setField('properties.Attributes.type', $attributeTypes);
            }
            $schema->addFilter('Attributes', function ($value, ValidationField $field) {
                return $this->filterValueDecode($value, $field);
            });
        }

        $this->readSchema = $schema;
        return $this;
    }

    /**
     * Set the write/input schema for the model.
     *
     * @param Schema $schema Schema representing the resource's database table.
     * @return Model Current instance for fluent calls.
     */
    private function setWriteSchema(Schema $schema): Model {
        // Transform Attributes field values, if available.
        $attributes = $schema->getField('properties.Attributes');
        if ($attributes) {
            $schema->addFilter('Attributes', function ($value, ValidationField $field) {
                return $this->filterValueEncode($value, $field);
            });
        }

        $this->writeSchema = $schema;
        return $this;
    }

    /**
     * Get a clean SQL driver instance.
     *
     * @return \Gdn_SQLDriver
     */
    private function sql(): \Gdn_SQLDriver {
        $sql = clone $this->database->sql();
        $sql->reset();
        return $sql;
    }

    /**
     * Update existing resource rows.
     *
     * @param array $set Field values to set.
     * @param array $where Conditions to restrict the update.
     * @throws \Exception If an error is encountered while performing the query.
     * @return bool True if no errors were encountered when performing the query.
     */
    public function update(array $set, array $where): bool {
        $set = $this->writeSchema->validate($set, true);
        $result = $this->sql()->put($this->table, $set, $where);
        if (!($result instanceof \Gdn_DataSet)) {
            throw new \Exception('An unknown error was encountered while performing the update.');
        }
        return true;
    }
}
