<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use Garden\EventManager;

/**
 * Tests for the /api/v2/dashboard endpoints.
 */
class DashboardTest extends AbstractAPIv2Test {

    private $calledInit = false;

    private $calledLegacyInit = false;

    /**
     * A basic smoke test of the dashboard menus.
     */
    public function testIndexMenusSmoke() {
        $r = $this->api()->get('/dashboard/menus');
        $data = $r->getBody();
        $this->assertSame(3, count($data));
    }

    /**
     * Handler for the modern init.
     */
    public function dashboardNavModule_init_handler() {
        $this->calledInit = true;
    }

    /**
     * Handler for the legacy init.
     *
     * @param mixed $sender
     */
    public function base_getAppSettingsMenuItems_handler($sender) {
        $this->calledLegacyInit = true;
        $adapter = $sender->EventArguments['SideMenu'];
        $this->assertInstanceOf(\NestedCollectionAdapter::class, $adapter);
    }

    /**
     * A basic smoke test of the dashboard menu html handler.
     */
    public function testHtmlMenuSmoke() {
        /** @var EventManager $eventManager */
        $eventManager = self::container()->get(EventManager::class);
        $eventManager->bindClass($this);

        $noActive = $this->api()->get('/dashboard/menu-legacy', [
            'activeUrl' => 'https://test.com',
            'locale' => 'en',
        ])->getBody();

        $this->assertNotNull($noActive['html']);
        $this->assertTrue($this->calledInit);
        $this->assertTrue($this->calledLegacyInit);

        $actualActive = $this->api()->get('/dashboard/menu-legacy', [
            'activeUrl' => \Gdn::request()->getSimpleUrl('/dashboard/settings/branding'),
            'locale' => 'en',
        ])->getBody();
        $this->assertEquals($actualActive, $noActive);
    }
}
