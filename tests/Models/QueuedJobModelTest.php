<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Models;

use Garden\Schema\ValidationException;
use Garden\Web\Exception\ClientException;
use PHPUnit\Framework\MockObject\MockObject;
use Vanilla\CurrentTimeStamp;
use Vanilla\Scheduler\Descriptor\NormalJobDescriptor;
use Vanilla\Scheduler\Job\LocalApiJob;
use Vanilla\Scheduler\SchedulerInterface;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Dashboard\Models\QueuedJobModel;
use VanillaTests\DatabaseTestTrait;
use VanillaTests\SiteTestCase;

/**
 * Automated tests for QueuedJobModel
 */
class QueuedJobModelTest extends SiteTestCase
{
    use DatabaseTestTrait;
    /** @var QueuedJobModel */
    private $queuedJobModel;

    /** @var MockObject */
    private $mockScheduler;

    public static $addons = ["vanilla"];
    /**
     * Instantiate fixtures.
     */
    public function setUp(): void
    {
        parent::setUp();

        /** @var SchedulerInterface */
        $this->mockScheduler = $this->getMockBuilder(SchedulerInterface::class)->getMock();
        $this->queuedJobModel = static::container()->get(QueuedJobModel::class);
    }

    /**
     * {@inheritDoc}
     */
    public function tearDown(): void
    {
        parent::tearDown();
        $this->queuedJobModel->delete(["queuedJobID >" => 0]);
    }

    /**
     * Test inserting a job queue
     *
     * @throws ClientException Not Applicable.
     * @throws ValidationException Not Applicable.
     * @throws NoResultsException Not Applicable.
     * @dataProvider insertDataProvider
     */
    public function testInsert(array $jobToSave)
    {
        $insertID = $this->queuedJobModel->insert($jobToSave);
        $this->assertGreaterThanOrEqual(1, $insertID);
        $inserted = $this->queuedJobModel->selectSingle(["queuedJobID" => $insertID]);
        $this->assertNotEmpty($inserted);
        $this->assertNotEmpty($inserted["jobID"]);
        $this->assertStringStartsWith($jobToSave["message"], $inserted["message"]);
    }

    /**
     * Test inserting a job queue get prunned after proper time.
     *
     * @throws ClientException Not Applicable.
     * @throws ValidationException Not Applicable.
     * @throws NoResultsException Not Applicable.
     * @dataProvider insertDataProvider
     */
    public function testInsertPrune(array $jobToSave)
    {
        $currentTime = CurrentTimeStamp::getDateTime();
        CurrentTimeStamp::mockTime($currentTime);
        CurrentTimeStamp::mockTime($currentTime->modify("-4 month"));
        $pruneInsertID = $this->queuedJobModel->insert($jobToSave);
        $this->assertGreaterThanOrEqual(1, $pruneInsertID);

        CurrentTimeStamp::mockTime($currentTime->modify("+3 month"));
        $insertID = $this->queuedJobModel->insert($jobToSave);
        $prunedInserted = $this->queuedJobModel->select(["queuedJobID" => $pruneInsertID]);
        $inserted = $this->queuedJobModel->select(["queuedJobID" => $insertID]);
        $this->assertEmpty($prunedInserted);
        $this->assertNotEmpty($inserted);
        $this->assertNotEmpty($inserted[0]["jobID"]);
        $this->assertStringStartsWith($jobToSave["message"], $inserted[0]["message"]);
    }

    /**
     * Test update record status
     *
     * @param array $insert
     * @param string $status
     * @dataProvider insertDataProvider
     */
    public function testUpdate(array $insert, string $status): void
    {
        $insertID = $this->queuedJobModel->insert($insert);

        $queuedJob = $this->queuedJobModel->updateStatus($status, $insertID)[0];
        $this->assertNotEmpty($queuedJob);
        $this->assertEquals($status, $queuedJob["status"]);
        $this->assertNotEmpty($queuedJob["dateUpdated"]);
    }

    /**
     * Test inserting a job queue get prunned after proper time for successful status.
     *
     * @throws ClientException Not Applicable.
     * @throws ValidationException Not Applicable.
     * @throws NoResultsException Not Applicable.
     * @dataProvider insertDataProvider
     */
    public function testInsertPruneSuccess(array $jobToSave)
    {
        $jobToSave["status"] = QueuedJobModel::STATUS_SUCCESS;
        $currentTime = CurrentTimeStamp::getDateTime();
        CurrentTimeStamp::mockTime($currentTime);
        CurrentTimeStamp::mockTime($currentTime->modify("-32 day"));
        $pruneInsertID = $this->queuedJobModel->insert($jobToSave);
        $this->assertGreaterThanOrEqual(1, $pruneInsertID);

        CurrentTimeStamp::mockTime($currentTime->modify("+32 day"));
        $insertID = $this->queuedJobModel->insert($jobToSave);
        $prunedInserted = $this->queuedJobModel->select(["queuedJobID" => $pruneInsertID]);
        $inserted = $this->queuedJobModel->select(["queuedJobID" => $insertID]);
        $this->assertEmpty($prunedInserted);
        $this->assertNotEmpty($inserted);
        $this->assertNotEmpty($inserted[0]["jobID"]);
        $this->assertStringStartsWith($jobToSave["message"], $inserted[0]["message"]);
    }

