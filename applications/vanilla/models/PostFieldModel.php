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
use Vanilla\Database\Operation\BooleanFieldProcessor;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Database\Operation\CurrentUserFieldProcessor;
use Vanilla\Database\Operation\JsonFieldProcessor;
use Vanilla\Models\PipelineModel;
use Vanilla\Utility\ArrayUtils;
use Vanilla\Web\ApiFilterMiddleware;

class PostFieldModel extends PipelineModel
{
    const DATA_TYPES = ["text", "boolean", "date", "number", "string[]", "number[]"];

    const FORM_TYPES = ["text", "text-multiline", "dropdown", "tokens", "checkbox", "date", "number"];

    const VISIBILITIES = ["public", "private", "internal"];

    /**
     * D.I.
     */
    public function __construct()
    {
        parent::__construct("postField");

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
    private function getMaxSort(string $postTypeID): int
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
     * {@inheritDoc}
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
        try {
            $this->database->beginTransaction();
            foreach ($sorts as $postFieldID => $sort) {
                $this->createSql()->update(
                    "postTypePostFieldJunction",
                    ["sort" => $sort],
                    ["postTypeID" => $postTypeID, "postFieldID" => $postFieldID]
                );
            }
            $this->database->commitTransaction();
        } catch (\Exception $e) {
            $this->database->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Get base query for querying post fields.
     *
     * @param array $where
     * @return \Gdn_SQLDriver
     */
    private function getWhereQuery(array $where = []): \Gdn_SQLDriver
    {
        $where = array_combine(
            array_map(fn($k) => str_contains($k, ".") || $k === "postTypeID" ? $k : "pf.$k", array_keys($where)),
            $where
        );
        $sql = $this->createSql()
            ->select("pf.*")
            ->select("ptpf.postTypeID", "JSON_ARRAYAGG", "postTypeIDs")
            ->from("postField pf")
            ->leftJoin("postTypePostFieldJunction ptpf", "ptpf.postFieldID = pf.postFieldID")
            ->groupBy("pf.postFieldID");

        if (isset($where["postTypeID"])) {
            $sql->join("postTypePostFieldJunction ptpf2", "ptpf2.postFieldID = pf.postFieldID")
                ->where("ptpf2.postTypeID", $where["postTypeID"])
                ->groupBy("ptpf2.postTypePostFieldJunctionID")
                ->orderBy("ptpf2.sort")
                ->select(["ptpf2.postTypeID", "ptpf2.sort"]);
            unset($where["postTypeID"]);
        }

        $sql->where($where);

        return $sql;
    }

    /**
     * Query post fields with filters.
     *
     * @param array $where Conditions for the select query.
     * @param array $options Keys should be constants from {@link Model::OPT_*}
     * @return array|null
     * @throws \Exception
     */
    public function getWhere(array $where, array $options = [])
    {
        $sql = $this->getWhereQuery($where);

        $sql->applyModelOptions($options);

        $rows = $sql->get()->resultArray();
        $this->normalizeRows($rows);
        return $rows;
    }

    /**
     * Query post field count with filters.
     *
     * @param array $where
     * @return int
     */
    public function getWhereCount(array $where): int
    {
        return $this->getWhereQuery($where)->getPagingCount("pf.postFieldID");
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
        }
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
            "dateInserted",
            "dateUpdated",
            "insertUserID",
            "updateUserID",
            "sort?",
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
     * Creates a Schema filter used for validating post field values.
     * This is implemented as a filter because it needs to know the postTypeID to look up field data.
     *
     * @return callable
     */
    public function createPostFieldsFilter(): callable
    {
        return function ($data, \Garden\Schema\ValidationField $field) {
            if (!ArrayUtils::isArray($data)) {
                return $data;
            }
            if (isset($data["postTypeID"])) {
                $schema = $this->valueSchema($data["postTypeID"]);
                $schema->setFlag(Schema::VALIDATE_EXTRA_PROPERTY_EXCEPTION, true);

                $postFields = $data["postFields"] ?? [];
                try {
                    $postFields = $schema->validate($postFields);
                } catch (ValidationException $e) {
                    $validation = $field->getValidation();
                    $validation->merge($e->getValidation(), "postFields");
                    throw new ValidationException($validation);
                }

                if (isset($data["postFields"])) {
                    // Only put the validated postFields if they were there in the first place.
                    $data["postFields"] = $postFields;
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

        $schemaArray = [];
        foreach ($rows as $row) {
            $type = self::getSchemaTypeFromDataType($row["dataType"]);
            $name = $row["postFieldID"] . ($row["isRequired"] ? "" : "?");

            $properties = [];
            $properties["type"] = $type;
            $properties["allowNull"] = !$row["isRequired"];
            if ($type === "array") {
                $properties["items"]["type"] = $row["dataType"] == "number[]" ? "integer" : "string";
                $properties["minItems"] = $row["isRequired"] ? 1 : 0;
            }

            $schemaArray[$name] = $properties;
        }

        return Schema::parse($schemaArray);
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
            ->column("dateInserted", "datetime")
            ->column("dateUpdated", "datetime", true)
            ->column("insertUserID", "int")
            ->column("updateUserID", "int", true)
            ->set(true);
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
