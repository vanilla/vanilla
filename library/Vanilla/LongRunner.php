<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla;

use Garden\Web\Data;
use Gdn_Controller;
use Psr\Container\ContainerInterface;
use Vanilla\Scheduler\Descriptor\NormalJobDescriptor;
use Vanilla\Scheduler\Job\ExecuteIteratorJob;
use Vanilla\Scheduler\Job\JobPriority;
use Vanilla\Scheduler\SchedulerInterface;
use Vanilla\Utility\ModelUtils;
use Vanilla\Web\SystemTokenUtils;

/**
 * This class provides utility methods for executing longer running tasks that use iterators to break up their work.
 */
class LongRunner {

    public const OPT_LOCAL_JOB = "localJob";

    public const OPT_TIMEOUT = "timeout";

    /** @var ContainerInterface */
    private $container;

    /** @var SchedulerInterface */
    private $scheduler;

    /** @var SystemTokenUtils */
    private $tokenUtils;

    /**
     * LongRunner constructor.
     *
     * @param ContainerInterface $container
     * @param SystemTokenUtils $tokenUtils
     * @param SchedulerInterface $scheduler
     */
    public function __construct(ContainerInterface $container, SystemTokenUtils $tokenUtils, SchedulerInterface $scheduler) {
        $this->container = $container;
        $this->tokenUtils = $tokenUtils;
        $this->scheduler = $scheduler;
    }

    /**
     * Generate a response indicating the next iteration of a job.
     *
     * @param string $class
     * @param string $method
     * @param array $args
     * @param array $options
     * @return Data
     */
    public function makeJobRunResponse(string $class, string $method, array $args, array $options = []): Data {
        $payload = [
            "method" => "$class::$method",
            "args" => $args,
        ];
        $jwt = $this->tokenUtils->encode($payload);

        return new Data([
            'status' => 202,
            'statusType' => 'incomplete',
            SystemTokenUtils::CLAIM_REQUEST_BODY => $jwt
        ], 202);
    }

    /**
     * Execute an iterator in the context of the API and return a response indicating its progress.
     *
     * @param string $className
     * @param string $method
     * @param array $args
     * @param array $options
     * @return Data
     */
    public function runApi(string $className, string $method, array $args, array $options = []): Data {
        $options += [
            self::OPT_LOCAL_JOB => true,
            self::OPT_TIMEOUT => 30,
        ];

        if ($options[self::OPT_LOCAL_JOB]) {
            // Queue up a job to execute the entire iterator.
            $job = new NormalJobDescriptor(ExecuteIteratorJob::class);
            $job->setMessage([
                ExecuteIteratorJob::OPT_CLASS => $className,
                ExecuteIteratorJob::OPT_METHOD => $method,
                ExecuteIteratorJob::OPT_ARGS => $args,
            ]);
            $job->setPriority(JobPriority::normal());
            $job->setDelay(0);
            $this->scheduler->addJobDescriptor($job);

            return new Data(null, 204);
        }

        // New up the class and iteratively execute the method.
        $obj = $this->container->get($className);
        $iterator = call_user_func_array([$obj, $method], $args);
        $finished = ModelUtils::iterateWithTimeout($iterator, $options[self::OPT_TIMEOUT]);

        if ($finished) {
            return new Data(null, 204);
        } else {
            return $this->makeJobRunResponse($className, $method, $args, $options);
        }
    }
}
