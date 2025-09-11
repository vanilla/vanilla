<?php
/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace APIv2;

use RoleModel;
use Vanilla\CurrentTimeStamp;
use Vanilla\Web\PrivateCommunityMiddleware;
use VanillaTests\ExpectExceptionTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Test the /api/v2/users/by-names endpoints.
 */
class AtMentionUsersTest extends SiteTestCase
{
    use UsersAndRolesApiTestTrait;
    use ExpectExceptionTrait;
    use CommunityApiTestTrait;

    /**
     * @var array
     */
    private $testRole;
    /**
     * @var array
     */
    private $category;
    private string $baseUrl;
    private string $resourceName;
    private array $record;
    private array $sortFields;
    private $configuration;

    /**
     * {@inheritdoc}
     */
    public function __construct($name = null, array $data = [], $dataName = "")
    {
        $this->baseUrl = "/users";
        $this->resourceName = "user";
        $this->record = [
            "name" => null,
            "email" => null,
        ];
        $this->sortFields = ["dateInserted", "dateLastActive", "name", "userID", "points", "countPosts"];

        parent::__construct($name, $data, $dataName);
    }

    /**
     * Disable email before running tests.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->configuration = static::container()->get("Config");
        $this->configuration->set("Garden.Email.Disabled", true);

        /* @var PrivateCommunityMiddleware $middleware */
        $middleware = static::container()->get(PrivateCommunityMiddleware::class);
        $middleware->setIsPrivate(false);
        $this->resetTable("profileField");
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Create a user with the given name and role.
     *
     * @param string $name
     * @return array
     */
    public function setupUserForTest(string $name)
    {
        $this->createUserFixtures();
        $this->testRole = $this->createRole(["name" => "{$name}role"]);
        $this->category = $this->createPermissionedCategory([], [$this->testRole["roleID"]]);
        $this->userModel->save(
            ["UserID" => $this->memberID, "RoleID" => [RoleModel::MEMBER_ID, $this->testRole["roleID"]]],
            ["SaveRoles" => true]
        );
        $this->createUser([
            "name" => "{$name} User",
            "roleID" => RoleModel::MEMBER_ID,
        ]);
        $this->createUser([
            "name" => "{$name} Role User",
            "roleID" => $this->testRole["roleID"],
        ]);

        $this->createUser([
            "name" => "Fun {$name} User",
            "roleID" => $this->testRole["roleID"],
        ]);
    }

    /**
     * Test full-name filtering with GET /users/by-names.  Using Mode1 for @mentions, which is current default.
     *  The global, find any users matching the "name" parameter.
     *
     */
    public function testNamesMentionSearchGlobal()
    {
        $this->setupUserForTest("Test");
        $request = $this->runWithConfig(
            ["Garden.Format.Mentions" => \UsersApiController::AT_MENTION_GLOBAL],
            function () {
                return $this->api()->get("{$this->baseUrl}/by-names", [
                    "name" => "Test*",
                    "recordType" => "category",
                    "recordID" => $this->category["categoryID"],
                ]);
            }
        );

        $this->assertEquals(200, $request->getStatusCode());
        $searchFull = $request->getBody();
        $this->assertCount(2, $searchFull);
    }

    /**
     * that RecordID is required if recordType is provided
     *
     */
    public function testErrorMentionRecordIDRequired()
    {
        $this->runWithConfig(["Garden.Format.Mentions" => \UsersApiController::AT_MENTION_FILTER_LOOSE], function () {
            $this->expectExceptionMessage("All of recordType, recordID must be present if one of them is present.");
            return $this->api()->get("{$this->baseUrl}/by-names", [
                "name" => "FilterLoose*",
                "recordType" => "category",
            ]);
        });
        $this->runWithConfig(["Garden.Format.Mentions" => \UsersApiController::AT_MENTION_FILTER_LOOSE], function () {
            $this->expectExceptionMessage("All of recordType, recordID must be present if one of them is present.");
            return $this->api()->get("{$this->baseUrl}/by-names", [
                "name" => "FilterLoose*",
                "recordTypeID" => "1",
            ]);
        });
    }

    /**
     * Test full-name filtering with GET /users/by-names.  Using Mode2, new future client default.
     *  filter-loose will find any users matching the "name" parameter, but will also return a "canView" property.
     *  The users with canView == 0, should not be selectable dropdown, those users are the ones that cannot see the content where
     *  they are mentioned.
     *
     */
    public function testNamesMentionSearchFilterLoose()
    {
        $this->setupUserForTest("FilterLoose");
        $request = $this->runWithConfig(
            ["Garden.Format.Mentions" => \UsersApiController::AT_MENTION_FILTER_LOOSE],
            function () {
                return $this->api()->get("{$this->baseUrl}/by-names", [
                    "name" => "FilterLoose*",
                    "recordType" => "category",
                    "recordID" => $this->category["categoryID"],
                ]);
            }
        );

        $this->assertEquals(200, $request->getStatusCode());
        $searchFull = $request->getBody();
        $this->assertCount(2, $searchFull);
        $this->assertArrayHasKey("canView", $searchFull[0]);
        $this->assertTrue($searchFull[0]["canView"]);
        $this->assertFalse($searchFull[1]["canView"]);
    }

