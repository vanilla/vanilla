<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Exception;
use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Garden\Schema\ValidationField;
use Vanilla\Database\SetLiterals\RawExpression;
use Vanilla\Database\SetLiterals\SetLiteral;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\InjectableInterface;
use Vanilla\Utility\ArrayUtils;
use Vanilla\Utility\InstanceValidatorSchema;
use Webmozart\Assert\Assert;

/**
 * Basic model class.
 */
class Model implements InjectableInterface
{
    public const OPT_LIMIT = "limit";
    public const OPT_OFFSET = "offset";
    public const OPT_SELECT = "select";
    public const OPT_ORDER = "order";
    public const OPT_DIRECTION = "orderDirection";
    public const OPT_MODE = "mode";
    public const OPT_REPLACE = "replace";
    public const OPT_IGNORE = "ignore";
    public const OPT_META = "meta";
    public const OPT_JOINS = "joins";

    /** @var \Gdn_Database */
    protected $database;

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
    public function __construct(string $table)
    {
        $this->table = $table;
        $this->setPrimaryKey($table . "ID");
    }

    /**
     * Get the name of the table.
     *
     * @return string
     */
    public function getTableName(): string
    {
        return $this->table;
    }

    /**
     * Configure a Garden Schema instance for read operations by the model.
     *
     * @param Schema $schema Schema representing the resource's database table.
     * @return Schema Currently configured read schema.
     */
    protected function configureReadSchema(Schema $schema): Schema
    {
        // Child classes can make adjustments as necessary.
        return $schema;
    }

