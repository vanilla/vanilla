<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Cloud\ElasticSearch;

use RoleModel;
use ReactionModel;
use VanillaTests\APIv2\AbstractAPIv2Test;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Search discussion content with sort: hot, top.
 */
class DiscussionSortHotTopTest extends AbstractAPIv2Test
{
    use CommunityApiTestTrait;
    use UsersAndRolesApiTestTrait;

    protected static $addons = ["vanilla", "reactions"];

    protected static $data = [];

    /**
     * Prepare data for tests
     */
    public function testPrepareData()
    {
        $reactionModel = \Gdn::getContainer()->get(ReactionModel::class);
        $reactionModel::$ReactionTypes = null;
        $this->prepareUsers(5);
        $discussions = [
            [["name" => "No Reactions - No Comments", "body" => "Lorem ipsum.. "]],
            [["name" => "No Reactions - 2 Comments", "body" => "Lorem ipsum.. "], 2],
            [["name" => "No Reactions - 3 Comments", "body" => "Lorem ipsum.. "], 3],
            [
                ["name" => "4 Likes - 0 Comments", "body" => "Lorem ipsum.. "],
                0,
                [
                    "user_0" => "Like",
                    "user_1" => "Like",
                    "user_2" => "Like",
                    "user_3" => "Like",
                ],
            ],
            [
                ["name" => "3 Likes - 0 Comments", "body" => "Lorem ipsum.. "],
                0,
                [
                    "user_0" => "Like",
                    "user_1" => "Like",
                    "user_2" => "Like",
                ],
            ],
            [
                ["name" => "1 Like - 1 Comment", "body" => "Lorem ipsum.. "],
                1,
                [
                    "user_0" => "Like",
                ],
            ],
            [
                ["name" => "1 Like - 4 Comments", "body" => "Lorem ipsum.. "],
                4,
                [
                    "user_0" => "Like",
                ],
            ],
        ];

        foreach ($discussions as $discussion) {
            $this->prepareDiscussion($discussion[0], $discussion[1] ?? 0, $discussion[2] ?? []);
        }

        $discussions = $this->api()
            ->get("/discussions", ["limit" => 30])
            ->getBody();
        $this->assertEquals(7, count($discussions));
    }

    /**
     * @param int $countUsers
     */
    private function prepareUsers(int $countUsers)
    {
        for ($i = 0; $i < $countUsers; $i++) {
            self::$data["user_" . $i] = $this->createUser(["roleID" => [RoleModel::ADMIN_ID]]);
        }
    }

    /**
     * Prepare discussion with comments and reactions.
     *
     * @param array $discussion
     * @param int $comments
     * @param array $reactions
     * @param array $commentReactions
     */
    private function prepareDiscussion(
        array $discussion,
        int $comments = 0,
        array $reactions = [],
        array $commentReactions = []
    ) {
        $discussion = $this->createDiscussion($discussion);
        for ($i = 0; $i < $comments; $i++) {
            self::$data["comments"][] = $comment = $this->createComment([
                "body" => $discussion["name"] . " Comment " . $i . " " . md5(time()),
            ]);
            foreach ($reactions as $userKey => $reactionKey) {
                $this->api()->setUserID(self::$data[$userKey]["userID"]);
                $this->api()->post("/discussions/" . $discussion["discussionID"] . "/reactions", [
                    "reactionType" => $reactionKey,
                ]);
            }
        }
        foreach ($reactions as $userKey => $reactionKey) {
            $this->api()->setUserID(self::$data[$userKey]["userID"]);
            $this->api()->post("/discussions/" . $discussion["discussionID"] . "/reactions", [
                "reactionType" => $reactionKey,
            ]);
        }
        $this->setAdminApiUser();
    }

    /**
     * Tests for discussions sort options: score, hot
     *
     * @param array $params
     * @param array $expectedResults
     * @param string $paramKey
     * @depends testPrepareData
     * @dataProvider queryDataProvider
     */
    public function testDiscussionsSort(array $params, array $expectedResults, string $paramKey = null)
    {
        $this->assertApiResults("/discussions", $params, $expectedResults, true, count($expectedResults[$paramKey]));
    }

