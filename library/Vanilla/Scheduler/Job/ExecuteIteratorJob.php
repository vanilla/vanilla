<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler\Job;

use Garden\Schema\Schema;
use InvalidArgumentException;
use Iterator;
use Psr\Container\ContainerInterface;

/**
 * Execute an iterator in a local job.
 */
class ExecuteIteratorJob implements LocalJobInterface {

    public const OPT_ARGS = "args";

    public const OPT_CLASS = "class";

    public const OPT_METHOD = "method";

    /** @var ContainerInterface */
    private $container;

    /** @var string */
    private $class;

    /** @var string */
    private $method;

    /** @var array */
    private $args = [];

    /**
     * Setup the job.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    /**
     * Configure the job using a message.
     *
     * @param array $message
     */
    public function setMessage(array $message) {
        $schema = new Schema([
            self::OPT_ARGS . ":a",
            self::OPT_CLASS . ":s",
            self::OPT_METHOD . ":s",
        ]);
        $message = $schema->validate($message);

        $this->args = $message[self::OPT_ARGS];
        $this->class = $message[self::OPT_CLASS];
        $this->method = $message[self::OPT_METHOD];
    }

    /**
     * @inheritDoc
     */
    public function run(): JobExecutionStatus {
        $object = $this->container->get($this->class);

        if (method_exists($object, $this->method) === false) {
            throw new InvalidArgumentException("Method does not exist: " . get_class($object) . "::" . $this->method);
        }

        /** @var Iterator $iterator */
        $iterator = call_user_func_array([$object, $this->method], $this->args);

        if (!($iterator instanceof Iterator)) {
            throw new InvalidArgumentException("A valid iterator was not returned from " . get_class($object) . "::" . $this->method);
        }

        $iterations = 0;
        while ($iterator->valid()) {
            $iterations++;
            $iterator->next();
        }

        return JobExecutionStatus::complete();
    }
}
