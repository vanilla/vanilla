<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Addons\ProfileExtender;

/**
 * Trait for testing the profile extender.
 */
trait ProfileExtenderTestTrait
{
    /**
     * Create extended fields.
     */
    public static function setUpBeforeClassProfileExtenderTestTrait()
    {
        self::createExtendedFields();
    }

    /**
     * Create extended profile fields.
     */
    protected static function createExtendedFields()
    {
        self::bessy()->post("/settings/profile-field-add-edit", [
            "Name" => "text",
            "Label" => "Text",
            "FormType" => "TextBox",
            "OnProfile" => "1",
            "OnRegister" => true,
        ]);

        self::bessy()->post("/settings/profile-field-add-edit", [
            "Name" => "check",
            "Label" => "Check",
            "FormType" => "CheckBox",
        ]);

        self::bessy()->post("/settings/profile-field-add-edit", [
            "Name" => "DateOfBirth",
            "Label" => "Birthday",
            "FormType" => "DateOfBirth",
        ]);

        self::bessy()->post("/settings/profile-field-add-edit", [
            "Name" => "dropdown",
            "Label" => "Dropdown",
            "FormType" => "Dropdown",
            "Options" => "Option1\nOption2",
        ]);

        self::bessy()->post("/settings/profile-field-add-edit", [
            "Name" => "CustomRequiredField",
            "Label" => "Custom Required Field",
            "FormType" => "TextBox",
            "Required" => "1",
            "OnRegister" => true,
        ]);
    }

    /**
     * Get the plugin instance.
     *
     * @return \ProfileExtenderPlugin
     */
    protected static function getProfileExtenderPlugin(): \ProfileExtenderPlugin
    {
        return self::container()->get(\ProfileExtenderPlugin::class);
    }

    public function createSystemDefaultProfileFields()
    {
        self::bessy()->post("/settings/profile-field-add-edit", [
            "Name" => "Title",
            "Label" => "Your Job Title",
            "FormType" => "TextBox",
            "Required" => "1",
            "OnRegister" => true,
        ]);

        self::bessy()->post("/settings/profile-field-add-edit", [
            "Name" => "Location",
            "Label" => "Your Job Location",
            "FormType" => "TextBox",
            "Required" => "1",
            "OnRegister" => true,
        ]);
    }

    public function removeSystemDefaultProfileFields()
    {
        $profileFields = ["Title", "Location"];
        foreach ($profileFields as $field) {
            self::bessy()->post("/settings/profile-field-delete/$field");
        }
    }
}
