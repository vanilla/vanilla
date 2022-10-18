<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Widgets;

use Vanilla\Widgets\WidgetFactory;
use VanillaTests\BootstrapTestCase;
use VanillaTests\Fixtures\MockWidgets\MockWidget1;

/**
 * Tests for the widget factory.
 */
class WidgetFactoryTest extends BootstrapTestCase
{
    /**
     * Test fetching of widget parameters.
     */
    public function testWidgetParams()
    {
        $factory = new WidgetFactory(MockWidget1::class);
        $expected = [
            [
                "name" => "Title",
                "value" => "Hello Title",
            ],
            [
                "name" => "Nested Params",
                "value" => [
                    [
                        "name" => "Timeframe",
                        "value" => "Monthly",
                    ],
                ],
            ],
        ];
        $actual = $factory->getWidgetSummaryParameters([
            "name" => "Hello Title",
            "nested" => [
                "slotType" => "m",
            ],
        ]);

        $this->assertSame($expected, $actual);
    }
}
