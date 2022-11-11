<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla;

use Exception;
use Garden\Http\HttpResponse;
use Garden\Http\Mocks\MockHttpClient;
use Vanilla\PageScraper;
use VanillaTests\BootstrapTestCase;
use VanillaTests\Fixtures\LocalFilePageScraper;
use Vanilla\Metadata\Parser\OpenGraphParser;
use Vanilla\Metadata\Parser\JsonLDParser;

/**
 * Tests for the PageScraper.
 */
class PageScraperTest extends BootstrapTestCase
{
    /** @var string Directory of test HTML files. */
    const HTML_DIR = PATH_ROOT . "/tests/fixtures/html";

    /**
     * Grab a new testable instance of PageScraper.
     *
     * @return LocalFilePageScraper
     */
    private function pageScraper(): LocalFilePageScraper
    {
        // Create the test instance. Register the metadata handlers.
        $pageScraper = self::container()->get(LocalFilePageScraper::class);
        $pageScraper->setHtmlDir(self::HTML_DIR);

        $pageScraper->registerMetadataParser(new OpenGraphParser());
        $pageScraper->registerMetadataParser(new JsonLDParser());
        return $pageScraper;
    }

    /**
     * Provide data for testing the PageScraper::pageInfo method.
     *
     * @return array
     */
    public function provideInfoData(): array
    {
        $data = [
            [
                "jsonld.htm",
                [
                    "Title" => "I am a ständard title.",
                    "Description" => "I am a standard description.",
                    "Images" => [],
                    "Attributes" => [
                        "subtype" => "discussion",
                        "discussion" => [
                            "title" => "Welcome to awesome!",
                            "body" =>
                                'There\'s nothing sweeter than a fresh new forum, ready to welcome your community.',
                            "insertUser" => [
                                "name" => "Vanilla Forums",
                                "photoUrl" => "https://images.v-cdn.net/stubcontent/vanilla_avatar.jpg",
                                "url" => "https://vanilla.localhost/profile/Vanilla%20Forums",
                            ],
                            "dateInserted" => "2018-04-20T21:06:41+00:00",
                        ],
                    ],
                    "isCacheable" => true,
                ],
            ],
            [
                "no-description.htm",
                [
                    "Title" => "I am a standard title.",
                    "Description" =>
                        "I am a description. Instead of being part of the document head, I am inside the page contents." .
                        " This is not ideal and is only a fallback for pages without proper meta descriptors.",
                    "Images" => [],
                    "isCacheable" => true,
                ],
            ],
            [
                "og.htm",
                [
                    "Title" => "Online Community Software and Customer Forum Software by Vanilla Forums",
                    "Description" =>
                        "Engage your customers with a vibrant and modern online customer community forum." .
                        " A customer community helps to increases loyalty, reduce support costs and deliver feedback.",
                    "Images" => ["https://vanillaforums.com/images/metaIcons/vanillaForums.png"],
                    "isCacheable" => true,
                ],
            ],
            [
                "plain.htm",
                [
                    "Title" => "I am a standard title.",
                    "Description" => "I am a standard description.",
                    "Images" => [],
                    "isCacheable" => true,
                ],
            ],
        ];
        return $data;
    }

    /**
     * Test the PageInfo::pageInfo method.
     *
     * @param string $file
     * @param array $expected
     * @dataProvider provideInfoData
     */
    public function testFetch(string $file, array $expected)
    {
        $pageScraper = $this->pageScraper();

        $result = $pageScraper->pageInfo($file);
        $expected["Url"] = $file;
        $this->assertEquals($expected, $result);
    }

    /**
     * Scrape a file and return its result.
     *
     * @param string $file The file to scrape.
     * @return array Returns page info.
     * @throws Exception Throws an exception if there was a non-recoverable error scraping.
     */
    protected function scrapeFile(string $file): array
    {
        $scraper = $this->pageScraper();
        $result = $scraper->pageInfo($file);
        return $result;
    }

    /**
     * Test page fetching with unicode characters.
     *
     * @param string $file The file to test.
     * @dataProvider provideUnicodeFiles
     */
    public function testUnicodeFetch(string $file)
    {
        $result = $this->scrapeFile($file);

        $this->assertEquals("Test · Hello World", $result["Title"]);
        $this->assertEquals("😀😄😘<>", $result["Description"]);
    }

