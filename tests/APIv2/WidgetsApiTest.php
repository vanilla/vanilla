<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv2;

/**
 * Test the /api/v2/widgets endpoints.
 */
class WidgetsApiTest extends AbstractAPIv2Test {

    /** @var array */
    protected static $addons = ["vanilla"];

    /**
     * @inheritDoc
     */
    public function setUp(): void {
        parent::setUp();
    }

    /**
     * Test INDEX /widgets endpoint.
     */
    public function testWidgetsIndex() {
        $widgets = $this->api()->get('widgets')->getBody();
        $this->assertEquals(6, count($widgets));
    }

    /**
     * Test GET /widgets endpoint
     *
     * @dataProvider widgetNameProvider
     *
     * @param string $id
     * @param string $name
     * @param string $class
     */
    public function testGetWidgets(string $id, string $name, string $class) {
        $widget = $this->api()->get('widgets/' . $id)->getBody();
        $this->assertEquals($id, $widget['widgetID']);
        $this->assertEquals($name, $widget['name']);
        $this->assertEquals($class, $widget['widgetClass']);
    }

    /**
     * Widget name DataProvider
     *
     * @return array
     */
    public function widgetNameProvider() {
        $slugBase = "mock-widget-";
        $classBase = "VanillaTests\Fixtures\MockWidgets\MockWidget";

        return [
            ["{$slugBase}1", "Mock Widget 1", "{$classBase}1"],
            ["{$slugBase}2", "Mock Widget 2", "{$classBase}2"],
            ["{$slugBase}3", "Mock Widget 3", "{$classBase}3"],
        ];
    }
}
