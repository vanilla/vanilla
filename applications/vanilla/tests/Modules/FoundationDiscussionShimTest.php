<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Forum\Modules;

use PHPUnit\Framework\Error\Notice;
use Vanilla\Forum\Modules\FoundationDiscussionsShim;
use VanillaTests\ExpectExceptionTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;

/**
 * Tests for the shim.
 */
class FoundationDiscussionShimTest extends SiteTestCase
{
    use CommunityApiTestTrait;
    use ExpectExceptionTrait;

    /**
     * Clear table between tests.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->resetTable("Discussion");
        $this->resetTable("UserDiscussion");
    }

    /**
     * Test that our data is converted into the new format properly.
     */
    public function testConvertLegacyDiscussion()
    {
        $this->createDiscussion([
            "name" => "Hello name!",
        ]);

        $legacyItems = $this->getDiscussionModel()
            ->getWhereRecent([], 1)
            ->resultArray();

        /** @var FoundationDiscussionsShim $shim */
        $shim = self::container()->get(FoundationDiscussionsShim::class);

        $currentUserFragment = [
            "userID" => 2,
            "name" => "circleci",
            "url" => "http://vanilla.test/foundationdiscussionshimtest/profile/circleci",
            "photoUrl" => "http://vanilla.test/foundationdiscussionshimtest" . \UserModel::PATH_DEFAULT_AVATAR,
            "banned" => 0,
            "punished" => 0,
            "private" => false,
        ];

        $id = $legacyItems[0]["DiscussionID"];
        $expected = [
            [
                "discussionID" => $id,
                "type" => "discussion",
                "name" => "Hello name!",
                "excerpt" => "Hello Discussion",
                "categoryID" => -1,
                "insertUserID" => 2,
                "insertUser" => $currentUserFragment,
                "updateUserID" => null,
                "lastUserID" => 2,
                "lastUser" => $currentUserFragment,
                "pinned" => false,
                "pinLocation" => null,
                "closed" => false,
                "sink" => false,
                "countComments" => 0,
                "countViews" => 1,
                "score" => null,
                "hot" => 0,
                "url" => "http://vanilla.test/foundationdiscussionshimtest/discussion/$id/hello-name",
                "canonicalUrl" => "http://vanilla.test/foundationdiscussionshimtest/discussion/$id/hello-name",
                "format" => "text",
                "statusID" => 0,
                "bookmarked" => false,
                "unread" => false,
                "category" => [
                    "categoryID" => -1,
                    "name" => "FoundationDiscussionShimTest",
                    "url" => "http://vanilla.test/foundationdiscussionshimtest/categories",
                    "allowedDiscussionTypes" => [],
                ],
                "tags" => [],
            ],
        ];
        $actual = $shim->convertLegacyData($legacyItems);

        // normalize userInfo data
        $actual[0]["insertUser"] = $actual[0]["insertUser"]->jsonSerialize();
        $actual[0]["lastUser"] = $actual[0]["lastUser"]->jsonSerialize();

        unset($actual[0]["dateInserted"]);
        unset($actual[0]["dateUpdated"]);
        unset($actual[0]["dateLastComment"]);
        unset($actual[0]["insertUser"]["dateLastActive"]);
        unset($actual[0]["lastUser"]["dateLastActive"]);
        unset($actual[0]["attributes"]);

        $this->assertArraySubsetRecursive($expected, $actual);
    }

    /**
     * Test that bad items are dropped and a notice is logged.
     *
     * @depends testConvertLegacyDiscussion
     */
    public function testBadDataConversion()
    {
        $this->createDiscussion([
            "name" => "Hello name 2!",
        ]);
        // Has no fields. Will definitely fail validation.
        $legacyItems = $this->getDiscussionModel()
            ->getWhereRecent([], 1)
            ->resultArray();

        // Push in an invalid item.
        $legacyItems[] = [];

        /** @var FoundationDiscussionsShim $shim */
        $shim = self::container()->get(FoundationDiscussionsShim::class);
        $actual = $shim->convertLegacyData($legacyItems);
        $this->assertCount(1, $actual);
    }

    /**
     * @return \DiscussionModel
     */
    private function getDiscussionModel(): \DiscussionModel
    {
        return self::container()->get(\DiscussionModel::class);
    }
}
