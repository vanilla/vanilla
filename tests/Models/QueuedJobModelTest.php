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
     * {@inheritdoc}
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
        ];
        yield "insert queue-service" => [self::queueJob(QueuedJobModel::STATUS_PENDING)];
        yield "insert received queue-service" => [self::queueJob(QueuedJobModel::STATUS_QUEUED)];
        yield "insert progress queue-service" => [self::queueJob(QueuedJobModel::STATUS_PROGRESS)];
        yield "insert success queue-service" => [self::queueJob(QueuedJobModel::STATUS_SUCCESS)];
        yield "insert failed queue-service" => [self::queueJob(QueuedJobModel::STATUS_FAILED)];
    }

    /**
     * Get a body of a pending queue job row.
     *
     * @return array
     */
    public static function queueJob(string $status): array
    {
        return [
            "jobType" => "Vanilla\VanillaQueue\\Jobs\\HttpRequestJob",
            "driver" => "queue-service",
            "message" => "{\"test\": \"test\"}",
            "status" => $status,
        ];
    }

    /**
     * Test inserting a job queue over threshold causes jobs to be scheduled in queued service.
     */
    public function testThresholdInsert()
    {
        \Gdn::config()->saveToConfig([
            QueuedJobModel::CONF_THRESHOLD => 5,
        ]);

        for ($i = 0; $i < 4; $i++) {
            $this->queuedJobModel->insert(self::queueJob(QueuedJobModel::STATUS_PENDING));
        }
        // No need ot flush yet. Not at the threshold.
        $this->assertFalse($this->queuedJobModel->shouldFlushPendingJobs());

        // 1 more and now we're ready.
        $this->queuedJobModel->insert(self::queueJob(QueuedJobModel::STATUS_PENDING));
        $this->assertTrue($this->queuedJobModel->shouldFlushPendingJobs());
    }

    /**
     * Test inserting a job queue over 1 minute ago causes jobs to be scheduled in queued service.
     */
    public function testThreshTimeInsert()
    {
        \Gdn::config()->saveToConfig([
            QueuedJobModel::CONF_THRESHOLD => 100,
        ]);
        $currentTime = CurrentTimeStamp::getDateTime();

        CurrentTimeStamp::mockTime($currentTime);

        $this->queuedJobModel->insert(self::queueJob(QueuedJobModel::STATUS_PENDING));

        // It's too soon.
        $this->assertFalse($this->queuedJobModel->shouldFlushPendingJobs());

        CurrentTimeStamp::mockTime($currentTime->modify("+1 minute"));

        // Now we're ready.
        $this->assertTrue($this->queuedJobModel->shouldFlushPendingJobs());
    }
}
