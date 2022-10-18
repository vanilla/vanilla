<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Dashboard;

use Gdn;
use Gdn_Cache;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Rule\InvocationOrder;
use Vanilla\CurrentTimeStamp;
use Vanilla\Dashboard\Models\RemoteResourceModel;
use Vanilla\RemoteResource\LocalRemoteResourceJob;
use Vanilla\RemoteResource\RemoteResourceHttpClient;
use Vanilla\Scheduler\Descriptor\NormalJobDescriptor;
use Vanilla\Scheduler\SchedulerInterface;
use Vanilla\Webhooks\SiteSync\WebhooksProducer;
use VanillaTests\ExpectExceptionTrait;
use VanillaTests\SiteTestCase;

/**
 * Class RemoteResourceModelTest
 *
 * @package VanillaTests\Dashboard
 */
class RemoteResourceModelTest extends SiteTestCase
{
    use ExpectExceptionTrait;
    /** @var RemoteResourceModel */
    private $remoteResourceModel;

    /** @var MockObject */
    private $mockScheduler;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->remoteResourceModel = \Gdn::getContainer()->get(RemoteResourceModel::class);
    }

    /**
     * Test getByUrl.
     *
     * @param string $url
     * @param string $content
     *
     * @dataProvider getByUrlDataProvider
     */
    public function testGetByURL($url, $content)
    {
        $this->remoteResourceModel->insert(["url" => RemoteResourceModel::PREFIX . $url, "content" => $content]);
        if (empty($content)) {
            $this->expectExceptionCode(400);
        }
        $result = $this->remoteResourceModel->getByUrl($url);
        $this->assertEquals($content, $result);
    }
    /**
     * Test getByUrl.
     *
     * @param string $url
     * @param string $content
     *
     * @dataProvider getByUrlDataProvider
     */
    public function testGetByURLCaching($url, $content)
    {
        $this->remoteResourceModel->insert(["url" => RemoteResourceModel::PREFIX . $url, "content" => $content]);
        if (empty($content)) {
            $this->expectExceptionCode(400);
        }
        $this->remoteResourceModel->getByUrl($url);

        $this->resetTable("remoteResource", false);

        $result = $this->remoteResourceModel->getByUrl($url);
        $this->assertEquals($content, $result);
    }

    /**
     * Data provider for getByUrl tests.
     *
     * @return array
     */
    public function getByUrlDataProvider(): array
    {
        return [
            ["www.test.com", "This valid content"],
            ["www.twotimes.com", "some more valid content"],
            ["www.twotimes.com", "some more valid content"],
            ["www.anothertest.com", "more valid content"],
            ["www.firsttimerequest.com", null],
        ];
    }

    /**
     * Test remote resource job gets add.
     */
    public function testRemoteResourceJobExecutedFirstTime()
    {
        $this->resetTable("remoteResource", false);
        $this->assertIfJobIsRun($this->once(), "addJobDescriptor", "http://test.com");
    }

    /**
     * Test getByUrl Content already in DB
     */
    public function testRemoteResourceJobNotExecutedContentExisting()
    {
        $this->resetTable("remoteResource", false);
        $url = "http://amazing.com";

        $this->remoteResourceModel->insert([
            "url" => RemoteResourceModel::PREFIX . $url,
            "content" => "amazing content",
        ]);
        $this->assertIfJobIsRun($this->never(), "addJobDescriptor", $url);

        // Check cache
        $this->resetTable("remoteResource", false);
        $this->assertIfJobIsRun($this->never(), "addJobDescriptor", $url);
    }

    /**
     * Test getByUrl Content already in DB
     */
    public function testRemoteResourceJobExecuteStaleContent()
    {
        $this->resetTable("remoteResource", false);
        $url = "http://amazing.com";
        CurrentTimeStamp::mockTime("Jan 01 2020 01:01:01");
        $this->remoteResourceModel->insert([
            "url" => RemoteResourceModel::PREFIX . $url,
            "content" => "amazing content",
        ]);
        CurrentTimeStamp::mockTime("Jan 01 2020 02:02:01");
        $this->assertIfJobIsRun($this->once(), "addJobDescriptor", $url);
    }

    /**
     * Executes GetByUrl and determines if remoteJobResource is run.
     *
     * @param InvocationOrder $expects
     * @param string $method
     * @param string $url
     * @return mixed
     */
    private function assertIfJobIsRun($expects, $method, $url)
    {
        /** @var SchedulerInterface */
        $this->mockScheduler = $this->getMockBuilder(SchedulerInterface::class)->getMock();

        $jobDescriptor = new NormalJobDescriptor(LocalRemoteResourceJob::class);
        $jobDescriptor->setMessage(["url" => $url, "headers" => [], "callable" => null]);
        $this->container()->setInstance(SchedulerInterface::class, $this->mockScheduler);

        $this->mockScheduler
            ->expects($expects)
            ->method($method)
            ->with($jobDescriptor);

        /** @var RemoteResourceModel $remoteResourceModel */
        $remoteResourceModel = Gdn::getContainer()->get(RemoteResourceModel::class);
        return $remoteResourceModel->getByUrl($url);
    }

    /**
     * Test data with Invalid url returns error and
     * @return void
     */
    public function testGetByURLWithInvalidURL()
    {
        $url = "https:://invaliddomain.com";

        //Verify the jobber is called for the first time the test is run
        $this->assertIfJobIsRun($this->once(), "addJobDescriptor", $url);

        CurrentTimeStamp::mockTime("Sep 08 2022 01:01:01");
        $this->remoteResourceModel->insert([
            "url" => RemoteResourceModel::PREFIX . $url,
            "lastError" => "500 Internal Server Error",
        ]);

        //Verify that data is cached with corresponding error message and an exception is thrown
        $this->runWithExpectedExceptionCode(400, function () use ($url) {
            $this->assertIfJobIsRun($this->never(), "addJobDescriptor", $url);
        });

        //Verify the jobber is invoked when data become stale
        CurrentTimeStamp::mockTime("Sep 08 2022 03:01:01");
        $this->expectExceptionCode(400);
        $this->assertIfJobIsRun($this->once(), "addJobDescriptor", $url);
    }
}
