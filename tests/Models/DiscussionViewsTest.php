<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models;

use VanillaTests\DatabaseTestTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;

/**
 * Tests for discussion CountViews column.
 */
class DiscussionViewsTest extends SiteTestCase
{
    use CommunityApiTestTrait;
    use DatabaseTestTrait;

    /**
     * Test the cache-based discussion view count buffer.
     */
    public function testDenormalizedViews()
    {
        $cache = $this->enableCaching();
        $discussion1 = $this->createDiscussion();
        $discussion2 = $this->createDiscussion();
        $discussion3 = $this->createDiscussion();

        \Gdn::config()->saveToConfig([
            "Vanilla.Views.Denormalize" => true,
            "Vanilla.Views.DenormalizeWriteback" => 3,
        ]);
        $discussionModel = self::container()->get(\DiscussionModel::class);

        // 3 views triggers the writeback
        $discussionModel->addView($discussion1["discussionID"]);
        $discussionModel->addView($discussion1["discussionID"]);

        // We have written to the DB yet.
        $this->assertRecordsFound(
            "Discussion",
            [
                "DiscussionID" => $discussion1["discussionID"],
                "CountViews" => 1, // They start at one.
            ],
            1
        );

        $discussionModel->addView($discussion1["discussionID"]);

        // Now the buffer should be flushed
        $this->assertRecordsFound(
            "Discussion",
            [
                "DiscussionID" => $discussion1["discussionID"],
                "CountViews" => 4,
            ],
            1
        );

        $discussionModel->addView($discussion2["discussionID"]);

        $ids = [$discussion1["discussionID"], $discussion2["discussionID"], $discussion3["discussionID"]];
        $discussions = $this->api()
            ->get("/discussions", [
                "discussionID" => $ids,
                "sort" => "discussionID",
            ])
            ->getBody();

        $this->assertRowsLike(
            [
                "discussionID" => $ids,
                "countViews" => [4, 2, 1],
            ],
            $discussions
        );
    }
}
