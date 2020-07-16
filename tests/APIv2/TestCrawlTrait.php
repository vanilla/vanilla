<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license MIT
 */

namespace VanillaTests\APIv2;

use Garden\Http\HttpResponse;
use PHPUnit\Framework\MockObject\Api;
use PHPUnit\Framework\TestCase;
use Vanilla\ApiUtils;

/**
 * Adds a basic test for crawling.
 */
trait TestCrawlTrait {
    /**
     * Test a basic crawl.
     */
    public function testResourceCrawl(): void {
        if (empty($this->resourceName)) {
            $this->markTestSkipped('No resource to crawl.');
        }
        $rows = $this->generateIndexRows();

        $resources = $this->api()->get('/resources', ['crawlable' => true])->getBody();
        foreach ($resources as $row) {
            if ($row['recordType'] !== $this->resourceName) {
                continue;
            }

            ['url' => $url] = $row;
            $this->assertResourceCrawl($url);
            return;
        }
        $this->fail('Did not find resource: '.$this->resourceName);
    }

    /**
     * Assert a basic crawl URL.
     *
     * @param string $url
     */
    protected function assertResourceCrawl(string $url): void {
        $r = $this->api()->get($url, ['expand' => 'crawl'])->getBody();
        ['url' => $crawlUrl, 'parameter' => $param, 'min' => $min, 'max' => $max] = $r['crawl'];

        $min = $min ?? 0;
        $max = $max ?? 100;

        /** @var HttpResponse $crawl */
        $crawl = $this->api()->get($crawlUrl, [$param => "$min..$max"]);

        TestCase::assertTrue($crawl->hasHeader('link'), 'The crawl response is missing the paging header.');
        $paging = ApiUtils::parsePageHeader($crawl->getHeader('link'));
        TestCase::assertNotNull($paging, "The crawl link header did not have correct paging information.");

        TestCase::assertSame(200, $crawl->getStatusCode());
    }
}
