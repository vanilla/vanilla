<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Exception;
use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\InjectableInterface;
use Vanilla\Utility\ArrayUtils;

/**
 * Basic model class.
 */
class Model implements InjectableInterface {
    const OPT_LIMIT = "limit";
    const OPT_OFFSET = "offset";
    const OPT_SELECT = "select";
    const OPT_ORDER = 'order';

    /** @var \Gdn_Database */
    private $database;

    /** @var Schema */
    protected $readSchema;

    /** @var string */
    private $table;

    /**
     * @var string[]
     */
    private $primaryKey;

    /** @var Schema */
    protected $writeSchema;

    /**
     * @var Schema
     */
    private $databaseSchema;

    /**
     * Basic model constructor.
     *
     * @param string $table Database table associated with this resource.
     */
    public function __construct(string $table) {
        $this->table = $table;
        $this->setPrimaryKey($table.'ID');
    }

    /**
     * Configure a Garden Schema instance for read operations by the model.
     *
     * @param Schema $schema Schema representing the resource's database table.
     * @return Schema Currently configured read schema.
     */
    protected function configureReadSchema(Schema $schema): Schema {
        // Child classes can make adjustments as necessary.
        return $schema;
    }

    /**
     * Get the schema used to validate selects.
     *
     * @return Schema
     */
    public function getReadSchema(): Schema {
        if ($this->readSchema === null) {
            $schema = $this->getDatabaseSchema();
            $this->configureReadSchema($schema);
            $this->readSchema = $schema;
        }
        return $this->readSchema;
    }

    /**
     * Get the schema used to validate inserts and updates.
     *
     * @return Schema
     */
    public function getWriteSchema(): Schema {
        if ($this->writeSchema === null) {
            $schema = $this->getDatabaseSchema();
            $this->configureWriteSchema($schema);
            $this->writeSchema = $schema;
        }
        return $this->writeSchema;
    }

    /**
     * Configure a Garden Schema instance for write operations by the model.
     *
     * @param Schema $schema Schema representing the resource's database table.
     * @return Schema Currently configured write schema.
     */
    protected function configureWriteSchema(Schema $schema): Schema {
        // Child classes can make adjustments as necessary.
        return $schema;
    }

    /**
     * Delete resource rows.
     *
     * @param array $where Conditions to restrict the deletion.
     * @param array $options Options for the delete query.
     *    - limit (int): Limit on the results to be deleted.
     * @throws Exception If an error is encountered while performing the query.
     * @return bool True.
     */
    public function delete(array $where, array $options = []): bool {
        $limit = $options[self::OPT_LIMIT] ?? false;

        $this->createSql()->delete($this->table, $where, $limit);
        // If fully executed without an exception bubbling up, consider this a success.
        return true;
    }

    /**
     * Make sure we have configured schemas available to the instance.
     *
     * @deprecated Use `getReadSchema()` and `getWriteSchema()` instead.
     * @codeCoverageIgnore
     */
    protected function ensureSchemas() {
        $this->getReadSchema();
        $this->getWriteSchema();
    }

    /**
     * Get resource rows from a database table.
     *
     * @param array $where Conditions for the select query.
     * @param array $options Options for the select query.
     *    - orderFields (string, array): Fields to sort the result by.
     *    - orderDirection (string): Sort direction for the order fields.
     *    - limit (int): Limit on the total results returned.
     *    - offset (int): Row offset before capturing the result.
     * @return array Rows matching the conditions and within the parameters specified in the options.
     * @throws ValidationException If a row fails to validate against the schema.
     */
    public function select(array $where = [], array $options = []): array {
        $orderFields = $options[self::OPT_ORDER] ?? ($options["orderFields"] ?? []);
        $orderDirection = $options["orderDirection"] ?? "asc";
        $limit = $options[self::OPT_LIMIT] ?? false;
        $offset = $options[self::OPT_OFFSET] ?? 0;
        $selects =  $options[self::OPT_SELECT] ?? [];

        $sqlDriver = $this->createSql();

        if (!empty($selects)) {
            if (is_string($selects)) {
                $selects = ArrayUtils::explodeTrim(',', $selects);
            }
            $selects = $this->translateSelects($selects);

            $sqlDriver->select($selects);
        }
        $result = $sqlDriver->getWhere($this->table, $where, $orderFields, $orderDirection, $limit, $offset)
            ->resultArray();

        if (empty($selects)) {
            $schema = Schema::parse([":a" => $this->getReadSchema()]);
        } else {
            $schema = Schema::parse([":a" =>  Schema::parse($selects)->add($this->getReadSchema())]);
        }
        // What if a processor goes here?

        $result = $schema->validate($result);

        return $result;
    }

