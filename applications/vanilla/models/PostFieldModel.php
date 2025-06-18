<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Forum\Models;

use Garden\Schema\Invalid;
use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Garden\Schema\ValidationField;
use Garden\Web\Exception\ForbiddenException;
use Vanilla\Database\Operation\BooleanFieldProcessor;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Database\Operation\CurrentUserFieldProcessor;
use Vanilla\Database\Operation\JsonFieldProcessor;
use Vanilla\DateFilterSchema;
use Vanilla\Models\FullRecordCacheModel;
use Vanilla\Utility\ArrayUtils;
use Vanilla\Web\ApiFilterMiddleware;

class PostFieldModel extends FullRecordCacheModel
{
    const PUBLIC_DATA_FIELD_ID = "public-data";

    const PRIVATE_DATA_FIELD_ID = "private-data";

    const INTERNAL_DATA_FIELD_ID = "internal-data";

    const SPECIAL_CATCH_ALL_FIELDS = [
        self::PUBLIC_DATA_FIELD_ID,
        self::PRIVATE_DATA_FIELD_ID,
        self::INTERNAL_DATA_FIELD_ID,
    ];

    const DATA_TYPES = [
        "TEXT" => "text",
        "BOOLEAN" => "boolean",
        "DATE" => "date",
        "NUMBER" => "number",
        "STRING_MUL" => "string[]",
        "NUMBER_MUL" => "number[]",
    ];

    const FORM_TYPES = [
        "TEXT" => "text",
        "TEXT_MULTILINE" => "text-multiline",
        "DROPDOWN" => "dropdown",
        "TOKENS" => "tokens",
        "CHECKBOX" => "checkbox",
        "DATE" => "date",
        "NUMBER" => "number",
    ];

    const VISIBILITIES = ["public", "private", "internal"];

    /**
     * D.I.
     */
    public function __construct(\Gdn_Cache $cache)
    {
        parent::__construct("postField", $cache, [
            \Gdn_Cache::FEATURE_EXPIRY => 60 * 60, // 1 hour.
        ]);

        $this->addPipelineProcessor(new CurrentDateFieldProcessor(["dateInserted", "dateUpdated"], ["dateUpdated"]));
        $this->addPipelineProcessor(new BooleanFieldProcessor(["isRequired", "isActive"]));
        $userProcessor = new CurrentUserFieldProcessor(\Gdn::session());
        $userProcessor->setInsertFields(["insertUserID", "updateUserID"])->setUpdateFields(["updateUserID"]);
        $this->addPipelineProcessor($userProcessor);
        $this->addPipelineProcessor(new JsonFieldProcessor(["displayOptions"]));
        $this->addPipelineProcessor(new JsonFieldProcessor(["dropdownOptions"], 0));
    }

    /**
     * Get the max sort value for post fields of the given postTypeID.
     *
     * @param string $postTypeID
     * @return int
     * @throws \Exception
     */
    public function getMaxSort(string $postTypeID): int
    {
        return (int) $this->createSql()
            ->select("max(sort) as sortMax")
            ->from("postTypePostFieldJunction")
            ->where("postTypeID", $postTypeID)
            ->get()
            ->value("sortMax");
    }

    /**
     * Add a row with a default sort value if not provided.
     *
     * {@inheritdoc}
     */
    public function insert(array $set, array $options = [])
    {
        $result = parent::insert($set, $options);

        if (isset($set["postTypeID"])) {
            $this->createSql()->insert("postTypePostFieldJunction", [
                "postTypeID" => $set["postTypeID"],
                "postFieldID" => $set["postFieldID"],
                "sort" => $this->getMaxSort($set["postTypeID"]) + 1,
            ]);
            $this->clearCache();
        }
        return $result;
    }

    /**
     * Update sort values for records using a postFieldID => sort mapping.
     *
     * @param array<int,int> $sorts Key-value mapping of postFieldID => sort
     * @return void
     * @throws \Exception
     */
    public function updateSorts(string $postTypeID, array $sorts): void
    {
        $this->database->runWithTransaction(function () use ($sorts, $postTypeID) {
            foreach ($sorts as $postFieldID => $sort) {
                $this->createSql()->put(
                    "postTypePostFieldJunction",
                    ["sort" => $sort],
                    ["postTypeID" => $postTypeID, "postFieldID" => $postFieldID]
                );
            }
        });
        $this->clearCache();
    }

