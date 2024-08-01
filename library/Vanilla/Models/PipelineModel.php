<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Exception;
use Vanilla\Database\Operation;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Database\Operation\CurrentIPAddressProcessor;
use Vanilla\Database\Operation\CurrentUserFieldProcessor;
use Vanilla\Database\Operation\Pipeline;
use Vanilla\Database\Operation\Processor;
use Vanilla\InjectableInterface;
use Webmozart\Assert\Assert;

/**
 * Basic model class with database operation pipeline support.
 */
class PipelineModel extends Model implements InjectableInterface
{
    public const OPT_CALLBACK = "callback";

    public const OPT_RUN_PIPELINE = "runPipeline";

    /** @var Pipeline */
    protected $pipeline;

    /**
     * Model constructor.
     *
     * @param string $table Database table associated with this resource.
     */
    public function __construct(string $table)
    {
        parent::__construct($table);
        $this->pipeline = new Pipeline(function (Operation $op) {
            return $this->handleInnerOperation($op);
        });
    }

    /**
     * @param bool $includeUpdate
     * @param bool $includeIPAddress
     * @param bool $preciseDates
     */
    public function addInsertUpdateProcessors(
        bool $includeUpdate = true,
        bool $includeIPAddress = false,
        bool $preciseDates = false
    ): void {
        $request = \Gdn::request();
        $session = \Gdn::session();
        $userProcessor = new CurrentUserFieldProcessor($session);
        $userProcessor->camelCase();

        $dateProcessor = new CurrentDateFieldProcessor(["dateInserted"], ["dateUpdated"], $preciseDates);

        $this->addPipelineProcessor($userProcessor);
        $this->addPipelineProcessor($dateProcessor);

        if (!$includeUpdate) {
            $userProcessor->setUpdateFields([]);
            $dateProcessor->setUpdateFields([]);
        }

        if ($includeIPAddress) {
            $ipProcessor = new CurrentIPAddressProcessor($request);
            $ipProcessor->camelCase();
            if (!$includeUpdate) {
                $ipProcessor->setUpdateFields([]);
            }
            $this->addPipelineProcessor($ipProcessor);
        }
    }

    /**
     * Add a database operations processor to the pipeline.
     *
     * @param Processor $processor
     */
    public function addPipelineProcessor(Processor $processor)
    {
        $this->pipeline->addProcessor($processor);
    }

    /**
     * Add a database operations processor to the pipeline.
     *
     * @param Processor $processor
     * @deprecated Avoid using post-processors.
     */
    public function addPipelinePostProcessor(Processor $processor)
    {
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
    public function select(array $where = [], array $options = []): array
    {
        $operation = new Operation();
        $operation->setType(Operation::TYPE_SELECT);
        $operation->setCaller($this);
        $operation->setWhere($where);
        $operation->setOptions($options);
        $result = $this->performOperation($operation, $options[self::OPT_RUN_PIPELINE] ?? true);
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
    public function insert(array $set, array $options = [])
    {
        if (is_string($options)) {
            trigger_error("String options are deprecated in PipelineModel::insert().", E_USER_DEPRECATED);
            $options = [self::OPT_MODE => $options];
        }
        $options += [
            self::OPT_MODE => Operation::MODE_DEFAULT,
        ];

        $operation = new Operation();
        $operation->setType(Operation::TYPE_INSERT);
        $operation->setCaller($this);
        $operation->setSet($set);
        $operation->setOptions($options);
        $result = $this->performOperation($operation, $options[self::OPT_RUN_PIPELINE] ?? true);
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
    public function update(array $set, array $where, array $options = []): bool
    {
        if (is_string($options)) {
            trigger_error("String options are deprecated in PipelineModel::update().", E_USER_DEPRECATED);
            $options = [self::OPT_MODE => $options];
        }
        $options += [
            self::OPT_MODE => Operation::MODE_DEFAULT,
        ];

        $operation = new Operation();
        $operation->setType(Operation::TYPE_UPDATE);
        $operation->setCaller($this);
        $operation->setOptions($options);
        $operation->setSet($set);
        $operation->setWhere($where);

        $metaInformation = $options[self::OPT_META] ?? null;

        if ($metaInformation) {
            if (is_array($metaInformation)) {
                foreach ($metaInformation as $key => $meta) {
                    $operation->setMeta($key, $meta);
                }
            }
        }

        $result = $this->performOperation($operation, $options[self::OPT_RUN_PIPELINE] ?? true);
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
    public function delete(array $where, array $options = []): bool
    {
        $operation = new Operation();
        $operation->setType(Operation::TYPE_DELETE);
        $operation->setCaller($this);
        $operation->setWhere($where);
        $operation->setOptions($options);
        $result = $this->performOperation($operation, $options[self::OPT_RUN_PIPELINE] ?? true);
        return $result;
    }

    /**
     * Handle a database operation.
     *
     * @param Operation $op
     * @return mixed
     */
    protected function handleInnerOperation(Operation $op)
    {
        $callback = $op->getOptionItem(self::OPT_CALLBACK);
        if ($callback !== null) {
            Assert::isCallable($callback);
            return $callback($op);
        }

        return match ($op->getType()) {
            Operation::TYPE_INSERT => parent::insert($op->getSet(), $op->getOptions()),
            Operation::TYPE_UPDATE => parent::update($op->getSet(), $op->getWhere(), $op->getOptions()),
            Operation::TYPE_DELETE => parent::delete($op->getWhere(), $op->getOptions()),
            Operation::TYPE_SELECT => parent::select($op->getWhere(), $op->getOptions()),
            default => throw new \InvalidArgumentException("Invalid operation: " . $op->getType()),
        };
    }

    /**
     * Execute a database operation.
     *
     * @param Operation $op
     * @param bool $runPipeline
     * @return mixed
     */
    private function performOperation(Operation $op, bool $runPipeline = true)
    {
        if ($runPipeline === false) {
            return $this->handleInnerOperation($op);
        }
        return $this->pipeline->processOperation($op);
    }
}
