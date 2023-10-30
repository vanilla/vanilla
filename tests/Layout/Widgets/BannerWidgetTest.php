<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Layout\Widgets;

use Vanilla\Dashboard\Models\BannerImageModel;
use Vanilla\Widgets\React\BannerFullWidget;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\Layout\LayoutTestTrait;
use VanillaTests\SiteTestCase;

/**
 * Tests for the banner widget.
 */
class BannerWidgetTest extends SiteTestCase
{
    use LayoutTestTrait;
    use CommunityApiTestTrait;

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

    /**
     * Provide widget settings for testHydrateBackgroundImage
     *
     * @return array[]
     */
    public function provideBackgroundImageSourceData(): array
    {
        return [
            ["react.app-banner", BannerFullWidget::IMAGE_SOURCE_STYLEGUIDE],
            ["react.app-banner", BannerFullWidget::IMAGE_SOURCE_CATEGORY],
            ["react.app-banner", BannerFullWidget::IMAGE_SOURCE_CUSTOM],
            ["react.app.content-banner", BannerFullWidget::IMAGE_SOURCE_STYLEGUIDE],
            ["react.app.content-banner", BannerFullWidget::IMAGE_SOURCE_CATEGORY],
            ["react.app.content-banner", BannerFullWidget::IMAGE_SOURCE_CUSTOM],
        ];
    }

    /**
     * Test hydrating background image for the banner using configuration.
     *
     * @param string $widgetID
     * @param string $imageSource
     * @return void
     * @dataProvider provideBackgroundImageSourceData
     */
    public function testHydrateBackgroundImage(string $widgetID, string $imageSource)
    {
        // Unique banner image names for each image source attribute.
        $categoryImage = BannerFullWidget::IMAGE_SOURCE_CATEGORY;
        $styleGuideImage = BannerFullWidget::IMAGE_SOURCE_STYLEGUIDE;
        $customImage = BannerFullWidget::IMAGE_SOURCE_CUSTOM;

        // Create category for testing category-based images.
        $categoryID = $this->createCategory(["bannerUrl" => "https://www.example.com/$categoryImage.jpg"])[
            "categoryID"
        ];

        $spec = [
            '$hydrate' => $widgetID,
            "background" => [
                "imageSource" => $imageSource,
                "image" => "https://www.example.com/$customImage.jpg",
            ],
        ];
        $this->runWithConfig(
            [BannerImageModel::DEFAULT_CONFIG_KEY => "https://www.example.com/$styleGuideImage.jpg"],
            function () use ($spec, $categoryID, $imageSource) {
                $this->assertHydratesTo(
                    $spec,
                    [
                        "categoryID" => $categoryID,
                    ],
                    self::markForSparseComparision([
                        '$reactProps' => self::markForSparseComparision([
                            "background" => self::markForSparseComparision([
                                "imageSource" => $imageSource,
                                "image" => "https://www.example.com/$imageSource.jpg",
                            ]),
                        ]),
                    ])
                );
            }
        );
    }
}
