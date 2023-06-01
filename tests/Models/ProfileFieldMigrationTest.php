<?php

namespace VanillaTests\Models;

use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Dashboard\Models\LegacyProfileFieldMigrator;
use Vanilla\Dashboard\Models\ProfileFieldModel;
use VanillaTests\DatabaseTestTrait;
use VanillaTests\SiteTestCase;

/**
 * Tests for migrations of legacy profile fields.
 */
class ProfileFieldMigrationTest extends SiteTestCase
{
    const TEXTBOX_LEGACY = [
        "FormType" => "TextBox",
        "Label" => "TextBoxField",
        "Options" => "",
        "Required" => false,
        "OnRegister" => false,
        "OnProfile" => false,
        "Name" => "TextBoxField",
    ];

    const TEXTBOX_MODERN = [
        "label" => "TextBoxField",
        "apiName" => "TextBoxField",
        "dataType" => ProfileFieldModel::FORM_TYPE_TEXT,
        "formType" => ProfileFieldModel::DATA_TYPE_TEXT,
        "visibility" => ProfileFieldModel::VISIBILITY_PRIVATE,
        "mutability" => ProfileFieldModel::MUTABILITIES[1],
        "displayOptions" => ["userCards" => false, "posts" => false],
        "registrationOptions" => ProfileFieldModel::REGISTRATION_OPTIONAL,
    ];

    const CHECKBOX_LEGACY = [
        "FormType" => "CheckBox",
        "Label" => "CheckBoxField",
        "Options" => "",
        "Required" => true,
        "OnRegister" => true,
        "OnProfile" => true,
        "OnDiscussion" => true,
        "Name" => "CheckBoxField",
    ];

    const CHECKBOX_MODERN = [
        "label" => "CheckBoxField",
        "apiName" => "CheckBoxField",
        "dataType" => ProfileFieldModel::DATA_TYPE_BOOL,
        "formType" => ProfileFieldModel::FORM_TYPE_CHECKBOX,
        "visibility" => ProfileFieldModel::VISIBILITY_PUBLIC,
        "mutability" => ProfileFieldModel::MUTABILITIES[0],
        "displayOptions" => ["userCards" => false, "posts" => true],
        "registrationOptions" => ProfileFieldModel::REGISTRATION_REQUIRED,
    ];

    const DROPDOWN_LEGACY = [
        "FormType" => "Dropdown",
        "Label" => "DropdownField",
        "Options" => ["select", "not select", "nothing"],
        "Required" => true,
        "OnRegister" => true,
        "OnProfile" => false,
        "OnDiscussion" => false,
        "Name" => "DropdownField",
    ];

    const DROPDOWN_MODERN = [
        "label" => "DropdownField",
        "apiName" => "DropdownField",
        "dataType" => ProfileFieldModel::DATA_TYPE_TEXT,
        "formType" => ProfileFieldModel::FORM_TYPE_DROPDOWN,
        "mutability" => ProfileFieldModel::MUTABILITIES[0],
        "visibility" => ProfileFieldModel::VISIBILITY_PRIVATE,
        "displayOptions" => ["userCards" => false, "posts" => false],
        "dropdownOptions" => ["select", "not select", "nothing"],
        "registrationOptions" => ProfileFieldModel::REGISTRATION_REQUIRED,
    ];

    const EMTPY_DROPDOWN_LEGACY = [
        "FormType" => "Dropdown",
        "Label" => "EmptyDropdownField",
        "Options" => [],
        "Required" => true,
        "OnRegister" => true,
        "OnProfile" => false,
        "OnDiscussion" => false,
        "Name" => "EmptyDropdownField",
    ];

    const EMPTY_DROPDOWN_MODERN = [
        "label" => "EmptyDropdownField",
        "apiName" => "EmptyDropdownField",
        "dataType" => ProfileFieldModel::DATA_TYPE_TEXT,
        "formType" => ProfileFieldModel::FORM_TYPE_DROPDOWN,
        "visibility" => ProfileFieldModel::VISIBILITY_PRIVATE,
        "mutability" => ProfileFieldModel::MUTABILITIES[0],
        "displayOptions" => ["userCards" => false, "posts" => false],
        "registrationOptions" => ProfileFieldModel::REGISTRATION_REQUIRED,
    ];

    const DOB_LEGACY = [
        "FormType" => "DateOfBirth",
        "Label" => "BirthdayField",
        "Options" => "",
        "Required" => false,
        "OnRegister" => true,
        "OnProfile" => false,
        "Name" => "DateOfBirth",
    ];

    const DOB_MODERN = [
        "label" => "Birthday",
        "apiName" => "DateOfBirth",
        "dataType" => ProfileFieldModel::DATA_TYPE_DATE,
        "formType" => ProfileFieldModel::FORM_TYPE_DATE,
        "visibility" => ProfileFieldModel::VISIBILITY_PRIVATE,
        "mutability" => ProfileFieldModel::MUTABILITY_ALL,
        "displayOptions" => ["userCards" => false, "posts" => false],
        "registrationOptions" => ProfileFieldModel::REGISTRATION_OPTIONAL,
    ];

    use DatabaseTestTrait;

    /** @var ConfigurationInterface */
    private $config;

    /** @var ProfileFieldModel */
    private $profileFieldModel;

    /** @var LegacyProfileFieldMigrator */
    private $migrator;

