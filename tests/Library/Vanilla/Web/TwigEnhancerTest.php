<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Web;

use Garden\EventManager;
use Vanilla\Web\TwigEnhancer;
use VanillaTests\SiteTestCase;

/**
 * Tests for our twig enhancement utilities.
 */
class TwigEnhancerTest extends SiteTestCase {
    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * @var TwigEnhancer
     */
    private $enhancer;

    /**
     * @var \Gdn_Session
     */
    private $session;

    /**
     * {@inheritDoc}
     */
    public function setUp(): void {
        parent::setUp();

        $this->container()->call(function (
            EventManager $eventManager,
            TwigEnhancer $enhancer,
            \Gdn_Session $session
        ) {
            $this->eventManager = $eventManager;
            $this->enhancer = $enhancer;
            $this->session = $session;
        });

        $this->createUserFixtures();
    }

    /**
     * Test rendering controller assets.
     */
    public function testRenderControllerAsset(): void {
        $this->eventManager->bind('base_beforeRenderAsset', function () {
            echo "Before";
        });

        $this->eventManager->bind('base_afterRenderAsset', function () {
            echo "After";
        });

        $controller = new \Gdn_Controller();
        $controller->addAsset('Content', ' Content ', 'Item1');
        \Gdn::controller($controller);

        $result = $this->enhancer->renderControllerAsset('Content')->jsonSerialize();

        $this->assertEquals('Before Content After', $result);
    }

    /**
     * A basic integration test of the `hasPermission()` method.
     */
    public function testHasPermission(): void {
        $this->session->start($this->memberID);
        $this->assertTrue($this->enhancer->hasPermission('Garden.SignIn.Allow'));
        $this->assertTrue($this->enhancer->hasPermission('Vanilla.Discussions.View', 1));
        $this->assertFalse($this->enhancer->hasPermission('Vanilla.Discussions.Announce', 1));
    }
}
