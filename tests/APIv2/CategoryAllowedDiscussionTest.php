<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2023 Higher Logic Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv2;

use Garden\Web\Exception\ClientException;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Tests related to the AllowedDiscussionTypes for [PATCH] `/api/v2/category{id}` endpoint.
 */
class CategoryAllowedDiscussionTest extends SiteTestCase
{
    protected static $addons = ["QnA", "reactions", "polls"];

    use CommunityApiTestTrait;
    use UsersAndRolesApiTestTrait;

    /**
     * Test patch AllowedDiscussionTypes.
     *
     * @param array $body
     * @param array $expected
     * @return void
     * @dataProvider provideAllowedDiscussionTypesData
     */
    public function testPatchAllowedDiscussionTypes(array $body, array $expected): void
    {
        $category = $this->createCategory(["allowedDiscussionTypes" => ["Discussion"]]);
        $result = $this->api()
            ->patch("categories/{$category["categoryID"]}", [
                "allowedDiscussionTypes" => $body,
            ])
            ->getBody();
        $actual = $result["allowedDiscussionTypes"];
        $this->assertEquals($expected, $actual);
    }

    /**
     * Test patch with invalid AllowedDiscussionTypes.
     *
     * @param $body
     * @param $errorMessage
     * @return void
     * @dataProvider provideInvalidAllowedDiscussionTypesData
     */
    public function testInvalidAllowedDiscussionTypes($body, $errorMessage): void
    {
        $this->expectException(ClientException::class);
        $this->expectErrorMessage($errorMessage);
        $category = $this->createCategory();
        $this->api()->patch("categories/{$category["categoryID"]}", [
            "allowedDiscussionTypes" => $body,
        ]);
    }

    /**
     * Provide body and expected AllowedDiscussionTypes.
     *
     * @return array
     */
    public static function provideAllowedDiscussionTypesData(): array
    {
        $r = [
            "idemPotent" => [["Discussion"], ["discussion"]],
            "setQuestion" => [["Question"], ["question"]],
            "addTwo" => [["Poll", "Question"], ["question", "poll"]],
            "testDuplicates" => [["Question", "Question"], ["question"]],
        ];
        return $r;
    }

    /**
     * Provide invalid body of AllowedDiscussionTypes.
     *
     * @return array
     */
    public static function provideInvalidAllowedDiscussionTypesData(): array
    {
        $r = [
            "invalidType" => [
                ["Blog"],
                "allowedDiscussionTypes can only contain the following values: Discussion, Question, Poll",
            ],
            "notAnArray" => ["Question", "allowedDiscussionTypes is not a valid array."],
            "testEmpty" => [
                [],
                "allowedDiscussionTypes can only contain the following values: Discussion, Question, Poll",
            ],
        ];
        return $r;
    }
}
