<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use Vanilla\CurrentTimeStamp;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\UsersAndRolesApiTestTrait;

class TriageTest extends AbstractAPIv2Test
{
    use UsersAndRolesApiTestTrait;
    use CommunityApiTestTrait;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        CurrentTimeStamp::mockTime("2024-01-01");
    }

    /**
     * Test that we can filter by category.
     */
    public function testCategoryFilters(): void
    {
        $cat1 = $this->createCategory();
        $disc1 = $this->createDiscussion();
        $cat2 = $this->createCategory();
        $disc2 = $this->createDiscussion();

        $this->assertApiResults(
            "/reports/triage",
            ["placeRecordType" => "category", "placeRecordID" => $cat1["categoryID"]],
            ["recordID" => [$disc1["discussionID"]]]
        );

        $this->assertApiResults(
            "/reports/triage",
            ["placeRecordType" => "category", "placeRecordID" => $cat2["categoryID"]],
            ["recordID" => [$disc2["discussionID"]]]
        );

        $this->assertApiResults(
            "/reports/triage",
            ["placeRecordType" => "category", "placeRecordID" => [$cat1["categoryID"], $cat2["categoryID"]]],
            ["recordID" => [$disc1["discussionID"], $disc2["discussionID"]]]
        );
    }

    /**
     * Test filtering by recordUserID and recordUserRoleID
     */
    public function testUserFilters(): void
    {
        $cat1 = $this->createCategory();

        $user1 = $this->createUser([
            "roleID" => [\RoleModel::MOD_ID, \RoleModel::MEMBER_ID],
        ]);
        $user2 = $this->createUser([
            "roleID" => [\RoleModel::MEMBER_ID],
        ]);

        $disc1 = $this->runWithUser(function () {
            return $this->createDiscussion();
        }, $user1);
        $disc2 = $this->runWithUser(function () {
            return $this->createDiscussion();
        }, $user2);

        $this->assertApiResults(
            "/reports/triage",
            ["recordUserID" => $user1["userID"]],
            ["recordID" => [$disc1["discussionID"]]]
        );

        $this->assertApiResults(
            "/reports/triage",
            ["recordUserID" => $user2["userID"]],
            ["recordID" => [$disc2["discussionID"]]]
        );

        $this->assertApiResults(
            "/reports/triage",
            ["recordUserRoleID" => \RoleModel::MEMBER_ID],
            ["recordID" => [$disc1["discussionID"], $disc2["discussionID"]]]
        );

        $this->assertApiResults(
            "/reports/triage",
            ["recordUserRoleID" => \RoleModel::MOD_ID],
            ["recordID" => [$disc1["discussionID"]]]
        );
    }
}
