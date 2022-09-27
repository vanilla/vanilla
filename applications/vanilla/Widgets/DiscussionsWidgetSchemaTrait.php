<?php
/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Widgets;

use Garden\Schema\Schema;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Forms\StaticFormChoices;
use Vanilla\Forms\FieldMatchConditional;
use Vanilla\Forum\Controllers\Api\DiscussionsApiIndexSchema;
use Vanilla\Site\DefaultSiteSection;
use Vanilla\Site\SiteSectionModel;

/**
 * Abstraction layer to generate schemas for discussions.
 */
trait DiscussionsWidgetSchemaTrait
{
    /**
     * Get the schema for discussions excerpt.
     *
     * @param string $fieldName
     * @param ?array $allowedProperties
     * @param ?string $placeholder
     *
     * @return Schema
     */
    public static function optionsSchema(
        string $fieldName = "discussionOptions",
        array $allowedProperties = null,
        string $placeholder = null
    ): Schema {
        $schema = Schema::parse([
            "excerpt:?" => Schema::parse([
                "display:b?" => [
                    "description" => "Show excerpt in discussion.",
                    "default" => true,
                    "x-control" => SchemaForm::toggle(new FormOptions("Excerpt", "Show excerpt in discussion.")),
                ],
            ]),
            "metas?" => self::metasSchema("Configure meta options."),
        ]);

        if ($allowedProperties) {
            $schema = Schema::parse($allowedProperties)->add($schema);
        }

        return Schema::parse([
            "$fieldName?" => $schema
                ->setDescription("Configure various widget options")
                ->setField("x-control", SchemaForm::section(new FormOptions("Discussion Options"))),
        ]);
    }

    /**
     * Get the schema for a metas.
     *
     * @param string|null $description
     * @return Schema
     */
    public static function metasSchema(string $description = null): Schema
    {
        $schema = Schema::parse([
            "asIcons:b?" => [
                "description" => "Show Metas as Icons.",
                "default" => false,
                "x-control" => SchemaForm::checkBox(
                    new FormOptions("As Icons", "Metas will be displayed as icons."),
                    null,
                    "none"
                ),
            ],
            "display:?" => Schema::parse([
                "category:b?" => [
                    "description" => "Enable category option in meta.",
                    "default" => true,
                    "x-control" => SchemaForm::checkBox(
                        new FormOptions("Category", "Show category in metas."),
                        null,
                        "none"
                    ),
                ],
                "startedByUser:b?" => [
                    "description" => "Enable started by user option in meta.",
                    "default" => true,
                    "x-control" => SchemaForm::checkBox(
                        new FormOptions("Author", "Show author in metas."),
                        null,
                        "none"
                    ),
                ],
                "lastUser:b?" => [
                    "description" => "Enable last comment user option in meta.",
                    "default" => true,
                    "x-control" => SchemaForm::checkBox(
                        new FormOptions("Last Comment User", "Show last comment user in metas."),
                        null,
                        "none"
                    ),
                ],
                "lastCommentDate:b?" => [
                    "description" => "Enable last comment date option in meta.",
                    "default" => true,
                    "x-control" => SchemaForm::checkBox(
                        new FormOptions("Last Comment Date", "Show last comment date in metas."),
                        null,
                        "none"
                    ),
                ],
                "viewCount:b?" => [
                    "description" => "Enable view count option in meta.",
                    "default" => true,
                    "x-control" => SchemaForm::checkBox(
                        new FormOptions("View Count", "Show view count in metas."),
                        null,
                        "none"
                    ),
                ],
                "commentCount:b?" => [
                    "description" => "Enable comment count option in meta.",
                    "default" => true,
                    "x-control" => SchemaForm::checkBox(
                        new FormOptions("Comment Count", "Show comment count in metas."),
                        null,
                        "none"
                    ),
                ],
                "score:b?" => [
                    "description" => "Enable score count option in meta.",
                    "default" => true,
                    "x-control" => SchemaForm::checkBox(
                        new FormOptions("Score Count", "Show score count in metas."),
                        null,
                        "none"
                    ),
                ],
                "userTags:b?" => [
                    "description" => "Enable user tags option in meta.",
                    "default" => true,
                    "x-control" => SchemaForm::checkBox(
                        new FormOptions("User Tags", "Show user tags in metas."),
                        null,
                        "none"
                    ),
                ],
                "unreadCount:b?" => [
                    "description" => "Enable unread count option in meta.",
                    "default" => true,
                    "x-control" => SchemaForm::checkBox(
                        new FormOptions("Unread Count", "Show unread count in metas."),
                        null,
                        "none"
                    ),
                ],
            ]),
        ]);
        if ($description) {
            $schema->setField("description", $description);
        }

        return $schema->setField("x-control", SchemaForm::section(new FormOptions("Meta Options")));
    }

