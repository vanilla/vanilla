<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Layout\Widgets;

use VanillaTests\Layout\LayoutTestTrait;
use VanillaTests\SiteTestCase;

/**
 * Tests for the banner widget.
 */
class BannerWidgetTest extends SiteTestCase
{
    use LayoutTestTrait;

    /**
     * Test hydration of the banner widgets.
     */
    public function testHydrate()
    {
        $spec = [
            [
                '$hydrate' => "react.app-banner",
                "title" => "Hello title",
                // These both default to appearing
                "description" => "Hello description",
                '$reactTestID' => "basic",
            ],
            [
                '$hydrate' => "react.app-banner",
                "title" => "Hello title",
                "description" => "Hello description",
                // Description can be hidden
                "showDescription" => false,
                '$reactTestID' => "hide-description",
            ],
            [
                '$hydrate' => "react.app.content-banner",
                // These are hidden by default.
                "title" => "Hello title",
                "description" => "Hello description",
                '$reactTestID' => "content-banner",
            ],
            [
                '$hydrate' => "react.app.content-banner",
                // These are hidden by default.
                "title" => "Hello title",
                "description" => "Hello description",
                '$reactTestID' => "content-banner-show",
                "showTitle" => true,
                "showDescription" => true,
            ],
        ];

        $expected = [
            [
                '$reactComponent' => "BannerWidget",
                '$reactProps' => self::markForSparseComparision([
                    "title" => "Hello title",
                    "showTitle" => true,
                    // These both default to appearing
                    "description" => "Hello description",
                    "showDescription" => true,
                ]),
                '$reactTestID' => "basic",
                '$seoContent' => <<<HTML
<h1>Hello title</h1>
<p>Hello description</p>
HTML
            ,
            ],
            [
                '$reactComponent' => "BannerWidget",
                '$reactProps' => self::markForSparseComparision([
                    "title" => "Hello title",
                    "showTitle" => true,
                    // These both default to appearing
                    "description" => "Hello description",
                    "showDescription" => false,
                ]),
                '$reactTestID' => "hide-description",
                '$seoContent' => <<<HTML
<h1>Hello title</h1>
HTML
            ,
            ],
            [
                '$reactComponent' => "BannerContentWidget",
                '$reactProps' => self::markForSparseComparision([
                    "title" => "Hello title",
                    "description" => "Hello description",
                    "showTitle" => false,
                    "showDescription" => false,
                ]),
                '$reactTestID' => "content-banner",
                // Nothing
                '$seoContent' => "",
            ],
            [
                '$reactComponent' => "BannerContentWidget",
                '$reactProps' => self::markForSparseComparision([
                    "title" => "Hello title",
                    "description" => "Hello description",
                    "showTitle" => true,
                    "showDescription" => true,
                ]),
                '$reactTestID' => "content-banner-show",
                // Nothing,
                '$seoContent' => <<<HTML
<h1>Hello title</h1>
<p>Hello description</p>
HTML
            ,
            ],
        ];

        $this->assertHydratesTo($spec, [], $expected);
    }
}
