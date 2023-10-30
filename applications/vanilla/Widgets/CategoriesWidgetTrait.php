<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Widgets;

use Garden\Container\ContainerException;
use Garden\Container\NotFoundException;
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
     * @param bool $filter
     * @param bool $limit
     * @param bool $featured
     * @param bool $followed
     * @return Schema
     * @throws ContainerException
     * @throws NotFoundException
     */
    public static function getApiSchema(
        bool $filter = true,
        bool $limit = true,
        bool $featured = true,
        bool $followed = true
    ): Schema {
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

        $filterSchema = [];
        $limitSchema = [];
        $featuredSchema = [];
        $followedSchema = [];

        if ($filter) {
            $filterSchema = [
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
            ];
        }

        if ($limit) {
            $limitSchema = [
                "limit" => [
                    "type" => "integer",
                    "description" => t("Desired number of items."),
                    "minimum" => 1,
                    "step" => 1,
                    "maximum" => 100,
                    "default" => 10,
                    "x-control" => SchemaForm::textBox(
                        new FormOptions(
                            t("Limit"),
                            t("Choose how many records to display."),
                            "",
                            t("Up to a maximum of 100 items may be displayed.")
                        ),
                        "number"
                    ),
                ],
            ];
        }

        if ($featured) {
            $featuredSchema = [
                "featured:b?" => [
                    "description" => "Followed categories filter",
                    "x-control" => SchemaForm::toggle(new FormOptions("Featured", "Only featured categories.")),
                ],
            ];
        }

        if ($followed) {
            $followedSchema = [
                "followed:b?" => [
                    "description" => "Followed categories filter",
                    "x-control" => SchemaForm::toggle(new FormOptions("Followed", "Only followed categories.")),
                ],
            ];
        }

        $apiSchema = $apiSchema->merge(
            SchemaUtils::composeSchemas(Schema::parse($filterSchema + $limitSchema + $featuredSchema + $followedSchema))
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

    /**
     * Get the schema for category options.
     *
     * @param string $fieldName
     * @param ?array $allowedProperties
     *
     * @return Schema
     */
    public static function optionsSchema(string $fieldName = "categoryOptions", array $allowedProperties = null): Schema
    {
        // this is a kludge for 2023.020 release, there was no feature flag dependency for categoryOptions schema,
        // but we should have one, feature flag is removed in 2023.021 release
        if (!\Gdn::config("Feature.layoutEditor.categoryList.Enabled")) {
            return Schema::parse([]);
        }

        $schema = Schema::parse([
            "description:?" => Schema::parse([
                "display:b?" => [
                    "description" => t("Show description in category."),
                    "default" => true,
                    "x-control" => SchemaForm::toggle(
                        new FormOptions(t("Category Description"), t("Show description in category."))
                    ),
                ],
            ]),
            "followButton:?" => Schema::parse([
                "display:b?" => [
                    "description" => t("Show follow category action button."),
                    "default" => true,
                    "x-control" => SchemaForm::toggle(
                        new FormOptions(t("Follow Category Button"), t("Show follow category action button."))
                    ),
                ],
            ]),
            "metas?" => self::metasSchema(),
        ]);

        if ($allowedProperties) {
            $schema = Schema::parse($allowedProperties)->add($schema);
        }

        return Schema::parse([
            "$fieldName?" => $schema
                ->setDescription(t("Configure various widget options"))
                ->setField("x-control", SchemaForm::section(new FormOptions("Category Options"))),
        ]);
    }

    /**
     * Get the schema for a metas.
     *
     * @return Schema
     */
    private static function metasSchema(): Schema
    {
        return Schema::parse([
            "asIcons:s?" => [
                "default" => "text",
                "description" => t("Choose metas display type."),
                "x-control" => [
                    SchemaForm::radio(
                        new FormOptions(t("Meta Label Display"), t("Choose metas display type.")),
                        new StaticFormChoices([
                            "icon" => t("Icon"),
                            "text" => t("Text"),
                        ])
                    ),
                ],
            ],
            "includeSubcategoriesCount?" => [
                "type" => "array",
                "description" => t("Choose what record type counts to include in total counts."),
                "items" => [
                    "type" => "string",
                ],
                "default" => [],
                "x-control" => SchemaForm::dropDown(
                    new FormOptions(
                        t("Include Subcategories in Meta Counts"),
                        t("Choose what record type counts to include in total counts."),
                        ""
                    ),
                    new StaticFormChoices([
                        "discussions" => "Discussion Count",
                        "comments" => "Comment Count",
                        "posts" => "Post Count",
                    ]),
                    null,
                    true
                ),
            ],
            "display:?" => Schema::parse([
                "postCount:b?" => [
                    "description" => t("Enable post count in meta."),
                    "default" => true,
                    "x-control" => SchemaForm::checkBox(
                        new FormOptions(t("Post Count"), t("Enable post count in meta.")),
                        null,
                        "none"
                    ),
                ],
                "discussionCount:b?" => [
                    "description" => t("Enable discussion count in meta."),
                    "default" => true,
                    "x-control" => SchemaForm::checkBox(
                        new FormOptions(t("Discussion Count"), t("Enable discussion count in meta.")),
                        null,
                        "none"
                    ),
                ],
                "commentCount:b?" => [
                    "description" => t("Enable comment count in meta."),
                    "default" => true,
                    "x-control" => SchemaForm::checkBox(
                        new FormOptions(t("Comment Count"), t("Enable comment count in meta.")),
                        null,
                        "none"
                    ),
                ],
                "followerCount:b?" => [
                    "description" => t("Enable follower count in meta."),
                    "default" => true,
                    "x-control" => SchemaForm::checkBox(
                        new FormOptions(t("Follower Count"), t("Enable follower count in meta.")),
                        null,
                        "none"
                    ),
                ],
                "lastPostName:b?" => [
                    "description" => t("Enable last comment or discussion name in meta."),
                    "default" => true,
                    "x-control" => SchemaForm::checkBox(
                        new FormOptions(t("Last Post Name"), t("Enable last comment or discussion name in meta.")),
                        null,
                        "none"
                    ),
                ],
                "lastPostAuthor:b?" => [
                    "description" => t("Enable last comment or discussion author in meta."),
                    "default" => true,
                    "x-control" => SchemaForm::checkBox(
                        new FormOptions(t("Last Post Author"), t("Enable last comment or discussion author in meta.")),
                        null,
                        "none"
                    ),
                ],
                "lastPostDate:b?" => [
                    "description" => t("Enable last comment or discussion date in meta."),
                    "default" => true,
                    "x-control" => SchemaForm::checkBox(
                        new FormOptions(t("Last Post Date"), t("Enable last comment or discussion date in meta.")),
                        null,
                        "none"
                    ),
                ],
                "subcategories:b?" => [
                    "description" => t("Enable subcategories in meta."),
                    "default" => true,
                    "x-control" => SchemaForm::checkBox(
                        new FormOptions(t("Subcategories"), t("Enable subcategories in meta.")),
                        null,
                        "none"
                    ),
                ],
            ]),
        ])
            ->setDescription(t("Configure meta options."))
            ->setField("x-control", SchemaForm::section(new FormOptions(t("Meta Options"))));
    }

    /**
     * Extracts data from category and maps it into desired format.
     *
     * @param array $categories
     * @return array
     */
    public function mapCategoryToItem(array $categories): ?array
    {
        return array_map(function ($category) {
            $fallbackImage = $this->props["itemOptions"]["fallbackImage"] ?? null;
            $fallbackImage = $fallbackImage ?: null;
            $imageUrl = $category["bannerUrl"] ?? $fallbackImage;
            $imageUrlSrcSet = $category["bannerUrlSrcSet"] ?? null;
            if (!$imageUrlSrcSet && $fallbackImage && $this->imageSrcSetService) {
                $imageUrlSrcSet = $this->imageSrcSetService->getResizedSrcSet($imageUrl);
            }

            $fallbackIcon = $this->props["itemOptions"]["fallbackIcon"] ?? null;
            $fallbackIcon = $fallbackIcon ?: null;
            $iconUrl = $category["iconUrl"] ?? $fallbackIcon;
            $iconUrlSrcSet = $category["iconUrlSrcSet"] ?? null;
            if (!$iconUrlSrcSet && $fallbackIcon && $this->imageSrcSetService) {
                $iconUrlSrcSet = $this->imageSrcSetService->getResizedSrcSet($iconUrl);
            }

            $children = count($category["children"]) > 0 ? $this->mapCategoryToItem($category["children"]) : [];
            $children = array_values((array) $children);

            // date format and extract just the date string from
            $lastPost = $category["lastPost"];
            if ($lastPost) {
                $lastPost["dateInserted"] =
                    $lastPost["dateInserted"] instanceof \DateTimeImmutable
                        ? $lastPost["dateInserted"]->format(\DateTimeImmutable::RFC3339)
                        : null;
            }

            //for counts information, using this directly from model, as depending on outputFormat/maxDepth params, API results are different
            $categoryRow = $this->categoryModel->getID($category["categoryID"], DATASET_TYPE_ARRAY);

            $result = [
                "to" => $category["url"],
                "iconUrl" => $iconUrl,
                "iconUrlSrcSet" => $iconUrlSrcSet,
                "imageUrl" => $imageUrl,
                "imageUrlSrcSet" => $imageUrlSrcSet,
                "name" => $category["name"],
                "description" => $category["description"] ?? "",
                "counts" => [
                    [
                        "labelCode" => "discussions",
                        "count" => (int) $categoryRow["CountDiscussions"] ?? 0,
                        "countAll" => (int) $categoryRow["CountAllDiscussions"] ?? 0,
                    ],
                    [
                        "labelCode" => "comments",
                        "count" => (int) $categoryRow["CountComments"] ?? 0,
                        "countAll" => (int) $categoryRow["CountAllComments"] ?? 0,
                    ],
                    [
                        "labelCode" => "posts",
                        "count" => (int) $categoryRow["CountDiscussions"] + $categoryRow["CountComments"],
                        "countAll" => (int) $categoryRow["CountAllDiscussions"] + $categoryRow["CountAllComments"],
                    ],
                    [
                        "labelCode" => "followers",
                        "count" => (int) $categoryRow["CountFollowers"] ?? 0,
                    ],
                ],
                "categoryID" => $category["categoryID"],
                "parentCategoryID" => $category["parentCategoryID"],
                "displayAs" => $category["displayAs"],
                "depth" => $category["depth"],
                "children" => $children,
                "lastPost" => $lastPost,
                "preferences" => $category["preferences"],
            ];

            // appropriate message for heading categories with empty children
            if ($category["displayAs"] === "heading" && count($category["children"]) === 0) {
                // double-checking here to see if we really don't have categories, or it's a permission thing
                $permissionMessage = false;
                $childCategoryIDs = $this->categoryModel->getCollection()->getChildIDs([$category["categoryID"]]);
                if ($childCategoryIDs && count($childCategoryIDs) > 0) {
                    // allowed categories for our user
                    $visibleCategoryIDs = $this->categoryModel->getVisibleCategoryIDs();

                    if (array_diff($childCategoryIDs, $visibleCategoryIDs) == $childCategoryIDs) {
                        $permissionMessage = true;
                    }
                }

                $result["noChildCategoriesMessage"] = $permissionMessage
                    ? t("You don't have permission to see this section.")
                    : t("No categories found.");
            }

            return $result;
        }, $categories);
    }

    /**
     * Returns all direct children of given categories, merged in one array.
     *
     * @param array $categories
     * @return array
     */
    public function getAllChildCategories(array $categories): ?array
    {
        $childCategories = [];
        foreach ($categories as $category) {
            $childCategories = array_merge($childCategories, $category["children"]);
        }

        return $childCategories;
    }
}