    /**
     * Get base query for querying post fields.
     *
     * @param array $where
     * @return \Gdn_SQLDriver
     */
    private function getBaseQuery(array $where = []): \Gdn_SQLDriver
    {
        $where = array_combine(array_map(fn($k) => str_contains($k, ".") ? $k : "pf.$k", array_keys($where)), $where);

        return $this->createSql()
            ->select("pf.*")
            ->from("postField pf")
            ->leftJoin("postTypePostFieldJunction ptpf", "ptpf.postFieldID = pf.postFieldID")
            ->where($where);
    }

    /**
     * Query post fields with filters.
     *
     * @param array $where Conditions for the select query.
     * @param array $options Keys should be constants from {@link Model::OPT_*}
     * @return array|null
     * @throws \Exception
     */
    public function getWhere(array $where = [], array $options = [])
    {
        $postTypeID = $where["postTypeID"] ?? null;
        unset($where["postTypeID"]);

        $rows = $this->modelCache->getCachedOrHydrate([$where, $options, __FUNCTION__], function ($where, $options) {
            return $this->getBaseQuery($where)
                ->select("ptpf.postTypeID", "JSON_ARRAYAGG", "postTypeIDs")
                ->groupBy("pf.postFieldID")
                ->applyModelOptions($options)
                ->get()
                ->resultArray();
        });

        $this->normalizeRows($rows);
        return $this->filterRows($rows, $postTypeID);
    }

    /**
     * Returns cartesian product of post fields and post types.
     *
     * @param array $where
     * @return array
     * @throws \Exception
     */
    public function getPostFieldsByPostTypes(array $where = []): array
    {
        $rows = $this->modelCache->getCachedOrHydrate([$where, __FUNCTION__], function ($where) {
            return $this->getBaseQuery($where)
                ->select(["ptpf.sort", "ptpf.postTypeID"])
                ->get()
                ->resultArray();
        });
        $this->normalizeRows($rows);
        return $rows;
    }

    /**
     * @param array $rows
     * @return void
     */
    private function normalizeRows(array &$rows): void
    {
        foreach ($rows as &$row) {
            $row["postTypeIDs"] = isset($row["postTypeIDs"])
                ? array_values(array_filter(json_decode($row["postTypeIDs"])))
                : null;
            if (is_string($row["dropdownOptions"])) {
                $row["dropdownOptions"] = json_decode($row["dropdownOptions"]);
            }
            $row["isRequired"] = (bool) $row["isRequired"];
            $row["isActive"] = (bool) $row["isActive"];
        }
    }

    /**
     * Filters normalized rows by some criteria.
     *
     * @param array $rows
     * @param array|null $postTypeID
     * @return array
     */
    private function filterRows(array $rows, array|string|null $postTypeID): array
    {
        if (empty($postTypeID)) {
            return $rows;
        }
        if (is_string($postTypeID)) {
            $postTypeID = [$postTypeID];
        }
        return array_values(array_filter($rows, fn($row) => !empty(array_intersect($row["postTypeIDs"], $postTypeID))));
    }

    /**
     * Returns the schema for displaying post fields.
     *
     * @return Schema
     */
    public function outputSchema(): Schema
    {
        return Schema::parse([
            "postFieldID",
            "postTypeIDs",
            "label",
            "description",
            "dataType",
            "formType",
            "visibility",
            "displayOptions",
            "dropdownOptions",
            "isRequired",
            "isActive",
            "isSystemHidden",
            "dateInserted",
            "dateUpdated",
            "insertUserID",
            "updateUserID",
        ]);
    }

    /**
     * Returns the schema for validating query string parameters on the index endpoint.
     *
     * @return Schema
     */
    public function indexSchema(): Schema
    {
        return Schema::parse([
            "postTypeID:a?" => [
                "description" => "Filter post fields that belong to one or more post types.",
                "x-filter" => true,
                "items" => [
                    "type" => "string",
                ],
                "style" => "form",
            ],
            "dataType:s?" => ["enum" => PostFieldModel::DATA_TYPES, "x-filter" => true],
            "formType:s?" => ["enum" => PostFieldModel::FORM_TYPES, "x-filter" => true],
            "visibility:s?" => ["x-filter" => true],
            "isRequired:b?" => ["x-filter" => true],
            "isActive:b?" => ["x-filter" => true],
        ]);
    }

