<?php
/**
 * @author Raphaël Bergina <raphael.bergina@vanillaforums.com>
 * @copyright 2008-2021 Vanilla Forums, Inc.
 * @license Proprietary
 */

namespace VanillaTests\Forum\Modules;

use Vanilla\Community\CallToActionModule;
use VanillaTests\EventSpyTestTrait;
use VanillaTests\Storybook\StorybookGenerationTestCase;

/**
 * Test rendering of the "Call To Action" module.
 */
class CallToActionStorybookTest extends StorybookGenerationTestCase
{
    use EventSpyTestTrait;

    public static $addons = ["vanilla"];

    /**
     * Test rendering of the Call To Action module.
     */
    public function testRender()
    {
        $this->generateStoryHtml("/", "CTA Module");
    }

    /**
     * Event handler to mount Call To Action module.
     *
     * @param \Gdn_Controller $sender
     */
    public function base_render_before(\Gdn_Controller $sender)
    {
        /** @var CallToActionModule $module */
        $module = self::container()->get(CallToActionModule::class);
        $module->setTitle("Call To Action - No Image + Multiple CTAs");
        $module->setTextCTA("Click");
        $module->setUrl("https://www.vanillaforums.com");
        $otherCTA = ["to" => "https://www.vanillaforums.com/en/why-vanilla/community", "textCTA" => "Join"];
        $module->setOtherCTAs([$otherCTA]);
        $sender->addModule($module);
    }
}
