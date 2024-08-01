<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Dashboard\Controllers;

use Gdn;
use ReactionsController;
use ReactionModel;
use RoleModel;
use VanillaTests\Fixtures\TestUploader;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Class ReactionsEditTest
 * @package VanillaTests\APIv2
 */
class ReactionsEditTest extends SiteTestCase
{
    use UsersAndRolesApiTestTrait;

    /** @var ReactionsController  */
    protected $reactionsController;

    public static $addons = ["vanilla"];

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->reactionsController = Gdn::getContainer()->get(ReactionsController::class);
        $this->enableCaching();
        ReactionModel::$ReactionTypes = null;
    }

    /**
     * Test adding and removing a custom svg image.
     *
     * @throws \Garden\Web\Exception\ResponseException Throws Response Exception.
     */
    public function testAddCustomImage()
    {
        $session = $this->getSession();
        $session->start(self::$siteInfo["adminUserID"]);
        $reactionData = $this->bessy()->get("reactions/edit/Spam")->Data["Reaction"];

        // Upload a file and add it to the reaction.
        TestUploader::resetUploads();
        $upload = TestUploader::uploadFile("Photo", PATH_ROOT . "/tests/fixtures/insightful.svg");
        $reactionData["Photo"] = $upload;

        $this->bessy()->post("reactions/edit/Spam", $reactionData)->Data;

        // Bust the cache.
        ReactionModel::$ReactionTypes = null;

        // We should get a Photo field back with the path to the svg file
        $updatedReaction = $this->bessy()->get("/reactions/edit/Spam")->Data;
        $this->assertArrayHasKey("Photo", $updatedReaction["Reaction"]);
        $this->assertStringContainsString("svg", $updatedReaction["Reaction"]["Photo"]);

        // Now let's remove the photo.
        // Right now this only verifies the method was run, since redirectTo() throws an error in test mode.
        $this->expectExceptionCode(302);
        $this->reactionsController->removePhoto("Spam");
    }

    /**
     * Testing Saving settings for reactions.  Making sure Permission "Garden.Reactions.View" is added to/removed from roles as expected
     * on ShowUserReactions change.
     *
     * @return void
     */
    public function testUpdateReactSettings()
    {
        $roleModel = new RoleModel();
        $moderationManageRoles = $roleModel->getByPermission("Garden.Moderation.Manage");
        $moderationManageRoleIDs = array_column($moderationManageRoles->result(DATASET_TYPE_ARRAY), "RoleID");

        $reactionViewRoles = $roleModel->getByPermission("Garden.Reactions.View");
        $reactionViewManageRoleIDs = array_column($reactionViewRoles->result(DATASET_TYPE_ARRAY), "RoleID");
        $allRoles = RoleModel::roles();
        $allRolesIDs = array_column($allRoles, "RoleID");
        $allRections = array_values(array_diff($allRolesIDs, [RoleModel::GUEST_ID]));
        $this->assertSame($reactionViewManageRoleIDs, $allRections);

        $session = $this->getSession();
        $session->start(self::$siteInfo["adminUserID"]);
        $data = [
            "Vanilla.Reactions.ShowUserReactions" => "off",
        ];
        $this->bessy()->post("reactions/settings", $data);

        $reactionViewRoles = $roleModel->getByPermission("Garden.Reactions.View");
        $newReactionViewManageRoleIDs = array_column($reactionViewRoles->result(DATASET_TYPE_ARRAY), "RoleID");
        $this->assertSame($moderationManageRoleIDs, $newReactionViewManageRoleIDs);

        $data = [
            "Vanilla.Reactions.ShowUserReactions" => "avatar",
        ];
        $this->bessy()->post("reactions/settings", $data);

        $reactionViewRoles = $roleModel->getByPermission("Garden.Reactions.View");
        $newReactionViewManageRoleIDs = array_column($reactionViewRoles->result(DATASET_TYPE_ARRAY), "RoleID");
        $this->assertSame($newReactionViewManageRoleIDs, $allRections);
    }
}
