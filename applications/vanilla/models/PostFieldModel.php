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
    public function __construct(private PostTypeModel $postTypeModel)
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
     * Get current max sort value from the table
     *
     * @return int
     * @throws \Exception
     */
    private function getMaxSort(): int
    {
        return (int) $this->createSql()
            ->select("max(sort) as sortMax")
            ->from($this->getTable())
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
        if (!isset($set["sort"])) {
            // By default, set the sort value to the max sort value + 1;
            $set["sort"] = $this->getMaxSort() + 1;
        }
        return parent::insert($set, $options);
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
                $this->update(["sort" => $sort], ["postTypeID" => $postTypeID, "postFieldID" => $postFieldID]);
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
    private function getWhereQuery(array $where)
    {
        $sql = $this->createSql()->from($this->getTable());

        $sql->where($where);

        return $sql;
    }

    /**
     * Query post fields with filters.
     *
     * @param array $where
     * @param array $options
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
        return $this->getWhereQuery($where)->getPagingCount("postFieldID");
    }

    /**
     * @param array $rows
     * @return void
     */
    private function normalizeRows(array &$rows): void
    {
        foreach ($rows as &$row) {
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
            "postTypeID",
            "label",
            "description",
            "dataType",
            "formType",
            "visibility",
            "displayOptions",
            "dropdownOptions",
            "isRequired",
            "isActive",
            "sort",
            "dateInserted",
            "dateUpdated",
            "insertUserID",
            "updateUserID",
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
                    "postTypeID:s",
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
            })
            ->addValidator("", function ($data, ValidationField $field) {
                $where = ["postFieldID" => $data["postFieldID"], "postTypeID" => $data["postTypeID"]];

                $count = $this->createSql()->getCount($this->getTable(), $where);
                if ($count !== 0) {
                    $field->addError("This identifier is already in use. Use a unique identifier.");
                    return Invalid::value();
                }
                return true;
            })
            ->addValidator("postTypeID", function ($value, ValidationField $field) {
                $postType = $this->postTypeModel->select(["postTypeID" => $value], [self::OPT_LIMIT => 1]);

                if (empty($postType)) {
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
        $rows = $this->select(["postTypeID" => $postTypeID, "isActive" => true]);

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
            ->table("postField")
            ->column("postFieldID", "varchar(100)", keyType: "primary")
            ->column("postTypeID", "varchar(100)", keyType: "primary")
            ->column("label", "varchar(100)")
            ->column("description", "varchar(500)", true)
            ->column("dataType", self::DATA_TYPES)
            ->column("formType", self::FORM_TYPES)
            ->column("visibility", self::VISIBILITIES)
            ->column("displayOptions", "json", true)
            ->column("dropdownOptions", "json", true)
            ->column("isRequired", "tinyint", 0)
            ->column("isActive", "tinyint", 0)
            ->column("sort", "tinyint", 0)
            ->column("dateInserted", "datetime")
            ->column("dateUpdated", "datetime", true)
            ->column("insertUserID", "int")
            ->column("updateUserID", "int", true)
            ->set(true);
    }
}
