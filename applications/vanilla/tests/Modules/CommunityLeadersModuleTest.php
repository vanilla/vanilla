<?php
/**
 * @copyright 2008-2021 Vanilla Forums, Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\Modules;

use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Test CommunityLeadersModule title.
 */
class CommunityLeadersModuleTest extends SiteTestCase {

    use UsersAndRolesApiTestTrait;

    /**
     * Test that leaderboard widget takes custom title if its set and default one if its not.
     */
    public function testCommunityLeadersTitle() {
        $user = $this->createUser(['name' => 'testUser']);
        $this->givePoints($user, 40);

        /** @var CommunityLeadersModule $widgetModule */
        $widgetModule = self::container()->get(CommunityLeadersModule::class);
        $props = $widgetModule->getProps();

        //default title
        $this->assertEquals("This Week's Leaders", $props['title']);

        $widgetModule->title = "Custom title";
        $newProps = $widgetModule->getProps();

        //should be our custom title now
        $this->assertEquals("Custom title", $newProps['title']);
    }
}
