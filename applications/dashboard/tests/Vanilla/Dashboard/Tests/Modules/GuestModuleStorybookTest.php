<?php
/**
 * @author Raphaël Bergina <raphael.bergina@vanillaforums.com>
 * @copyright 2008-2021 Vanilla Forums, Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\Tests\Modules;

use VanillaTests\EventSpyTestTrait;
use VanillaTests\Storybook\StorybookGenerationTestCase;

/**
 * Test rendering of the Guest Module module.
 */
class GuestModuleStorybookTest extends StorybookGenerationTestCase
{
    use EventSpyTestTrait;

    public static $addons = ["vanilla"];

    /**
     * End the session.
     */
    public function setUp(): void
    {
        parent::setUp();
        \Gdn::session()->end();
    }

    /**
     * Test rendering of the Guest module.
     */
    public function testRender()
    {
        $this->generateStoryHtml("/", "Guest Module");
    }
}
