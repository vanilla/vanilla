<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Controllers\Api;

use Garden\Schema\Schema;
use Garden\Schema\ValidationField;
use RoleModel;
use Vanilla\ApiUtils;
use Vanilla\Dashboard\Models\RecordStatusModel;
use Vanilla\DateFilterSchema;
use Vanilla\Forms\ApiFormChoices;
use Vanilla\Forms\FieldMatchConditional;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Forms\StaticFormChoices;
use Vanilla\Forum\Models\PostFieldModel;
use Vanilla\Forum\Models\PostTypeModel;
use Vanilla\Schema\RangeExpression;
use Vanilla\Utility\ArrayUtils;

/**
 * Parameter schema for the discussions API controller index.
 */
class DiscussionsApiIndexSchema extends Schema
{
    /**
     * Setup the schema.
     *
     * @param int $defaultLimit The default limit of items to be returned.
     */
    public function __construct(int $defaultLimit)
    {
        parent::__construct(
            $this->parseInternal([
                "discussionID?" => RangeExpression::createSchema([":int"])->setField("x-filter", [
                    "field" => "discussionID",
                ]),
                "categoryID:i?" => RangeExpression::createSchema([":int"])
                    ->setField("x-filter", [
                        "field" => "CategoryID",
                    ])
                    ->setField("x-control", self::getCategoryIDFormOptions())
                    ->setDescription("Filter by a range of categories."),
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
                        "field" => "DateInserted",
                        "processor" => [DateFilterSchema::class, "dateFilterField"],
                    ],
                ]),
                "slotType?" => [
                    "type" => "string",
                    // Daily, weekly, monthly, yearly, all time.
                    "enum" => ["d", "w", "m", "y", "a"],
                ],
                "dateUpdated?" => new DateFilterSchema([
                    "description" => "When the discussion was updated.",
                    "x-filter" => [
                        "field" => "DateUpdated",
                        "processor" => [DateFilterSchema::class, "dateFilterField"],
                    ],
                ]),
                "dateLastComment?" => new DateFilterSchema([
                    "description" => "When the last comment was posted.",
                    "x-filter" => [
                        "field" => "DateLastComment",
                        "processor" => [DateFilterSchema::class, "dateFilterField"],
                    ],
                ]),
                "tagID?" => RangeExpression::createSchema([":int"]),
                "type:a?" => [
                    "description" => "Filter by discussion type.",
                    "x-filter" => [
                        "field" => "Type",
                    ],
                    "items" => [
                        "type" => "string",
                    ],
                    "style" => "form",
                    "x-control" => SchemaForm::dropDown(
                        new FormOptions("Discussion Type", "Choose a specific type of discussions to display."),
                        new StaticFormChoices($this->discussionTypesEnumValues())
                    ),
                ],
                "postTypeID:a?" => [
                    "description" => "Filter by one or more postTypeIDs.",
                    "x-filter" => true,
                    "items" => [
                        "type" => "string",
                    ],
                    "style" => "form",
                ],
                "excludeHiddenCategories:b?" => [
                    "default" => false,
                    "description" =>
                        "Exclude discussions from categories that has the `HideAllDiscussions` option set to true.",
                ],
                "followed:b?" => [
                    "default" => false,
                    "description" =>
                        "Only fetch discussions from followed categories. Pinned discussions are mixed in.",
                ],
                "score?" => RangeExpression::createSchema([":int"])->setField("x-filter", [
                    "field" => "Score",
                ]),
                "pinned:b?" => [
                    "x-control" => SchemaForm::toggle(new FormOptions("Announcements", "Only fetch announcements.")),
                ],
                "pinOrder:s?" => [
                    "default" => "mixed",
                    "enum" => ["first", "mixed"],
                    "x-control" => SchemaForm::dropDown(
                        new FormOptions("Announcement Pinning", "Choose how announcements display."),
                        new StaticFormChoices([
                            "first" => "Announcements display first.",
                            "mixed" => "Announcements are displayed in the default sort order with other discussions.",
                        ])
                    ),
                ],
                "hasComments:b?",
                "dirtyRecords:b?",
                "siteSectionID:s?",
                "page:i?" => [
                    "description" => "Page number. See [Pagination](https://docs.vanillaforums.com/apiv2/#pagination).",
                    "default" => 1,
                    "minimum" => 1,
                ],
                "sort:s?" => [
                    "enum" => ApiUtils::sortEnum(
                        "dateLastComment",
                        "dateInserted",
                        "discussionID",
                        "score",
                        "hot",
                        \DiscussionModel::SORT_EXPIRIMENTAL_TRENDING
                    ),
                    "x-control" => self::getSortFormOptions(),
                ],
                "limit:i?" => [
                    "description" => "Desired number of items per page.",
                    "default" => $defaultLimit,
                    "minimum" => 1,
                    "maximum" => ApiUtils::getMaxLimit(),
                    "x-control" => self::getLimitFormOptions(),
                ],
                "insertUserID?" => [
                    "description" => "Filter by author.",
                    "style" => "form",
                    "type" => "array",
                    "items" => [
                        "type" => "integer",
                    ],
                    "x-filter" => [
                        "field" => "InsertUserID",
                    ],
                ],
                "insertUserRoleID?" => [
                    "type" => "array",
                    "items" => [
                        "type" => "integer",
                    ],
                    "style" => "form",
                    "x-filter" => [
                        "field" => "uri.RoleID",
                    ],
                ],
                "expand?" => \DiscussionExpandSchema::commonExpandDefinition(),
                "statusID:a?" => [
                    "items" => [
                        "type" => "integer",
                    ],
                    "x-search-scope" => true,
                    "x-filter" => [
                        "field" => "statusID",
                    ],
                ],
                "internalStatusID:a?" => [
                    "items" => [
                        "type" => "integer",
                    ],
                    "x-search-scope" => true,
                    "x-filter" => [
                        "field" => "internalStatusID",
                    ],
                ],
                "resolved:b?", // Legacy
                "reactionType:s?",
                "suggested:b?", //filter to the user interests
                "roleIDs:a?" => [
                    "items" => [
                        "type" => "integer",
                    ],
                ],
                "excerptLength:i?",
                "excludedCategoryIDs:a?" => [
                    "items" => ["type" => "integer"],
                ],
            ])
        );
        if (PostTypeModel::isPostTypesFeatureEnabled()) {
            $schema = Schema::parse(["postMeta:o?" => PostFieldModel::getPostMetaFilterSchema()]);
            $this->merge($schema);
        }
        $this->addValidator("insertUserRoleID", function ($data, $field) {
            RoleModel::roleViewValidator($data, $field);
        });
        $this->addValidator("", function (array $value, ValidationField $field) {
            if (!ArrayUtils::isArray($value)) {
                return $value;
            }
            $slotType = $value["slotType"] ?? null;
            $sort = $value["sort"] ?? null;
            $validSlotTypes = ["w", "d", "m"];
            if (
                str_contains($sort ?? "", \DiscussionModel::SORT_EXPIRIMENTAL_TRENDING) &&
                !in_array($slotType, ["d", "w", "m"])
            ) {
                $field->getValidation()->addError("sort", "badSlotType", [
                    "messageCode" => "The {sort} sort requires 'slotType' to be one of {validSlotTypes}.",
                    "sort" => $sort,
                    "validSlotTypes" => $validSlotTypes,
                ]);
            }
            return $value;
        });
    }

    /**
     * Get the schema without default values.
     *
     * @return Schema
     */
    public function withNoDefaults(): Schema
    {
        $schemaArray = $this->getSchemaArray();
        foreach ($schemaArray["properties"] as $key => &$val) {
            if (isset($val["default"])) {
                unset($val["default"]);
            }
        }
        return new Schema($schemaArray);
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
     * @param bool $multiple Is the dropdown control allowing multiple simultaneous choices?
     * @return array
     */
    public static function getCategoryIDFormOptions(
        FieldMatchConditional $conditional = null,
        bool $multiple = false
    ): array {
        return SchemaForm::dropDown(
            new FormOptions(t("Category"), t("Display records from this category.")),
            new ApiFormChoices(
                "/api/v2/categories/search?query=%s&limit=30",
                "/api/v2/categories/%s",
                "categoryID",
                "name"
            ),
            $conditional,
            $multiple
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
