<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Models;

use DateTimeZone;
use Garden\Schema\Invalid;
use Garden\Schema\Schema;
use Garden\Schema\ValidationField;
use Gdn;
use Vanilla\Attributes;
use Vanilla\Database\Operation\BooleanFieldProcessor;
use Vanilla\Database\Operation\JsonFieldProcessor;
use Vanilla\Models\FullRecordCacheModel;

/**
 * Model for interacting with the profileFields table
 */
class ProfileFieldModel extends FullRecordCacheModel
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

    const REGISTRATION_REQUIRED = "required";
    const REGISTRATION_OPTIONAL = "optional";
    const REGISTRATION_HIDDEN = "hidden";

    const REGISTRATION_OPTIONS = [self::REGISTRATION_REQUIRED, self::REGISTRATION_OPTIONAL, self::REGISTRATION_HIDDEN];

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
        $cache = gdn::getContainer()->get(\Gdn_Cache::class);
        parent::__construct(self::TABLE_NAME, $cache, [
            \Gdn_Cache::FEATURE_EXPIRY => 3600, // 1 hour.
        ]);
        $this->addPipelineProcessor(new JsonFieldProcessor(["displayOptions", "dropdownOptions"]));
        $this->addPipelineProcessor(new BooleanFieldProcessor(["enabled"]));
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
            self::DATA_TYPE_STRING_MUL => [self::FORM_TYPE_TOKENS, self::FORM_TYPE_DROPDOWN],
            self::DATA_TYPE_NUMBER_MUL => [self::FORM_TYPE_TOKENS, self::FORM_TYPE_DROPDOWN],
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
            ->column("displayOptions", "mediumtext")
            ->column("dropdownOptions", "mediumtext", true)
            ->column("registrationOptions", self::REGISTRATION_OPTIONS, self::REGISTRATION_HIDDEN)
            ->column("sort", "int")
            ->column("enabled", "tinyint", 1)
            ->set();

        $requiredColumnExists =
            Gdn::structure()->tableExists(self::TABLE_NAME) &&
            Gdn::structure()
                ->table(self::TABLE_NAME)
                ->columnExists("required");
        if ($requiredColumnExists) {
            Gdn::database()
                ->sql()
                ->update(self::TABLE_NAME)
                ->set("registrationOptions", self::REGISTRATION_HIDDEN)
                ->where("required", 0)
                ->put();
            Gdn::database()
                ->sql()
                ->update(self::TABLE_NAME)
                ->set("registrationOptions", self::REGISTRATION_REQUIRED)
                ->where("required", 1)
                ->put();
            Gdn::structure()
                ->table(self::TABLE_NAME)
                ->dropColumn("required");
        }
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
            "registrationOptions:s?" => ["x-filter" => true, "enum" => self::REGISTRATION_OPTIONS],
            "enabled:b?" => ["x-filter" => true],
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
            "dropdownOptions:a|n",
            "visibility:s" => ["enum" => self::VISIBILITIES],
            "mutability:s" => ["enum" => self::MUTABILITIES],
            "displayOptions:o" => Schema::parse(["profiles:b", "userCards:b", "posts:b"]),
            "registrationOptions:s" => ["x-filter" => true, "enum" => self::REGISTRATION_OPTIONS],
            "sort:i",
            "enabled:b",
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
            "dropdownOptions:a|n?",
            "visibility:s?" => ["enum" => self::VISIBILITIES],
            "mutability:s?" => ["enum" => self::MUTABILITIES],
            "displayOptions:o?" => Schema::parse(["profiles:b", "userCards:b", "posts:b"]),
            "registrationOptions:s?" => ["x-filter" => true, "enum" => self::REGISTRATION_OPTIONS],
            "sort:i?",
            "enabled:b?",
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
            "dropdownOptions:a|n?",
            "visibility:s" => ["enum" => self::VISIBILITIES],
            "mutability:s" => ["enum" => self::MUTABILITIES],
            "displayOptions:o" => Schema::parse(["profiles:b", "userCards:b", "posts:b"]),
            "registrationOptions:s" => ["enum" => self::REGISTRATION_OPTIONS, "default" => self::REGISTRATION_HIDDEN],
            "sort:i?",
            "enabled:b?",
        ])
            ->addValidator("", [$this, "validateDropdownOptions"])
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

    public function validateDropdownOptions(array $data, ValidationField $field)
    {
        $formType = $data["formType"] ?? null;
        $dataType = $data["dataType"] ?? null;
        $dropdownOptions = $data["dropdownOptions"] ?? null;

        if ($dropdownOptions && $formType !== self::FORM_TYPE_DROPDOWN) {
            $field->addError("dropdownOptions can only be set when the formType is " . self::FORM_TYPE_DROPDOWN . ".");
        }

        if ($formType === self::FORM_TYPE_DROPDOWN && (!isset($dropdownOptions) || empty($dropdownOptions))) {
            $field->addError(
                "At least one dropdown option must be assigned when saving a formType of '" .
                    self::FORM_TYPE_DROPDOWN .
                    "'."
            );
        }

        if ($dropdownOptions) {
            $errorMessage = "All dropdownOptions must be of dataType {$dataType}";
            foreach ($dropdownOptions as $option) {
                switch ($dataType) {
                    case self::DATA_TYPE_NUMBER:
                    case self::DATA_TYPE_NUMBER_MUL:
                        if (!is_int($option)) {
                            $field->addError($errorMessage);
                        }
                        break;
                    case self::DATA_TYPE_TEXT:
                    case self::DATA_TYPE_STRING_MUL:
                        if (!is_string($option)) {
                            $field->addError($errorMessage);
                        }
                        break;
                    default:
                        break;
                }
            }
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
        $required = $data["registrationOptions"] ?? null;
        $visibility = $data["visibility"] ?? null;
        $mutability = $data["mutability"] ?? null;

        if (is_null($required) || is_null($visibility) || is_null($mutability)) {
            return;
        }

        if ($required instanceof Invalid || $visibility instanceof Invalid || $mutability instanceof Invalid) {
            // If any of these fields are already known to be invalid, we can skip this
            return;
        }

        if (
            $required === self::REGISTRATION_REQUIRED &&
            !(in_array($visibility, ["public", "private"]) && $mutability === "all")
        ) {
            $field->addError(
                "To mark a field as required, visibility must be public or private and mutability must be all"
            );
        }
    }

    public function getUserProfileFieldSchema(): Schema
    {
        // Dynamically build the schema based on the fields and data types.
        $schemaArray = [];

        foreach ($this->select() as $field) {
            $name = $field["apiName"];

            switch ($field["dataType"]) {
                case ProfileFieldModel::DATA_TYPE_TEXT:
                    $schemaArray[] = "$name:s?";
                    break;
                case ProfileFieldModel::DATA_TYPE_BOOL:
                    $schemaArray[] = "$name:b?";
                    break;
                case ProfileFieldModel::DATA_TYPE_DATE:
                    $schemaArray[] = "$name:dt?";
                    break;
                case ProfileFieldModel::DATA_TYPE_NUMBER:
                    $schemaArray[] = "$name:i?";
                    break;
                case ProfileFieldModel::DATA_TYPE_STRING_MUL:
                    $schemaArray["$name:a?"] = ["items" => ["type" => "string"]];
                    break;
                case ProfileFieldModel::DATA_TYPE_NUMBER_MUL:
                    $schemaArray["$name:a?"] = ["items" => ["type" => "integer"]];
                    break;
            }
        }

        return Schema::parse($schemaArray);
    }

    /**
     * Get profile field values for an array of user IDs
     *
     * @param int[] $userIDs
     * @return array<int, array<string, mixed>> Example: [$userID => [$apiName => $value, ...], ...]
     */
    public function getUsersProfileFields(array $userIDs): array
    {
        $results = [];

        $usersValues = Gdn::userModel()->getMeta($userIDs, "Profile.%", "Profile.");
        foreach ($usersValues as $userID => $values) {
            $this->processUserProfileFields($userID, $values);
            $results[$userID] = $values;
        }

        return $results;
    }

    /**
     * Get profile field values for given user ID
     *
     * @param int $userID
     * @return array<string, mixed> Example: [$apiName => $value, ...]
     */
    public function getUserProfileFields(int $userID): array
    {
        $values = \UserModel::getMeta($userID, "Profile.%", "Profile.");
        $this->processUserProfileFields($userID, $values);

        return $values;
    }

    /**
     * Get profile fields
     *
     * @param array $where
     * @param array $options
     * @return array
     */
    public function getProfileFields(array $where = [], array $options = []): array
    {
        $options = array_merge(["orderFields" => "sort", "orderDirection" => "asc"], $options);
        return $this->select($where, $options);
    }

    /**
     * Helper method for processing the retrieved fields of a single user.
     *
     * @param int $userID
     * @param array $values
     * @return void
     */
    private function processUserProfileFields(int $userID, array &$values)
    {
        $fields = array_column($this->select(), null, "apiName");
        $utc = new DateTimeZone("UTC");

        foreach ($values as $name => &$value) {
            if (!isset($fields[$name])) {
                unset($values[$name]);
                continue;
            }

            if (!$this->canView($userID, $fields[$name])) {
                unset($values[$name]);
                continue;
            }

            $dataType = $fields[$name]["dataType"] ?? null;
            switch ($dataType) {
                case ProfileFieldModel::DATA_TYPE_BOOL:
                    $value = (bool) $value;
                    break;
                case ProfileFieldModel::DATA_TYPE_DATE:
                    try {
                        $value = new \DateTimeImmutable($value, $utc);
                    } catch (\Exception $ex) {
                        $value = null;
                    }
                    break;
                case ProfileFieldModel::DATA_TYPE_STRING_MUL:
                case ProfileFieldModel::DATA_TYPE_NUMBER_MUL:
                    $value = json_decode($value);
                    break;
            }
        }
    }

    /**
     * Update user with new profile fields.
     *
     * @param int $userID The user ID to update.
     * @param array $values Key/value pairs of fields to update.
     */
    public function updateUserProfileFields(int $userID, array $values)
    {
        // Retrieve whitelist
        $allowedFields = array_column($this->select(), null, "apiName");

        foreach ($values as $name => &$value) {
            // Whitelist.
            if (!isset($allowedFields[$name])) {
                unset($values[$name]);
                continue;
            }

            $dataType = $allowedFields[$name]["dataType"] ?? null;
            switch ($dataType) {
                case ProfileFieldModel::DATA_TYPE_BOOL:
                    $value = $value ? 1 : 0;
                    break;
                case ProfileFieldModel::DATA_TYPE_DATE:
                    $value = $value instanceof \DateTimeInterface ? $value->format("Y-m-d") : null;
                    break;
                case ProfileFieldModel::DATA_TYPE_STRING_MUL:
                case ProfileFieldModel::DATA_TYPE_NUMBER_MUL:
                    $value = json_encode($value);
                    break;
            }
        }

        // Update UserMeta if any made it through.
        if (count($values)) {
            \UserModel::setMeta($userID, $values, "Profile.");
        }
    }

    /**
     * Returns true if the currently signed-in user can view a profile field based on visibility
     *
     * @param int $userID
     * @param array $profileField A profileField record
     * @return bool
     */
    public function canView(int $userID, array $profileField): bool
    {
        if (!$profileField["enabled"]) {
            return false;
        }
        $session = Gdn::session();
        $visibility = $profileField["visibility"] ?? null;
        switch ($visibility) {
            case "public":
                return $session->checkPermission("profiles.view");
            case "private":
                return $session->UserID === $userID || $session->checkPermission("personalInfo.view");
            case "internal":
                return $session->checkPermission("internalInfo.view");
        }
        return false;
    }

    /**
     * Returns true if the currently signed-in user can edit a profile field based on mutability
     *
     * @param int $userID
     * @param array $profileField A profileField record
     * @return bool
     */
    public function canEdit(int $userID, array $profileField): bool
    {
        $session = Gdn::session();
        if (!$profileField["enabled"]) {
            return false;
        }
        $mutability = $profileField["mutability"] ?? null;
        switch ($mutability) {
            case "all":
                return $session->UserID === $userID || $session->checkPermission("users.edit");
            case "restricted":
                return $session->checkPermission("users.edit");
            case "none":
                return false;
        }
        return false;
    }

    /**
     * Get a ProfileField record by it's label.
     *
     * @param $label
     * @return array|bool
     */
    public function getByLabel($label)
    {
        $result = $this->select(["label" => $label]);

        if (isset($result[0])) {
            return $result[0];
        }

        return false;
    }

    /**
     * Get a ProfileField record by it's $apiName.
     *
     * @param string $apiName
     * @return array|bool
     */
    public function getByApiName(string $apiName)
    {
        $result = $this->select(["apiName" => $apiName]);

        if (isset($result[0])) {
            return $result[0];
        }

        return false;
    }
}
