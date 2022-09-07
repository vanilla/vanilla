<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Models;

use Garden\Schema\Invalid;
use Garden\Schema\Schema;
use Garden\Schema\ValidationField;
use Vanilla\Database\Operation\BooleanFieldProcessor;
use Vanilla\Database\Operation\JsonFieldProcessor;
use Vanilla\Models\PipelineModel;

/**
 * Model for interacting with the profileFields table
 */
class ProfileFieldModel extends PipelineModel
{
    private const TABLE_NAME = "profileField";

    const DATA_TYPE_TEXT = "text";
    const DATA_TYPE_BOOL = "boolean";
    const DATA_TYPE_DATE = "date";
    const DATA_TYPE_NUMBER = "number";
    const DATA_TYPE_STRING_MUL = "string[]";
    const DATA_TYPE_NUMBER_MUL = "number[]";

    const DATA_TYPES = [
        self::DATA_TYPE_TEXT,
        self::DATA_TYPE_BOOL,
        self::DATA_TYPE_DATE,
        self::DATA_TYPE_NUMBER,
        self::DATA_TYPE_STRING_MUL,
        self::DATA_TYPE_NUMBER_MUL,
    ];

    const FORM_TYPE_TEXT = "text";
    const FORM_TYPE_TEXT_MULTILINE = "text-multiline";
    const FORM_TYPE_DROPDOWN = "dropdown";
    const FORM_TYPE_TOKENS = "tokens";
    const FORM_TYPE_CHECKBOX = "checkbox";
    const FORM_TYPE_DATE = "date";
    const FORM_TYPE_NUMBER = "number";

    const FORM_TYPES = [
        self::FORM_TYPE_TEXT,
        self::FORM_TYPE_TEXT_MULTILINE,
        self::FORM_TYPE_DROPDOWN,
        self::FORM_TYPE_TOKENS,
        self::FORM_TYPE_CHECKBOX,
        self::FORM_TYPE_DATE,
        self::FORM_TYPE_NUMBER,
    ];

    const VISIBILITIES = ["public", "private", "internal"];

    const MUTABILITIES = ["all", "restricted", "none"];

    /**
     * ProfileFieldModel constructor.
     */
    public function __construct()
    {
        parent::__construct(self::TABLE_NAME);
        $this->addPipelineProcessor(new JsonFieldProcessor(["displayOptions"]));
        $this->addPipelineProcessor(new BooleanFieldProcessor(["required"]));
    }

    /**
     * Provides a mapping of data type to valid form type values in the form of a datatype-indexed array of arrays
     *
     * @return string[][]
     */
    public static function getValidTypeMapping(): array
    {
        return [
            self::DATA_TYPE_TEXT => [self::FORM_TYPE_TEXT, self::FORM_TYPE_TEXT_MULTILINE, self::FORM_TYPE_DROPDOWN],
            self::DATA_TYPE_BOOL => [self::FORM_TYPE_CHECKBOX],
            self::DATA_TYPE_DATE => [self::FORM_TYPE_DATE],
            self::DATA_TYPE_NUMBER => [self::FORM_TYPE_DROPDOWN, self::FORM_TYPE_NUMBER],
            self::DATA_TYPE_STRING_MUL => [self::FORM_TYPE_TOKENS],
            self::DATA_TYPE_NUMBER_MUL => [self::FORM_TYPE_TOKENS],
        ];
    }

    /**
     * Structure the profileField table schema.
     *
     * @return void
     * @throws \Exception
     */
    public static function structure()
    {
        \Gdn::structure()
            ->table(self::TABLE_NAME)
            ->column("apiName", "varchar(50)", false, "primary")
            ->column("label", "varchar(100)", false, "unique")
            ->column("description", "varchar(100)")
            ->column("dataType", self::DATA_TYPES)
            ->column("formType", self::FORM_TYPES)
            ->column("visibility", self::VISIBILITIES)
            ->column("mutability", self::MUTABILITIES)
            ->column("displayOptions", "varchar(255)")
            ->column("required", "tinyint", 0)
            ->column("sort", "int")
            ->set();
    }

    /**
     * Gets the input query schema for listing profile field resources.
     *
     * @return Schema
     */
    public function getQuerySchema(): Schema
    {
        return Schema::parse([
            "dataType:s?" => ["x-filter" => true, "enum" => self::DATA_TYPES],
            "formType:s?" => ["x-filter" => true, "enum" => self::FORM_TYPES],
            "visibility:s?" => ["x-filter" => true, "enum" => self::VISIBILITIES],
            "mutability:s?" => ["x-filter" => true, "enum" => self::MUTABILITIES],
            "required:b?" => ["x-filter" => true],
        ]);
    }

