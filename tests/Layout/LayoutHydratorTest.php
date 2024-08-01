<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Layout;

use Garden\Container\Container;
use Vanilla\Layout\LayoutHydrator;
use VanillaTests\BootstrapTestCase;
use VanillaTests\Layout\Asset\MockAsset;
use VanillaTests\SiteTestTrait;

/**
 * Tests for our layout exception handler.
 */
class LayoutHydratorTest extends BootstrapTestCase
{
    use LayoutTestTrait;
    use SiteTestTrait;

    /**
     * @inheritDoc
     */
    public static function configureContainerBeforeStartup(Container $container)
    {
        // Use the MockAsset for testing.
        $container->rule(LayoutHydrator::class)->addCall("addReactResolver", [MockAsset::class]);
    }

    /**
     * Test that various layout inputs hydrate into specific outputs.
     *
     * @param array $input The input.
     * @param array $expected The expected result.
     * @param array $jsonLDExpected The JsonLD expected result.
     * @param array $params The parameters for rendering.
     *
     * @dataProvider provideLayoutHydratesTo
     */
    public function testLayoutHydratesTo(array $input, array $expected, array $jsonLDExpected, array $params = [])
    {
        $actual = $this->assertHydratesTo($input, $params, $expected);
    }

    /**
     * Test that hydrateLayout layout inputs hydrate into specific outputs.
     *
     * @param array $input The input.
     * @param array $expected The expected result.
     * @param array $jsonLDExpected The JsonLD expected result.
     * @param array $params The parameters for rendering.
     *
     * @dataProvider provideLayoutHydratesTo
     */
    public function testHydrateLayout(array $input, array $expected, array $jsonLDExpected, array $params = [])
    {
        $actual = self::getLayoutHydrator()->hydrateLayout("home", $params, $input);
        $actual = $this->getLayoutService()->stripSeoHtmlFromHydratedLayout($actual);
        // Make sure we see it as the API output would.
        $this->assertArrayHasKey("seo", $actual);
        $seo = json_decode(json_encode($actual["seo"]), true);
        unset($actual["seo"]);
        $actual = json_decode(json_encode($actual), true);
        $this->assertEquals(6, count($seo));
        $this->assertSame($expected, $actual);
        $this->assertSame(json_encode($jsonLDExpected, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), $seo["json-ld"]);
    }

    /**
     * Test that getAssetLayout returns a list of assets.
     *
     */
    public function testGetAssetLayout()
    {
        $leaderboard = [
            "layout" => [
                [
                    "rightBottom" => [
                        [
                            '$hydrate' => "react.leaderboard",
                            "title" => "Community Leaders",
                            "apiParams" => [
                                "slotType" => "a",
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $expected = "LeaderboardWidget";
        $actual = self::getLayoutHydrator()->getAssetLayout("home", [], $leaderboard);
        // Make sure we see it as the API output would.
        $this->assertArrayHasKey("js", $actual);
        $this->assertArrayHasKey("css", $actual);
    }

    /**
     * @return iterable
     */
    public function provideLayoutHydratesTo(): iterable
    {
        $layoutDefinition = [
            '$hydrate' => "react.asset.mockComponent",
            "recordType" => [],
        ];
        yield "Exceptions propagate up to the nearest react node" => [
            [
                "layout" => [
                    [
                        '$hydrate' => "react.section.1-column",
                        "children" => [$layoutDefinition],
                    ],
                ],
            ],
            [
                "layout" => [
                    [
                        '$reactComponent' => "SectionOneColumn",
                        '$reactProps' => [
                            "children" => [
                                [
                                    '$reactComponent' => "LayoutError",
                                    '$reactProps' => [
                                        "layoutDefinition" => $layoutDefinition,
                                        "componentName" => "react.asset.mockComponent",
                                        "message" => "recordType is not a valid string.",
                                    ],
                                ],
                            ],
                            "isNarrow" => false,
                        ],
                    ],
                ],
            ],
            [
                "@context" => "https://schema.org",
                "@graph" => [
                    [
                        "@context" => "http://schema.org",
                        "@type" => "BreadcrumbList",
                        "itemListElement" => [
                            [
                                "@type" => "ListItem",
                                "position" => 0,
                                "name" => "Home",
                                "item" => "/",
                            ],
                        ],
                    ],
                ],
            ],
        ];

        yield "Resolvers not found turn into layout error react nodes" => [
            [
                '$hydrate' => "react.some-react",
                "thiswillfail" => true,
            ],
            [
                '$reactComponent' => "LayoutError",
                '$reactProps' => [
                    "layoutDefinition" => [
                        '$hydrate' => "react.some-react",
                        "thiswillfail" => true,
                    ],
                    "componentName" => "react.some-react",
                    "message" => "Resolver not registered: react.some-react",
                ],
            ],
            [
                "@context" => "https://schema.org",
                "@graph" => [
                    [
                        "@context" => "http://schema.org",
                        "@type" => "BreadcrumbList",
                        "itemListElement" => [
                            [
                                "@type" => "ListItem",
                                "position" => 0,
                                "name" => "Home",
                                "item" => "/",
                            ],
                        ],
                    ],
                ],
            ],
        ];

        yield "Component with null props is removed" => [
            [
                '$hydrate' => "react.section.1-column",
                "children" => [
                    [
                        '$hydrate' => "react.asset.mockComponent",
                        // When we don't have a recordID, the mockAsset doesn't render.
                        "recordID" => null,
                    ],
                ],
            ],
            [
                '$reactComponent' => "SectionOneColumn",
                '$reactProps' => [
                    "children" => [],
                    "isNarrow" => false,
                ],
            ],
            [
                "@context" => "https://schema.org",
                "@graph" => [
                    [
                        "@context" => "http://schema.org",
                        "@type" => "BreadcrumbList",
                        "itemListElement" => [
                            [
                                "@type" => "ListItem",
                                "position" => 0,
                                "name" => "Home",
                                "item" => "/",
                            ],
                        ],
                    ],
                ],
            ],
        ];

        yield "Success hydration" => [
            [
                "layout" => [
                    '$hydrate' => "react.section.1-column",
                    "children" => [
                        [
                            // Assets should be available.
                            '$hydrate' => "react.asset.mockComponent",
                            "recordType" => "category",
                            "recordID" => 1,
                        ],
                    ],
                ],
            ],
            [
                "layout" => [
                    '$reactComponent' => "SectionOneColumn",
                    '$reactProps' => [
                        "children" => [
                            [
                                '$reactComponent' => "MockComponentWidget",
                                '$reactProps' => [
                                    "mockProps" => [
                                        "recordType" => "category",
                                        "recordID" => 1,
                                    ],
                                ],
                            ],
                        ],
                        "isNarrow" => false,
                    ],
                ],
            ],
            [
                "@context" => "https://schema.org",
                "@graph" => [
                    [
                        "@context" => "http://schema.org",
                        "@type" => "BreadcrumbList",
                        "itemListElement" => [
                            [
                                "@type" => "ListItem",
                                "position" => 0,
                                "name" => "Home",
                                "item" => "/",
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
