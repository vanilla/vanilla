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
trait TestPrimaryKeyRangeFilterTrait {
    /**
     * Test a basic integration of the PK range.
     */
    public function testIndexRange(): void {
        [$_, $rows] = $this->testIndex();

        TestCase::assertGreaterThanOrEqual(4, count($rows), "The testIndex() method doesn't provide enough rows to test.");

        [$minID, $maxID] = $this->minMaxIDs($rows);

        /** @var HttpResponse $r */
        $r = $this->api()->get(
            $this->baseUrl,
            [
                $this->pk => "($minID,$maxID)",
                'limit' => count($rows) - 3,
            ]
        );
        $newRows = $r->getBody();

        [$newMinID, $newMaxID] = $this->minMaxIDs($newRows);
        TestCase::assertGreaterThan($minID, $newMinID, "The min ID range was not respected.");
        TestCase::assertLessThan($maxID, $newMaxID, "The max ID range was not respected.");

        TestCase::assertTrue($r->hasHeader("Link"), "No paging information was found.");
        $paging = ApiUtils::parsePageHeader($r->getHeader('Link'));
        TestCase::assertIsArray($paging, "The link header should parse to an array of page links.");
        TestCase::assertArrayHasKey('next', $paging);
        TestCase::assertIsString(filter_var($paging['next'], FILTER_VALIDATE_URL), "The next page is not a valid URL.");
    }

    /**
     * Calculate the min and max IDs of a dataset.
     *
     * @param array $rows
     * @return array
     */
    private function minMaxIDs(array $rows): array {
        $minID = null;
        $maxID = null;
        foreach ($rows as $row) {
            $minID = min($minID ?: PHP_INT_MAX, $row[$this->pk]);
            $maxID = max($maxID ?: PHP_INT_MIN, $row[$this->pk]);
        }
        return array($minID, $maxID);
    }
}
