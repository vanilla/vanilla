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
            "$fieldName?" => $schema->setDescription("Configure various widget options"),
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
}
