<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Exception;
use Garden\Schema\Schema;
use Vanilla\Database\Operation;
use Vanilla\Database\Operation\Pipeline;
use Vanilla\Database\Operation\Processor;
use Vanilla\InjectableInterface;

/**
 * Basic model class with database operation pipeline support.
 */
class PipelineModel extends Model implements InjectableInterface {

    /** @var Pipeline */
    protected $pipeline;

    /**
     * Model constructor.
     *
     * @param string $table Database table associated with this resource.
     */
    public function __construct(string $table) {
        parent::__construct($table);
        $this->pipeline = new Pipeline();
    }

    /**
     * Add a database operations processor to the pipeline.
     *
     * @param Processor $processor
     */
    public function addPipelineProcessor(Processor $processor) {
        $this->pipeline->addProcessor($processor);
    }

    /**
     * Add a database operations processor to the pipeline.
     *
     * @param Processor $processor
     */
    public function addPipelinePostProcessor(Processor $processor) {
        $this->pipeline->addPostProcessor($processor);
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
     */
    public function get(array $where = [], array $options = []): array {
        $databaseOperation = new Operation();
        $databaseOperation->setType(Operation::TYPE_SELECT);
        $databaseOperation->setCaller($this);
        $databaseOperation->setWhere($where);
        $databaseOperation->setOptions($options);
        $result = $this->pipeline->process($databaseOperation, function (Operation $databaseOperation) {
            return parent::get(
                $databaseOperation->getWhere(),
                $databaseOperation->getOptions()
            );
        });
        return $result;
    }

    /**
     * Add a resource row.
     *
     * @param array $set Field values to set.
     * @param array $options Operation mode (force || default).
     * @return mixed ID of the inserted row.
     * @throws Exception If an error is encountered while performing the query.
     */
    public function insert(array $set, $options = []) {
        if (is_string($options)) {
            deprecated("String options are deprecated.");
            $options = [self::OPT_MODE => $options];
        }
        $options += [
            self::OPT_MODE => Operation::MODE_DEFAULT,
        ];

        $databaseOperation = new Operation();
        $databaseOperation->setType(Operation::TYPE_INSERT);
        $databaseOperation->setCaller($this);
        $databaseOperation->setSet($set);
        $databaseOperation->setOptions($options);
        $result = $this->pipeline->process($databaseOperation, function (Operation $databaseOperation) {
            return parent::insert($databaseOperation->getSet(), $databaseOperation->getOptions());
        });
        return $result;
    }

    /**
     * Update existing resource rows.
     *
     * @param array $set Field values to set.
     * @param array $where Conditions to restrict the update.
     * @param array $options Update options.
     * @throws Exception If an error is encountered while performing the query.
     * @return bool True.
     */
    public function update(array $set, array $where, $options = []): bool {
        if (is_string($options)) {
            deprecated("String options are deprecated.");
            $options = [self::OPT_MODE => $options];
        }
        $options += [
            self::OPT_MODE => Operation::MODE_DEFAULT,
        ];

        $databaseOperation = new Operation();
        $databaseOperation->setType(Operation::TYPE_UPDATE);
        $databaseOperation->setCaller($this);
        $databaseOperation->setOptions($options);
        $databaseOperation->setSet($set);
        $databaseOperation->setWhere($where);
        $result = $this->pipeline->process($databaseOperation, function (Operation $databaseOperation) {
            return parent::update(
                $databaseOperation->getSet(),
                $databaseOperation->getWhere()
            );
        });
        return $result;
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
        $databaseOperation = new Operation();
        $databaseOperation->setType(Operation::TYPE_DELETE);
        $databaseOperation->setCaller($this);
        $databaseOperation->setWhere($where);
        $databaseOperation->setOptions($options);
        $result = $this->pipeline->process($databaseOperation, function (Operation $databaseOperation) {
            return parent::delete(
                $databaseOperation->getWhere(),
                $databaseOperation->getOptions()
            );
        });
        return $result;
    }
}
