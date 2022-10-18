<?php
/**
 * @author Gary Pomerant <gpomerant@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Layout\Middleware;

use Vanilla\Layout\LayoutHydrator;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Tests for our middleware filters.
 */
class RoleFilterMiddlewareTest extends SiteTestCase
{
    use UsersAndRolesApiTestTrait;

    /** @var $hydrator */
    private $hydrator;

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
        $user = $this->createUser();
        $this->runWithUser(function () use ($input, $params, $expected) {
            $this->hydrator = self::getLayoutService()->getHydrator("home");
            $actual = $this->hydrator->resolve($input, $params);
            // Make sure we see it as the API output would.
            $actual = json_decode(json_encode($actual), true);
            $this->assertSame($expected, $actual);
        }, $this->lastUserID);
    }

    /**
     * @return iterable
     */
    public function provideMiddlewareFiltersTo(): iterable
    {
        $roleMiddlewareDefinitionFail = [
            '$middleware' => [
                "role-filter" => [
                    "roleIDs" => [1],
                ],
            ],
        ];

        yield "Fail to resolve node when role filter fails " => [
            [
                "layoutViewType" => "home",
                "layout" => [
                    [
                        "data" => [
                            '$hydrate' => "sprintf",
                            "format" => "not %s args",
                            "args" => ["resolved"],
                        ],
                        $roleMiddlewareDefinitionFail,
                    ],
                ],
            ],
            [
                "layoutViewType" => "home",
                "layout" => [
                    [
                        "data" => "not resolved args",
                        0 => null, // this indicates we have a null node returned
                    ],
                ],
            ],
            [[1]],
        ];

        $roleMiddlewareDefinitionPass = [
            '$middleware' => [
                "role-filter" => [
                    "roleIDs" => [8],
                ],
            ],
        ];

        yield "Success resolving node when role filter passes " => [
            [
                "layoutViewType" => "home",
                "layout" => [
                    [
                        "data" => [
                            '$hydrate' => "sprintf",
                            "format" => "is %s args",
                            "args" => ["resolved"],
                        ],
                        $roleMiddlewareDefinitionPass,
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
            [[16]],
        ];

        $permissions = ["noAds.use" => true];

        yield "Success resolving node when user has doesn't have noAds.use permission " => [
            [
                "layoutViewType" => "home",
                "layout" => [
                    [
                        "data" => [
                            "mainTop" => [
                                [
                                    "isAdvertisement" => true,
                                    '$hydrate' => "react.html",
                                    "html" => "<h1 style='margin-top: 0'>Hello Layout Editor</h1>",
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                "layoutViewType" => "home",
                "layout" => [
                    [
                        "data" => [
                            "mainTop" => [
                                [
                                    '$reactComponent' => "HtmlWidget",
                                    '$reactProps' => [
                                        "isAdvertisement" => true,
                                        "html" => "<h1 style='margin-top: 0'>Hello Layout Editor</h1>",
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [[16]],
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