    /**
     * @return array
     */
    public function queryDataProvider()
    {
        return [
            'sort by "score" ASC' => [
                [
                    "title" => "Like",
                    "sort" => "score",
                    "limit" => 30,
                ],
                [
                    "name" => [
                        "No Reactions - No Comments",
                        "No Reactions - 2 Comments",
                        "No Reactions - 3 Comments",
                        "1 Like - 1 Comment",
                        "1 Like - 4 Comments",
                        "3 Likes - 0 Comments",
                        "4 Likes - 0 Comments",
                    ],
                ],
                "name",
            ],
            'sort by "score" DESC' => [
                [
                    "sort" => "-score",
                    "limit" => 30,
                ],
                [
                    "name" => [
                        "4 Likes - 0 Comments",
                        "3 Likes - 0 Comments",
                        "1 Like - 1 Comment",
                        "1 Like - 4 Comments",
                        "No Reactions - No Comments",
                        "No Reactions - 2 Comments",
                        "No Reactions - 3 Comments",
                    ],
                ],
                "name",
            ],
            'sort by "hot" ASC' => [
                [
                    "sort" => "hot",
                    "limit" => 30,
                ],
                [
                    "name" => [
                        "No Reactions - No Comments",
                        "3 Likes - 0 Comments",
                        "4 Likes - 0 Comments",
                        "1 Like - 1 Comment",
                        "No Reactions - 2 Comments",
                        "No Reactions - 3 Comments",
                        "1 Like - 4 Comments",
                    ],
                ],
                "name",
            ],
            'sort by "hot" DESC' => [
                [
                    "sort" => "-hot",
                    "limit" => 30,
                ],
                [
                    "name" => [
                        "1 Like - 4 Comments",
                        "No Reactions - 3 Comments",
                        "No Reactions - 2 Comments",
                        "1 Like - 1 Comment",
                        "4 Likes - 0 Comments",
                        "3 Likes - 0 Comments",
                        "No Reactions - No Comments",
                    ],
                ],
                "name",
            ],
        ];
    }

    /**
     *  Test hot column values are calculated and records are sorted appropriately.
     *  @return void
     */
    public function testSortHot()
    {
        $this->resetTable("Comment");
        $this->resetTable("Discussion");
        $discussionArray = [];
        $commentArray = [];
        $discussions = [
            0 => [
                "Name" => "Test Discussion A",
                "Body" => "Old discussion having latest comment",
                "CategoryID" => -1,
                "Format" => "Text",
                "DateInserted" => date("Y-m-d H:i:s", strtotime("2020-01-01 09:00:00")),
            ],
            1 => [
                "Name" => "Test Discussion B",
                "Body" => "New discussion",
                "CategoryID" => -1,
                "Format" => "Text",
                "DateInserted" => date("Y-m-d H:i:s", strtotime("2021-10-10 10:00:00")),
            ],
        ];

        $discussionModel = $this->container()->get(\DiscussionModel::class);
        foreach ($discussions as $discussion) {
            $discussionArray[] = $discussionModel->save($discussion);
        }

        $comments = [
            0 => [
                "Body" => "Comment for Discussion A",
                "DiscussionID" => $discussionArray[0],
                "Format" => "Text",
            ],
            1 => [
                "Body" => "Comment for Discussion B",
                "DiscussionID" => $discussionArray[1],
                "Format" => "Text",
                "DateInserted" => date("Y-m-d H:i:s", strtotime("2022-06-05 13:00:00")),
            ],
        ];
        $commentModel = $this->container()->get(\CommentModel::class);
        foreach ($comments as $key => $comment) {
            $commentArray[] = $commentModel->save($comment);
        }
        $discussion2Hot = strtotime("2021-10-10 10:00:00") + strtotime("2022-06-05 13:00:00 + 10 minute");
        $response = $this->api()->get("/discussions", ["sort" => "-hot", "limit" => 10]);
        $this->assertEquals(200, $response->getStatusCode());
        $results = $response->getBody();
        $this->assertEquals($discussionArray[1], $results[0]["discussionID"]);
        $this->assertEquals($discussion2Hot, $results[0]["hot"]);
    }
}