    /**
     * Returns the schema for creating post fields.
     *
     * @return Schema
     */
    public function postSchema(): Schema
    {
        $schema = $this->commonSchema()
            ->merge(
                Schema::parse([
                    "postFieldID:s",
                    "postTypeID:s?",
                    "dataType:s" => ["enum" => PostFieldModel::DATA_TYPES],
                    "formType" => ["enum" => PostFieldModel::FORM_TYPES],
                ])
            )
            ->addValidator("postFieldID", function ($postFieldID, ValidationField $field) {
                if (preg_match("#[.\s/]#", $postFieldID)) {
                    $field->addError("Whitespace, slashes, periods and uppercase letters are not allowed");
                    return Invalid::value();
                }
                $forbiddenNames = \Gdn::getContainer()
                    ->get(ApiFilterMiddleware::class)
                    ->getBlacklistFields();
                if (in_array(strtolower($postFieldID), $forbiddenNames)) {
                    $field->addError("The following values are not allowed: " . implode(",", $forbiddenNames));
                    return Invalid::value();
                }
                return true;
            })
            ->addValidator("postFieldID", function ($postFieldID, ValidationField $field) {
                $where = ["postFieldID" => $postFieldID];

                $count = $this->createSql()->getCount($this->getTable(), $where);
                if ($count !== 0) {
                    $field->addError("This identifier is already in use. Use a unique identifier.");
                    return Invalid::value();
                }
                return true;
            })
            ->addValidator("postTypeID", function ($value, ValidationField $field) {
                $postType = \Gdn::sql()->getCount("postType", ["postTypeID" => $value]);

                if ($postType === 0) {
                    $field->addError("The post type does not exist", 404);
                    return Invalid::value();
                }
                return true;
            })
            ->addValidator("", function ($value, ValidationField $field) {
                $validFormTypes = self::getValidFormTypes($value["dataType"]);
                if (!in_array($value["formType"], $validFormTypes)) {
                    $field->addError(
                        "The dataType `{$value["dataType"]}` is not compatible with `{$value["formType"]}`. Valid formType values are: " .
                            implode("|", $validFormTypes)
                    );
                    return Invalid::value();
                }
                return true;
            })
            ->addFilter("", $this->createDropdownOptionsFilter());

        return $schema;
    }

    /**
     * Returns the schema for updating post fields.
     *
     * @return Schema
     */
    public function patchSchema(array $existingPostField): Schema
    {
        return $this->commonSchema()
            ->addFilter("", function ($data) use ($existingPostField) {
                // Add current record data because some properties require validating against existing properties.

                if (isset($data["dropdownOptions"])) {
                    // Replace instead of merging dropdownOptions
                    unset($existingPostField["dropdownOptions"]);
                }

                return ArrayUtils::mergeRecursive($existingPostField, $data);
            })
            ->addFilter("", $this->createDropdownOptionsFilter());
    }

