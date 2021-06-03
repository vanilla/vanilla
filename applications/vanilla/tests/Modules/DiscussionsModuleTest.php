<?php
/**
 * @author Raphaël Bergina <raphael.bergina@vanillaforums.com>
 * @copyright 2008-2021 Vanilla Forums, Inc.
 * @license Proprietary
 */

namespace VanillaTests\Forum\Modules;

use Vanilla\Community\CallToActionModule;
use Vanilla\Forum\Modules\DiscussionWidgetModule;
use VanillaTests\CategoryAndDiscussionApiTestTrait;
use VanillaTests\EventSpyTestTrait;
use VanillaTests\Storybook\StorybookGenerationTestCase;

/**
 * Test rendering of the "Discussions" module.
 */
class DiscussionsModuleTest extends StorybookGenerationTestCase {

    use EventSpyTestTrait;
    use CategoryAndDiscussionApiTestTrait;

    public static $addons = ['vanilla'];

    /**
     * Configure the container.
     */
    public function setUp(): void {
        parent::setUp();
        $this->createData();
    }

    /**
     * Test rendering of the Call To Action module.
     */
    public function testRender() {
        $this->generateStoryHtml('/', 'Discussions Widget Module');
    }

    /**
     * Event handler to mount Discussions module.
     *
     * @param \Gdn_Controller $sender
     */
    public function base_render_before(\Gdn_Controller $sender) {
        /** @var DiscussionWidgetModule $module */
        $module1 = self::container()->get(DiscussionWidgetModule::class);
        $module1->setTitle('Discussions Widget Module');
        $sender->addModule($module1);
    }

    /**
     * Create some discussions and categories for the module.
     */
    public function createData() {
        $this->resetTable('Category');
        $this->resetTable('Discussion');
        $this->createCategory();
        $this->createDiscussion(['name' => 'test 1']);
        $this->createDiscussion(['name' => 'test 2']);
        $this->createDiscussion(['name' => 'test 3']);
        $this->createDiscussion(['name' => 'test 4']);
    }
}
