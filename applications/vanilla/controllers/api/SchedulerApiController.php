<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\EventManager;
use Vanilla\Scheduler\Auth\AdHocAuth;
use Vanilla\Scheduler\Auth\AdHocAuthException;
use Vanilla\Scheduler\Job\JobExecutionType;
use Vanilla\Scheduler\Meta\SchedulerMetaDao;
use Vanilla\Scheduler\SchedulerInterface;

/**
 * Class SchedulerApiController
 */
class SchedulerApiController extends AbstractApiController {
    public const CRON_TRIGGER_EVENT = 'CRON_TRIGGER_EVENT';

    /** @var SchedulerInterface */
    protected $scheduler;

    /** @var EventManager */
    protected $eventManager;

    /** @var AdHocAuth */
    protected $auth;

    /** @var SchedulerMetaDao */
    protected $schedulerMetaDao;

    /**
     * SchedulerApiController constructor
     *
     * @param SchedulerInterface $scheduler
     * @param EventManager $eventManager
     * @param AdHocAuth $auth
     * @param SchedulerMetaDao $schedulerMetaDao
     */
    public function __construct(
        SchedulerInterface $scheduler,
        EventManager $eventManager,
        AdHocAuth $auth,
        SchedulerMetaDao $schedulerMetaDao
    ) {
        $this->scheduler = $scheduler;
        $this->eventManager = $eventManager;
        $this->auth = $auth;
        $this->schedulerMetaDao = $schedulerMetaDao;
    }

    /**
     * Post Cron
     *
     * @throws AdHocAuthException On bad authentication.
     */
    public function post_cron() {
        $this->permissionOrToken('Garden.Scheduler.Cron');

        $this->scheduler->setExecutionType(JobExecutionType::cron());
        $this->eventManager->fire(self::CRON_TRIGGER_EVENT);

        return "success";
    }

    /**
     * Authenticate based on permission or token
     *
     * @param string|array $permission The permissions you are requiring.
     * @throws AdHocAuthException On bad authentication.
     */
    protected function permissionOrToken($permission) {
        try {
            $this->permission($permission);
        } catch (Exception $standardAuthenticationFailed) {
            $this->auth->validateToken();
        }
    }
}
