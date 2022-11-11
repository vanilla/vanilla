<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use Vanilla\CurrentTimeStamp;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;

/**
 * Test the /api/v2/comments endpoints.
 */
class CommentsSortDateUpdatedTest extends AbstractAPIv2Test
{
    use CommunityApiTestTrait;

    /**
     * Test the sorting on dateUpdated.
     */
    public function testSortDateUpdated(): void
    {
        $now = time();
        CurrentTimeStamp::mockTime($now);
        $cat = $this->createCategory();
        $d1 = $this->createDiscussion();
        $c1 = $this->createComment();
        CurrentTimeStamp::mockTime(++$now);
        $c2 = $this->createComment();
        $d2 = $this->createDiscussion();
        CurrentTimeStamp::mockTime(++$now);
        $c3 = $this->createComment();
        CurrentTimeStamp::mockTime(++$now);
        $c4 = $this->createComment();
        CurrentTimeStamp::mockTime(++$now);
        $c3 = $this->api()
            ->patch("/comments/" . $c3["commentID"], $c3)
            ->getBody();
        CurrentTimeStamp::mockTime(++$now);
        $c1 = $this->api()
            ->patch("/comments/" . $c1["commentID"], $c1)
            ->getBody();

        $expectedDesc = [$c1, $c3, $c4, $c2];
        $expectedAsc = array_reverse($expectedDesc);
        $expectedDescIDs = array_column($expectedDesc, "commentID");
        $expectedAscIDs = array_column($expectedAsc, "commentID");

        $query = ["insertUserID" => $c1["insertUserID"], "sort" => "-dateUpdated"];

        $comments = $this->api()
            ->get("/comments", $query)
            ->getBody();
        $this->assertEquals($expectedDescIDs, array_column($comments, "commentID"));

        $query["sort"] = "dateUpdated";
        $comments = $this->api()
            ->get("/comments", $query)
            ->getBody();
        $this->assertEquals($expectedAscIDs, array_column($comments, "commentID"));
    }
}
