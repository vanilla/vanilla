<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\Modules;

use ReactionsModule;
use VanillaTests\EventSpyTestTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\Storybook\StorybookGenerationTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Class ReactionModuleTest
 *
 * @package VanillaTests\Modules
 */
class ReactionModuleStoryBookTest extends StorybookGenerationTestCase
{
    use EventSpyTestTrait;
    use CommunityApiTestTrait;
    use UsersAndRolesApiTestTrait;

    public static $addons = ["reactions"];

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->createUserFixtures();
        $this->container()->call(function (\ReactionModel $reactionModel) {
            $this->reactionModel = $reactionModel;
        });
        \ReactionModel::$ReactionTypes = null;
    }

    /**
     * Test rendering of the Reactions Module.
     */
    public function testRender()
    {
        $discussion = $this->createDiscussion(["categoryID" => 1]);
        $this->api->post("discussions/{$discussion["discussionID"]}/reactions", ["reactionType" => "Agree"]);
        $discussion2 = $this->createDiscussion(["categoryID" => 1]);
        $this->api->post("discussions/{$discussion2["discussionID"]}/reactions", ["reactionType" => "Like"]);
        $this->generateStoryHtml("/", "Reactions Module");
    }

    /**
     * Event handler to mount Reaction Module module.
     *
     * @param \Gdn_Controller $sender
     */
    public function base_render_before(\Gdn_Controller $sender)
    {
        /** @var ReactionsModule $module */
        $module = self::container()->get(\ReactionsModule::class);
        $sender->addModule($module);
    }
}
