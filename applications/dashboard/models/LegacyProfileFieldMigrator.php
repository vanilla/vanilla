<?php
namespace Vanilla\Dashboard\Models;

use Garden\EventManager;
use Vanilla\Contracts\ConfigurationInterface;

/**
 * Class providing migrations from legacy ProfileExtender configs into the GDN_profileField table.
 */
class LegacyProfileFieldMigrator
{
    const CONF_ALREADY_RAN_MIGRATION = "ProfileExtender.AlreadyRanMigration";

    /** @var ConfigurationInterface */
    private ConfigurationInterface $config;

    /** @var ProfileFieldModel */
    private ProfileFieldModel $profileFieldModel;

    /** @var EventManager */
    private EventManager $eventManager;

    /**
     * DI.
     *
     * @param ConfigurationInterface $config
     * @param ProfileFieldModel $profileFieldModel
     * @param EventManager $eventManager
     */
    public function __construct(
        ConfigurationInterface $config,
        ProfileFieldModel $profileFieldModel,
        EventManager $eventManager
    ) {
        $this->config = $config;
        $this->profileFieldModel = $profileFieldModel;
        $this->eventManager = $eventManager;
    }

    /**
     * Run the migration of profile field configs.
     */
    public function runMigration()
    {
        $alreadyRan = $this->config->get(self::CONF_ALREADY_RAN_MIGRATION, false);
        if ($alreadyRan) {
            // Nothing left to do.
            return;
        }

        // Clear out our cache in case the jobber was migrating some values.
        $this->profileFieldModel->clearCache();

        $baseField = [
            "apiName" => "",
            "label" => "",
            "description" => "",
            "dataType" => ProfileFieldModel::DATA_TYPE_TEXT,
            "formType" => ProfileFieldModel::FORM_TYPE_TEXT,
            "dropdownOptions" => null,
            "visibility" => ProfileFieldModel::VISIBILITY_PRIVATE,
            "mutability" => "",
            "displayOptions" => ["userCards" => false, "posts" => false],
            "registrationOptions" => ProfileFieldModel::REGISTRATION_OPTIONAL,
            "sort" => "",
            "enabled" => true,
        ];

        $configFields = $this->config->get("ProfileExtender.Fields", []);

        foreach ($configFields as $apiName => $configField) {
            $apiName = $configField["Name"] ?? ($apiName ?? null);
            if (empty($apiName)) {
                continue;
            }

            $existingProfileField = $this->profileFieldModel->getByApiName($apiName);
            if ($existingProfileField) {
                // Field already exists.
                continue;
            }

            $formType = $configField["FormType"] ?? "TextBox";

            // Profile field already exists.
            $insertField = $baseField;
            $insertField["label"] = $configField["Label"] ?? $apiName;
            $insertField["description"] = $configField["Description"] ?? "";
            $insertField["apiName"] = $apiName;

            switch ($formType) {
                case "TextBox":
                    $isMultiline = $configField["Options"]["MultiLine"] ?? false;
                    $insertField["dataType"] = ProfileFieldModel::DATA_TYPE_TEXT;
                    $insertField["formType"] = $isMultiline
                        ? ProfileFieldModel::FORM_TYPE_TEXT_MULTILINE
                        : ProfileFieldModel::FORM_TYPE_TEXT;
                    break;
                case "Dropdown":
                    $insertField["dataType"] = ProfileFieldModel::DATA_TYPE_TEXT;
                    $insertField["formType"] = ProfileFieldModel::FORM_TYPE_DROPDOWN;

                    // Extract the options.
                    $options = $configField["Options"] ?? [];
                    $options = array_values($options);
                    $insertField["dropdownOptions"] = $options;
                    break;
                case "CheckBox":
                    $insertField["formType"] = ProfileFieldModel::FORM_TYPE_CHECKBOX;
                    $insertField["dataType"] = ProfileFieldModel::DATA_TYPE_BOOL;
                    break;
                case "DateOfBirth":
                    $insertField["label"] = "Birthday";
                    $insertField["formType"] = ProfileFieldModel::FORM_TYPE_DATE;
                    $insertField["dataType"] = ProfileFieldModel::DATA_TYPE_DATE;
                    break;
                case "Date":
                    $insertField["formType"] = ProfileFieldModel::FORM_TYPE_DATE;
                    $insertField["dataType"] = ProfileFieldModel::DATA_TYPE_DATE;
                    break;
            }

            // Default is private.
            if (isset($configField["OnProfile"]) && $configField["OnProfile"]) {
                $insertField["visibility"] = ProfileFieldModel::VISIBILITY_PUBLIC;
            }

            if ($configField["OnDiscussion"] ?? false) {
                $insertField["displayOptions"]["posts"] = true;
            }

            $insertField["sort"] = $configField["Sort"] ?? null;
            if ($configField["OnRegister"] ?? false) {
                $insertField["registrationOptions"] =
                    $configField["Required"] ?? false
                        ? ProfileFieldModel::REGISTRATION_REQUIRED
                        : ProfileFieldModel::REGISTRATION_OPTIONAL;
                $insertField["mutability"] = ProfileFieldModel::MUTABILITY_ALL;
            } else {
                $insertField["mutability"] = ProfileFieldModel::MUTABILITY_RESTRICTED;
            }
            $this->profileFieldModel->insert($insertField);
        }
        $this->eventManager->fire("afterProfileFieldMigration");
        $this->config->saveToConfig(self::CONF_ALREADY_RAN_MIGRATION, true);
    }
}
