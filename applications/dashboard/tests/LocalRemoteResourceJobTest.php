<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

use Garden\Http\Mocks\MockHttpHandler;
use Vanilla\Dashboard\Models\RemoteResourceModel;
use Vanilla\Metadata\Parser\RSSFeedParser;
use Vanilla\RemoteResource\LocalRemoteResourceJob;
use Vanilla\RemoteResource\RemoteResourceHttpClient;
use Vanilla\Scheduler\Job\JobExecutionStatus;
use VanillaTests\SiteTestCase;

/**
 * Class LocalRemoteResourceJobTest
 */
class LocalRemoteResourceJobTest extends SiteTestCase
{
    /** @var RemoteResourceModel */
    private $remoteResourceModel;

    /** @var Gdn_Cache */
    private $cache;

    /**
     * {@inheritDoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        /** @var Gdn_Cache cache */
        $this->cache = $this->enableCaching();

        /** @var RemoteResourceModel $remoteResourceModel */
        $this->remoteResourceModel = Gdn::getContainer()->get(RemoteResourceModel::class);
    }

    /**
     * Test remote resource job gets add.
     *
     * @param string $url
     * @param string $urlContent
     * @param array $expected
     * @dataProvider remoteResourceJobDataProvider
     */
    public function testRemoteResourceJobRun($url, $urlContent, $expected)
    {
        $client = $this->getMockClient($url, $urlContent, $expected);
        $localRemoteResourceJob = $this->getLocalRemoteResourceJob($client);
        $localRemoteResourceJob->setMessage(["url" => $url]);

        /** \Vanilla\Scheduler\Job\JobExecutionStatus $test */
        $status = $localRemoteResourceJob->run();

        $this->assertEquals($expected["jobStatus"], $status->getStatus());

        $resource = $this->remoteResourceModel->select(["url" => RemoteResourceModel::PREFIX . $url]);
        $this->assertEquals($expected["expectContent"], $resource[0]["content"]);
    }

    /**
     * DataProvider for testRemoteResourceJobRun
     */
    public function remoteResourceJobDataProvider()
    {
        return [
            [
                "http://test.com",
                "<h1>Test Test</h1>",
                [
                    "code" => 200,
                    "jobStatus" => JobExecutionStatus::complete()->getStatus(),
                    "expectContent" => "<h1>Test Test</h1>",
                ],
            ],
            [
                "http://fail.com",
                "<h1>Fail</h1>",
                [
                    "code" => 404,
                    "jobStatus" => JobExecutionStatus::error()->getStatus(),
                    "expectContent" => null,
                ],
            ],
        ];
    }

    /**
     * Test the that remote resource job is locked.
     */
    public function testLocalRemoteResourceJobLock()
    {
        $url = "test123.com";
        $client = $this->getMockClient($url, "<h1>LOCKED</h1>", ["code" => 404]);
        $localRemoteResourceJob = $this->getLocalRemoteResourceJob($client);
        $key = sprintf(LocalRemoteResourceJob::REMOTE_RESOURCE_LOCK, $url);
        $this->cache->add($key, $url, [
            Gdn_Cache::FEATURE_EXPIRY => RemoteResourceHttpClient::REQUEST_TIMEOUT,
        ]);

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
    private function getMockClient($url, $urlContent, $expected): RemoteResourceHttpClient
    {
        $mockHandler = new MockHttpHandler();
        /** @var RemoteResourceHttpClient $client */
        $client = $this->container()->get(RemoteResourceHttpClient::class);
        $client->setHandler($mockHandler);
        $mockHandler->addMockResponse(
            $url,
            new \Garden\Http\HttpResponse(
                $expected["code"],
                $expected["headers"] ?? ["content-type" => "application/html"],
                $urlContent
            )
        );

        return $client;
    }

    /**
     * Get a new LocalRemoteResourceJob
     *
     * @param RemoteResourceHttpClient $client
     * @return LocalRemoteResourceJob
     */
    private function getLocalRemoteResourceJob($client): LocalRemoteResourceJob
    {
        $localRemoteResourceJob = new LocalRemoteResourceJob($this->remoteResourceModel, $client, $this->cache);
        return $localRemoteResourceJob;
    }

    /**
     * Test remote resource job gets processed and saved.
     *
     * @param string $url
     * @param string $urlContent
     * @param array $expected
     * @dataProvider rssResourceJobDataProvider
     */
    public function testRssResourceRun($url, $urlContent, $expected)
    {
        $client = $this->getMockClient($url, $urlContent, $expected);
        $localRemoteResourceJob = $this->getLocalRemoteResourceJob($client);
        $feedParser = Gdn::getContainer()->get(RSSFeedParser::class);
        $callable = function ($content) use ($url, $feedParser) {
            $results = [];
            if (!empty($content)) {
                $rssFeedDOM = new \DOMDocument();
                libxml_use_internal_errors(true);
                $loaded = $rssFeedDOM->loadXML($content);
                $errors = libxml_get_errors();
                libxml_use_internal_errors(false);
                libxml_clear_errors();
                if ($loaded) {
                    $results = $feedParser->parse($rssFeedDOM);
                }
            }
            return !empty($results) ? json_encode($results) : "";
        };
        $headers = [
            "Accept" => [
                "application/rss+xml",
                "application/rdf+xml",
                "application/atom+xml",
                "application/xml",
                "text/xml",
            ],
        ];
        $localRemoteResourceJob->setMessage(["url" => $url, "headers" => $headers, "callable" => $callable]);

        /** \Vanilla\Scheduler\Job\JobExecutionStatus $test */
        $status = $localRemoteResourceJob->run();

        $this->assertEquals($expected["jobStatus"], $status->getStatus());

        $resource = $this->remoteResourceModel->select(["url" => RemoteResourceModel::PREFIX . $url]);
        $this->assertEquals($expected["expectContent"], $resource[0]["content"]);
    }

    /**
     * DataProvider for testRssResourceRun
     */
    public function rssResourceJobDataProvider()
    {
        return [
            [
                "https://success.vanillaforums.com/discussions/feed.rss",
                '<?xml version="1.0" encoding="utf-8"?>
<rss version="2.0"
     xmlns:content="http://purl.org/rss/1.0/modules/content/"
     xmlns:dc="http://purl.org/dc/elements/1.1/"
     xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
        <title>Vanilla Success Community</title>
        <link>https://success.vanillaforums.com/</link>
        <pubDate>Tue, 13 Sep 2022 03:51:41 +0000</pubDate>
        <language>en</language>
        <description>Vanilla Success Community</description>
        <atom:link href="https://success.vanillaforums.com/discussions/feed.rss" rel="self" type="application/rss+xml"/>
        <item>
            <title>Test 1</title>
            <link>https://success.vanillaforums.com/discussion/1/test1</link>
            <pubDate>Fri, 12 Aug 2022 18:07:41 +0000</pubDate>
            <description><![CDATA[<p>Hello this is test 1</p>]]>
            </description>
        </item>
        <item>
            <title>Test 2</title>
            <link>https://success.vanillaforums.com/discussion/2/test-2</link>
            <pubDate>Fri, 12 Aug 2022 18:07:41 +0000</pubDate>
            <description><![CDATA[<p>Hello this is test-2</p>]]>
            </description>
        </item>
    </channel>
</rss>',
                [
                    "code" => 200,
                    "jobStatus" => JobExecutionStatus::complete()->getStatus(),
                    "headers" => ["content-type" => "application/rss+xml"],
                    "expectContent" =>
                        '{"channel":{"title":"Vanilla Success Community","link":"https:\/\/success.vanillaforums.com\/","description":"Vanilla Success Community"},"item":[{"title":"Test 1","link":"https:\/\/success.vanillaforums.com\/discussion\/1\/test1","description":"<p>Hello this is test 1<\/p>","pubDate":"Fri, 12 Aug 2022 18:07:41 +0000"},{"title":"Test 2","link":"https:\/\/success.vanillaforums.com\/discussion\/2\/test-2","description":"<p>Hello this is test-2<\/p>","pubDate":"Fri, 12 Aug 2022 18:07:41 +0000"}]}',
                ],
            ],
            [
                "http://test.com",
                "<h1>Test Test</h1>",
                [
                    "code" => 200,
                    "jobStatus" => JobExecutionStatus::complete()->getStatus(),
                    "expectContent" => "",
                ],
            ],
        ];
    }
}