    /**
     * Return a schema for validating post meta filters.
     *
     * @return Schema
     */
    public static function getPostMetaFilterSchema(): Schema
    {
        $availableFields = self::getAvailableViewFieldsForCurrentSessionUser();
        $schemaArray = [];
        foreach ($availableFields as $field) {
            $formType = $field["formType"];
            $dataType = $field["dataType"];
            $postField = $field["postFieldID"];

            switch ([$dataType, $formType]) {
                case [self::DATA_TYPES["BOOLEAN"], self::FORM_TYPES["CHECKBOX"]]:
                    $schemaArray["$postField?"] = ["type" => "boolean", "example" => "true|false"];
                    break;
                case [self::DATA_TYPES["TEXT"], self::FORM_TYPES["DROPDOWN"]]:
                case [self::DATA_TYPES["STRING_MUL"], self::FORM_TYPES["TOKENS"]]:
                    $schemaArray["$postField:a?"] = [
                        "items" => ["type" => "string", "enum" => $field["dropdownOptions"] ?? [], "minItems" => 1],
                        "style" => "form",
                        "example" => "option1,option2,option3",
                    ];
                    break;
                case [self::DATA_TYPES["NUMBER"], self::FORM_TYPES["NUMBER"]]:
                    $schemaArray["$postField:i?"] = [];
                    break;
                case [self::DATA_TYPES["DATE"], self::FORM_TYPES["DATE"]]:
                    $schemaArray["$postField?"] = new DateFilterSchema();
                    break;
                case [self::DATA_TYPES["TEXT"], self::FORM_TYPES["TEXT"]]:
                case [self::DATA_TYPES["TEXT"], self::FORM_TYPES["TEXT_MULTILINE"]]:
                    $schemaArray["$postField:s?"] = ["minLength" => 1];
            }
        }

        return Schema::parse($schemaArray)->addFilter("", function ($metaData, ValidationField $field) use (
            $availableFields
        ) {
            $postFieldIDs = array_column($availableFields, "postFieldID");
            $invalidFields = array_diff(array_keys($metaData), $postFieldIDs);
            if (!empty($invalidFields)) {
                throw new ForbiddenException(
                    "You don't have permission to access the following fields: " . implode(", ", $invalidFields)
                );
            }
            return $metaData;
        });
    }

    /**
     * Get the available view fields for the current session user.
     *
     * @return array
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     */
    public static function getAvailableViewFieldsForCurrentSessionUser(): array
    {
        $schemaArray = [];
        $where = ["isActive" => 1];
        // check user permission for visibility of the field
        $visibility = ["public"];
        if (\Gdn::session()->checkPermission("personalInfo.view")) {
            $visibility[] = "private";
        }
        if (\Gdn::session()->checkPermission("internalInfo.view")) {
            $visibility[] = "internal";
        }
        if (count($visibility) !== 3) {
            $where["visibility"] = $visibility;
        }
        $postTypeModel = \Gdn::getContainer()->get(PostFieldModel::class);
        return $postTypeModel->select($where, [
            self::OPT_SELECT => ["postFieldID", "formType", "dataType", "displayOptions", "dropdownOptions"],
        ]);
    }

    /**
     * Add filter to remove dropdownOptions if the formType doesn't support them. And validate individual options.
     * @return callable
     */
    public function createDropdownOptionsFilter(): callable
    {
        return function ($data, \Garden\Schema\ValidationField $field) {
            if (!ArrayUtils::isArray($data)) {
                return $data;
            }
            if (!in_array($data["formType"], ["dropdown", "tokens"])) {
                $data["dropdownOptions"] = null;
            }

            if (isset($data["dropdownOptions"])) {
                foreach ($data["dropdownOptions"] as $dropdownOption) {
                    if ($data["dataType"] === "string[]" && !is_string($dropdownOption)) {
                        $field->addError("Dropdown options can only contain strings");
                    }
                    if ($data["dataType"] === "number[]" && !is_numeric($dropdownOption)) {
                        $field->addError("Dropdown options can only contain numbers");
                    }
                }
            }

            return $data;
        };
    }

    /**
     * Creates a Schema filter used for validating post meta values.
     * This is implemented as a filter because it needs to know the postTypeID to look up field data.
     *
     * @return callable
     */
    public function createPostMetaFilter(): callable
    {
        return function ($data, \Garden\Schema\ValidationField $field) {
            if (!ArrayUtils::isArray($data)) {
                return $data;
            }
            if (isset($data["postTypeID"])) {
                $schema = $this->valueSchema($data["postTypeID"]);
                $schema->setFlag(Schema::VALIDATE_EXTRA_PROPERTY_EXCEPTION, true);

                $postMeta = $data["postMeta"] ?? [];
                try {
                    $postMeta = $schema->validate($postMeta);
                } catch (ValidationException $e) {
                    $validation = $field->getValidation();
                    $validation->merge($e->getValidation(), "postMeta");
                    throw new ValidationException($validation);
                }

                if (isset($data["postMeta"])) {
                    // Only put the validated postMeta if they were there in the first place.
                    $data["postMeta"] = $postMeta;
                }
            }
            return $data;
        };
    }

