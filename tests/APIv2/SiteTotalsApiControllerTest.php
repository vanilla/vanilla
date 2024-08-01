<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use Gdn;
use Vanilla\Models\SiteTotalService;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;

/**
 * Tests for the SiteTotalsApiController.
 */
class SiteTotalsApiControllerTest extends AbstractAPIv2Test
{
    use CommunityApiTestTrait;

    // Don't enable stub content.
    public static $addons = ["vanilla"];

    protected $baseUrl = "/site-totals";

    /**
     * Test getting a single site-totals count.
     */
    public function testGettingSingleCount()
    {
        $this->createCategory(["name" => "Single Count"]);
        $this->createDiscussion();
        $this->createDiscussion();
        $this->createDiscussion();
        $counts = $this->api()->get($this->baseUrl . "?counts[]=discussion");
        $this->assertSame($counts["counts"]["discussion"]["count"], 3);
    }

    /**
     * Test getting more than one count in a single call.
     *
     * @depends testGettingSingleCount
     */
    public function testGettingMultipleCounts()
    {
        $this->createCategory(["name" => "Multiple Count"]);
        $this->createDiscussion();
        $this->createComment();
        $this->createComment();
        $this->createComment();

        // Make sure all counts are up-to-date.
        $categoryModel = Gdn::getContainer()->get(\CategoryModel::class);

        $commentAndDiscussionCounts = $this->api()->get($this->baseUrl . "?counts=comment,discussion,post");
        $this->assertSame(4, $commentAndDiscussionCounts["counts"]["discussion"]["count"]);
        $this->assertSame(3, $commentAndDiscussionCounts["counts"]["comment"]["count"]);
        $this->assertSame(7, $commentAndDiscussionCounts["counts"]["post"]["count"]);
    }

    /**
     * Test site total post count.
     *
     * @depends testGettingMultipleCounts
     */
    public function testPostCounts()
    {
        $postCounts = $this->api()->get($this->baseUrl . "?counts[]=post");

        $this->assertSame($postCounts["counts"]["post"]["count"], 7);
    }

    /**
     * Test site total category count.
     */
    public function testCategoryCounts()
    {
        $categoryModel = \CategoryModel::instance();
        //Avoid root category
        $currentCount = $categoryModel->getCount(["CategoryID <>" => -1]);
        $this->createCategory();
        $this->createCategory();
        $catCount = $this->api()->get($this->baseUrl . "?counts[]=category");
        $this->assertSame($currentCount + 2, $catCount["counts"]["category"]["count"]);
    }

    /**
     * Test passing "all" to get all available counts.
     */
    public function testGettingAllCounts()
    {
        $siteTotalService = Gdn::getContainer()->get(SiteTotalService::class);
        $allRecordTypes = $siteTotalService->getCountRecordTypes();
        $allCountsResponse = $this->api()->get($this->baseUrl . "?counts[]=all");
        $countFields = array_keys($allCountsResponse["counts"]);
        foreach ($allRecordTypes as $recordType) {
            $this->assertTrue(in_array($recordType, $countFields));
        }

        foreach ($allCountsResponse["counts"] as $count) {
            // Each count should have three fields.
            $this->assertArrayHasKey("count", $count);
            $this->assertArrayHasKey("isCalculating", $count);
            $this->assertArrayHasKey("isFiltered", $count);
            // Is filtered should be false for everything.
            $this->assertFalse($count["isFiltered"]);
        }
    }

    /**
     * Test that the site total are updated after a new record is created.
     *
     * @return void
     */
    public function testSiteTotalAfterUpdate(): void
    {
        $countsBefore = $this->api()
            ->get($this->baseUrl . "?counts[]=all")
            ->getBody()["counts"];

        $this->createUserFixture(self::ROLE_MEMBER);
        $this->createCategory();
        $this->createDiscussion();
        $this->createComment();

        self::$testCache->flush();

        $countsAfter = $this->api()
            ->get($this->baseUrl . "?counts[]=all")
            ->getBody()["counts"];

        $this->assertEquals($countsBefore["user"]["count"] + 1, $countsAfter["user"]["count"]);
        $this->assertEquals($countsBefore["category"]["count"] + 1, $countsAfter["category"]["count"]);
        $this->assertEquals($countsBefore["discussion"]["count"] + 1, $countsAfter["discussion"]["count"]);
        $this->assertEquals($countsBefore["comment"]["count"] + 1, $countsAfter["comment"]["count"]);
        $this->assertEquals($countsBefore["post"]["count"] + 2, $countsAfter["post"]["count"]);
    }
}
