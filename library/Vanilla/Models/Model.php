<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla\Models;

use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Vanilla\InjectableInterface;

/**
 * Basic model class.
 */
class Model implements InjectableInterface {

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
     * @param string $table Database table associated with this resource.
     */
    public function __construct(string $table) {
        $this->table = $table;
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
     * @param \Gdn_Database $database
     */
    public function setDependencies(\Gdn_Database $database) {
        $this->database = $database;

        $schema = $this->database->simpleSchema($this->table);
        $this->readSchema = $this->configureReadSchema(clone $schema);
        $this->writeSchema = $this->configureWriteSchema(clone $schema);
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
     * @return bool True.
     */
    public function update(array $set, array $where): bool {
        $set = $this->writeSchema->validate($set, true);
        $this->sql()->put($this->table, $set, $where);
        // If fully executed without an exception bubbling up, consider this a success.
        return true;
    }
}
