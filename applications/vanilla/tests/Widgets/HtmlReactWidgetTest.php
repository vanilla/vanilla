<?php
/**
 * @copyright 2008-2022 Vanilla Forums, Inc.
 * @license Proprietary
 */

namespace VanillaTests\Forum\Widgets;

use VanillaTests\SiteTestCase;
use VanillaTests\Layout\LayoutTestTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Test CategoriesWidget.
 */
class HtmlReactWidgetTest extends SiteTestCase
{
    use LayoutTestTrait, CommunityApiTestTrait, UsersAndRolesApiTestTrait;

    /**
     * Test that we can hydrate Categories Widget.
     *
     * @param array $input The input.
     * @param array|null $expected The expected result.
     * @param array $permissions The permissions to add to the user.
     *
     * @dataProvider provideHtmlDatasTo
     */
    public function testHydrateCategoriesWidget(array $input, array $expected = null, array $permissions = [])
    {
        $this->runWithPermissions(function () use ($input, $expected) {
            $this->assertHydratesTo($input, [], $expected);
        }, $permissions);
    }

    /**
     * @return iterable
     */
    public function provideHtmlDatasTo(): iterable
    {
        yield "Html with isAdvertisement is false without noAdd.use permission" => [
            [
                '$hydrate' => "react.html",
                "html" => "<h1 style='margin-top: 0'>Hello Layout Editor</h1>",
                "isAdvertisement" => false,
            ],
            [
                '$reactComponent' => "HtmlWidget",
                '$reactProps' => [
                    "html" => "<h1 style='margin-top: 0'>Hello Layout Editor</h1>",
                    "isAdvertisement" => false,
                ],
            ],
            ["noAds.use" => false],
        ];

        yield "Html with isAdvertisement is false with noAdd.use permission" => [
            [
                '$hydrate' => "react.html",
                "html" => "<h1 style='margin-top: 0'>Hello Layout Editor</h1>",
                "isAdvertisement" => false,
            ],
            [
                '$reactComponent' => "HtmlWidget",
                '$reactProps' => [
                    "html" => "<h1 style='margin-top: 0'>Hello Layout Editor</h1>",
                    "isAdvertisement" => false,
                ],
            ],
            ["noAds.use" => true],
        ];
        yield "Html with isAdvertisement is true without noAdd.use permission" => [
            [
                '$hydrate' => "react.html",
                "html" => "<h1 style='margin-top: 0'>Hello Layout Editor</h1>",
                "isAdvertisement" => true,
            ],
            [
                '$reactComponent' => "HtmlWidget",
                '$reactProps' => [
                    "html" => "<h1 style='margin-top: 0'>Hello Layout Editor</h1>",
                    "isAdvertisement" => true,
                ],
            ],
            ["noAds.use" => false],
        ];

        yield "Html with isAdvertisement is true with noAdd.use permission" => [
            [
                '$hydrate' => "react.html",
                "html" => "<h1 style='margin-top: 0'>Hello Layout Editor</h1>",
                "isAdvertisement" => true,
            ],
            null,
            ["noAds.use" => true],
        ];

        yield "Adding javascript adds a nonce" => [
            [
                '$hydrate' => "react.html",
                "html" => "<h1 style='margin-top: 0'>Hello Layout Editor</h1>",
                "javascript" => "console.log('hello world')",
            ],
            [
                '$reactComponent' => "HtmlWidget",
                '$reactProps' => [
                    "html" => "<h1 style='margin-top: 0'>Hello Layout Editor</h1>",
                    "javascript" => "console.log('hello world')",
                    "javascriptNonce" => "TEST_NONCE",
                    "isAdvertisement" => false,
                ],
            ],
            ["noAds.use" => true],
        ];
    }
}
