<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Exception;
use Garden\Pipeline;
use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Vanilla\InjectableInterface;

/**
 * Basic model class.
 */
class Model implements InjectableInterface {

    /** @var string Destination for processors looking to alter read operation results. */
    const PIPELINE_READ = "read";

    /** @var string Destination for processors looking to alter write operations. */
    const PIPELINE_WRITE = "write";

    /** @var \Gdn_Database */
    private $database;

    /** @var Pipeline */
    private $readPipeline;

    /** @var Schema */
    private $readSchema;

    /** @var string */
    private $table;

    /** @var Pipeline */
    private $writePipeline;

    /** @var Schema */
    private $writeSchema;

    /**
     * Basic model constructor.
     *
     * @param string $table Database table associated with this resource.
     */
    public function __construct(string $table) {
        $this->table = $table;
        $this->readPipeline = new Pipeline();
        $this->writePipeline = new Pipeline();
    }

    /**
     * Add a new processor to a pipeline.
     *
     * @param callable $processor
     * @param string $target
     * @return Model
     * @throws Exception If an invalid pipeline is specified.
     */
    public function addToPipeline(callable $processor, string $target): Model {
        $pipeline = $this->getPipeline($target);

        $pipeline->addProcessor($processor);
        return $this;
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
     * Make sure we have configured schemas available to the instance.
     */
    private function ensureSchemas() {
        if ($this->readSchema === null || $this->writeSchema === null) {
            $schema = $this->database->simpleSchema($this->table);

            if (!($this->readSchema instanceof Schema)) {
                $this->readSchema = $this->configureReadSchema(clone $schema);
            }
            if (!($this->writeSchema instanceof Schema)) {
                $this->writeSchema = $this->configureWriteSchema(clone $schema);
            }
        }
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
    public function get(array $where = [], array $options = []): array {
        // Lazy load schemas.
        $this->ensureSchemas();

        $orderFields = $options["orderFields"] ?? "";
        $orderDirection = $options["orderDirection"] ?? "asc";
        $limit = $options["limit"] ?? false;
        $offset = $options["offset"] ?? 0;

        $result = $this->sql()
            ->getWhere($this->table, $where, $orderFields, $orderDirection, $limit, $offset)
            ->resultArray();

        $schema = Schema::parse([":a" => $this->readSchema]);
        $result = $schema->validate($result);

        return $result;
    }

    /**
     * Get a pipeline by its name.
     *
     * @param string $name
     * @return Pipeline
     * @throws Exception If the pipeline name specified is invalid.
     */
    private function getPipeline(string $name): Pipeline {
        switch ($name) {
            case self::PIPELINE_READ:
                $pipeline = $this->readPipeline;
                break;
            case self::PIPELINE_WRITE:
                $pipeline = $this->writePipeline;
                break;
            default:
                throw new Exception("Invalid pipeline specified.");
        }
        return $pipeline;
    }

    /**
     * Get a single row.
     *
     * @param array $where Conditions for the select query.
     * @param array $options Options for the select query.
     *    - orderFields (string, array): Fields to sort the result by.
     *    - orderDirection (string): Sort direction for the order fields.
     *    - limit (int): Limit on the total results returned.
     *    - offset (int): Row offset before capturing the result.
     * @return array
     * @throws ValidationException If a row fails to validate against the schema.
     * @throws Exception If no rows could be found.
     */
    public function getSingle(array $where = [], array $options = []): array {
        $options["limit"] = 1;
        $rows = $this->get($where, $options);
        if (empty($rows)) {
            throw new Exception("No rows matched the provided criteria.");
        }
        $row = reset($rows);
        $result = $this->invokePipeline($row, self::PIPELINE_READ);
        return $result;
    }

    /**
     * Add a resource row.
     *
     * @param array $set Field values to set.
     * @return mixed ID of the inserted row.
     * @throws Exception If an error is encountered while performing the query.
     */
    public function insert(array $set) {
        // Lazy load schemas.
        $this->ensureSchemas();

        $set = $this->writeSchema->validate($set);
        $result = $this->sql()->insert($this->table, $set);
        if ($result === false) {
            throw new Exception("An unknown error was encountered while inserting the row.");
        }
        return $result;
    }

    /**
     * Invoke a pipeline on a value.
     *
     * @param mixed $value
     * @param string $name
     * @return mixed
     * @throws Exception If the pipeline name specified is invalid.
     */
    private function invokePipeline($value, string $name) {
        $result = $this->getPipeline($name)->process($value);
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
     * @throws Exception If an error is encountered while performing the query.
     * @return bool True.
     */
    public function update(array $set, array $where): bool {
        // Lazy load schemas.
        $this->ensureSchemas();

        $set = $this->writeSchema->validate($set, true);
        $this->sql()->put($this->table, $set, $where);
        // If fully executed without an exception bubbling up, consider this a success.
        return true;
    }
}
