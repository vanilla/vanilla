<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Layout\Middleware;

use Garden\Hydrate\DataHydrator;
use Vanilla\Layout\LayoutHydrator;
use VanillaTests\BootstrapTestCase;
use VanillaTests\Layout\LayoutTestTrait;
use VanillaTests\SiteTestTrait;

/**
 * Test for the visibility middleware.
 */
class VisibilityMiddlewareTest extends BootstrapTestCase
{
    use LayoutTestTrait;
    use SiteTestTrait;

    /**
     * Test that our middleware passes through its values on react nodes.
     */
    public function testPassesThroughReactNode()
    {
        $hydrator = self::container()->get(LayoutHydrator::class);

        $middlewareDef = [
            "visibility" => [
                "device" => "mobile",
            ],
        ];

        $input = [
            $this->layoutSection([
                $this->layoutHtml("Should appear"),
                $this->layoutHtml(
                    "Should have middleware passed to frontend.",
                    $middlewareDef + ["hideMe" => "Should get removed."]
                ),
            ]),
            "notReact" => [
                DataHydrator::KEY_MIDDLEWARE => $middlewareDef,
                DataHydrator::KEY_HYDRATE => "sprintf",
                "format" => "No middleware %s",
                "args" => ["here"],
            ],
        ];

        $expected = [
            [
                '$reactComponent' => "SectionOneColumn",
                '$reactProps' => [
                    "children" => [
                        [
                            '$reactComponent' => "HtmlWidget",
                            '$reactProps' => [
                                "isAdvertisement" => false,
                                "html" => "Should appear",
                            ],
                        ],
                        [
                            '$middleware' => $middlewareDef,
                            '$reactComponent' => "HtmlWidget",
                            '$reactProps' => [
                                "isAdvertisement" => false,
                                "html" => "Should have middleware passed to frontend.",
                            ],
                        ],
                    ],
                    "isNarrow" => false,
                ],
            ],
            "notReact" => "No middleware here",
        ];

        $output = $hydrator->getHydrator(null)->resolve($input);
        $this->assertSame($expected, $output);
    }
}
