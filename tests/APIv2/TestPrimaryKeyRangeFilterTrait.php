<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use PHPUnit\Framework\TestCase;
use Vanilla\ApiUtils;
use VanillaTests\VanillaTestCase;

/**
 * Adds some tests for `AbstractResourceTest` subclasses that have support for primary key range filters.
 */
trait TestPrimaryKeyRangeFilterTrait
{
    /**
     * Test a basic integration of the PK range.
     */
    public function testIndexRange(): void
    {
        [$_, $rows] = $this->testIndex();

        TestCase::assertGreaterThanOrEqual(
            4,
            count($rows),
            "The testIndex() method doesn't provide enough rows to test."
        );

        [$minID, $maxID] = $this->minMaxIDs($rows);

        $r = $this->api()->get($this->baseUrl, [
            $this->pk => "($minID,$maxID)",
            "limit" => count($rows) - 3,
        ]);
        $newRows = $r->getBody();

        [$newMinID, $newMaxID] = $this->minMaxIDs($newRows);
        TestCase::assertGreaterThan($minID, $newMinID, "The min ID range was not respected.");
        TestCase::assertLessThan($maxID, $newMaxID, "The max ID range was not respected.");

        TestCase::assertTrue($r->hasHeader("Link"), "No paging information was found.");
        $paging = ApiUtils::parsePageHeader($r->getHeader("Link"));
        TestCase::assertIsArray($paging, "The link header should parse to an array of page links.");
        TestCase::assertArrayHasKey("first", $paging);
        TestCase::assertIsString(
            filter_var($paging["first"], FILTER_VALIDATE_URL),
            "The first page is not a valid URL."
        );
    }

    /**
     * Calculate the min and max IDs of a dataset.
     *
     * @param array $rows
     * @return array
     */
    private function minMaxIDs(array $rows): array
    {
        $minID = null;
        $maxID = null;
        foreach ($rows as $row) {
            $minID = min($minID ?: PHP_INT_MAX, $row[$this->pk]);
            $maxID = max($maxID ?: PHP_INT_MIN, $row[$this->pk]);
        }
        return [$minID, $maxID];
    }

    /**
     * Test that an endpoint can use a range expression as a cursor when sorting by the primary key.
     *
     * @return void
     */
    public function testIndexRageGetsModifiedWhenPageCountExceedsLimit()
    {
        [$_, $rows] = $this->testIndex();
        $rowCount = count($rows);

        // Insert a few rows.
        for ($i = $rowCount; $i < 10; $i++) {
            $rows[] = $this->testPost();
        }
        [$minID, $maxID] = $this->minMaxIDs($rows);

        // Sorted Asc
        $r = $this->api()->get($this->baseUrl, [
            "page" => 1,
            $this->pk => "$minID..$maxID",
            "sort" => $this->pk,
            "limit" => 2,
        ]);
        $newRows = $r->getBody();
        $newID = $newRows[1][$this->pk] + 1;
        TestCase::assertTrue($r->hasHeader("Link"), "No paging information was found.");

        $paging = ApiUtils::parsePageHeader($r->getHeader("Link"));
        TestCase::assertIsArray($paging, "The link header should parse to an array of page links.");
        TestCase::assertNull($paging["prev"] ?? null, "The first page should not have a prev link.");
        TestCase::assertArrayHasKey("next", $paging);
        self::assertUrlQueryParams(
            [
                $this->pk => "{$newID}..{$maxID}",
                "page" => "1",
                "sort" => $this->pk,
            ],
            $paging["next"]
        );

        // Sorted Desc
        // It works even without the page and full primary key parameters.
        // The only requirement is that there is a primary key sort applied.
        $r = $this->api()->get($this->baseUrl, [
            "limit" => 2,
            "sort" => "-" . $this->pk,
            $this->pk => ">=0",
        ]);
        $newRows = $r->getBody();
        $newID = $newRows[1][$this->pk] - 1;
        $paging = ApiUtils::parsePageHeader($r->getHeader("Link"));
        TestCase::assertIsArray($paging, "The link header should parse to an array of page links.");
        TestCase::assertNull($paging["prev"] ?? null, "The first page should not have a prev link.");
        TestCase::assertArrayHasKey("next", $paging);
        self::assertUrlQueryParams(
            [
                $this->pk => "0..{$newID}",
                "page" => "1",
                "sort" => "-{$this->pk}",
            ],
            $paging["next"]
        );
    }

    /**
     * Assert that a URL has certain query parameters.
     *
     * @param array $expected
     * @param string $url
     *
     * @return void
     */
    private function assertUrlQueryParams(array $expected, string $url)
    {
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        VanillaTestCase::assertArraySubsetRecursive($expected, $query);
    }
}
