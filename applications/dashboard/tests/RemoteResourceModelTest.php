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
use VanillaTests\SchedulerTestTrait;
use VanillaTests\SiteTestCase;

/**
 * Class RemoteResourceModelTest
 *
 * @package VanillaTests\Dashboard
 */
class RemoteResourceModelTest extends SiteTestCase
{
    use ExpectExceptionTrait;
    use SchedulerTestTrait;

    /** @var RemoteResourceModel */
    private $remoteResourceModel;

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
        $this->assertResourceJobScheduled("http://test.com");
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
        $this->assertResourceJobScheduled($url, false);

        // Check cache
        $this->resetTable("remoteResource", false);
        $this->assertResourceJobScheduled($url, false);
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
        $this->assertResourceJobScheduled($url);
    }

    /**
     * Executes GetByUrl and determines if remoteJobResource is run.
     *
     * @param string $forUrl
     * @param bool $isScheduled
     *
     * @return mixed
     */
    private function assertResourceJobScheduled(string $forUrl, bool $isScheduled = true)
    {
        $this->getScheduler()->pause();

        /** @var RemoteResourceModel $remoteResourceModel */
        $remoteResourceModel = Gdn::getContainer()->get(RemoteResourceModel::class);
        $result = $remoteResourceModel->getByUrl($forUrl);

        if ($isScheduled) {
            $this->getScheduler()->assertJobScheduled(
                LocalRemoteResourceJob::class,
                expectedMessage: [
                    "url" => $forUrl,
                    "headers" => [],
                    "callable" => null,
                ]
            );
        } else {
            $this->getScheduler()->assertJobNotScheduled(LocalRemoteResourceJob::class);
        }

        $this->getScheduler()->reset();

        return $result;
    }

    /**
     * Test data with Invalid url returns error and
     * @return void
     */
    public function testGetByURLWithInvalidURL()
    {
        $url = "https:://invaliddomain.com";

        //Verify the jobber is called for the first time the test is run
        $this->assertResourceJobScheduled($url);

        CurrentTimeStamp::mockTime("Sep 08 2022 01:01:01");
        $this->remoteResourceModel->insert([
            "url" => RemoteResourceModel::PREFIX . $url,
            "lastError" => "500 Internal Server Error",
        ]);

        //Verify that data is cached with corresponding error message and an exception is thrown
        $this->runWithExpectedExceptionCode(400, function () use ($url) {
            $this->assertResourceJobScheduled($url, false);
        });

        //Verify the jobber is invoked when data become stale
        CurrentTimeStamp::mockTime("Sep 08 2022 03:01:01");
        $this->expectExceptionCode(400);
        $this->assertResourceJobScheduled($url);
    }
}
