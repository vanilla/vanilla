<?php
/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\AutomationRules;

use Vanilla\Dashboard\Models\ProfileFieldModel;

/**
 * Trait to support profile field creation for tests
 */
trait ProfileFieldTrait
{
    /**
     * Create a profileField
     *
     * @param array $profileField
     * @return array
     */
    private function createNewProfileField(array $profileField): array
    {
        $response = $this->api()->post("/profile-fields", $profileField);
        $this->assertEquals(201, $response->getStatusCode());
        return $response->getBody();
    }

    /**
     * Generate profile field record.
     *
     * @param array $params
     * @return array
     */
    public function generateProfileField(array $params): array
    {
        $uniqueField = uniqid("field-");
        $profileField = [
            "apiName" => $uniqueField,
            "label" => "label-" . $uniqueField,
            "description" => "this is a test field",
            "dataType" => "text",
            "formType" => "text",
            "visibility" => "public",
            "mutability" => "all",
            "displayOptions" => ["userCards" => true, "posts" => true, "search" => true],
            "dropdownOptions" => null,
            "registrationOptions" => ProfileFieldModel::REGISTRATION_OPTIONAL,
        ];
        $profileField = array_merge($profileField, $params);
        return $this->createNewProfileField($profileField);
    }

    /**
     * Generate a dropdown field.
     *
     * @param array $overrides
     * @return array
     */
    public function generateDropDownField(array $overrides = []): array
    {
        $profileFields = [
            "apiName" => "fruits",
            "label" => "Fruits",
            "formType" => "dropdown",
            "dropdownOptions" => ["apple", "banana", "cherry"],
        ];
        $profileFields = $overrides + $profileFields;
        return $this->generateProfileField($profileFields);
    }

    /**
     * Generate Checkbox field.
     */
    public function generateCheckboxField(array $overrides = []): array
    {
        $profileFields = [
            "apiName" => "terms",
            "label" => "Check if you accept our terms",
            "formType" => "checkbox",
            "dataType" => "boolean",
        ];
        $profileFields = $overrides + $profileFields;
        return $this->generateProfileField($profileFields);
    }

    /**
     * Generate a string token input field.
     *
     * @param array $overrides
     * @return array
     */
    public function generateStringTokenInputField(array $overrides = []): array
    {
        $profileFields = [
            "apiName" => "Hobbies",
            "label" => "Select Your Hobbies",
            "formType" => "tokens",
            "dataType" => "string[]",
            "dropdownOptions" => ["Reading", "Writing", "Singing", "Dancing"],
        ];
        return $this->generateProfileField($overrides + $profileFields);
    }

    /**
     * Update user profile fields
     *
     * @param array $profileFields
     * @param int $userID
     * @return array
     */
    public function updateProfileFields(array $profileFields, int $userID): array
    {
        $this->api()->patch("/users/$userID/profile-fields", $profileFields);
        $response = $this->api()->get("/users/$userID/profile-fields");
        return $response->getBody();
    }
}
