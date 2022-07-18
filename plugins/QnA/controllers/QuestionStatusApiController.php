<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPLv2
 */

use Vanilla\QnA\Models\QuestionStatusMigrate;
use Vanilla\Scheduler\LongRunner;
use Vanilla\Scheduler\LongRunnerAction;

/**
 * API Controller for the `/question-status` .
 *  Controller for the long runner process to migrate question statuses
 */
class QuestionStatusApiController extends AbstractApiController
{
    /** @var LongRunner */
    private $longRunner;

    /**
     * QuestionStatusApiController constructor.
     *
     * @param LongRunner $longRunner
     */
    public function __construct(LongRunner $longRunner)
    {
        $this->longRunner = $longRunner;
    }

    /**
     * Long Runner job to migrate current question status with
     * corresponding unified statuses
     */
    public function patch_migrate()
    {
        $this->permission("site.manage");

        return $this->longRunner->runApi(
            new LongRunnerAction(QuestionStatusMigrate::class, "migrateStatusIterator", [])
        );
    }
}