    /**
     * Test full-name filtering with GET /users/by-names.  Using Mode2, new future client default.
     *  filter-loose will find any users matching the "name" parameter, but will also return a "canView" property.
     *  The users with canView == 0, should not be selectable dropdown, those users are the ones that cannot see the content where
     *  they are mentioned.
     *
     */
    public function testNamesMentionSearchFilterLooseEscalation()
    {
        $request = $this->runWithConfig(
            ["Garden.Format.Mentions" => \UsersApiController::AT_MENTION_FILTER_LOOSE],
            function () {
                return $this->api()->get("{$this->baseUrl}/by-names", [
                    "name" => "System*",
                    "recordType" => "escalation",
                    "recordID" => 1,
                ]);
            }
        );

        $this->assertEquals(200, $request->getStatusCode());
        $searchFull = $request->getBody();
        $this->assertCount(1, $searchFull);
    }

    /**
     * Test full-name filtering with GET /users/by-names.  Using filter_strict, similar to filter-loose, but only returns users with the "canView" == 1 permission.
     *  Does not even show inaccessible users in the results.  Unless admin is using hte @mention, then acts as Mode2.
     *
     */
    public function testNamesMentionSearchFilterStrict()
    {
        $this->setupUserForTest("FilterStrict");
        // will only show users with the "canView" permission
        $request = $this->runWithConfig(
            ["Garden.Format.Mentions" => \UsersApiController::AT_MENTION_FILTER_STRICT],
            function () {
                return $this->runWithUser(function () {
                    return $this->api()->get("{$this->baseUrl}/by-names", [
                        "name" => "FilterStrict*",
                        "recordType" => "category",
                        "recordID" => $this->category["categoryID"],
                    ]);
                }, $this->memberID);
            }
        );

        $this->assertEquals(200, $request->getStatusCode());
        $searchFull = $request->getBody();
        $this->assertCount(1, $searchFull);
        $this->assertArrayHasKey("canView", $searchFull[0]);
        $this->assertTrue($searchFull[0]["canView"]);
        //Access as admin acts as mode2
        $request = $this->runWithConfig(
            ["Garden.Format.Mentions" => \UsersApiController::AT_MENTION_FILTER_STRICT],
            function () {
                return $this->runWithUser(function () {
                    return $this->api()->get("{$this->baseUrl}/by-names", [
                        "name" => "FilterStrict*",
                        "recordType" => "category",
                        "recordID" => $this->category["categoryID"],
                    ]);
                }, $this->adminID);
            }
        );

        $this->assertEquals(200, $request->getStatusCode());
        $searchFull = $request->getBody();
        $this->assertCount(2, $searchFull);
        $this->assertArrayHasKey("canView", $searchFull[0]);
        $this->assertTrue($searchFull[0]["canView"]);
        $this->assertFalse($searchFull[1]["canView"]);
    }

