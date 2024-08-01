<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\AutomationRules\Triggers;

use Exception;
use Garden\Container\ContainerException;
use Garden\Container\NotFoundException;
use Garden\Schema\Invalid;
use Garden\Schema\Schema;
use Garden\Schema\ValidationField;
use Vanilla\AutomationRules\Trigger\AutomationTrigger;
use Vanilla\Dashboard\AutomationRules\Models\UserRuleDataType;
use Vanilla\Dashboard\Models\ProfileFieldModel;
use Vanilla\Forms\ApiFormChoices;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Logger;

/**
 * Class ProfileFieldTrigger
 */
class ProfileFieldSelectionTrigger extends AutomationTrigger
{
    use UserSearchTrait;
    /**
     * @inheridoc
     */
    public static function getType(): string
    {
        return "profileFieldTrigger";
    }

    /**
     * @inheridoc
     */
    public static function getName(): string
    {
        return "New/Updated Profile field";
    }

    /**
     * @inheridoc
     */
    public static function getContentType(): string
    {
        return "users";
    }

    /**
     * @inheridoc
     */
    public static function getActions(): array
    {
        return UserRuleDataType::getActions();
    }

    /**
     * @inheridoc
     */
    public static function getSchema(): Schema
    {
        $schema = [
            "profileField" => [
                "type" => "string",
                "required" => true,
                "x-control" => SchemaForm::dropDown(
                    new FormOptions("Profile Field", "Select a profile field"),
                    new ApiFormChoices(
                        "/api/v2/profile-fields?enabled=true&formType[]=dropdown&formType[]=tokens&formType[]=checkbox",
                        "/api/v2/profile-fields/%s",
                        "apiName",
                        "label"
                    )
                ),
            ],
        ];
        return Schema::parse($schema);
    }

    /**
     * @inheridoc
     */
    public static function getPostPatchSchema(Schema &$schema): void
    {
        $profileFieldValueSchema = Schema::parse(["profileField:o"])
            ->addValidator("profileField", function ($profileField, ValidationField $field) {
                if (empty($profileField)) {
                    $field->addError("Profile field is required.", ["code" => 403]);
                    return false;
                }
                $profileFieldName = key($profileField);
                $profileFieldRecord = self::getProfileFieldByApiName($profileFieldName);
                if (!$profileFieldRecord) {
                    $field->addError("$profileFieldName doesn't exist.", ["code" => 403]);
                    return Invalid::value();
                }
                switch ($profileFieldRecord["formType"]) {
                    case ProfileFieldModel::FORM_TYPE_CHECKBOX:
                        Schema::parse(["{$profileFieldName}:b"])->validate($profileField);
                        break;
                    case ProfileFieldModel::FORM_TYPE_DROPDOWN:
                    case ProfileFieldModel::FORM_TYPE_TOKENS:
                        Schema::parse([
                            "{$profileFieldName}:a" => [
                                "type" => "array",
                                "minItems" => 1,
                                "items" => [
                                    "type" =>
                                        $profileFieldRecord["dataType"] === ProfileFieldModel::DATA_TYPE_NUMBER_MUL
                                            ? "integer"
                                            : "string",
                                    "enum" => $profileFieldRecord["dropdownOptions"],
                                ],
                            ],
                        ])->validate($profileField);
                        break;
                    case ProfileFieldModel::FORM_TYPE_NUMBER:
                        Schema::parse(["{$profileFieldName}:i"])->validate($profileField);
                        break;
                    case ProfileFieldModel::FORM_TYPE_DATE:
                        Schema::parse(["{$profileFieldName}:dt"])->validate($profileField);
                        break;
                    default:
                        Schema::parse(["{$profileFieldName}:s"])->validate($profileField);
                }
            })
            ->addFilter("profileField", function ($profileField) {
                if (!empty($profileField)) {
                    $profileFieldName = key($profileField);
                    $profileFieldRecord = self::getProfileFieldByApiName($profileFieldName);
                    if (
                        $profileFieldRecord &&
                        $profileFieldRecord["dataType"] === ProfileFieldModel::DATA_TYPE_NUMBER_MUL &&
                        is_array($profileField[$profileFieldName])
                    ) {
                        //if we are receiving values as string we need to convert them to integer values
                        $profileField[$profileFieldName] = array_map("intval", $profileField[$profileFieldName]);
                    }
                }
                return $profileField;
            });

        $profileFieldSchema = Schema::parse([
            "trigger:o" => [
                "triggerType:s" => [
                    "enum" => [self::getType()],
                ],
                "triggerValue:o" => $profileFieldValueSchema,
            ],
        ]);
        $schema->merge($profileFieldSchema);
    }

    /**
     * Get profile field data by api name.
     *
     * @param string $apiName
     * @return array|null
     * @throws ContainerException
     * @throws NotFoundException
     */
    private static function getProfileFieldByApiName(string $apiName): ?array
    {
        $profileFieldModel = \Gdn::getContainer()->get(ProfileFieldModel::class);
        $profileField = $profileFieldModel->getByApiName($apiName);
        return $profileField ?: null;
    }

    /**
     * @inheridoc
     */
    public function getRecordCountsToProcess(array $where): int
    {
        $profileField = $where["profileField"] ?? "";
        if (empty($profileField)) {
            return 0;
        }
        $key = key($profileField);
        $profileFieldData = self::getProfileFieldByApiName($key);
        if (!$profileFieldData) {
            return 0;
        }
        // Token Fields should be passed as comma separated values in the query
        if ($profileFieldData["formType"] === ProfileFieldModel::FORM_TYPE_TOKENS) {
            $profileField[$key] = implode(",", $profileField[$key]);
        }
        $query = [
            "profileFields" => $profileField,
        ];
        try {
            return $this->getCount($query);
        } catch (\Exception $e) {
            $this->getLogger()->error("Error getting record count for the profile field trigger", [
                Logger::FIELD_CHANNEL => Logger::CHANNEL_APPLICATION,
                Logger::FIELD_EVENT => "ProfileFieldSelectionTrigger",
                Logger::FIELD_TAGS => ["automation rules"],
                Logger::ERROR => $e->getMessage(),
                "profileField" => $profileField,
            ]);
            throw new Exception("Failed to get record count. Please try again later.");
        }
    }

    /**
     * @inheridoc
     */
    public function getRecordsToProcess($lastRecordId, array $where): iterable
    {
        try {
            $profileField = $where["profileField"] ?? "";
            if (empty($profileField)) {
                return yield;
            }
            $key = key($profileField);
            $profileFieldData = self::getProfileFieldByApiName($key);
            if (!$profileFieldData) {
                return yield;
            }
            if ($profileFieldData["formType"] === ProfileFieldModel::FORM_TYPE_TOKENS) {
                $profileField[$key] = implode(",", $profileField[$key]);
            }
            $query = [
                "profileFields" => $profileField,
            ];
            if ($lastRecordId) {
                $query["userID"] = $lastRecordId;
            }
            foreach ($this->getUserRecordIterator($query) as $key => $record) {
                yield $key => $record;
            }
        } catch (Exception $e) {
            $this->getLogger()->error("Error searching for records to process profile field.", [
                Logger::FIELD_CHANNEL => Logger::CHANNEL_APPLICATION,
                Logger::FIELD_EVENT => "ProfileFieldSelectionTrigger",
                Logger::FIELD_TAGS => ["automation rules"],
                Logger::ERROR => $e->getMessage(),
                "profileField" => $profileField,
            ]);
            throw new Exception("Failed to get records to process. Please try again later.");
        }
    }
}
