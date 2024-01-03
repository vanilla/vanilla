<?php
/**
 * @author David Barbier <dbarbier@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Widgets\React;

use Garden\Container\ContainerException;
use Garden\Container\NotFoundException;
use Garden\EventManager;
use Garden\Schema\Schema;
use Gdn;
use Vanilla\Forms\ApiFormChoices;
use Vanilla\Forms\FieldMatchConditional;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Forms\StaticFormChoices;
use Vanilla\Forum\Controllers\Api\DiscussionsApiIndexSchema;
use Vanilla\Site\DefaultSiteSection;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Utility\ArrayUtils;

/**
 * Trait for filterable widget.
 */
trait FilterableWidgetTrait
{
    /**
     * Get filters schema.
     *
     * @param string[] $usedFilterTypes
     * @param bool|array $followedFilter
     * @param array $extraOptions Possible keys with boolean values: `hasSubcommunitySubTypeOptions`, `hasCategorySubTypeOptions`, `hasGroupSubTypeOptions`.
     * @return Schema
     * @throws ContainerException
     * @throws NotFoundException
     */
    public static function filterTypeSchema(
        array $usedFilterTypes = ["subcommunity", "category", "group", "featured", "none"],
        $followedFilter = false,
        array $extraOptions = []
    ): Schema {
        $fullSchema = [];
        $filterTypes = [];

        $eventManager = Gdn::getContainer()->get(EventManager::class);
        $filterTypes = $eventManager->fireFilter("filterableWidgetTrait_beforeGetFilterTypes", $filterTypes);

        // Every available filter types
        $filterTypes = $filterTypes + [
            "category" => t("Category"),
            "group" => t("Group"),
            "featured" => t("Featured"),
            "none" => t("None"),
        ];

        $filterTypes = ArrayUtils::pluck($filterTypes, $usedFilterTypes);

        // Filter Type
        $fullSchema += [
            "filter" => [
                "type" => "string",
                "default" => in_array("none", $usedFilterTypes) ? "none" : array_key_first($filterTypes),
                "x-control" => SchemaForm::dropDown(
                    new FormOptions(t("Filter By"), t("How the content is going to be filtered.")),
                    new StaticFormChoices($filterTypes)
                ),
            ],
        ];

        // Sub Filter Type for Subcommunity
        if (isset($filterTypes["subcommunity"])) {
            $fullSchema += self::subcommunityFilterTypeSchema($extraOptions["hasSubcommunitySubTypeOptions"] ?? true);
        }

        // Sub Filter Type for category
        if (isset($filterTypes["category"])) {
            $fullSchema += self::categoryFilterTypeSchema($extraOptions["hasCategorySubTypeOptions"] ?? true);
        }

        // Sub Filter Type for group
        if (isset($filterTypes["group"])) {
            $fullSchema += self::groupFilterTypeSchema($extraOptions["hasGroupSubTypeOptions"] ?? true);
        }

        // Sub Filter Type for featured
        if (isset($filterTypes["featured"])) {
            $fullSchema += self::featuredCategoriesFilterTypeSchema();
        }

        $conditionalFollowedFilter = null;
        if (is_array($followedFilter)) {
            $conditionalFollowedFilter = $followedFilter;
        }
        if ($followedFilter || is_array($followedFilter)) {
            $fullSchema += self::followedFilterTypeSchema($conditionalFollowedFilter);
        }

        return Schema::parse($fullSchema);
    }

    /**
     * Get all site-sections form options.
     *
     * @return array
     * @throws ContainerException
     * @throws NotFoundException
     */
    protected static function getSiteSectionFormOptions(): array
    {
        /** @var SiteSectionModel $siteSectionModel */
        $siteSectionModel = Gdn::getContainer()->get(SiteSectionModel::class);
        $siteSections = $siteSectionModel->getAll();

        // If there's only one site-section (default) then we don't
        // need to build the choices.
        if (count($siteSections) === 1) {
            return [];
        }

        $siteSectionFormChoices = [];
        foreach ($siteSections as $siteSection) {
            $id = $siteSection->getSectionID();
            $name = $siteSection->getSectionName();

            if ($id !== DefaultSiteSection::DEFAULT_ID) {
                $siteSectionFormChoices[$id] = $name;
            }
        }

        return $siteSectionFormChoices;
    }

    /**
     * Returns schema chunks for subcommunity filter type.
     *
     * @return array|array[]
     * @throws ContainerException
     * @throws NotFoundException
     */
    public static function subcommunityFilterTypeSchema($hasSubTypeOption = true): array
    {
        $schema = [];

        if ($hasSubTypeOption) {
            $schema += [
                "filterSubcommunitySubType" => [
                    "type" => "string",
                    "default" => "set",
                    "x-control" => SchemaForm::radio(
                        new FormOptions("", t("How the content is going to be filtered.")),
                        new StaticFormChoices([
                            "contextual" => t("Contextual"),
                            "set" => t("Set"),
                        ]),
                        new FieldMatchConditional(
                            "apiParams",
                            Schema::parse(["filter" => ["const" => "subcommunity"]])
                        ),
                        [
                            "contextual" => t(
                                "Content shown is filtered dynamically based on the subcommunity the user is viewing."
                            ),
                        ]
                    ),
                ],
            ];
        }

        // Subcommunity / Site section
        $schema += [
            "siteSectionID" => [
                "type" => ["string", "null"],
                "default" => null,
                "x-control" => SchemaForm::dropDown(
                    new FormOptions(t("Subcommunity"), t("Display records from this subcommunity")),
                    new StaticFormChoices(self::getSiteSectionFormOptions()),
                    new FieldMatchConditional(
                        "apiParams",
                        Schema::parse(
                            $hasSubTypeOption
                                ? [
                                    "filter" => ["const" => "subcommunity"],
                                    "filterSubcommunitySubType" => ["const" => "set"],
                                ]
                                : ["filter" => ["const" => "subcommunity"]]
                        )
                    )
                ),
            ],
        ];

        return $schema;
    }

