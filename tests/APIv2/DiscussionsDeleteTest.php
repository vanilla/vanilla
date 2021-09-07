<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use Garden\Events\ResourceEvent;
use Vanilla\Http\InternalRequest;
use Vanilla\Scheduler\Driver\LocalDriverSlip;
use Vanilla\Scheduler\Job\JobExecutionProgress;
use Vanilla\Scheduler\Job\LocalApiBulkDeleteJob;
use Vanilla\Web\Middleware\LogTransactionMiddleware;
use VanillaTests\DatabaseTestTrait;
use VanillaTests\EventSpyTestTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SchedulerTestTrait;
use VanillaTests\SiteTestCase;

/**
 * Test deletion of a discussion.
 */
class DiscussionsDeleteTest extends SiteTestCase {

    use CommunityApiTestTrait;
    use SchedulerTestTrait;
    use DatabaseTestTrait;
    use EventSpyTestTrait;

    /**
     * @inheritdoc
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetTable('Log');
        $this->resetTable('jobStatus');
    }

    /**
     * Test that deleting a discussion deletes all comments and logs them with the correct transactionID.
     */
    public function testResourceEvents() {
        $this->container()->rule(InternalRequest::class)
            ->addCall('setHeader', [LogTransactionMiddleware::HEADER_NAME, 1000]);

        $category = $this->createCategory();
        $discussion = $this->createDiscussion();
        $comment1 = $this->createComment();
        $comment2 = $this->createComment();
        $comment3 = $this->createComment();

        $this->api()->delete("/discussions/{$discussion['discussionID']}");

        /** @var \LogModel $logModel */
        $logModel = $this->container()->get(\LogModel::class);
        $logs = $logModel->getWhere([
            'TransactionLogID' => 1000,
        ]);
        $this->assertCount(4, $logs);

        // Comment count
        $this->assertCount(0, $this->api()->get('/comments', ['insertUserID' => $this->api()->getUserID()])->getBody());

        // Restoration off of our transactionID.
        $logModel->restore($logs[0]);
        $this->assertCount(0, $logModel->getWhere([ 'TransactionLogID' => 1000 ]));
        $this->assertCount(3, $this->api()->get('/comments', ['insertUserID' => $this->api()->getUserID()])->getBody());


        $this->assertEventsDispatched([
            $this->expectedResourceEvent("comment", ResourceEvent::ACTION_DELETE, $comment1),
            $this->expectedResourceEvent("comment", ResourceEvent::ACTION_DELETE, $comment2),
            $this->expectedResourceEvent("comment", ResourceEvent::ACTION_DELETE, $comment3),
            $this->expectedResourceEvent("discussion", ResourceEvent::ACTION_DELETE, $discussion),
        ], ['commentID', 'discussionID', 'name']);
    }

    /**
     * Assert that discussions are deleted immediately.
     */
    public function testSimpleDelete() {
        $discussion = $this->createDiscussion();
        $response = $this->api()->delete("/discussions/{$discussion['discussionID']}");
        $this->assertEquals(204, $response->getStatusCode());
        $this->assertTrackedJobCount(0, []);
    }

    /**
     * Test behaviour when multiple jobs are queued.
     */
    public function testQueuedDelete() {
        $scheduler = $this->getScheduler();

        // Create a discussion with comments.
        $discussion = $this->createDiscussion();
        $this->createComment();
        $where = ['DiscussionID' => $discussion['discussionID']];

        // Pause the scheduler so the job doesn't execute immediately.
        $scheduler->pause();
        $response = $this->api()->delete("/discussions/{$discussion['discussionID']}");

        // We receive the scheduled job info.
        $this->assertEquals(202, $response->getStatusCode());
        $this->assertTrackedJobCount(1, []);
        $body = $response->getBody();
        $trackingSlips = $body['trackingSlips'];
        $this->assertCount(1, $trackingSlips);
        $this->assertSame('received', $trackingSlips[0]['jobExecutionStatus']);

        // Records weren't deleted yet.
        $this->assertRecordsFound('Discussion', $where);
        $this->assertRecordsFound('Comment', $where);

        // After the job executes its status is updated again.
        $scheduler->resume();
        $this->assertTrackedJobCount(1, [
            'jobExecutionStatus' => 'complete',
        ]);
        $this->assertNoRecordsFound('Discussion', $where);
        $this->assertNoRecordsFound('Comment', $where);
    }

    /**
     * Test that our progress is tracked when deleting multiple items.
     */
    public function testDeleteMultipleProgress() {
        $scheduler = $this->getScheduler();

        // Create a discussion with comments.
        $discussion = $this->createDiscussion();
        $this->createComment();
        $this->createComment();
        $this->createComment();
        $this->createComment();
        $this->createComment();


        $scheduler->pause();
        $response = $this->api()->delete("/discussions/{$discussion['discussionID']}");
        $this->assertEquals(202, $response->getStatusCode());

        // Grab thejob out of the container.
        /** @var LocalDriverSlip $driverSlip */
        $driverSlip = $scheduler->getTrackingSlips()[0]->getDriverSlip();
        $this->assertInstanceOf(LocalDriverSlip::class, $driverSlip);

        /** @var LocalApiBulkDeleteJob $job */
        $job = $driverSlip->getJob();
        $this->assertInstanceOf(LocalApiBulkDeleteJob::class, $job);

        $iterator = $job->runIterator(2);
        /** @var JobExecutionProgress $status */
        $status = $iterator->current();
        $this->assertInstanceOf(JobExecutionProgress::class, $status);
        $this->assertEquals(2, $status->getQuantityComplete());
        $this->assertEquals(5, $status->getQuantityTotal());
        $iterator->next();
        $status = $iterator->current();
        $this->assertEquals(4, $status->getQuantityComplete());
        $iterator->next();
        $status = $iterator->current();
        $this->assertEquals("complete", $status->getStatus());
    }
}