    /**
     * Returns a common schema for both post and patch endpoints.
     *
     * @return Schema
     */
    private function commonSchema(): Schema
    {
        $schema = Schema::parse([
            "label:s",
            "description:s?",
            "visibility" => ["enum" => PostFieldModel::VISIBILITIES],
            "dropdownOptions:a|n?",
            "isRequired:b" => ["default" => false],
            "isActive:b" => ["default" => true],
        ])->addValidator("", function ($data, ValidationField $field) {
            if ($data["isRequired"] && $data["visibility"] === "internal") {
                $field->addError("To designate a field as required, visibility must be public or private.");
                return Invalid::value();
            }
            return true;
        });
        return $schema;
    }

    /**
     * Returns an array of valid formType values for the given dataType.
     *
     * @param string $dataType
     * @return string[]|null
     */
    private static function getValidFormTypes(string $dataType)
    {
        return match ($dataType) {
            "text" => ["text", "text-multiline", "dropdown"],
            "boolean" => ["checkbox"],
            "date" => ["date"],
            "number" => ["dropdown", "number"],
            "string[]", "number[]" => ["tokens", "dropdown"],
            default => null,
        };
    }

    /**
     * Returns the Schema `type` string for the given dataType.
     *
     * @param string $dataType
     * @return string
     */
    private static function getSchemaTypeFromDataType(string $dataType): string
    {
        return match ($dataType) {
            "boolean" => "boolean",
            "date" => "datetime",
            "number" => "integer",
            "string[]", "number[]" => "array",
            default => "string",
        };
    }

    /**
     * Return the schema for validating post field values.
     *
     * @param string $postTypeID
     * @return Schema
     */
    public function valueSchema(string $postTypeID): Schema
    {
        $rows = $this->getWhere(["postTypeID" => $postTypeID, "isActive" => true]);
        $tokenFields = [];

        $schemaArray = [];
        foreach ($rows as $row) {
            $type = self::getSchemaTypeFromDataType($row["dataType"]);
            $name = $row["postFieldID"] . ($row["isRequired"] ? "" : "?");

            $properties = [];
            $properties["type"] = $type;
            $properties["allowNull"] = !$row["isRequired"];
            if ($type === "array") {
                $properties["items"]["type"] = $row["dataType"] == "number[]" ? "integer" : "string";
                $properties["minItems"] = 1;
            }
            if ($row["formType"] == self::FORM_TYPES["DROPDOWN"]) {
                $properties["enum"] = $row["dropdownOptions"] ?? [];
            }
            if ($row["formType"] == self::FORM_TYPES["TOKENS"]) {
                $tokenFields[$row["postFieldID"]] = $row["dropdownOptions"] ?? [];
            }

            $schemaArray[$name] = $properties;
        }

        return Schema::parse($schemaArray)->addValidator("", function ($data, ValidationField $field) use (
            $tokenFields
        ) {
            foreach ($tokenFields as $postFieldID => $options) {
                if (isset($data[$postFieldID])) {
                    $tokens = $data[$postFieldID];
                    foreach ($tokens as $token) {
                        if (!in_array($token, $options)) {
                            $field->addError("postMeta.$postFieldID must be one of: " . implode(", ", $options));
                            return Invalid::value();
                        }
                    }
                }
            }
            return true;
        });
    }

    /**
     * Structures the postField table.
     *
     * @param \Gdn_MySQLStructure $structure
     * @return void
     */
    public static function structure(\Gdn_DatabaseStructure $structure)
    {
        $structure
            ->table("postTypePostFieldJunction")
            ->primaryKey("postTypePostFieldJunctionID")
            ->column("postTypeID", "varchar(100)", keyType: "index")
            ->column("postFieldID", "varchar(100)", keyType: "index")
            ->column("sort", "int", 0)
            ->set();

        $structure->table("postField");

        // We previously had postTypeID as part of the primary key.
        // This block removes it and migrates the associations to a junction table.
        if ($structure->tableExists() && $structure->columnExists("postTypeID")) {
            // We need to keep the postTypeID here. We need to recreate the primary key before dropping it.
            $structure->column("postTypeID", "varchar(100)", keyType: "primary");
            self::migratePostFieldsWithPostTypeID($structure);

            // We need to drop and add the primary key index in one statement for in place table alters.
            $structure->modifyPrimaryKeyIndex(["postFieldID"]);
            $structure->dropColumn("postTypeID");
        }

        $structure
            ->table("postField")
            ->column("postFieldID", "varchar(100)", keyType: "primary")
            ->column("label", "varchar(100)")
            ->column("description", "varchar(500)", true)
            ->column("dataType", self::DATA_TYPES)
            ->column("formType", self::FORM_TYPES)
            ->column("visibility", self::VISIBILITIES)
            ->column("displayOptions", "json", true)
            ->column("dropdownOptions", "json", true)
            ->column("isRequired", "tinyint", 0)
            ->column("isActive", "tinyint", 0)
            ->column("isSystemHidden", "tinyint", 0)
            ->column("dateInserted", "datetime")
            ->column("dateUpdated", "datetime", true)
            ->column("insertUserID", "int")
            ->column("updateUserID", "int", true)
            ->set(true);

        // Add default post types.
        if (!$structure->CaptureOnly) {
            self::createInitialPostFields();
        }
    }

