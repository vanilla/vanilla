<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use Garden\Container\Container;
use Vanilla\Dashboard\Controllers\Api\JobStatusesApiController;
use Vanilla\Scheduler\Descriptor\NormalJobDescriptor;
use Vanilla\Scheduler\Job\JobExecutionStatus;
use Vanilla\Scheduler\Job\JobStatusModel;
use Vanilla\Scheduler\TrackingSlipInterface;
use VanillaTests\ExpectExceptionTrait;
use VanillaTests\Fixtures\Scheduler\InstantScheduler;
use VanillaTests\Fixtures\Scheduler\MockTrackableJob;
use VanillaTests\SchedulerTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Tests for the /api/v2/job-statuses endpoints.
 */
class JobStatusTest extends SiteTestCase
{
    use SchedulerTestTrait;
    use UsersAndRolesApiTestTrait;
    use ExpectExceptionTrait;

    /**
     * @param Container $container
     */
    public static function configureContainerBeforeStartup(Container $container)
    {
        $container->rule(JobStatusModel::class)->setShared(false);
    }

    /**
     * Cleanup before test.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->resetTable("jobStatus");
    }

    /**
     * Test our index endpoint.
     */
    public function testIndex()
    {
        $scheduler = $this->getScheduler();

        $slip1 = $scheduler->addJobDescriptor(self::trackableDescriptor(JobExecutionStatus::complete()));
        $slip2 = $scheduler->addJobDescriptor(self::trackableDescriptor(JobExecutionStatus::error()));

        $scheduler->pause();
        $slip3 = $scheduler->addJobDescriptor(self::trackableDescriptor(JobExecutionStatus::abandoned()));

        $response = $this->api()->get("/job-statuses");
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertRowsLike(
            [
                // Item 3 wasn't executed because we were paused.
                "jobExecutionStatus" => ["complete", "error", "received"],
                "jobTrackingID" => [$slip1->getTrackingID(), $slip2->getTrackingID(), $slip3->getTrackingID()],
            ],
            $response->getBody(),
            true,
            3
        );

        // Test filtering.
        $scheduler->resume();
        $response = $this->api()->get("/job-statuses", [
            "jobTrackingID" => $slip3->getTrackingID(),
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertRowsLike(
            [
                // Item 3 wasn't executed because we were paused.
                "jobExecutionStatus" => ["abandoned"],
                "jobTrackingID" => [$slip3->getTrackingID()],
            ],
            $response->getBody(),
            true,
            1
        );
    }

    /**
     * Test validation of our user.
     */
    public function testUserValidation()
    {
        $scheduler = $this->getScheduler();
        $otherUser = $this->createUser();
        $otherAdmin = $this->createUser(["roleID" => [\RoleModel::ADMIN_ID]]);

        $otherUserSlip = $scheduler->addJobDescriptor(
            self::trackableDescriptor(JobExecutionStatus::complete(), $otherUser["userID"])
        );
        $otherAdminSlip = $scheduler->addJobDescriptor(
            self::trackableDescriptor(JobExecutionStatus::complete(), $otherAdmin["userID"])
        );

        $this->runWithUser(function () use ($otherUserSlip, $otherAdminSlip, $otherAdmin) {
            $this->assertIndexSlips([$otherUserSlip], []);

            // Can't get the other users slips.
            $this->assertIndexSlips([], ["jobTrackingID" => $otherAdminSlip->getTrackingID()]);

            // Permission error when getting other users slips.
            $this->runWithExpectedExceptionCode(400, function () use ($otherAdmin) {
                $this->api()->get("/job-statuses", [
                    "trackingUserID" => $otherAdmin["userID"],
                ]);
            });
        }, $otherUser);

        $this->runWithUser(function () use ($otherUserSlip, $otherUser) {
            // Able to fetch slips of other users.
            $this->assertIndexSlips([$otherUserSlip], ["trackingUserID" => $otherUser["userID"]]);
        }, $otherAdmin);

        // Non-existent user.
        $this->runWithExpectedExceptionCode(400, function () use ($otherAdmin) {
            $this->api()->get("/job-statuses", [
                "trackingUserID" => 523141414210,
            ]);
        });
    }

    /**
     * Test polling on the poll endpoint.
     */
    public function testPoll()
    {
        $scheduler = $this->getScheduler();
        $scheduler->pause();
        $slip = $scheduler->addJobDescriptor(self::trackableDescriptor(JobExecutionStatus::complete()));

        JobStatusesApiController::$callAfterPollIterationsDoNotUseInProduction = [
            JobStatusTest::class,
            "resumePauseRequeueJob",
        ];

        $response = $this->api()->post("/job-statuses/poll", ["maxDuration" => 1]);
        $this->assertEquals(200, $response->getStatusCode());
        $body = $response->getBody();
        $this->assertRowsLike(["jobTrackingID" => [$slip->getTrackingID()]], $body["updatedJobs"], true, 1);
        $this->assertEquals(1, $body["incompleteJobCount"]);

        // Go for the full duration and get nothing.
        $response = $this->api()->post("/job-statuses/poll", ["maxDuration" => 1]);
        $this->assertEquals(200, $response->getStatusCode());
        $body = $response->getBody();
        $this->assertEmpty($body["updatedJobs"]);
        $this->assertEquals(1, $body["incompleteJobCount"]);

        // Now wrap it all up.
        JobStatusesApiController::$callAfterPollIterationsDoNotUseInProduction = [$scheduler, "resume"];
        $response = $this->api()->post("/job-statuses/poll", ["maxDuration" => 1]);
        $this->assertEquals(200, $response->getStatusCode());
        $body = $response->getBody();
        $this->assertCount(1, $body["updatedJobs"]);
        $this->assertEquals(0, $body["incompleteJobCount"]);
    }

    /**
     * Resume jobs, pause and requeue another.
     */
    public static function resumePauseRequeueJob()
    {
        /** @var InstantScheduler $scheduler */
        $scheduler = \Gdn::scheduler();
        $scheduler->resume();
        // Queue another so we can see remaining jobs.
        $scheduler->pause();
        $scheduler->addJobDescriptor(self::trackableDescriptor(JobExecutionStatus::complete()));
    }

    /**
     * Test that various cases are not allowed to poll.
     */
    public function testPollNotAllowed()
    {
        $this->runWithExpectedExceptionCode(500, function () {
            $this->runWithConfig(["Cache.Enabled" => false], function () {
                $this->api()->post("/job-statuses/poll");
            });
        });

        $this->runWithExpectedExceptionCode(403, function () {
            // We don't have any statuses to track.
            $this->api()->post("/job-statuses/poll");
        });

        $this->runWithExpectedExceptionCode(400, function () {
            // Bad user ID.
            $this->api()->post("/job-statuses/poll", ["trackingUserID" => 1241414]);
        });

        // No permission for other users.
        $otherUser = $this->createUser();
        $adminUserID = $this->api()->getUserID();
        $this->runWithUser(function () use ($adminUserID) {
            $this->runWithExpectedExceptionCode(400, function () use ($adminUserID) {
                // Bad user ID.
                $this->api()->post("/job-statuses/poll", ["trackingUserID" => $adminUserID]);
            });
        }, $otherUser);
    }

    /**
     * Assert that a query on the index returns jobs tracking for certain slips.
     *
     * @param array $expectedSlips
     * @param array $query
     */
    private function assertIndexSlips(array $expectedSlips, array $query)
    {
        $response = $this->api()->get("/job-statuses", $query);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertRowsLike(
            [
                // Item 3 wasn't executed because we were paused.
                "jobTrackingID" => array_map(function (TrackingSlipInterface $slip) {
                    return $slip->getTrackingID();
                }, $expectedSlips),
            ],
            $response->getBody(),
            true,
            count($expectedSlips)
        );
    }

    /**
     * Create a trackable job descriptor for a user.
     *
     * @param JobExecutionStatus $jobStatus
     * @param int|null $userID
     * @return NormalJobDescriptor
     */
    private static function trackableDescriptor(JobExecutionStatus $jobStatus, int $userID = null): NormalJobDescriptor
    {
        $descriptor = new NormalJobDescriptor(MockTrackableJob::class, [
            "status" => $jobStatus->getStatus(),
        ]);
        $descriptor->setTrackingUserID($userID ?? \Gdn::session()->UserID);
        return $descriptor;
    }
}
