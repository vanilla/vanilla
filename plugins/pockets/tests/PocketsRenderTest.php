<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Addons\Pockets;

use Vanilla\Addons\Pockets\PocketsModel;
use VanillaTests\APIv0\TestDispatcher;
use VanillaTests\APIv2\AbstractAPIv2Test;
use VanillaTests\Fixtures\MockWidgets\MockWidget1;

/**
 * Tests for pocket rendering.
 */
class PocketsRenderTest extends AbstractAPIv2Test {

    public static $addons = ['vanilla', 'pockets'];

    /** @var PocketsModel */
    private $pocketsModel;

    /**
     * Setup.
     */
    public function setUp(): void {
        parent::setUp();
        $this->pocketsModel = $this->container()->get(PocketsModel::class);
        $this->resetTable('Pocket');
        \PocketsPlugin::instance()->resetState();
    }

    /**
     * Test rendering of the pockets.
     */
    public function testRenderPockets() {
        $this->pocketsModel->touchPocket('HTML Pocket', [
            'Body' => '<div id="htmlpocket">hello custom</div>',
            'Disabled' => \Pocket::ENABLED,
        ]);
        $this->pocketsModel->touchPocket('Widget Pocket', [
            'WidgetParameters' => ['name' => 'My Widget 1'],
            'Format' => PocketsModel::FORMAT_WIDGET,
            'WidgetID' => MockWidget1::getWidgetID(),
            'Disabled' => \Pocket::ENABLED,
        ]);

        $html = $this->bessy()->getHtml('/discussions', [], [TestDispatcher::OPT_DELIVERY_TYPE => DELIVERY_TYPE_ALL]);
        $html->assertCssSelectorText("#htmlpocket", "hello custom");
        $html->assertCssSelectorText(".mockWidget", "My Widget 1");
    }
}
