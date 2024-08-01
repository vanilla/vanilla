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
                "titleType" => "static",
                "descriptionType" => "static",
                // These both default to appearing
                "description" => "Hello description",
                '$reactTestID' => "basic",
            ],
            [
                '$hydrate' => "react.app-banner",
                "title" => "Hello title",
                "description" => "Hello description",
                "titleType" => "static",
                "descriptionType" => "none",
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
                "titleType" => "none",
                "descriptionType" => "none",
            ],
            [
                '$hydrate' => "react.app.content-banner",
                // These are hidden by default.
                "title" => "Hello title",
                "description" => "Hello description",
                '$reactTestID' => "content-banner-show",
                "showTitle" => true,
                "showDescription" => true,
                "titleType" => "static",
                "descriptionType" => "static",
            ],
        ];

        $expected = [
            [
                '$reactComponent' => "BannerWidget",
                '$reactProps' => self::markForSparseComparision([
                    "title" => "Hello title",
                    "showTitle" => true,
                    "titleType" => "static",
                    // These both default to appearing
                    "description" => "Hello description",
                    "showDescription" => true,
                    "descriptionType" => "static",
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
                    "titleType" => "static",
                    // These both default to appearing
                    "description" => "Hello description",
                    "showDescription" => false,
                    "descriptionType" => "none",
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
                    "titleType" => "none",
                    "descriptionType" => "none",
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
                    "titleType" => "static",
                    "descriptionType" => "static",
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
            ["react.app-banner", BannerFullWidget::IMAGE_SOURCE_STYLEGUIDE, null],
            ["react.app-banner", BannerFullWidget::IMAGE_SOURCE_CATEGORY, "https://www.example.com/category.jpg"],
            ["react.app-banner", BannerFullWidget::IMAGE_SOURCE_CUSTOM, "https://www.example.com/custom.jpg"],
            ["react.app.content-banner", BannerFullWidget::IMAGE_SOURCE_STYLEGUIDE, null],
            [
                "react.app.content-banner",
                BannerFullWidget::IMAGE_SOURCE_CATEGORY,
                "https://www.example.com/category.jpg",
            ],
            ["react.app.content-banner", BannerFullWidget::IMAGE_SOURCE_CUSTOM, "https://www.example.com/custom.jpg"],
        ];
    }

    /**
     * Test hydrating background image for the banner using configuration.
     *
     * @param string $widgetID
     * @param string $imageSource
     * @param string|null $expectedImage
     * @return void
     * @dataProvider provideBackgroundImageSourceData
     */
    public function testHydrateBackgroundImage(string $widgetID, string $imageSource, ?string $expectedImage)
    {
        // Unique banner image names for each image source attribute.
        $categoryImage = BannerFullWidget::IMAGE_SOURCE_CATEGORY;
        $customImage = BannerFullWidget::IMAGE_SOURCE_CUSTOM;

        // Create category for testing category-based images.
        $categoryID = $this->createCategory(["bannerUrl" => "https://www.example.com/$categoryImage.jpg"])[
            "categoryID"
        ];

        $spec = [
            '$hydrate' => $widgetID,
            "title" => "Test",
            "titleType" => "static",
            "description" => "Test",
            "descriptionType" => "static",
            "background" => [
                "imageSource" => $imageSource,
                "image" => "https://www.example.com/$customImage.jpg",
            ],
        ];

        $this->assertHydratesTo(
            $spec,
            [
                "categoryID" => $categoryID,
            ],
            self::markForSparseComparision([
                '$reactProps' => self::markForSparseComparision([
                    "background" => self::markForSparseComparision([
                        "imageSource" => $imageSource,
                        "image" => $expectedImage,
                    ]),
                ]),
            ])
        );
    }
}