    /**
     * Returns schema chunks for category filter type.
     *
     * @param $hasSubTypeOption
     * @return array|array[]
     */
    private static function categoryFilterTypeSchema($hasSubTypeOption = true): array
    {
        $schema = [];

        if ($hasSubTypeOption) {
            $schema += [
                "filterCategorySubType" => [
                    "type" => "string",
                    "default" => "set",
                    "x-control" => SchemaForm::radio(
                        new FormOptions("", t("How the content is going to be filtered.")),
                        new StaticFormChoices([
                            "contextual" => t("Contextual"),
                            "set" => t("Set"),
                        ]),
                        new FieldMatchConditional("apiParams", Schema::parse(["filter" => ["const" => "category"]])),
                        [
                            "contextual" => t(
                                "Content shown is filtered dynamically based on the category the user is viewing."
                            ),
                        ]
                    ),
                ],
            ];
        }

        // Category (Set)
        $schema += [
            "categoryID" => [
                "type" => ["integer", "array", "null"],
                "default" => null,
                "x-control" => DiscussionsApiIndexSchema::getCategoryIDFormOptions(
                    new FieldMatchConditional(
                        "apiParams",
                        Schema::parse(
                            $hasSubTypeOption
                                ? [
                                    "filter" => ["const" => "category"],
                                    "filterCategorySubType" => ["const" => "set"],
                                ]
                                : ["filter" => ["const" => "category"]]
                        )
                    )
                ),
            ],
        ];

        return $schema;
    }

    /**
     * Returns schema chunks for group filter type.
     *
     * @param $hasSubTypeOption
     * @return array|array[]
     */
    private static function groupFilterTypeSchema($hasSubTypeOption = true): array
    {
        $schema = [];

        if ($hasSubTypeOption) {
            $schema += [
                "filterGroupSubType?" => [
                    "type" => "string",
                    "default" => "set",
                    "x-control" => SchemaForm::radio(
                        new FormOptions("", t("How the content is going to be filtered.")),
                        new StaticFormChoices([
                            "contextual" => t("Contextual"),
                            "set" => t("Set"),
                        ]),
                        new FieldMatchConditional("apiParams", Schema::parse(["filter" => ["const" => "group"]])),
                        [
                            "contextual" => t(
                                "Content shown is filtered dynamically based on the group the user is viewing."
                            ),
                        ]
                    ),
                ],
            ];
        }

        // Group (Set)
        $schema += [
            "groupID" => [
                "type" => ["integer", "null"],
                "default" => null,
                "x-control" => SchemaForm::dropDown(
                    new FormOptions("Group", "Choose a group"),
                    new ApiFormChoices("/api/v2/groups", "/api/v2/groups/%s", "groupID", "name"),
                    new FieldMatchConditional(
                        "apiParams",
                        Schema::parse(
                            $hasSubTypeOption
                                ? [
                                    "filter" => ["const" => "group"],
                                    "filterCategorySubType" => ["const" => "set"],
                                ]
                                : ["filter" => ["const" => "group"]]
                        )
                    )
                ),
            ],
        ];

        return $schema;
    }

    /**
     * Returns schema chunks for featured categories filter type.
     *
     * @return array|array[]
     */
    private static function featuredCategoriesFilterTypeSchema(): array
    {
        $schema = [];

        $schema += [
            "featuredCategoryID?" => [
                "type" => ["array", "null"],
                "default" => null,
                "x-control" => DiscussionsApiIndexSchema::getCategoryIDFormOptions(
                    new FieldMatchConditional("apiParams", Schema::parse(["filter" => ["const" => "featured"]])),
                    true
                ),
            ],
        ];

        return $schema;
    }

    /**
     * Returns schema chunks for followed filter type.
     *
     * @param null|array $conditionalFollowedFilter
     * @return array|array[]
     */
    private static function followedFilterTypeSchema($conditionalFollowedFilter = null): array
    {
        if ($conditionalFollowedFilter === null) {
            $conditionalFollowedFilter = ["subcommunity", "category", "none"];
        }

        $schema = [];

        $schema += [
            "followed?" => [
                "type" => "boolean",
                "default" => false,
                "x-control" => SchemaForm::checkBox(
                    new FormOptions(t("Only Show Followed Categories"), t("Display content from followed categories")),
                    new FieldMatchConditional(
                        "apiParams",
                        Schema::parse(["filter" => ["enum" => $conditionalFollowedFilter]])
                    )
                ),
            ],
        ];

        return $schema;
    }
}
