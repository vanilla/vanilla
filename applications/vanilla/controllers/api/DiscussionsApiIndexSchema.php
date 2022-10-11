<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Controllers\Api;

use Garden\Schema\Schema;
use Vanilla\ApiUtils;
use Vanilla\Dashboard\Models\RecordStatusModel;
use Vanilla\DateFilterSchema;
use Vanilla\Forms\ApiFormChoices;
use Vanilla\Forms\FieldMatchConditional;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Forms\StaticFormChoices;
use Vanilla\Utility\SchemaUtils;

/**
 * Parameter schema for the discussions API controller index.
 */
class DiscussionsApiIndexSchema extends Schema
{
    /**
     * Setup the schema.
     *
     * @param int $defaultLimit The default limit of items ot be returned.
     */
    public function __construct(int $defaultLimit)
    {
        parent::__construct(
            $this->parseInternal([
                "discussionID?" => \Vanilla\Schema\RangeExpression::createSchema([":int"])->setField("x-filter", [
                    "field" => "d.discussionID",
                ]),
                "categoryID:i?" => [
                    "description" => "Filter by a category.",
                    "x-filter" => [
                        "field" => "d.CategoryID",
                    ],
                    "x-control" => self::getCategoryIDFormOptions(),
                ],
                "bookmarkUserID:i?" => [
                    "description" => "Filter on bookmarked UserID.",
                    "minimum" => 1,
                ],
                "participatedUserID:i?" => [
                    "description" => "Filter on participated UserID.",
                    "minimum" => 1,
                ],
                "includeChildCategories:b?" => [
                    "default" => false,
                    "description" => "Filter by a category.",
                ],
                "dateInserted?" => new DateFilterSchema([
                    "description" => "When the discussion was created.",
                    "x-filter" => [
                        "field" => "d.DateInserted",
                        "processor" => [DateFilterSchema::class, "dateFilterField"],
                    ],
                ]),
                "dateUpdated?" => new DateFilterSchema([
                    "description" => "When the discussion was updated.",
                    "x-filter" => [
                        "field" => "d.DateUpdated",
                        "processor" => [DateFilterSchema::class, "dateFilterField"],
                    ],
                ]),
                "dateLastComment?" => new DateFilterSchema([
                    "description" => "When the last comment was posted.",
                    "x-filter" => [
                        "field" => "d.DateLastComment",
                        "processor" => [DateFilterSchema::class, "dateFilterField"],
                    ],
                ]),
                "tagID?" => \Vanilla\Schema\RangeExpression::createSchema([":int"]),
                "type:s?" => [
                    "description" => "Filter by discussion type.",
                    "x-filter" => [
                        "field" => "d.Type",
                    ],
                    "x-control" => SchemaForm::dropDown(
                        new FormOptions("Discussion Type", "Choose a specific type of discussions to display."),
                        new StaticFormChoices($this->discussionTypesEnumValues())
                    ),
                ],
                "excludeHiddenCategories:b" => [
                    "default" => false,
                    "description" =>
                        "Exclude discussions from categories that has the `HideAllDiscussions` option set to true.",
                ],
                "followed:b" => [
                    "default" => false,
                    "description" =>
                        "Only fetch discussions from followed categories. Pinned discussions are mixed in.",
                ],
                "pinned:b?" => [
                    "default" => false,
                    "x-control" => SchemaForm::toggle(new FormOptions("Announcements", "Only fetch announcements.")),
                ],
                "pinOrder:s?" => [
                    "default" => "first",
                    "enum" => ["first", "mixed"],
                    "x-control" => SchemaForm::dropDown(
                        new FormOptions("Announcement Pinning", "Choose how announcements display."),
                        new StaticFormChoices([
                            "first" => "Announcements display first.",
                            "mixed" => "Announcements are displayed in the default sort order with other discussions.",
                        ])
                    ),
                ],
                "dirtyRecords:b?",
                "siteSectionID:s?",
                "page:i?" => [
                    "description" => "Page number. See [Pagination](https://docs.vanillaforums.com/apiv2/#pagination).",
                    "default" => 1,
                    "minimum" => 1,
                ],
                "sort:s?" => [
                    "enum" => ApiUtils::sortEnum("dateLastComment", "dateInserted", "discussionID", "score", "hot"),
                    "x-control" => self::getSortFormOptions(),
                ],
                "limit:i?" => [
                    "description" => "Desired number of items per page.",
                    "default" => $defaultLimit,
                    "minimum" => 1,
                    "maximum" => ApiUtils::getMaxLimit(),
                    "x-control" => self::getLimitFormOptions(),
                ],
                "insertUserID:i?" => [
                    "description" => "Filter by author.",
                    "x-filter" => [
                        "field" => "d.InsertUserID",
                    ],
                ],
                "expand?" => \DiscussionExpandSchema::commonExpandDefinition(),
                "statusID:a?" => [
                    "items" => [
                        "type" => "integer",
                    ],
                    "x-search-scope" => true,
                    "x-filter" => [
                        "field" => "d.statusID",
                    ],
                ],
                "internalStatusID:a?" => [
                    "items" => [
                        "type" => "integer",
                    ],
                    "x-search-scope" => true,
                    "x-filter" => [
                        "field" => "d.internalStatusID",
                    ],
                ],
            ])
        );
        $this->addValidator("", SchemaUtils::onlyOneOf(["internalStatusID", "statusID"]));
    }

    /**
     * Get sort form options.
     *
     * @param FieldMatchConditional|null $conditional
     * @return array
     */
    public static function getSortFormOptions(FieldMatchConditional $conditional = null): array
    {
        return SchemaForm::dropDown(
            new FormOptions(t("Sort Order"), t("Choose the order records are sorted.")),
            new StaticFormChoices([
                "-dateLastComment" => t("Recently Commented"),
                "-dateInserted" => t("Recently Added"),
                "-score" => t("Top"),
                "-hot" => t("Hot (score + activity)"),
            ]),
            $conditional
        );
    }

    /**
     * Get CategoryID form options.
     *
     * @param FieldMatchConditional|null $conditional
     *
     * @return array
     */
    public static function getCategoryIDFormOptions(FieldMatchConditional $conditional = null): array
    {
        return SchemaForm::dropDown(
            new FormOptions(t("Category"), t("Display records from this category.")),
            new ApiFormChoices(
                "/api/v2/categories/search?query=%s&limit=30",
                "/api/v2/categories/%s",
                "categoryID",
                "name"
            ),
            $conditional
        );
    }

    /**
     * Get limit form options.
     *
     * @param FieldMatchConditional|null $conditional
     * @return array
     */
    public static function getLimitFormOptions(FieldMatchConditional $conditional = null): array
    {
        return SchemaForm::dropDown(
            new FormOptions(t("Limit"), t("Choose how many records to display.")),
            new StaticFormChoices([
                "3" => 3,
                "5" => 5,
                "10" => 10,
            ]),
            $conditional
        );
    }

    /**
     * Return ['apiType' => 'label']
     *
     * @return array
     */
    private function discussionTypesEnumValues(): array
    {
        $rawTypes = \DiscussionModel::discussionTypes();
        $result = [];
        foreach ($rawTypes as $rawType) {
            $apiType = $rawType["apiType"] ?? null;
            $label = $rawType["Singular"] ?? null;
            if ($apiType === null || $label === null) {
                continue;
            }

            $result[$apiType] = $label;
        }
        return $result;
    }
}
