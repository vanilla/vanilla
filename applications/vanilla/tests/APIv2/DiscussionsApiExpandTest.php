<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv2;

use QnAPlugin;
use Vanilla\Forum\Models\DiscussionPermissions;
use VanillaTests\APIv2\AbstractAPIv2Test;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Search discussion content with sort: hot, top.
 */
class DiscussionsApiExpandTest extends AbstractAPIv2Test
{
    use CommunityApiTestTrait;
    use UsersAndRolesApiTestTrait;

    protected static $addons = ["vanilla", "QnA", "test-mock-issue"];

    /**
     * Prepare data for tests
     */
    public function testPrepareData()
    {
        $discussions = [
            ["name" => "Discussion one", "body" => "Body one"],
            ["name" => "Discussion two", "body" => "Body two"],
        ];

        foreach ($discussions as $discussion) {
            $this->createDiscussion($discussion);
        }

        $discussions = $this->api()
            ->get("/discussions", ["limit" => 30])
            ->getBody();
        $this->assertEquals(2, count($discussions));

        foreach ($discussions as $discussion) {
            $this->api()->put("/discussions/{$discussion["discussionID"]}/status", [
                "statusID" => QnAPlugin::DISCUSSION_STATUS_UNANSWERED,
                "statusNotes" => "Pending answer {$discussion["discussionID"]}",
            ]);
        }
    }

    /**
     * Tests for discussions expand options: excerpt, -body
     *
     * @param array $params
     * @param array $expectedResults
     * @param string $paramKey
     * @depends testPrepareData
     * @dataProvider queryDataProvider
     */
    public function testDiscussionsExpand(array $params, array $expectedResults, string $paramKey = null)
    {
        $this->assertApiResults("/discussions", $params, $expectedResults, false, count($expectedResults[$paramKey]));
    }

    /**
     * @return array
     */
    public function queryDataProvider()
    {
        return [
            "no expand options" => [
                [
                    "expand" => [],
                ],
                [
                    "name" => ["Discussion one", "Discussion two"],
                    "body" => ["Body one", "Body two"],
                    "excerpt" => null,
                ],
                "name",
            ],
            "expand -body" => [
                [
                    "expand" => ["-body"],
                ],
                [
                    "name" => ["Discussion one", "Discussion two"],
                    "body" => null,
                    "excerpt" => null,
                ],
                "name",
            ],
            "expand excerpt" => [
                [
                    "expand" => ["excerpt"],
                ],
                [
                    "name" => ["Discussion one", "Discussion two"],
                    "body" => ["Body one", "Body two"],
                    "excerpt" => ["Body one", "Body two"],
                ],
                "name",
            ],
            "expand: excerpt, -body" => [
                [
                    "expand" => ["excerpt", "-body"],
                ],
                [
                    "name" => ["Discussion one", "Discussion two"],
                    "body" => null,
                    "excerpt" => ["Body one", "Body two"],
                ],
                "name",
            ],
            "expand: status" => [
                [
                    "expand" => ["status", "status.log"],
                ],
                [
                    "status.log.reasonUpdated" => ["Pending answer 1", "Pending answer 2"],
                ],
                "status.log.reasonUpdated",
            ],
        ];
    }

    /**
     * Test expanding attachments via the "/discussions/{id} endpoint.
     *
     * @return void
     */
    public function testExpandDiscussionAttachments(): void
    {
        $discussion = $this->createDiscussion();
        $attachment = $this->createAttachment("discussion", $discussion["discussionID"]);
        $result = $this->api()
            ->get("/discussions/{$discussion["discussionID"]}", ["expand" => "attachments"])
            ->getBody();
        $this->assertArrayHasKey("attachments", $result);
        $this->assertCount(1, $result["attachments"]);
        $this->assertEquals($attachment["AttachmentID"], $result["attachments"][0]["attachmentID"]);
    }

    /**
     * Test that expanding attachments via the "/discussion/{id}" endpoint without the "Garden.Staff.Allow" permission
     * returns no attachments.
     *
     * @return void
     */
    public function testExpandDiscussionAttachmentsWithoutPermission(): void
    {
        $discussion = $this->createDiscussion();
        $this->createAttachment("discussion", $discussion["discussionID"]);
        $member = $this->createUser();
        $this->api()->setUserID($member["userID"]);
        $discussion = $this->api()
            ->get("/discussions/{$discussion["discussionID"]}", ["expand" => "attachments"])
            ->getBody();
        $this->assertArrayNotHasKey("attachments", $discussion);
    }

    /**
     * Test that expanding attachments via the "/discussions" endpoint without the "Garden.Staff.Allow" permission
     * returns no attachments.
     *
     * @return void
     */
    public function testExpandDiscussionsAttachmentsWithoutPermission(): void
    {
        $discussion = $this->createDiscussion();
        $this->createAttachment("discussion", $discussion["discussionID"]);
        $member = $this->createUser();
        $this->api()->setUserID($member["userID"]);
        $result = $this->api()
            ->get("/discussions", ["expand" => "attachments"])
            ->getBody();
        $retrievedDiscussion = $result[0];
        $this->assertArrayNotHasKey("attachments", $retrievedDiscussion);
    }

    /**
     * Test expansion of discussion permissions.
     *
     * @return void
     */
    public function testExpandPermissions()
    {
        $moderatorCategory = $this->createCategory();
        $partialPermissions =
            [
                DiscussionPermissions::VIEW => true,
                DiscussionPermissions::ADD => true,
                DiscussionPermissions::EDIT => true,
                DiscussionPermissions::DELETE => false,
                DiscussionPermissions::COMMENTS_DELETE => true,
            ] + array_fill_keys(DiscussionPermissions::getPermissionNames(), false);
        $user = $this->createUserWithCategoryPermissions($moderatorCategory, $partialPermissions);
        $discussion = $this->createDiscussion();

        // First let's check it as our admin, we should have everything.
        $allPermissions = array_fill_keys(DiscussionPermissions::getPermissionNames(), true);

        $this->assertUserHasDiscussionPermissions(
            $allPermissions,
            discussion: $discussion,
            user: $this->api()->getUserID()
        );
        $this->assertUserHasCategoryDiscussionPermissions(
            $allPermissions,
            category: $moderatorCategory,
            user: $this->api()->getUserID()
        );

        $this->assertUserHasDiscussionPermissions($partialPermissions, discussion: $discussion, user: $user);
        $this->assertUserHasCategoryDiscussionPermissions(
            $partialPermissions,
            category: $moderatorCategory,
            user: $user
        );
    }
}
