<?php
/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Widgets;

use Garden\Schema\Schema;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Forms\StaticFormChoices;
use Vanilla\Utility\ModelUtils;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Web\TwigRenderTrait;
use Vanilla\Widgets\HomeWidgetContainerSchemaTrait;

/**
 * Abstraction layer to generate schema for DiscussionCommentAsset.
 */
trait DiscussionCommentsAssetSchemaTrait
{
    /**
     * Get the schema.
     *
     * @return Schema
     */
    public static function getAssetSchema(): Schema
    {
        $threadStyle = \Gdn::config("threadStyle", "flat");
        $maxDepthReplies = \Gdn::config("Vanilla.Comment.MaxDepth", 5);

        $collapseChildDepthFormChoices = [];
        $collapseChildDepthEnum = $maxDepthReplies > 2 ? array_values(range(2, $maxDepthReplies - 1)) : [];
        foreach ($collapseChildDepthEnum as $key => $value) {
            // 1 is not an option
            if ($value > 1) {
                $collapseChildDepthFormChoices[$value] = $value;
            }
        }
        $defaultLevelCollapseSchema = [
            "type" => "integer",
            "x-control" => SchemaForm::dropDown(
                new FormOptions(
                    "Default Level Collapse",
                    "",
                    "",
                    "Replies nested at this level and deeper will be collapsed by default."
                ),
                new StaticFormChoices($collapseChildDepthFormChoices)
            ),
        ];

        $apiSchema = [
            "default" => [],
            "type" => "object",
            "description" => "Configure API options",
            "properties" => [
                "limit?" => [
                    "type" => "integer",
                    "description" => t("Desired number of items."),
                    "minimum" => 1,
                    "maximum" => 100,
                    "step" => 1,
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
                "sort?" => [
                    "type" => "string",
                    "default" => "dateInserted",
                    "enum" => ["-dateInserted", "dateInserted", "-score", "-" . ModelUtils::SORT_TRENDING],
                    "x-control" => SchemaForm::dropDown(
                        new FormOptions(t("Default Sort Order"), t("Choose the order records are sorted by default.")),
                        new StaticFormChoices([
                            "dateInserted" => t("Oldest"),
                            "-dateInserted" => t("Newest"),
                            "-score" => t("Top"),
                            "-experimentalTrending" => t("Trending"),
                        ])
                    ),
                ],
            ],
        ];

        // we'll show this setting only for nested comment style
        if ($threadStyle === "nested") {
            $apiSchema["properties"]["collapseChildDepth?"] = $defaultLevelCollapseSchema;
        }

        return SchemaUtils::composeSchemas(
            Schema::parse([
                "title" => [
                    "type" => "string",
                    "description" => "Title of the widget",
                    "default" => t("Comments"),
                    "x-control" => SchemaForm::textBox(new FormOptions("Title", "Set a custom title.", ""), "text"),
                ],
            ]),
            Schema::parse([
                "apiParams?" => $apiSchema,
            ]),
            Schema::parse([
                "showOPTag?" => [
                    "type" => "boolean",
                    "default" => true,
                    "x-control" => SchemaForm::toggle(
                        new FormOptions(
                            t("Show OP Indicator"),
                            "",
                            "",
                            t("If this option is enabled, replies from the Original Poster will have an OP indicator.")
                        )
                    ),
                ],
            ]),
            self::containerOptionsSchema("containerOptions", ["innerBackground?", "borderType?", "headerAlignment?"])
        );
    }
}
