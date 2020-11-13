<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use VanillaTests\Forum\Utils\CommunityApiTestTrait;

/**
 * Test the /api/v2/comments endpoints.
 */
class CommentsSortDateUpdatedTest extends AbstractAPIv2Test {
    use CommunityApiTestTrait;

    /**
     * Test the sorting on dateUpdated.
     */
    public function testSortDateUpdated(): void {
        $cat = $this->createCategory();
        $d1 = $this->createDiscussion();
        $c1 = $this->createComment();
        sleep(1);
        $c2 = $this->createComment();
        $d2 = $this->createDiscussion();
        sleep(1);
        $c3 = $this->createComment();
        sleep(1);
        $c4 = $this->createComment();
        sleep(1);
        $c3 = $this->api()->patch('/comments/'.$c3['commentID'], $c3)->getBody();
        sleep(1);
        $c1 = $this->api()->patch('/comments/'.$c1['commentID'], $c1)->getBody();

        $expected = [$c1, $c3, $c4, $c2];

        $comments = $this->api()->get('/comments', ['insertUserID' => $c1['insertUserID'], 'sort' => '-dateUpdated'])->getBody();
        foreach ($comments as $key => $comment) {
            $this->assertEquals($expected[$key]['commentID'], $comment['commentID']);
        }

        $comments = $this->api()->get('/comments', ['insertUserID' => $c1['insertUserID'], 'sort' => 'dateUpdated'])->getBody();
        foreach ($comments as $key => $comment) {
            $this->assertEquals($expected[3-$key]['commentID'], $comment['commentID']);
        }
    }
}
