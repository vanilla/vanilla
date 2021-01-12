<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler\Meta;

use Garden\EventManager;
use Psr\Log\LoggerInterface;
use Throwable;
use Vanilla\Contracts\ConfigurationInterface;

/**
 * Class CronMetaDao
 */
class SchedulerMetaDao {

    protected const SCHEDULER_CONTROL_META_NAME = "Garden.Scheduler.ControlMeta";

    /** @var ConfigurationInterface */
    protected $config;

    /** @var EventManager */
    protected $eventManager;

    /** @var LoggerInterface */
    protected $logger;

    /**
     * CronMetaDao constructor.
     *
     * @param ConfigurationInterface $config
     * @param EventManager $eventManager
     * @param LoggerInterface $logger
     */
    public function __construct(ConfigurationInterface $config, EventManager $eventManager, LoggerInterface $logger) {
        $this->config = $config;
        $this->eventManager = $eventManager;
        $this->logger = $logger;
    }

    /**
     * @return SchedulerControlMeta|null
     */
    public function getControl(): ?SchedulerControlMeta {
        $values = $this->config->get(self::SCHEDULER_CONTROL_META_NAME, null);
        if ($values === null) {
            return null;
        } else {
            try {
                return new SchedulerControlMeta($values['lockTime'] ?? 0, $values['hostname'] ?? 'unknown');
            } catch (Throwable $t) {
                // If somehow we cannot instantiate a SchedulerControlMeta, we want to act as it doesn't exists
                return null;
            }
        }
    }

    /**
     * PutJob
     *
     * @param SchedulerControlMeta $schedulerControlMeta
     * @return bool
     */
    public function putControl(SchedulerControlMeta $schedulerControlMeta): bool {
        $values = [
            'lockTime' => $schedulerControlMeta->getLockTime(),
            'hostname' => $schedulerControlMeta->getHostname(),
        ];

        $this->config->saveToConfig(self::SCHEDULER_CONTROL_META_NAME, $values);

        return true;
    }
}