    /**
     * Create built-in post fields.
     *
     * @return void
     */
    private static function createInitialPostFields(): void
    {
        $postFieldModel = \Gdn::getContainer()->get(PostFieldModel::class);
        $postFieldModel->createInitialPostField([
            "postFieldID" => self::PUBLIC_DATA_FIELD_ID,
            "label" => "Public Data",
            "description" => "Public post field data from moved other post will appear here",
            "dataType" => "text",
            "formType" => "text-multiline",
            "visibility" => "public",
            "isActive" => true,
            "isSystemHidden" => true,
        ]);
        $postFieldModel->createInitialPostField([
            "postFieldID" => self::PRIVATE_DATA_FIELD_ID,
            "label" => "Private Data",
            "description" => "Private post field data from moved other post will appear here",
            "dataType" => "text",
            "formType" => "text-multiline",
            "visibility" => "private",
            "isActive" => true,
            "isSystemHidden" => true,
        ]);
        $postFieldModel->createInitialPostField([
            "postFieldID" => self::INTERNAL_DATA_FIELD_ID,
            "label" => "Internal Data",
            "description" => "Internal post field data from moved other post will appear here",
            "dataType" => "text",
            "formType" => "text-multiline",
            "visibility" => "internal",
            "isActive" => true,
            "isSystemHidden" => true,
        ]);
    }

    /**
     * Create or update a built-in post field.
     *
     * @param array $row
     * @return void
     * @throws \Exception
     */
    public function createInitialPostField(array $row): void
    {
        $hasExisting = $this->createSql()->getCount($this->getTable(), ["postFieldID" => $row["postFieldID"]]) > 0;
        if ($hasExisting) {
            $this->update($row, ["postFieldID" => $row["postFieldID"]]);
        } else {
            $this->insert($row);
        }
    }

    /**
     * Migrate post field associations from older table structure.
     *
     * @param \Gdn_DatabaseStructure $structure
     * @return void
     * @throws \Exception
     */
    private static function migratePostFieldsWithPostTypeID(\Gdn_DatabaseStructure $structure): void
    {
        if (!$structure->CaptureOnly) {
            $sql = \Gdn::database()->createSql();
            $existingRows = $sql
                ->select(["postTypeID", "postFieldID", "sort"])
                ->from("postField")
                ->where("postTypeID<>", "")
                ->get()
                ->resultArray();

            foreach ($existingRows as $existingRow) {
                $countExists = $sql->getCount("postTypePostFieldJunction", [
                    "postTypeID" => $existingRow["postTypeID"],
                    "postFieldID" => $existingRow["postFieldID"],
                ]);
                if ($countExists == 0) {
                    // Need to force an update on post field ID to guarantee uniqueness when the schema changes.
                    $renamedPostField = $existingRow["postFieldID"] . "-" . bin2hex(random_bytes(5));
                    $sql->insert("postTypePostFieldJunction", ["postFieldID" => $renamedPostField] + $existingRow);

                    $sql->update(
                        "postField",
                        ["postFieldID" => $renamedPostField],
                        ["postTypeID" => $existingRow["postTypeID"], "postFieldID" => $existingRow["postFieldID"]]
                    )->put();
                }
            }
        }
    }
}
