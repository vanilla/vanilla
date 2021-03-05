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
use VanillaTests\SiteTestCase;

/**
 * Class RemoteResourceModelTest
 *
 * @package VanillaTests\Dashboard
 */
class RemoteResourceModelTest extends SiteTestCase {

    /** @var RemoteResourceModel */
    private $remoteResourceModel;

    /** @var MockObject */
    private $mockScheduler;

    /** @var \Gdn_Cache */
    private $cache;

    /**
     * @inheritDoc
     */
    public function setUp(): void {
        parent::setUp();
        $this->cache = $this->enableCaching();
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
    public function testGetByURL($url, $content) {
        $this->remoteResourceModel->insert(["url" => $url, "content" => $content]);
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
    public function testGetByURLCaching($url, $content) {
        $this->remoteResourceModel->insert(["url" => $url, "content" => $content]);
        $this->remoteResourceModel->getByUrl($url);

        $this->resetTable("remoteResource");

        $result = $this->remoteResourceModel->getByUrl($url);
        $this->assertEquals($content, $result);
    }

    /**
     * Data provider for getByUrl tests.
     *
     * @return array
     */
    public function getByUrlDataProvider(): array {
        return [
            [
                "www.test.com",
                "This valid content",
            ],
            [
                "www.twotimes.com",
                "some more valid content"
            ],
            [
                "www.twotimes.com",
                "some more valid content"
            ],
            [
                "www.anothertest.com",
                "more valid content"
            ],
            [
                "www.firsttimerequest.com",
                null
            ]
        ];
    }

    /**
     * Test remote resource job gets add.
     */
    public function testRemoteResourceJobExecutedFirstTime() {
        $this->resetTable('remoteResource');
        $this->assertIfJobIsRun($this->once(), "addJobDescriptor", "http://test.com");
    }

    /**
     * Test getByUrl Content already in DB
     */
    public function testRemoteResourceJobNotExecutedContentExisting() {
        $this->resetTable('remoteResource');
        $url = "http://amazing.com";

        $this->remoteResourceModel->insert(["url" => $url, "content" => "amazing content"]);
        $this->assertIfJobIsRun($this->never(), "addJobDescriptor", $url);

        // Check cache
        $this->resetTable('remoteResource');
        $this->assertIfJobIsRun($this->never(), "addJobDescriptor", $url);
    }

    /**
     * Test getByUrl Content already in DB
     */
    public function testRemoteResourceJobExecuteStaleContent() {
        $this->resetTable('remoteResource');
        $url = "http://amazing.com";
        CurrentTimeStamp::mockTime('Jan 01 2020 01:01:01');
        $this->remoteResourceModel->insert(["url" => $url, "content" => "amazing content"]);
        CurrentTimeStamp::mockTime('Jan 01 2020 02:02:01');
        $this->assertIfJobIsRun($this->once(), "addJobDescriptor", $url);
    }

    /**
     * Executes GetByUrl and determines if remoteJobResource is run.
     *
     * @param InvocationOrder $expects
     * @param string $method
     * @param string $url
     */
    private function assertIfJobIsRun($expects, $method, $url) {
        /** @var SchedulerInterface */
        $this->mockScheduler = $this->getMockBuilder(SchedulerInterface::class)
            ->getMock();

        $jobDescriptor = new NormalJobDescriptor(LocalRemoteResourceJob::class);
        $jobDescriptor->setMessage(["url" => $url]);
        $this->container()->setInstance(SchedulerInterface::class, $this->mockScheduler);

        $this->mockScheduler
            ->expects($expects)
            ->method($method)
            ->with($jobDescriptor);

        /** @var RemoteResourceModel $remoteResourceModel */
        $remoteResourceModel = Gdn::getContainer()->get(RemoteResourceModel::class);
        $remoteResourceModel->getByUrl($url);
    }
}