    /**
     * Test at-mention sorting functionality based on searchByNameWithDocument method.
     *
     * Tests the sorting of users in at-mention search results based on:
     * 1. Users who commented in the discussion (sortInThread) - highest priority
     * 2. Users active in the last month (sortMonthActive) - second priority
     * 3. User's post count (CountPosts) - third priority
     * 4. User's name (Name) - final sort
     */
    public function testAtMentionSortingWithDocument()
    {
        $currentTime = CurrentTimeStamp::mockTime(time());
        $this->createUserFixtures();
        $this->category = $this->createCategory();

        // Create a discussion for testing
        $discussion = $this->createDiscussion([
            "name" => "Test Discussion for Mentions",
            "body" => "This is a test discussion for mention sorting",
            "categoryID" => $this->category["categoryID"],
        ]);

        // Create users with different characteristics for testing sorting
        $users = [];
        CurrentTimeStamp::mockTime($currentTime->modify("-2 month"));
        // User 1: Not active in last month, no posts, no comments in discussion
        $users["alice"] = $this->createUser([
            "name" => "Sort Alice",
            "dateLastActive" => date("Y-m-d H:i:s", strtotime("-2 months")), // Not active in last month
        ]);
        $this->setPostCount($users["alice"]["userID"], 1, 4);
        CurrentTimeStamp::mockTime(time());
        // User 2: Active in last month, has posts, no comments in discussion
        $users["bob"] = $this->createUser([
            "name" => "Sort Bob",
        ]);
        $this->setPostCount($users["bob"]["userID"], 10, 10);
        CurrentTimeStamp::mockTime($currentTime->modify("-2 month"));
        // User 3: Not active in last month, has many posts, no comments in discussion
        $users["charlie"] = $this->createUser([
            "name" => "Sort Charlie",
        ]);
        $this->setPostCount($users["charlie"]["userID"], 40, 9);
        CurrentTimeStamp::mockTime(time());
        // User 4: Active in last month, has posts, commented in discussion
        $users["diana"] = $this->createUser([
            "name" => "Sort Diana",
        ]);
        $this->setPostCount($users["diana"]["userID"], 15, 15);
        // Create comments in the discussion for some users
        // Diana comments in the discussion (should be highest priority)
        $this->runWithUser(function () use ($discussion) {
            $this->createComment([
                "discussionID" => $discussion["discussionID"],
                "body" => "Diana's comment in the discussion",
            ]);
        }, $users["diana"]["userID"]);

        // Test the sorting behavior
        $request = $this->runWithConfig(
            [
                "Garden.Format.Mentions" => \UsersApiController::AT_MENTION_GLOBAL,
            ],
            function () use ($discussion) {
                return $this->api()->get("{$this->baseUrl}/by-names", [
                    "name" => "Sort*",
                    "recordType" => "discussion",
                    "recordID" => $discussion["discussionID"],
                ]);
            }
        );

        $this->assertEquals(200, $request->getStatusCode());
        $results = $request->getBody();
        $this->assertCount(4, $results);

        // Extract just the names for easier testing
        $actualOrder = array_column($results, "name");

        // Expected sorting order:
        // 1. Diana - commented in discussion + active in last month + has posts
        // 2. Bob - active in last month + has posts (but no comments in discussion)
        // 3. Charlie - has many posts (but not active in last month, no comments)
        // 4. Alice - has fewest posts, not active in last month, no comments
        $expectedOrder = [
            $users["diana"]["name"], // Priority 1: Has commented in discussion
            $users["bob"]["name"], // Priority 2: Active in last month, has posts
            $users["charlie"]["name"], // Priority 3: Has more posts than Alice
            $users["alice"]["name"], // Priority 4: Lowest on all criteria
        ];

        $this->assertEquals(
            $expectedOrder,
            $actualOrder,
            "At-mention sorting with document context did not match expected order. Expected: " .
                implode(", ", $expectedOrder) .
                " | Actual: " .
                implode(", ", $actualOrder)
        );
    }

    /**
     * Helper method to set. CountPosts for a user.
     *
     * @param int $userID
     * @param int $countDiscussion
     * @param int $countComments
     * @return void
     * @throws \Exception
     */
    public function setPostCount(int $userID, int $countDiscussion, int $countComments)
    {
        \Gdn::sql()
            ->update("User")
            ->set("CountDiscussions", $countDiscussion)
            ->set("CountComments", $countComments)
            ->where("UserID", $userID)
            ->put();
    }

    /**
     * Test at-mention sorting without discussionID (no document context).
     *
     * Should only sort by:
     * 1. Users active in the last month
     * 2. User's post count
     * 3. User's name
     */
    public function testAtMentionSortingWithoutDocument()
    {
        $currentTime = CurrentTimeStamp::mockTime(time());
        $this->createUserFixtures();
        $this->testRole = $this->createRole(["name" => "SortNoDocRole"]);
        $this->category = $this->createPermissionedCategory([], [$this->testRole["roleID"]]);

        // Create users with different characteristics
        $users = [];

        CurrentTimeStamp::mockTime($currentTime->modify("-2 month"));
        // User 1: Not active in last month, low posts
        $users["emma"] = $this->createUser([
            "name" => "NewTest Emma",
            "roleID" => $this->testRole["roleID"],
        ]);
        $this->setPostCount($users["emma"]["userID"], 1, 0);
        CurrentTimeStamp::mockTime(time());
        // User 2: Active in last month, medium posts
        $users["frank"] = $this->createUser([
            "name" => "NewTest Frank",
            "roleID" => $this->testRole["roleID"],
        ]);
        $this->setPostCount($users["frank"]["userID"], 8, 2);
        // User 3: Active in last month, high posts
        $users["grace"] = $this->createUser([
            "name" => "NewTest Grace",
            "roleID" => $this->testRole["roleID"],
        ]);
        $this->setPostCount($users["grace"]["userID"], 1, 29);

        // Test without discussionID
        $request = $this->runWithConfig(
            [
                "Garden.Format.Mentions" => \UsersApiController::AT_MENTION_GLOBAL,
            ],
            function () {
                return $this->api()->get("{$this->baseUrl}/by-names", [
                    "name" => "NewTest*",
                    "recordType" => "category",
                    "recordID" => $this->category["categoryID"],
                ]);
            }
        );

        $this->assertEquals(200, $request->getStatusCode());
        $results = $request->getBody();
        $this->assertCount(3, $results);

        $actualOrder = array_column($results, "name");

        // Expected order: Active users first (sorted by post count), then inactive users
        $expectedOrder = [
            $users["grace"]["name"], // Active + highest posts
            $users["frank"]["name"], // Active + medium posts
            $users["emma"]["name"], // Not active + lowest posts
        ];

        $this->assertEquals(
            $expectedOrder,
            $actualOrder,
            "At-mention sorting without document context did not match expected order. Expected: " .
                implode(", ", $expectedOrder) .
                " | Actual: " .
                implode(", ", $actualOrder)
        );
    }
}