    /**
     * An alias of `select()`.
     *
     * @param array $where
     * @param array $options
     * @return array
     */
    public function get(array $where = [], array $options = []): array {
        return $this->select($where, $options);
    }

    /**
     * Get the database table name.
     *
     * @return string
     */
    public function getTable(): string {
        return $this->table;
    }

    /**
     * Get the primary key columns.
     *
     * @return array
     */
    public function getPrimaryKey(): array {
        return $this->primaryKey;
    }

    /**
     * Set the primary key columns.
     *
     * @param string $columns
     */
    protected function setPrimaryKey(string ...$columns): void {
        $this->primaryKey = $columns;
    }

    /**
     * Return an array suitable for a where clause for a primary key.
     *
     * @param mixed $id
     * @param mixed $ids
     * @return array
     */
    public function primaryWhere($id, ...$ids): array {
        $values = array_merge([$id], $ids);
        $where = [];
        foreach ($this->getPrimaryKey() as $i => $column) {
            $where[$column] = $values[$i] ?? null;
        }
        return $where;
    }

    /**
     * Select a single row.
     *
     * @param array $where Conditions for the select query.
     * @param array $options Options for the select query.
     *    - orderFields (string, array): Fields to sort the result by.
     *    - orderDirection (string): Sort direction for the order fields.
     *    - limit (int): Limit on the total results returned.
     *    - offset (int): Row offset before capturing the result.
     * @return array
     * @throws ValidationException If a row fails to validate against the schema.
     * @throws NoResultsException If no rows could be found.
     */
    public function selectSingle(array $where = [], array $options = []): array {
        $options[self::OPT_LIMIT] = 1;
        $rows = $this->get($where, $options);
        if (empty($rows)) {
            throw new NoResultsException("No rows matched the provided criteria.");
        }
        $result = reset($rows);
        return $result;
    }

    /**
     * Add a resource row.
     *
     * @param array $set Field values to set.
     * @param array $options An array of options to affect the insert.
     * @return int|true ID of the inserted row.
     * @throws Exception If an error is encountered while performing the query.
     */
    public function insert(array $set, array $options = []) {
        $set = $this->getWriteSchema()->validate($set);
        $result = $this->createSql()->insert($this->table, $set);
        if ($result === false) {
            // @codeCoverageIgnoreStart
            throw new Exception("An unknown error was encountered while inserting the row.");
            // @codeCoverageIgnoreEnd
        }
        // This is a bit of a kludge, but we want a true integer because we are otherwise string with schemas.
        if (is_numeric($result)) {
            $result = (int)$result;
        }
        return $result;
    }

    /**
     * @param \Gdn_Database $database
     */
    public function setDependencies(\Gdn_Database $database) {
        $this->database = $database;
    }

    /**
     * Get a clean SQL driver instance.
     *
     * @return \Gdn_SQLDriver
     */
    protected function createSql(): \Gdn_SQLDriver {
        $sql = clone $this->database->sql();
        $sql->reset();
        return $sql;
    }

    /**
     * Alias of `createSql()`.
     *
     * @return \Gdn_SQLDriver
     * @deprecated
     * @codeCoverageIgnore
     */
    protected function sql(): \Gdn_SQLDriver {
        return $this->createSql();
    }

    /**
     * Update existing resource rows.
     *
     * @param array $set Field values to set.
     * @param array $where Conditions to restrict the update.
     * @param array $options Options to control the update.
     * @throws Exception If an error is encountered while performing the query.
     * @return bool True.
     */
    public function update(array $set, array $where, array $options = []): bool {
        $set = $this->getWriteSchema()->validate($set, true);
        $this->createSql()->put($this->table, $set, $where);
        // If fully executed without an exception bubbling up, consider this a success.
        return true;
    }

    /**
     * Translate selects with some additional support.
     *
     * @param array $selects
     * @return array
     */
    private function translateSelects(array $selects): array {
        $negatives = [];
        foreach ($selects as $select) {
            if ($select[0] === '-') {
                $negatives[] = substr($select, 1);
            }
        }

        if (!empty($negatives)) {
            $columns = array_keys($this->getReadSchema()->getField('properties'));
            $selects = array_values(array_diff($columns, $negatives));
        }
        return $selects;
    }

    /**
     * Get or generate the schema returned by the database.
     *
     * @return Schema
     */
    private function getDatabaseSchema(): Schema {
        if ($this->databaseSchema === null) {
            $this->databaseSchema = $this->database->simpleSchema($this->getTable());
        }
        return $this->databaseSchema;
    }
}
