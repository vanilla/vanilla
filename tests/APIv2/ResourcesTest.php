<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use Vanilla\Utility\ArrayUtils;
use Vanilla\Utility\StringUtils;

/**
 * Tests for the `/resources` endpoints.
 */
class ResourcesTest extends AbstractAPIv2Test {
    use TestCrawlTrait;

    /**
     * @inheritDoc
     */
    public static function getAddons(): array {
        return ['dashboard', 'vanilla'];
    }

    /**
     * The index should return the resource for the main tables.
     */
    public function testIndex(): void {
        $r = $this->api()->get('/resources')->getBody();
        $expected = [
            [
                'recordType' => 'user',
                'url' => 'http://vanilla.test/resourcestest/api/v2/resources/user',
                'crawlable' => true,
            ],
            [
                'recordType' => 'category',
                'url' => 'http://vanilla.test/resourcestest/api/v2/resources/category',
                'crawlable' => true,
            ],
            [
                'recordType' => 'discussion',
                'url' => 'http://vanilla.test/resourcestest/api/v2/resources/discussion',
                'crawlable' => true,
            ],
            [
                'recordType' => 'comment',
                'url' => 'http://vanilla.test/resourcestest/api/v2/resources/comment',
                'crawlable' => true,
            ],
        ];

        usort($expected, ArrayUtils::sortCallback('recordType'));
        usort($r, ArrayUtils::sortCallback('recordType'));

        $this->assertSame($expected, $r);
    }

    /**
     * A basic smoke test of the individual resource endpoints.
     */
    public function testResourceCrawlInspection(): void {
        $resources = $this->api()->get('/resources', ['crawlable' => true])->getBody();
        foreach ($resources as $row) {
            ['url' => $url] = $row;
            $r = $this->api()->get($url, ['expand' => 'crawl'])->getBody();
            $this->assertIsInt($r['crawl']['count']);
        }
    }

    /**
     * A basic smoke test of the individual resource endpoints.
     */
    public function testResourceCrawlInspectionAndCrawl(): void {
        $resources = $this->api()->get('/resources', ['crawlable' => true])->getBody();
        foreach ($resources as $row) {
            ['url' => $url] = $row;
            $this->assertResourceCrawl($url);
        }
    }
}
