<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Web;

use Garden\EventManager;
use PHPUnit\Framework\TestCase;
use Vanilla\Web\TwigEnhancer;
use VanillaTests\SharedBootstrapTestCase;

/**
 * Tests for our twig enhancement utilties.
 */
class TwigEnhancerTest extends SharedBootstrapTestCase {

    /**
     * Test rendering controller assets.
     */
    public function testRenderControllerAsset() {
        /** @var EventManager $eventManager */
        $eventManager = self::container()->get(EventManager::class);

        /** @var TwigEnhancer $enhancer */
        $enhancer = self::container()->get(TwigEnhancer::class);

        $eventManager->bind('base_beforeRenderAsset', function () {
            echo "Before";
        });

        $eventManager->bind('base_afterRenderAsset', function () {
            echo "After";
        });

        $controller = new \Gdn_Controller();
        $controller->addAsset('Content', ' Content ', 'Item1');
        \Gdn::controller($controller);

        $result = $enhancer->renderControllerAsset('Content')->jsonSerialize();

        $this->assertEquals('Before Content After', $result);
    }
}
