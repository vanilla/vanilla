<?php
/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Widgets;

use Garden\Schema\Schema;
use Vanilla\Forms\ApiFormChoices;
use Vanilla\Forms\FieldMatchConditional;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Forms\StaticFormChoices;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Utility\SchemaUtils;

/**
 * Sharable properties and methods between the CategoriesWidget and the module.
 */
trait CategoriesWidgetTrait
{
    /**
     * Get the schema of our api params.
     *
     * @return Schema
     */
    public static function getApiSchema(): Schema
    {
        $apiSchema = new Schema([
            "type" => "object",
            "default" => new \stdClass(),
            "description" => "Api parameters for categories endpoint.",
        ]);

        $filterEnum = ["none", "currentCategory", "parentCategory", "category"];
        $staticFormChoices = [
            "none" => "None",
            "currentCategory" => "Current Category",
            "parentCategory" => "Parent Category",
            "category" => "Specific Categories",
        ];

        $siteSectionModel = \Gdn::getContainer()->get(SiteSectionModel::class);
        $siteSectionSchema = $siteSectionModel->getSiteSectionFormOption(
            new FieldMatchConditional(
                "apiParams.filter",
                Schema::parse([
                    "type" => "string",
                    "const" => "siteSection",
                ])
            )
        );

        // include subcommunities filter
        if ($siteSectionSchema !== null) {
            $filterEnum[] = "currentSiteSection";
            $filterEnum[] = "siteSection";
            $staticFormChoices["currentSiteSection"] = "Current Subcommunity";
            $staticFormChoices["siteSection"] = "Subcommunity";
        }

        $apiSchema = $apiSchema->merge(
            SchemaUtils::composeSchemas(
                Schema::parse([
                    "filter:s" => [
                        "enum" => $filterEnum,
                        "description" => "Choose which categories to be included in the widget.",
                        "default" => "none",
                        "x-control" => SchemaForm::dropDown(
                            new FormOptions("Filter By"),
                            new StaticFormChoices($staticFormChoices)
                        ),
                    ],
                    "categoryID?" => [
                        "type" => "array",
                        "items" => ["type" => ["integer", "string", "null"]],
                        "description" => "One or range of categoryIDs",
                        "x-control" => SchemaForm::dropDown(
                            new FormOptions("Categories", "Select the categories to use"),
                            new ApiFormChoices(
                                "/api/v2/categories/search?query=%s&limit=30",
                                "/api/v2/categories/%s",
                                "categoryID",
                                "name"
                            ),
                            new FieldMatchConditional(
                                "apiParams.filter",
                                Schema::parse([
                                    "type" => "string",
                                    "const" => "category",
                                ])
                            ),
                            true
                        ),
                    ],
                    "parentCategoryID?" => [
                        "type" => ["integer", "string", "null"],
                        "description" => "Category ID",
                        "x-control" => SchemaForm::dropDown(
                            new FormOptions("Category", "Select the category to use"),
                            new ApiFormChoices(
                                "/api/v2/categories/search?query=%s&limit=30",
                                "/api/v2/categories/%s",
                                "categoryID",
                                "name"
                            ),
                            new FieldMatchConditional(
                                "apiParams.filter",
                                Schema::parse([
                                    "type" => "string",
                                    "const" => "parentCategory",
                                ])
                            )
                        ),
                    ],
                    "siteSectionID?" => $siteSectionSchema,
                    "limit?" => [
                        "type" => "integer",
                        "minimum" => 1,
                        "default" => 10,
                        "description" => "Number of results to fetch.",
                        "x-control" => SchemaForm::dropDown(
                            new FormOptions(t("Limit"), t("Choose how many records to display.")),
                            new StaticFormChoices([
                                "3" => 3,
                                "5" => 5,
                                "10" => 10,
                            ])
                        ),
                    ],
                    "featured:b?" => [
                        "description" => "Followed categories filter",
                        "x-control" => SchemaForm::toggle(new FormOptions("Featured", "Only featured categories.")),
                    ],
                    "followed:b?" => [
                        "description" => "Followed categories filter",
                        "x-control" => SchemaForm::toggle(new FormOptions("Followed", "Only followed categories.")),
                    ],
                ])
            )
        );

        return $apiSchema;
    }

    /**
     * Get the schema of our fallback image/icon.
     *
     * @return Schema
     */
    public static function getFallbackImageSchema(): Schema
    {
        return Schema::parse([
            "fallbackImage:s?" => [
                "description" => "Set fallback image for the item.",
                "x-control" => SchemaForm::upload(
                    new FormOptions(
                        "Fallback Image",
                        "Fallback image for item.",
                        "",
                        "By default, an SVG image using your brand color displays when there’s nothing else to show. Upload your own image to customize. Recommended size: 1200px by 600px."
                    ),
                    new FieldMatchConditional(
                        "itemOptions.contentType",
                        Schema::parse([
                            "type" => "string",
                            "enum" => ["title-background", "title-description-image"],
                        ])
                    )
                ),
            ],
            "fallbackIcon:s?" => [
                "description" => "Set fallback icon for the item.",
                "x-control" => SchemaForm::upload(
                    new FormOptions(
                        "Fallback Icon",
                        "Fallback icon for item.",
                        "",
                        "By default, an SVG image using your brand color displays when there’s nothing else to show. Upload your own icon to customize. Recommended size: 200px by 200px."
                    ),
                    new FieldMatchConditional(
                        "itemOptions.contentType",
                        Schema::parse([
                            "type" => "string",
                            "const" => "title-description-icon",
                            "default" => "title-description-icon",
                        ])
                    )
                ),
            ],
        ]);
    }
}