    /**
     * Get the schema used to validate selects.
     *
     * @return Schema
     */
    public function getReadSchema(): Schema
    {
        if ($this->readSchema === null) {
            $schema = clone $this->getDatabaseSchema();
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
    public function getWriteSchema(): Schema
    {
        if ($this->writeSchema === null) {
            $schema = clone $this->getDatabaseSchema();
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
    protected function configureWriteSchema(Schema $schema): Schema
    {
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
    public function delete(array $where, array $options = []): bool
    {
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
    protected function ensureSchemas()
    {
        $this->getReadSchema();
        $this->getWriteSchema();
    }

    /**
     * Select a paging count.
     *
     * @param array $where
     * @param int|null $limit
     * @return int
     */
    public function selectPagingCount(array $where, int $limit = null): int
    {
        $limit = $limit ?? \Gdn::config("Vanilla.APIv2.MaxCount", 10000);
        $innerQuery = $this->createSql()
            ->from($this->getTable())
            ->select($this->getPrimaryKey())
            ->where($where)
            ->limit($limit)
            ->getSelect(true);

        $countQuery = <<<SQL
SELECT COUNT(*) as count FROM ({$innerQuery}) iq
SQL;

        $result = $this->createSql()->query($countQuery);

        return $result->firstRow(DATASET_TYPE_ARRAY)["count"];
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
     * @todo Document support for the "order" option.
     * @todo Add support for a "page" option to set the limit.
     */
    public function select(array $where = [], array $options = []): array
    {
        $query = $this->createSql()
            ->from($this->table)
            ->where($where);

        $query = $query->applyModelOptions($options, $this->getReadSchema());
        $result = $query->get()->resultArray();

        $result = $this->validateOutputRows($result, $options);
        return $result;
    }

    /**
     * Validate output rows from a select.
     *
     * @param array $rows
     * @param array $options
     * @return array
     */
    protected function validateOutputRows(array $rows, array $options)
    {
        $selects = $options[self::OPT_SELECT] ?? [];
        if (is_string($selects)) {
            $selects = ArrayUtils::explodeTrim(",", $selects);
        }
        $selects = \Gdn_SQLDriver::translateSelects($selects, $this->getReadSchema());
        if (empty($selects)) {
            $schema = $this->getReadSchema();
        } else {
            $selectExpressions = $this->createSql()->parseSelectExpression($selects);
            $selectFinalFieldNames = [];
            foreach ($selectExpressions as $selectExpression) {
                $selectFinalFieldNames[] = $selectExpression["Alias"] ?: $selectExpression["Field"];
            }

            $schema = Schema::parse($selectFinalFieldNames)->add($this->getReadSchema());
        }
        foreach ($rows as &$row) {
            $row = $schema->validate($row);
        }
        return $rows;
    }

    /**
     * Apply options to a query.
     *
     * @param \Gdn_SQLDriver $query
     * @param array $options
     *
     * @return \Gdn_SQLDriver
     *
     * @deprecated Use {@link \Gdn_SQLDriver::applyModelOptions()} instead.
     */
    protected function applyOptionsToQuery(\Gdn_SQLDriver $query, array $options = []): \Gdn_SQLDriver
    {
        return $query->applyModelOptions($options, $this->getReadSchema());
    }

    /**
     * An alias of `select()`.
     *
     * @param array $where
     * @param array $options
     * @return array
     * @deprecated Use Model::select
     */
    public function get(array $where = [], array $options = []): array
    {
        return $this->select($where, $options);
    }

    /**
     * Get the database table name.
     *
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Get the primary key columns.
     *
     * @return array
     */
    public function getPrimaryKey(): array
    {
        return $this->primaryKey;
    }

    /**
     * Set the primary key columns.
     *
     * @param string $columns
     */
    protected function setPrimaryKey(string ...$columns): void
    {
        $this->primaryKey = $columns;
    }

    /**
     * Return an array suitable for a where clause for a primary key.
     *
     * @param mixed $id
     * @param mixed $ids
     * @return array
     */
    public function primaryWhere($id, ...$ids): array
    {
        $values = array_merge([$id], $ids);
        $where = [];
        foreach ($this->getPrimaryKey() as $i => $column) {
            $where[$column] = $values[$i] ?? null;
        }
        return $where;
    }

    /**
     * Extract the primary key out of a row.
     *
     * @param array $row The row to pluck.
     * @return array Returns an array suitable to pass as a where parameter.
     */
    public function pluckPrimaryWhere(array $row): array
    {
        $where = [];
        foreach ($this->getPrimaryKey() as $column) {
            $where[$column] = $row[$column];
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
    public function selectSingle(array $where = [], array $options = []): array
    {
        $options[self::OPT_LIMIT] = 1;
        $rows = $this->select($where, $options);
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
    public function insert(array $set, array $options = [])
    {
        $options += [
            self::OPT_REPLACE => false,
            self::OPT_IGNORE => false,
        ];

        $set = $this->getWriteSchema()->validate($set);

        $sql = $this->createSql();
        if ($options[self::OPT_REPLACE]) {
            $sql->options("Replace", true);
        } elseif ($options[self::OPT_IGNORE]) {
            $sql->options("Ignore", true);
        }
        $result = $sql->insert($this->table, $set);
        if ($result === false) {
            // @codeCoverageIgnoreStart
            throw new Exception("An unknown error was encountered while inserting the row.");
            // @codeCoverageIgnoreEnd
        }
        // This is a bit of a kludge, but we want a true integer because we are otherwise string with schemas.
        if (is_numeric($result)) {
            $result = (int) $result;
        }
        return $result;
    }

    /**
     * @param \Gdn_Database $database
     */
    public function setDependencies(\Gdn_Database $database)
    {
        $this->database = $database;
    }

    /**
     * Get a clean SQL driver instance.
     *
     * @return \Gdn_SQLDriver
     */
    protected function createSql(): \Gdn_SQLDriver
    {
        return $this->database->createSql();
    }

    /**
     * Alias of `createSql()`.
     *
     * @return \Gdn_SQLDriver
     * @deprecated
     * @codeCoverageIgnore
     */
    protected function sql(): \Gdn_SQLDriver
    {
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
    public function update(array $set, array $where, array $options = []): bool
    {
        $set = $this->getWriteSchema()->validate($set, true);
        $this->createSql()->put($this->table, $set, $where);
        // If fully executed without an exception bubbling up, consider this a success.
        return true;
    }

    /**
     * Get or generate the schema returned by the database.
     *
     * @return Schema
     */
    private function getDatabaseSchema(): Schema
    {
        if ($this->databaseSchema === null) {
            $this->databaseSchema = $this->database->simpleSchema($this->getTable());
        }
        return $this->databaseSchema;
    }

    /**
     * Filter a list of recordIDs to only ones that currently exist.
     *
     * @param int[] $recordIDs The incoming recordIDs.
     *
     * @return array The filtered recordIDs.
     */
    public function filterExistingRecordIDs(array $recordIDs): array
    {
        $primaryKey = $this->getPrimaryKey();
        Assert::count($primaryKey, 1, "filterExistingRecordIDs() only works on simple primary keys.");
        $primaryKey = $primaryKey[0];
        $existing = $this->createSql()
            ->select($primaryKey)
            ->from($this->table)
            ->where($primaryKey, $recordIDs)
            ->get()
            ->column($primaryKey);
        return $existing;
    }
}
