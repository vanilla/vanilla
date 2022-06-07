<?php
/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use ReactionModel;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Test integrations between Reactions and the users API endpoint.
 */
class ReactionsUsersTest extends AbstractAPIv2Test
{
    use CommunityApiTestTrait, UsersAndRolesApiTestTrait;

    /** @var ReactionModel */
    private $reactionModel;

    protected static $addons = ["reactions"];

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->createUserFixtures();
        $this->container()->call(function (ReactionModel $reactionModel) {
            $this->reactionModel = $reactionModel;
        });
        ReactionModel::$ReactionTypes = null;
    }

    /**
     * Verify expanding reactions on the users API index.
     */
    public function testExpandReceivedUsersIndex(): void
    {
        $response = $this->api()
            ->get("users", ["expand" => "reactionsReceived"])
            ->getBody();
        $user = reset($response);
        $actual = $user["reactionsReceived"];
        $expected = $this->reactionModel->compoundTypeFragmentSchema()->validate($actual);
        $this->assertSame($expected, $actual);
    }

    /**
     * Verify not expanding reactions on the users API index does not include the field.
     */
    public function testNoExpandReceivedUsersIndex(): void
    {
        $response = $this->api()
            ->get("users")
            ->getBody();
        $user = reset($response);
        $this->assertArrayNotHasKey("reactionsReceived", $user);
    }

    /**
     * Verify expanding reactions when getting a single user from the API.
     */
    public function testExpandReceivedUsersGet(): void
    {
        $discussion = null;

        $this->createUser();
        $this->runWithUser(function () use (&$discussion) {
            $discussion = $this->createDiscussion(["categoryID" => 1]);
        }, $this->lastUserID);
        $this->runWithUser(function () use ($discussion) {
            $this->api->post("discussions/{$discussion["discussionID"]}/reactions", ["reactionType" => "Like"]);
        }, $this->adminID);

        $response = $this->api()
            ->get("users/{$this->lastUserID}", ["expand" => "reactionsReceived"])
            ->getBody();

        // Verify the shape.
        $actual = $response["reactionsReceived"];
        $expected = $this->reactionModel->compoundTypeFragmentSchema()->validate($actual);
        $this->assertSame($expected, $actual);

        // Verify we received our one reaction and not another.
        $this->assertSame(1, $actual["Like"]["count"]);
        $this->assertSame(0, $actual["LOL"]["count"]);
    }

    /**
     * Verify not expanding reactions when getting a single user from the API does not include the field.
     */
    public function testNoExpandReceivedUsersGet(): void
    {
        $user = $this->insertDummyUser();
        $actual = $this->api()
            ->get("users/{$user["UserID"]}")
            ->getBody();
        $this->assertArrayNotHasKey("reactionsReceived", $actual);
    }

    /**
     * Test that the profile reactions page is not in edit mode.
     */
    public function testReactionProfilePage(): void
    {
        $discussion = null;

        $user = $this->createUser();
        $this->runWithUser(function () use (&$discussion) {
            $discussion = $this->createDiscussion(["categoryID" => 1]);
        }, $this->lastUserID);
        $this->runWithUser(function () use ($discussion) {
            $this->api->post("discussions/{$discussion["discussionID"]}/reactions", ["reactionType" => "Like"]);
        }, $this->adminID);

        $profileController = $this->bessy()->get("/profile/reactions/{$user["name"]}?reaction=like");
        // Make sure the profile options module doesn't come through.
        $this->assertIsNotObject($profileController->getAsset("Content"));
        // Make sure we aren't, and never have been, in edit mode.
        $this->assertFalse($profileController->EditMode);
        // The Css class will be "EditMode Profile" if edit mode has been set to true.
        $this->assertSame($profileController->CssClass, " Profile");
    }
}
