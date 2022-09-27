<?php
/**
 * @copyright 2008-2022 Vanilla Forums, Inc.
 * @license Proprietary
 */

namespace VanillaTests\Forum\Widgets;

use Vanilla\ImageSrcSet\ImageSrcSet;
use Vanilla\Layout\LayoutHydrator;
use VanillaTests\SiteTestCase;
use VanillaTests\Layout\LayoutTestTrait;

/**
 * Test CallToActionWidget.
 */
class CallToActionWidgetTest extends SiteTestCase
{
    use LayoutTestTrait;

    /**
     * Test that we can hydrate CallToAction Widget.
     */
    public function testHydrateCalltoActionWidget()
    {
        $spec = [
            '$hydrate' => "react.cta",
            "title" => "My CallToAction Widget",
            "description" => "Some description here.",
            "button" => [
                "title" => "My Button",
                "type" => "standard",
                "url" => "https://testurl.com",
            ],
        ];

        //with background image
        $spec2 = array_merge($spec, [
            "button" => [
                "title" => "My Button",
                "url" => "https://testurl.com",
                "type" => "standard",
            ],
            "background" => [
                "image" => "https://myimage.jpg",
            ],
        ]);

        $expected = [
            '$reactComponent' => "CallToActionWidget",
            '$reactProps' => [
                "title" => "My CallToAction Widget",
                "description" => "Some description here.",
                "button" => [
                    "title" => "My Button",
                    "type" => "standard",
                    "url" => "https://testurl.com",
                ],
            ],
        ];
        $expected2 = [
            '$reactComponent' => "CallToActionWidget",
            '$reactProps' => [
                "title" => "My CallToAction Widget",
                "description" => "Some description here.",
                "background" => [
                    "image" => "https://myimage.jpg",
                    "imageUrlSrcSet" => [
                        "data" => [
                            10 => "",
                            300 => "",
                            800 => "",
                            1200 => "",
                            1600 => "",
                        ],
                    ],
                ],
            ],
        ];
        $this->assertHydratesTo($spec, [], $expected);

        $layoutService = self::container()->get(LayoutHydrator::class);
        $hydrator = $layoutService->getHydrator(null);
        $result = $hydrator->resolve($spec2, []);

        $this->assertInstanceOf(ImageSrcSet::class, $result["\$reactProps"]["background"]["imageUrlSrcSet"]);
        $this->assertSame(
            $expected2["\$reactProps"]["background"]["imageUrlSrcSet"]["data"],
            $result["\$reactProps"]["background"]["imageUrlSrcSet"]->jsonSerialize()
        );
    }
}
