<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use Gdn;
use ReactionsController;
use ReactionModel;
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

    public static $addons = ["vanilla", "reactions"];

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
}
