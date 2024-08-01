<?php
/**
 * @author Gary Pomerant <gpomerant@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Forums\Layout\Middleware;

use Vanilla\Forum\Layout\Middleware\CategoryFilterMiddleware;
use Vanilla\Layout\LayoutHydrator;
use VanillaTests\BootstrapTestCase;
use VanillaTests\Layout\LayoutTestTrait;
use VanillaTests\SiteTestTrait;

/**
 * Tests for our middleware filters.
 */
class CategoryFilterMiddlewareTest extends BootstrapTestCase
{
    use LayoutTestTrait;
    use SiteTestTrait;
    /**
     * Test that middleware inputs filter specific outputs.
     *
     * @param array $input The input.
     * @param array $expected The expected result.
     * @param array $params The parameters for rendering.
     *
     * @dataProvider provideMiddlewareFiltersTo
     */
    public function testLayoutHydratesTo(array $input, array $expected, array $params = [])
    {
        $hydrator = self::getLayoutService()->getHydrator("home");
        $hydrator->addMiddleware(new CategoryFilterMiddleware(new \CategoryModel()));
        $actual = $hydrator->resolve($input, $params);
        // Make sure we see it as the API output would.
        $actual = json_decode(json_encode($actual), true);
        $this->assertSame($expected, $actual);
    }

    /**
     * @return iterable
     */
    public function provideMiddlewareFiltersTo(): iterable
    {
        $categoryMiddlewareDefinitionFail = [
            '$middleware' => [
                "category-filter" => [
                    "categoryID" => -1,
                ],
            ],
        ];

        yield "Fail to resolve node when category filter fails " => [
            [
                "layoutViewType" => "home",
                "layout" => [
                    [
                        "data" => [
                            '$hydrate' => "sprintf",
                            "format" => "is %s args",
                            "args" => ["resolved"],
                        ],
                        $categoryMiddlewareDefinitionFail,
                    ],
                ],
            ],
            [
                "layoutViewType" => "home",
                "layout" => [
                    [
                        "data" => "is resolved args",
                        0 => null, // this indicates we have a null node returned
                    ],
                ],
            ],
            [
                "categoryID" => 2,
            ],
        ];

        $categoryMiddlewareDefinitionPass = [
            '$middleware' => [
                "category-filter" => [
                    "categoryID" => 2,
                ],
            ],
        ];

        yield "Success resolving node when category filter passes " => [
            [
                "layoutViewType" => "home",
                "layout" => [
                    [
                        "data" => [
                            '$hydrate' => "sprintf",
                            "format" => "is %s args",
                            "args" => ["resolved"],
                        ],
                        $categoryMiddlewareDefinitionPass,
                    ],
                ],
            ],
            [
                "layoutViewType" => "home",
                "layout" => [
                    [
                        "data" => "is resolved args",
                        0 => [], //not null is successful node response
                    ],
                ],
            ],
            [
                "categoryID" => 2,
            ],
        ];
    }

    /**
     * @return LayoutHydrator
     */
    private function getLayoutService(): LayoutHydrator
    {
        return self::container()->get(LayoutHydrator::class);
    }
}