    /**
     * Returns the output schema for a single profile field resource.
     *
     * @return Schema
     */
    public function getOutputSchema(): Schema
    {
        return Schema::parse([
            "apiName:s",
            "label:s",
            "description:s" => [
                "default" => "",
            ],
            "dataType:s" => ["enum" => self::DATA_TYPES],
            "formType:s" => ["enum" => self::FORM_TYPES],
            "visibility:s" => ["enum" => self::VISIBILITIES],
            "mutability:s" => ["enum" => self::MUTABILITIES],
            "displayOptions:o" => Schema::parse(["profiles:b", "userCards:b", "posts:b"]),
            "required:b",
            "sort:i",
        ]);
    }

    /**
     * Returns the input schema for validating data for updating a profile field resource.
     *
     * @return Schema
     */
    public function getPatchSchema(): Schema
    {
        return Schema::parse([
            "label:s?" => [
                "minLength" => 1,
            ],
            "description:s?",
            "formType:s?" => ["enum" => self::FORM_TYPES],
            "visibility:s?" => ["enum" => self::VISIBILITIES],
            "mutability:s?" => ["enum" => self::MUTABILITIES],
            "displayOptions:o?" => Schema::parse(["profiles:b", "userCards:b", "posts:b"]),
            "required:b?",
            "sort:i?",
        ]);
    }

    /**
     * Returns the full schema defining all the properties for a profile field record.
     * Includes custom validation for conditional properties.
     *
     * @return Schema
     */
    public function getFullSchema(): Schema
    {
        return Schema::parse([
            "apiName:s" => [
                "minLength" => 1,
            ],
            "label:s" => [
                "minLength" => 1,
            ],
            "description:s" => [
                "default" => "",
            ],
            "dataType:s" => ["enum" => self::DATA_TYPES],
            "formType:s" => ["enum" => self::FORM_TYPES],
            "visibility:s" => ["enum" => self::VISIBILITIES],
            "mutability:s" => ["enum" => self::MUTABILITIES],
            "displayOptions:o" => Schema::parse(["profiles:b", "userCards:b", "posts:b"]),
            "required:b" => ["default" => false],
            "sort:i?",
        ])
            ->addValidator("", [$this, "validateTypeFields"])
            ->addValidator("", [$this, "validateRequired"])
            ->addValidator("apiName", function (string $apiName, ValidationField $field) {
                if (preg_match("/[.\s]/", $apiName)) {
                    $field->addError("Whitespace and periods are not allowed");
                }
            });
    }

    /**
     * Update sort values for records using a apiName => sort mapping.
     *
     * @param array<string,int> $sorts Key-value mapping of apiName => sort
     * @return void
     * @throws \Exception
     */
    public function updateSorts(array $sorts)
    {
        try {
            $this->database->beginTransaction();
            foreach ($sorts as $apiName => $sort) {
                $this->update(["sort" => $sort], ["apiName" => $apiName]);
            }
            $this->database->commitTransaction();
        } catch (\Exception $e) {
            $this->database->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Inserts a record into the profileFields table with a default sort value.
     *
     * @param array $set
     * @param array $options
     * @return mixed
     * @throws \Exception
     */
    public function insert(array $set, array $options = [])
    {
        if (!isset($set["sort"])) {
            // By default, set the sort value to the max sort value + 1
            $max = (int) $this->createSql()
                ->select("max(sort) as max")
                ->from(self::TABLE_NAME)
                ->get()
                ->value("max");
            $set["sort"] = $max + 1;
        }
        return parent::insert($set, $options);
    }

    /**
     * Check that the data type is compatible with the form type
     *
     * @param array $data
     * @param ValidationField $field
     * @return void
     */
    public function validateTypeFields(array $data, ValidationField $field)
    {
        $dataType = $data["dataType"] ?? null;
        $formType = $data["formType"] ?? null;

        if (is_null($dataType) || is_null($formType)) {
            return;
        }

        if ($dataType instanceof Invalid || $formType instanceof Invalid) {
            // If any of these fields are already known to be invalid, we can skip this
            return;
        }

        $typeMapping = self::getValidTypeMapping();
        $validFormTypes = $typeMapping[$dataType] ?? [];
        if (!in_array($formType, $validFormTypes, true)) {
            $field->addError(
                sprintf("For dataType %s, formType must be one of: (%s)", $dataType, implode("|", $validFormTypes))
            );
        }
    }

    /**
     * Check that the conditions allow the 'required' setting to be enabled
     *
     * @param array $data
     * @param ValidationField $field
     * @return void
     */
    public function validateRequired(array $data, ValidationField $field)
    {
        $required = $data["required"] ?? null;
        $visibility = $data["visibility"] ?? null;
        $mutability = $data["mutability"] ?? null;

        if (is_null($required) || is_null($visibility) || is_null($mutability)) {
            return;
        }

        if ($required instanceof Invalid || $visibility instanceof Invalid || $mutability instanceof Invalid) {
            // If any of these fields are already known to be invalid, we can skip this
            return;
        }

        if ($required && !(in_array($visibility, ["public", "private"]) && $mutability === "all")) {
            $field->addError(
                "To mark a field as required, visibility must be public or private and mutability must be all"
            );
        }
    }
}
