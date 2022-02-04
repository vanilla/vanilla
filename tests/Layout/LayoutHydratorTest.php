<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Layout;

use VanillaTests\BootstrapTestCase;
use VanillaTests\SiteTestTrait;

/**
 * Tests for our layout exception handler.
 */
class LayoutHydratorTest extends BootstrapTestCase {

    use LayoutTestTrait;
    use SiteTestTrait;

    /**
     * Test that varius layout inputs hydrate into specific outputs.
     *
     * @param array $input The input.
     * @param array $expected The expected result.
     * @param array $params The parameters for rendering.
     *
     * @dataProvider provideLayoutHydratesTo
     */
    public function testLayoutHydratesTo(array $input, array $expected, array $params = []) {
        $hydrator = self::getLayoutService()->getHydrator('home');
        $actual = $hydrator->resolve($input, $params);
        // Make sure we see it as the API output would.
        $actual = json_decode(json_encode($actual), true);
        $this->assertSame($expected, $actual);
    }

    /**
     * Test that hydrateLayout layout inputs hydrate into specific outputs.
     *
     * @param array $input The input.
     * @param array $expected The expected result.
     * @param array $params The parameters for rendering.
     *
     * @dataProvider provideLayoutHydratesTo
     */
    public function testHydrateLayout(array $input, array $expected, array $params = []) {
        $actual = self::getLayoutService()->hydrateLayout('home', $params, $input);
        // Make sure we see it as the API output would.
        $this->assertArrayHasKey('seo', $actual);
        $seo = json_decode(json_encode($actual['seo']), true);
        unset($actual['seo']);
        $actual = json_decode(json_encode($actual), true);
        $this->assertEquals(4, count($seo));
        $this->assertSame($expected, $actual);
    }

    /**
     * @return iterable
     */
    public function provideLayoutHydratesTo(): iterable {
        $breadcrumbDefinition = [
            '$hydrate' => "react.asset.breadcrumbs",

            /// Invalid value here.
            "recordType" => []
        ];
        yield "Exceptions propagate up to the nearest react node" => [
            [
                "layout" => [[
                    '$hydrate' => "react.section.1-column",
                    "contents" => [
                        $breadcrumbDefinition,
                    ],
                    'autoWrap' => true,
                ]]
            ],
            [
                'layout' => [[
                    '$reactComponent' => 'SectionOneColumn',
                    '$reactProps' => [
                        'contents' => [[
                            '$reactComponent' => 'LayoutError',
                            '$reactProps' => [
                                'layoutDefinition' => $breadcrumbDefinition,
                                'error' => [
                                    'message' => 'Validation Failed',
                                    'status' => 422,
                                    'errors' => [[
                                        'field' => 'recordType',
                                        'code' => 'invalid',
                                        'status' => 422,
                                        'message' => 'recordType is not a valid string.',
                                    ]]
                                ],
                            ],
                        ]],
                        'isNarrow' => false,
                        'autoWrap' => true,
                    ],
                ]]
            ]
        ];

        yield "Resolvers not found turn into layout error react nodes" => [
            [
                '$hydrate' => 'react.some-react',
                'thiswillfail' => true,
            ],
            [
                '$reactComponent' => 'LayoutError',
                '$reactProps' => [
                    'layoutDefinition' => [
                        '$hydrate' => 'react.some-react',
                        'thiswillfail' => true,
                    ],
                    'error' => [
                        'message' => 'Resolver not registered: react.some-react',
                        'code' => 404,
                    ],
                ],
            ]
        ];

        yield "Component with null props is removed" => [
            [
                '$hydrate' => "react.section.1-column",
                "contents" => [[
                    '$hydrate' => 'react.asset.breadcrumbs',
                    // When we don't have a recordID, breadcrumbs don't render.
                    'recordID' => null,
                    'includeHomeCrumb' => false,
                ]],
            ],
            [
                '$reactComponent' => "SectionOneColumn",
                '$reactProps' => [
                    "contents" => [],
                    'isNarrow' => false,
                    'autoWrap' => true,
                ],
            ],
        ];
    }
}