    /**
     * Data Provider for insert test
     *
     * @return iterable
     */
    public function insertDataProvider(): iterable
    {
        yield "insert local Driver" => [
            [
                "jobType" => "Vanilla\VanillaQueue\\Jobs\\HttpRequestJob",
                "driver" => "local",
                "message" => "{\"test\": \"test\"}",
                "status" => "pending",
            ],
            "received",
        ];
        yield "insert queue-service" => [
            [
                "jobType" => "Vanilla\VanillaQueue\\Jobs\\HttpRequestJob",
                "driver" => "queue-service",
                "message" => "{\"test\": \"test\"}",
                "status" => "pending",
            ],
            "received",
        ];
        yield "insert received queue-service" => [
            [
                "jobType" => "Vanilla\VanillaQueue\\Jobs\\HttpRequestJob",
                "driver" => "queue-service",
                "message" => "{\"test\": \"test\"}",
                "status" => "received",
            ],
            "progress",
        ];
        yield "insert progress queue-service" => [
            [
                "jobType" => "Vanilla\VanillaQueue\\Jobs\\HttpRequestJob",
                "driver" => "queue-service",
                "message" => "{\"test\": \"test\"}",
                "status" => "progress",
            ],
            "success",
        ];
        yield "insert success queue-service" => [
            [
                "jobType" => "Vanilla\VanillaQueue\\Jobs\\HttpRequestJob",
                "driver" => "queue-service",
                "message" => "{\"test\": \"test\"}",
                "status" => "success",
            ],
            "failed",
        ];
        yield "insert failed queue-service" => [
            [
                "jobType" => "Vanilla\VanillaQueue\\Jobs\\HttpRequestJob",
                "driver" => "queue-service",
                "message" => "{\"test\": \"test\"}",
                "status" => "failed",
            ],
            "success",
        ];
    }

    /**
     * Test inserting a job queue over threshold causes jobs to be scheduled in queued service.
     *
     * @throws ClientException Not Applicable.
     * @throws ValidationException Not Applicable.
     * @throws NoResultsException Not Applicable.
     * @dataProvider insertDataProvider
     */
    public function testThresholdInsert(array $jobToSave)
    {
        $this->queuedJobModel->setScheduler($this->mockScheduler);
        $this->mockScheduler
            ->expects(
                $jobToSave["driver"] == QueuedJobModel::QUEUE_SERVICE &&
                $jobToSave["status"] == QueuedJobModel::STATUS_PENDING
                    ? $this->atLeastOnce()
                    : $this->never()
            )
            ->method("addJobDescriptor")
            ->with(new NormalJobDescriptor(LocalApiJob::class));
        for ($index = 0; $index < $this->queuedJobModel->getJobThreshold(); $index++) {
            $lastID = $this->queuedJobModel->insert($jobToSave);
            $this->queuedJobModel->checkToScheduleJob(LocalApiJob::class);
        }
    }

    /**
     * Test inserting a job queue over 1 minute ago causes jobs to be scheduled in queued service.
     *
     * @throws ClientException Not Applicable.
     * @throws ValidationException Not Applicable.
     * @throws NoResultsException Not Applicable.
     * @dataProvider insertDataProvider
     */
    public function testThreshTimeInsert(array $jobToSave)
    {
        $currentTime = CurrentTimeStamp::getDateTime();
        $this->queuedJobModel->setScheduler($this->mockScheduler);
        $this->mockScheduler
            ->expects(
                $jobToSave["driver"] == QueuedJobModel::QUEUE_SERVICE &&
                $jobToSave["status"] == QueuedJobModel::STATUS_PENDING
                    ? $this->atLeastOnce()
                    : $this->never()
            )
            ->method("addJobDescriptor")
            ->with(new NormalJobDescriptor(LocalApiJob::class));
        CurrentTimeStamp::mockTime($currentTime);

        $this->queuedJobModel->insert($jobToSave);

        CurrentTimeStamp::mockTime($currentTime->modify("+1 minute"));
        $this->queuedJobModel->insert($jobToSave);
        $this->queuedJobModel->checkToScheduleJob(LocalApiJob::class);
    }
}