    /**
     * Get only followed categories trigger schema.
     *
     * @return Schema
     */
    public static function followedCategorySchema(): Schema
    {
        return Schema::parse([
            "followed?" => [
                "type" => "boolean",
                "default" => false,
                "x-control" => SchemaForm::toggle(
                    new FormOptions(
                        t("Display content from followed categories"),
                        t("Enable to only show posts from categories a user follows.")
                    )
                ),
            ],
        ]);
    }

    /**
     * Get categorySchema.
     *
     * @return Schema
     */
    protected static function categorySchema(): Schema
    {
        return Schema::parse([
            "categoryID?" => [
                "type" => ["integer", "null"],
                "default" => null,
                "x-control" => DiscussionsApiIndexSchema::getCategoryIDFormOptions(
                    new FieldMatchConditional(
                        "apiParams",
                        Schema::parse([
                            "siteSectionID" => [
                                "type" => "null",
                            ],
                            "followed" => [
                                "const" => false,
                            ],
                        ])
                    )
                ),
            ],
            "includeChildCategories?" => [
                "type" => "boolean",
                "default" => true,
                "x-control" => SchemaForm::toggle(
                    new FormOptions(t("Include Child Categories"), t("Include records from child categories.")),
                    new FieldMatchConditional(
                        "apiParams",
                        Schema::parse([
                            "categoryID" => [
                                "type" => "integer",
                            ],
                            "siteSectionID" => [
                                "type" => "null",
                            ],
                            "followed" => [
                                "const" => false,
                            ],
                        ])
                    )
                ),
            ],
        ]);
    }

    /**
     * Get site-section-id schema.
     *
     * @return Schema
     */
    protected static function siteSectionIDSchema(): Schema
    {
        return Schema::parse([
            "siteSectionID?" => [
                "type" => ["string", "null"],
                "default" => null,
                "x-control" => SchemaForm::dropDown(
                    new FormOptions(t("Subcommunity"), t("Display records from this subcommunity.")),
                    new StaticFormChoices(self::getSiteSectionFormChoices()),
                    new FieldMatchConditional(
                        "apiParams",
                        Schema::parse([
                            "categoryID" => [
                                "type" => "null",
                            ],
                            "followed" => [
                                "const" => false,
                            ],
                        ])
                    )
                ),
            ],
        ]);
    }

    /**
     * Get slotType schema.
     *
     * @return Schema
     */
    protected static function getSlotTypeSchema(): Schema
    {
        return Schema::parse([
            "slotType?" => [
                "type" => "string",
                "default" => "a",
                "enum" => ["d", "w", "m", "a"],
                "x-control" => [
                    SchemaForm::radio(
                        new FormOptions(t("Timeframe"), t("Choose when to load records from.")),
                        new StaticFormChoices([
                            "d" => t("Last Day"),
                            "w" => t("Last Week"),
                            "m" => t("Last Month"),
                            "a" => t("All Time"),
                        ]),
                        new FieldMatchConditional(
                            "apiParams.sort",
                            Schema::parse([
                                "type" => "string",
                                "const" => "-score",
                            ])
                        )
                    ),
                    SchemaForm::radio(
                        new FormOptions(t("Timeframe"), t("Choose when to load discussions from.")),
                        new StaticFormChoices([
                            "d" => t("Last Day"),
                            "w" => t("Last Week"),
                            "m" => t("Last Month"),
                            "a" => t("All Time"),
                        ]),
                        new FieldMatchConditional(
                            "apiParams.sort",
                            Schema::parse([
                                "type" => "string",
                                "const" => "-hot",
                            ])
                        )
                    ),
                ],
            ],
        ]);
    }

    /**
     * Get all site-sections form choices.
     *
     * @return array
     */
    protected static function getSiteSectionFormChoices(): array
    {
        /** @var SiteSectionModel $siteSectionModel */
        $siteSectionModel = \Gdn::getContainer()->get(SiteSectionModel::class);
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

            if ($id !== (string) DefaultSiteSection::DEFAULT_ID) {
                $siteSectionFormChoices[$id] = $name;
            }
        }

        return $siteSectionFormChoices;
    }
}
