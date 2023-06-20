<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use Garden\Http\HttpResponse;
use PHPUnit\Framework\TestCase;
use Vanilla\ApiUtils;

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

    public function testIndexRageGetsModifiedWhenPageCountExceedsLimit()
    {
        [$_, $rows] = $this->testIndex();
        $rowCount = count($rows);

        // Insert a few rows.
        for ($i = $rowCount; $i < 10; $i++) {
            $rows[] = $this->testPost();
        }
        [$minID, $maxID] = $this->minMaxIDs($rows);

        $pageUrl = $this->api()->getBaseUrl() . "{$this->baseUrl}?page=%s&{$this->pk}=%s&sort=%s&limit=2";
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
        TestCase::assertArrayHasKey("prev", $paging);
        TestCase::assertStringContainsString(sprintf($pageUrl, 1, "$minID..$maxID", $this->pk), $paging["prev"]);
        TestCase::assertArrayHasKey("next", $paging);
        TestCase::assertStringContainsString(sprintf($pageUrl, 1, "$newID..$maxID", $this->pk), $paging["next"]);

        // Sorted Desc
        $r = $this->api()->get($this->baseUrl, [
            "page" => 1,
            $this->pk => "$minID..$maxID",
            "limit" => 2,
            "sort" => "-" . $this->pk,
        ]);
        $newRows = $r->getBody();
        $newID = $newRows[1][$this->pk] - 1;
        $paging = ApiUtils::parsePageHeader($r->getHeader("Link"));
        TestCase::assertIsArray($paging, "The link header should parse to an array of page links.");
        TestCase::assertArrayHasKey("prev", $paging);
        TestCase::assertStringContainsString(sprintf($pageUrl, 1, "$minID..$maxID", "-" . $this->pk), $paging["prev"]);
        TestCase::assertArrayHasKey("next", $paging);
        TestCase::assertStringContainsString(sprintf($pageUrl, 1, "$minID..$newID", "-" . $this->pk), $paging["next"]);
    }
}
