<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use Vanilla\CurrentTimeStamp;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;

/**
 * Tests for the experimental trending sort.
 */
class DiscussionsTrendingTest extends SiteTestCase
{
    use CommunityApiTestTrait;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->resetTable("Discussion");
    }

    /**
     * Test slot types.
     */
    public function testSlotType()
    {
        CurrentTimeStamp::mockTime("2022-01-01");
        $jan1 = $this->createDiscussion();
        CurrentTimeStamp::mockTime("2022-01-02");
        $jan2 = $this->createDiscussion();

        CurrentTimeStamp::mockTime("2022-01-10");
        $jan10 = $this->createDiscussion();

        CurrentTimeStamp::mockTime("2022-01-14");
        $jan14 = $this->createDiscussion();

        CurrentTimeStamp::mockTime("2022-02-01");
        $feb1 = $this->createDiscussion();

        CurrentTimeStamp::mockTime("2023-01-02 00:00:01");
        $jan2_2023 = $this->createDiscussion();

        // Jan 1 is not included.
        CurrentTimeStamp::mockTime("2022-01-02 00:01:00");
        $this->assertDiscussionsInIndex(
            [$jan2],
            [
                "slotType" => "d",
            ]
        );

        // Jan 2 is not included.
        CurrentTimeStamp::mockTime("2022-01-15");
        $this->assertDiscussionsInIndex(
            [$jan14, $jan10],
            [
                "slotType" => "w",
            ]
        );

        // Jan 1 is not included.
        CurrentTimeStamp::mockTime("2022-02-01 00:01:00");
        $this->assertDiscussionsInIndex(
            [$feb1, $jan14, $jan10, $jan2],
            [
                "slotType" => "m",
            ]
        );

        // Jan 1 2022 is not included.
        CurrentTimeStamp::mockTime("2023-01-02 00:00:01");
        $this->assertDiscussionsInIndex(
            [$jan2_2023, $feb1, $jan14, $jan10],
            [
                "slotType" => "y",
            ]
        );

        // All are included
        CurrentTimeStamp::mockTime("2023-02-02");
        $this->assertDiscussionsInIndex(
            [$jan2_2023, $feb1, $jan14, $jan10, $jan2, $jan1],
            [
                "slotType" => "a",
            ]
        );
    }

    /**
     * Test validation errors.
     *
     * @param array $invalidQuery
     *
     * @dataProvider provideInvalidTrendingQuery
     */
    public function testInvalidTrendingQuery(array $invalidQuery)
    {
        $this->expectExceptionCode(400);
        $this->api()->get("/discussions", $invalidQuery);
    }

    /**
     * @return iterable
     */
    public function provideInvalidTrendingQuery(): iterable
    {
        yield "no slot type" => [
            [
                "sort" => "-experimentalTrending",
            ],
        ];

        yield "invalid slot type a" => [
            [
                "sort" => "-experimentalTrending",
                "slotType" => "a",
            ],
        ];

        yield "invalid slot type y" => [
            [
                "sort" => "-experimentalTrending",
                "slotType" => "y",
            ],
        ];
    }

    /**
     * Test the relative weights of comments vs score vs views.
     */
    public function testRelativeWeights()
    {
        CurrentTimeStamp::mockTime("2023-02-02");

        $commentDisc = $this->createDiscussion([], ["CountComments" => 10]);
        $scoreDisc = $this->createDiscussion([], ["Score" => 21]);
        $viewDisc = $this->createDiscussion([], ["CountViews" => 220]);

        $this->assertDiscussionsInIndex(
            [$viewDisc, $scoreDisc, $commentDisc],
            [
                "sort" => "-experimentalTrending",
                "slotType" => "m",
            ]
        );
    }

    /**
     * Test our date falloff. Since this is algorithmic it's really tough to create a good test case, so this just tests extremes.
     */
    public function testDateFallOff()
    {
        CurrentTimeStamp::mockTime("2022-01-01");
        $trendingOld = $this->createDiscussion([], ["Score" => 200, "CountViews" => 1000]);
        CurrentTimeStamp::mockTime("2022-01-05");
        $trendingNew = $this->createDiscussion([], ["Score" => 40, "CountViews" => 400]);

        CurrentTimeStamp::mockTime("2022-01-06");
        $this->assertDiscussionsInIndex(
            [$trendingOld, $trendingNew],
            [
                "sort" => "-experimentalTrending",
                "slotType" => "w",
            ]
        );
    }

    /**
     * Assert that the discussions API returns certain discussions.
     *
     * @param array $expectedDiscussions
     * @param array $query
     */
    private function assertDiscussionsInIndex(array $expectedDiscussions, array $query = [])
    {
        $result = $this->api()->get(
            "/discussions",
            $query + [
                // With the fake times make sure we aren't looking into the "future".
                "dateInserted" => "<=" . CurrentTimeStamp::getDateTime()->format(DATE_ATOM),
            ]
        );
        $data = $result->getBody();

        $this->assertRowsLike(["discussionID" => array_column($expectedDiscussions, "discussionID")], $data, true);
    }
}