    /** @var \UserMetaModel */
    private $userMetaModel;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->config = self::container()->get(ConfigurationInterface::class);
        $this->profileFieldModel = self::container()->get(ProfileFieldModel::class);
        $this->migrator = self::container()->get(LegacyProfileFieldMigrator::class);
        $this->userMetaModel = self::container()->get(\UserMetaModel::class);
        $this->resetTable("profileField");
    }

    private function runStructure()
    {
        $updateModel = self::container()->get(\UpdateModel::class);
        $updateModel->runStructure();
    }

    /**
     * Test that we run migrations before creating initial fields and bail out of creating them.
     */
    public function testRunsMigrationsInsteadOfInitialFields()
    {
        $this->runWithConfig(
            [
                ProfileFieldModel::CONFIG_FEATURE_FLAG => true,
                "ProfileExtender.Fields" => [
                    "textField" => self::TEXTBOX_LEGACY,
                ],
            ],
            function () {
                $this->runStructure();
                $allFields = $this->profileFieldModel->select(["enabled" => true]);
                $this->assertCount(1, $allFields);
                $this->assertDataLike(self::TEXTBOX_MODERN, $allFields[0]);
            }
        );
    }

    /**
     * Test that we won't remigrate legacy fields if we've already migrated them.
     */
    public function testAlreadyRanMigration()
    {
        $this->runWithConfig(
            [
                ProfileFieldModel::CONFIG_FEATURE_FLAG => true,
                LegacyProfileFieldMigrator::CONF_ALREADY_RAN_MIGRATION => true,
                "ProfileExtender.Fields" => [
                    "textField" => self::TEXTBOX_LEGACY,
                ],
            ],
            function () {
                $this->runStructure();

                // We should just have the default fields.
                $allFields = $this->profileFieldModel->getAll();
                $apiNames = array_column($allFields, "apiName");
                $this->assertEqualsCanonicalizing(
                    ["first-name", "last-name", "company", "pronouns", "bio", "Title", "Location", "DateOfBirth"],
                    $apiNames
                );
            }
        );
    }

    /**
     * Test that legacy fields are properly migrated to the profileField table.
     *
     * @param array $legacyField Plugin provider field.
     * @param array $expectedResult Expected migrated fields.
     *
     * @dataProvider LegacyFieldMigrationProvider
     */
    public function testMigration(array $legacyField, array $expectedResult)
    {
        $legacyConf = "ProfileExtender.Fields." . ($legacyField["Name"] ?? $legacyField["Label"]);

        $this->runWithConfig(
            [
                ProfileFieldModel::CONFIG_FEATURE_FLAG => true,
                $legacyConf => $legacyField,
                LegacyProfileFieldMigrator::CONF_ALREADY_RAN_MIGRATION => false,
            ],
            function () use ($expectedResult, $legacyField, $legacyConf) {
                $this->runStructure();
                // It leaves the config value alone.
                $this->assertConfigValue($legacyConf, $legacyField);

                $result = $this->profileFieldModel->getByApiName($expectedResult["apiName"]);
                $this->assertDataLike($expectedResult, $result);

                // It saves that it performed the migration.
                $this->assertConfigValue(LegacyProfileFieldMigrator::CONF_ALREADY_RAN_MIGRATION, true);
            }
        );
    }

    /**
     * Data Provider for testLegacyFieldMigration
     */
    public function legacyFieldMigrationProvider(): array
    {
        $data = [
            "textBox Field" => [self::TEXTBOX_LEGACY, self::TEXTBOX_MODERN],
            "checkBox Field" => [self::CHECKBOX_LEGACY, self::CHECKBOX_MODERN],
            "dropBox Field" => [self::DROPDOWN_LEGACY, self::DROPDOWN_MODERN],
            "emptyDropdown Field" => [self::EMTPY_DROPDOWN_LEGACY, self::EMPTY_DROPDOWN_MODERN],
            "DateOfBirth Field" => [self::DOB_LEGACY, self::DOB_MODERN],
        ];
        return $data;
    }

    /*
     * Test impacts of creating Built-in fields through the plugin on accessing the user's `Location`, `Title` and `Gender` fields.
     */
    public function testNewMovedDefaultFields(): void
    {
        $this->createUserFixtures();
        $this->runWithConfig(
            [
                ProfileFieldModel::CONFIG_FEATURE_FLAG => true,
            ],
            function () {
                $this->runStructure();
                // Title and location become stored as profile fields.
                // We set a member user's `Title`, `Location`.
                $userNewValues = [
                    "Title" => "Mr Awesome",
                    "Location" => "Right Here",
                ];
                $this->userModel->setField($this->memberID, $userNewValues);
                // It shouldn't be saved in the `User` table.
                $userTableValues = \Gdn::sql()
                    ->select("Title, Location, Gender")
                    ->from("User")
                    ->where("userID", $this->memberID)
                    ->get()
                    ->firstRow(DATASET_TYPE_ARRAY);
                $this->assertEquals(["Title" => null, "Location" => null, "Gender" => ""], $userTableValues);

                // It should be saved in the `UserMeta` table.
                $userMetaTableValues = $this->userMetaModel->getUserMeta(
                    $this->memberID,
                    null,
                    null,
                    \UserModel::USERMETA_FIELDS_PREFIX
                );
                $this->assertArraySubsetRecursive($userNewValues, $userMetaTableValues);

                // UserModel's `getID()` function should return correct `Title`, `Location` & `Gender` values.
                $userModelData = $this->userModel->getID($this->memberID, DATASET_TYPE_ARRAY);
                $this->assertArraySubsetRecursive($userNewValues, $userModelData);

                // Getting the user's data through the API should return the appropriate `Title`, but not the sensitive
                // `Location` and `Gender` fields.
                $userApiData = $this->api()
                    ->get("users/{$this->memberID}")
                    ->getBody();
                $this->assertEquals($userNewValues["Title"], $userApiData["title"]);
                $this->assertFalse(array_key_exists("location", $userApiData));
                $this->assertFalse(array_key_exists("gender", $userApiData));
            }
        );
    }
}
