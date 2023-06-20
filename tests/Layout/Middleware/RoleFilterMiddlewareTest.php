<?php
/**
 * @author Gary Pomerant <gpomerant@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Layout\Middleware;

use Vanilla\Layout\LayoutHydrator;
use Vanilla\Utility\ArrayUtils;
use VanillaTests\Layout\LayoutTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Tests for our middleware filters.
 */
class RoleFilterMiddlewareTest extends SiteTestCase
{
    use UsersAndRolesApiTestTrait;
    use LayoutTestTrait;

    /**
     * Test that middleware inputs filter specific outputs.
     *
     * @param array $input The input.
     * @param array $expected The expected result.
     * @param array $rolesOrPermissions The parameters for rendering.
     *
     * @dataProvider provideMiddlewareFiltersTo
     */
    public function testLayoutHydratesTo(array $input, array $expected, array $rolesOrPermissions = [])
    {
        if (!ArrayUtils::isAssociative($rolesOrPermissions)) {
            $user = $this->createUser([
                "roleID" => $rolesOrPermissions,
            ]);
            $this->runWithUser(function () use ($input, $expected) {
                $this->assertHydratesTo($input, [], $expected, "home");
            }, $user);
        } else {
            // otherwise they are permissions.
            $this->runWithPermissions(function () use ($input, $expected) {
                $this->assertHydratesTo($input, [], $expected, "home");
            }, $rolesOrPermissions);
        }
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
            [8],
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
            [8],
        ];

        yield "Ads appear when a user does not have noAds.use permission" => [
            [
                [
                    '$hydrate' => "react.html",
                    "isAdvertisement" => true,
                    "html" => "<h1 style='margin-top: 0'>Hello Layout Editor</h1>",
                ],
            ],
            [
                [
                    '$reactComponent' => "HtmlWidget",
                    '$reactProps' => [
                        "isAdvertisement" => true,
                        "html" => "<h1 style='margin-top: 0'>Hello Layout Editor</h1>",
                    ],
                ],
            ],
            ["noAds.use" => false],
        ];

        yield "Ads do not appear when a user doeshas the noAds.use permission" => [
            [
                [
                    '$hydrate' => "react.html",
                    "isAdvertisement" => true,
                    "html" => "<h1 style='margin-top: 0'>Hello Layout Editor</h1>",
                ],
            ],
            [null],
            ["noAds.use" => true],
        ];
    }
}
