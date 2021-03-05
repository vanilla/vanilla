<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

use Garden\Http\Mocks\MockHttpHandler;
use Vanilla\Dashboard\Models\RemoteResourceModel;
use Vanilla\RemoteResource\LocalRemoteResourceJob;
use Vanilla\RemoteResource\RemoteResourceHttpClient;
use Vanilla\Scheduler\Job\JobExecutionStatus;
use VanillaTests\SiteTestCase;

/**
 * Class LocalRemoteResourceJobTest
 */
class LocalRemoteResourceJobTest extends SiteTestCase {

    /** @var RemoteResourceModel */
    private $remoteResourceModel;

    /** @var Gdn_Cache */
    private $cache;

    /** @var RemoteResourceHttpClient */
    private $remoteResourceHttpClient;

    /**
     * {@inheritDoc}
     */
    public function setUp(): void {
        parent::setUp();

        /** @var Gdn_Cache cache */
        $this->cache =$this->enableCaching();

        /** @var RemoteResourceModel $remoteResourceModel */
        $this->remoteResourceModel = Gdn::getContainer()->get(RemoteResourceModel::class);

        /** @var RemoteResourceHttpClient $client */
        $this->remoteResourceHttpClient = $this->container()->get(RemoteResourceHttpClient::class);
    }

    /**
     * Test remote resource job gets add.
     *
     * @param string $url
     * @param string $urlContent
     * @param array $expected
     * @dataProvider remoteResourceJobDataProvider
     */
    public function testRemoteResourceJobRun($url, $urlContent, $expected) {
        $client = $this->getMockClient($url, $urlContent, $expected);
        $localRemoteResourceJob = $this->getLocalRemoteResourceJob($client);
        $localRemoteResourceJob->setMessage(["url" => $url]);

        /** \Vanilla\Scheduler\Job\JobExecutionStatus $test */
        $status = $localRemoteResourceJob->run();

        $this->assertEquals($expected['jobStatus'], $status->getStatus());

        $resource = $this->remoteResourceModel->select(["url" => $url]);
        $this->assertEquals($expected['expectContent'], $resource[0]["content"]);
    }

    /**
     * DataProvider for testRemoteResourceJobRun
     */
    public function remoteResourceJobDataProvider() {
        return [
            [
                "http://test.com",
                "<h1>Test Test</h1>",
                [
                    "code" => 200,
                    "jobStatus" => JobExecutionStatus::complete()->getStatus(),
                    "expectContent" => "<h1>Test Test</h1>",
                ]
            ],
            [
                "http://fail.com",
                "<h1>Fail</h1>",
                [
                    "code" => 404,
                    "jobStatus" => JobExecutionStatus::error()->getStatus(),
                    "expectContent" =>  null,
                ]
            ],
        ];
    }

    /**
     * Test the that remote resource job is locked.
     */
    public function testLocalRemoteResourceJobLock() {
        $url = "test123.com";
        $client = $this->getMockClient($url, "<h1>LOCKED</h1>", ["code" => 404]);
        $localRemoteResourceJob = $this->getLocalRemoteResourceJob($client);
        $key = sprintf(LocalRemoteResourceJob::REMOTE_RESOURCE_LOCK, $url);
        $this->cache->add(
            $key,
            $url,
            [
                Gdn_Cache::FEATURE_EXPIRY => RemoteResourceHttpClient::REQUEST_TIMEOUT
            ]
        );

        $localRemoteResourceJob->setMessage(["url" => $url]);

        /** \Vanilla\Scheduler\Job\JobExecutionStatus $test */
        $status = $localRemoteResourceJob->run();
        $this->assertEquals(JobExecutionStatus::progress()->getStatus(), $status->getStatus());
    }

    /**
     * Get a MockClient with a set response.
     *
     * @param string $url
     * @param string $urlContent
     * @param array $expected
     * @return RemoteResourceHttpClient
     */
    private function getMockClient($url, $urlContent, $expected): RemoteResourceHttpClient {
        $mockHandler = new MockHttpHandler();
        /** @var RemoteResourceHttpClient $client */
        $client = $this->container()->get(RemoteResourceHttpClient::class);
        $client->setHandler($mockHandler);
        $mockHandler->addMockResponse($url, new \Garden\Http\HttpResponse(
            $expected['code'],
            ['content-type' => 'application/html'],
            $urlContent
        ));

        return $client;
    }

    /**
     * Get a new LocalRemoteResourceJob
     *
     * @param RemoteResourceHttpClient $client
     * @return LocalRemoteResourceJob
     */
    private function getLocalRemoteResourceJob($client): LocalRemoteResourceJob {
        $localRemoteResourceJob = new LocalRemoteResourceJob($this->remoteResourceModel, $client, $this->cache);
        return $localRemoteResourceJob;
    }
}