    /**
     * Provide some unicode test files.
     *
     * @return array Returns a data provider.
     */
    public function provideUnicodeFiles(): array
    {
        $r = [
            ["unicode.htm"],
            ["unicode-xml.htm"],
            ["unicode-no-hint.htm"],
            ["unicode-xml-comment.htm"],
            ["unicode-http-equiv.htm"],
        ];

        return array_column($r, null, 0);
    }

    /**
     * Test scraping a file that isn't in unicode to make sure it doesn't bork.
     *
     * @param string $file The name of the file to test.
     * @dataProvider provideKOI8RFiles
     */
    public function testKoi8(string $file)
    {
        $result = $this->scrapeFile($file);

        $this->assertEquals("ЧАСНЫЕ ОБЪЯВЛЕНИЯ", $result["Title"]);
    }

    /**
     * Provide some KOI8-R test files.
     *
     * @return array Returns a data provider.
     */
    public function provideKOI8RFiles(): array
    {
        $r = [["koi8-1.htm"], ["koi8-2.htm"], ["koi8-3.htm"]];
        return array_column($r, null, 0);
    }

    /**
     * Populate mock HTML client with possible returns.
     *
     * @return PageScraper page scraper
     * @throws \Garden\Container\ContainerException Container exception.
     * @throws \Garden\Container\NotFoundException Not found exception.
     */
    public function populateMockHttpClientScraper(): PageScraper
    {
        $mockHttpClient = new MockHttpClient();
        /** @var PageScraper $pageScraper */
        $pageScraper = \Gdn::getContainer()->getArgs(PageScraper::class, ["httpClient" => $mockHttpClient]);
        $mockHttpClient->addMockResponse(
            "https://test.com/fails",
            new HttpResponse(
                403,
                [],
                "
            <html lang='en-us'>
                <head>
                    <title>No permission to access resource</title>
                    <meta name='description' content='Try again later'>
                </head>
                <body></body>
            </html>
        "
            )
        );

        $mockHttpClient->addMockResponse(
            "https://test.com/robots.txt",
            new HttpResponse(
                200,
                [],
                "
            # If you would like to crawl GitHub contact us via https://support.github.com/contact/
# We also provide an extensive API: https://docs.github.com
User-agent: baidu
crawl-delay: 1


User-agent: *

Allow: /fails
Disallow: /disallow
        "
            )
        );
        return $pageScraper;
    }

    /**
     * Test error handling of the page scraper.
     */
    public function testErrorHandling()
    {
        /** @var PageScraper $pageScraper */
        $pageScraper = $this->populateMockHttpClientScraper();

        try {
            $pageScraper->pageInfo("https://test.com/fails");
        } catch (Exception $e) {
            $caught = $e;
        }

        if (!isset($caught) || !$caught instanceof \JsonSerializable) {
            $this->fail("No serializable exception was thrown.");
        } else {
            $serialized = $caught->jsonSerialize();
            $this->assertEquals(
                [
                    "message" => 'Site \'test.com\' did not respond successfully.',
                    "status" => 403,
                    "description" => "No permission to access resource: Try again later",
                ],
                $serialized
            );
        }
    }

    /**
     * Test deny url from robots.txt on the page scraper.
     */
    public function testRobotsDenyHandling()
    {
        /** @var PageScraper $pageScraper */
        $pageScraper = $this->populateMockHttpClientScraper();

        try {
            $pageScraper->pageInfo("https://test.com/disallow");
        } catch (Exception $e) {
            $caught = $e;
        }

        if (!isset($caught) || !$caught instanceof \JsonSerializable) {
            $this->fail("No serializable exception was thrown.");
        } else {
            $serialized = $caught->jsonSerialize();
            $this->assertEquals(
                [
                    "message" => "Site's 'test.com' robots.txt did not allow access to the url.",
                    "status" => 403,
                    "description" => null,
                ],
                $serialized
            );
        }
    }

    /**
     * Test allow of Url via robots.txt file of the page scraper.
     */
    public function testRobotsAllowHandling()
    {
        /** @var PageScraper $pageScraper */
        $pageScraper = $this->populateMockHttpClientScraper();

        try {
            $pageScraper->pageInfo("https://test.com/fails");
        } catch (Exception $e) {
            $caught = $e;
        }

        if (!isset($caught) || !$caught instanceof \JsonSerializable) {
            $this->fail("No serializable exception was thrown.");
        } else {
            $serialized = $caught->jsonSerialize();
            $this->assertEquals(
                [
                    "message" => 'Site \'test.com\' did not respond successfully.',
                    "status" => 403,
                    "description" => "No permission to access resource: Try again later",
                ],
                $serialized
            );
        }
    }
}
