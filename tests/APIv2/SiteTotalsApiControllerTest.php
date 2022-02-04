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
class SiteTotalsApiControllerTest extends AbstractAPIv2Test {
    use CommunityApiTestTrait;

    // Don't enable stub content.
    public static $addons = ['vanilla'];

    protected $baseUrl = "/site-totals";

    protected $cache;

    /**
     * @inheritDoc
     */
    public function setUp(): void {
        parent::setUp();
        // Make sure we have a fresh cache for each test.
        self::$testCache->flush();
    }

    /**
     * Test getting a single site-totals count.
     */
    public function testGettingSingleCount() {
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
    public function testGettingMultipleCounts() {
        $this->createDiscussion();
        $this->createComment();
        $this->createComment();
        $this->createComment();
        $commentAndDiscussionCounts = $this->api()->get($this->baseUrl . "?counts[]=discussion&counts[]=comment");
        $this->assertSame($commentAndDiscussionCounts["counts"]["discussion"]["count"], 4);
        $this->assertSame($commentAndDiscussionCounts["counts"]["comment"]["count"], 3);
    }

    /**
     * Test site total post count.
     *
     * @depends testGettingMultipleCounts
     */
    public function testPostCounts() {
        // Make sure the category table's counts are up-to-date.
        $categoryModel = Gdn::getContainer()->get(\CategoryModel::class);

        $categoryModel->counts("CountComments");
        $categoryModel->counts("CountAllComments");

        $postCounts = $this->api()->get($this->baseUrl. "?counts[]=post");
        $this->assertSame($postCounts["counts"]["post"]["count"], 7);
    }

    /**
     * Test passing "all" to get all available counts.
     */
    public function testGettingAllCounts() {
        $siteTotalService = Gdn::getContainer()->get(SiteTotalService::class);
        $allRecordTypes = $siteTotalService->getCountRecordTypes();
        $allCountsResponse = $this->api()->get($this->baseUrl . "?counts[]=all");
        $countFields = array_keys($allCountsResponse["counts"]);
        foreach ($allRecordTypes as $recordType) {
            $this->assertTrue(in_array($recordType, $countFields));
        }
    }
}
