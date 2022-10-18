<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\ProfileExtender\Tests\APIv2;

use Exception;
use Gdn_Configuration;
use Vanilla\Attributes;
use Vanilla\Dashboard\Models\ProfileFieldModel;
use VanillaTests\Addons\ProfileExtender\ProfileExtenderTestTrait;
use VanillaTests\Fixtures\Html\TestHtmlDocument;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Tests for the profile extender addon.
 */
class ProfileExtenderAddonTest extends \VanillaTests\SiteTestCase
{
    use ProfileExtenderTestTrait;
    use UsersAndRolesApiTestTrait;

    /** @var \ProfileExtenderPlugin */
    private $profileExtender;

    /** @var Gdn_Configuration */
    private $config;

    /** @var ProfileFieldModel */
    private $profileFieldModel;

    /**
     * {@inheritdoc}
     */
    public static function getAddons(): array
    {
        return ["vanilla", "profileextender"];
    }

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->container()->call(function (\ProfileExtenderPlugin $profileExtender) {
            $this->profileExtender = $profileExtender;
        });

        $this->config = self::container()->get(Gdn_Configuration::class);
        $this->profileFieldModel = self::container()->get(ProfileFieldModel::class);

        $this->createUserFixtures();
    }

    /**
     * Test the basic profile extender get/set flow.
     */
    public function testUpdateUserField(): void
    {
        $this->profileExtender->updateUserFields($this->memberID, ["text" => __FUNCTION__]);
        $values = $this->profileExtender->getUserFields($this->memberID);
        $this->assertSame(__FUNCTION__, $values["text"]);
    }

    /**
     * Test basic profile field expansion.
     */
    public function testBasicExpansion(): void
    {
        $fields = ["text" => __FUNCTION__, "check" => true];

        $this->profileExtender->updateUserFields($this->memberID, $fields);
        $data = $this->api()
            ->get("/users/{$this->memberID}", ["expand" => "extended"])
            ->getBody();
        $this->assertArraySubsetRecursive($fields, $data["extended"]);
    }

    /**
     * Verify our expander still creates an empty Attributes object for users with no extended profile fields.
     */
    public function testEmptyExpansion(): void
    {
        $result = $this->profileExtender->getUserProfileValuesChecked([$this->memberID]);
        $this->assertInstanceOf(Attributes::class, $result[$this->memberID]);
        $this->assertSame(0, $result[$this->memberID]->count());
    }

    /**
     * The /users/me endpoint should expand all fields.
     */
    public function testMeDefaultExpansion(): void
    {
        $fields = ["text" => __FUNCTION__, "check" => true];

        $this->profileExtender->updateUserFields($this->api()->getUserID(), $fields);
        $data = $this->api()
            ->get("/users/me")
            ->getBody();
        $this->assertArraySubsetRecursive($fields, $data["extended"]);
    }

    /**
     * Verify Profile Extender values appear when editing user profiles, complete with values.
     */
    public function testFieldsOnEditProfile(): void
    {
        /** @var \Gdn_Session $session */
        $session = self::container()->get(\Gdn_Session::class);
        $this->profileExtender->updateUserFields($session->UserID, ["text" => __FUNCTION__]);

        $result = $this->bessy()->getHtml("profile/edit");
        $result->assertFormInput("text", __FUNCTION__);
        $result->assertCssSelectorNotExists('select[name="dropdown"]');
    }

    /**
     * Verify Profile Extender values appear when an admin is editing users in the dashboard
     */
    public function testFieldsOnEditUser(): void
    {
        $this->profileExtender->updateUserFields($this->memberID, ["text" => __FUNCTION__, "dropdown" => __FUNCTION__]);

        $result = $this->bessy()->getJsonData("/user/edit/{$this->memberID}?DeliveryType=VIEW&DeliveryMethod=JSON");
        $html = new TestHtmlDocument($result->getDataItem("Data"));
        $html->assertFormInput("text", __FUNCTION__);
        $html->assertCssSelectorExists('select[name="dropdown"]');
    }

    /**
     * Verify field validation for required fields.
     */
    public function testRequiredFieldWarning(): void
    {
        // We check if our Custom Required Field exists on the registration page.
        $registerPage = $this->bessy()->getHtml("/entry/register");
        $registerPage->assertCssSelectorExists("#Form_CustomRequiredField");

        // Trying to register providing an empty CustomrequiredField should display an exception message.
        $this->expectExceptionMessage("Custom Required Field");
        $registrationResults = $this->bessy()->post("/entry/register", [
            "Email" => "new@user.com",
            "Name" => "NewUserName",
            "CustomRequiredField" => "",
            "Password" => "jXM>e!gL4#38cP3Z",
            "PasswordMatch" => "jXM>e!gL4#38cP3Z",
            "TermsOfService" => "1",
            "Save" => "Save",
        ]);

        // Run the following with an authenticated user.
        $this->runWithUser(function () {
            // We also check if the field exists on profile edition page.
            $profilePage = $this->bessy()->getHtml("/profile/edit/");
            $profilePage->assertCssSelectorExists("#Form_CustomRequiredField");
        }, $this->adminID);
    }

    /**
     * Test editing Profile Extender fields over the APIv2 users/{id}/extended endpoint.
     *
     * @param string $field
     * @param string|bool $value
     * @param string $fieldToCheck
     * @param string|bool $expectedValue
     * @dataProvider provideTestPatchUsersExtendedEndpointData
     */
    public function testPatchUsersExtendedEndpoint($field, $value, $fieldToCheck, $expectedValue)
    {
        $id = $this->memberID;
        $result = $this->api()
            ->patch("/users/{$id}/extended", [$field => $value])
            ->getBody();
        $this->assertSame($result[$fieldToCheck], $expectedValue);
    }

    /**
     * Provides an array of data for testPatchUsersExtendedEndpoint().
     *
     * @return array
     */
    public function provideTestPatchUsersExtendedEndpointData(): array
    {
        $data = [
            "testTextInput" => ["text", "sometext", "text", "sometext"],
            "testCheckboxInput" => ["check", true, "check", true],
            "textDropdown" => ["dropdown", "Option2", "dropdown", "Option2"],
        ];

        return $data;
    }

    /**
     * Test patching multiple fields from
     */
    public function testPatchingMultipleFieldsFromExtendedEndpoint(): void
    {
        $id = $this->memberID;
        $fieldsToPatch = [
            "text" => "foo",
            "check" => false,
            "DateOfBirth" => "1980-06-17",
            "dropdown" => "Option1",
        ];
        $result = $this->api()
            ->patch("/users/{$id}/extended", $fieldsToPatch)
            ->getBody();
        $formattedDate = new \DateTimeImmutable($result["DateOfBirth"]);
        $result["DateOfBirth"] = $formattedDate->format("Y-m-d");
        $this->assertEquals($fieldsToPatch, $result);
    }

    /**
     * Test patching invalid data.
     */
    public function testPatchWithBadValues(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("DateOfBirth is not a valid datetime.");
        $this->expectExceptionMessage("dropdown must be one of: Option1, Option2.");
        $this->expectExceptionMessage("text is not a valid string.");
        $id = $this->memberID;
        $fieldsToPatch = [
            "text" => false,
            "DateOfBirth" => true,
            "dropdown" => "Option3",
        ];
        $this->api()->patch("/users/{$id}/extended", $fieldsToPatch);
    }

    /**
     * Test schema.
     */
    public function testSchemaExists(): void
    {
        $openApi = $this->api()
            ->get("/open-api/v3")
            ->getBody();
        $schemaProperties = $openApi["components"]["schemas"]["ExtendedUserFields"]["properties"];
        $this->assertSame($schemaProperties["text"]["type"], "string");
        $this->assertSame($schemaProperties["check"]["type"], "boolean");
        $this->assertSame($schemaProperties["DateOfBirth"]["type"], "string");
        $this->assertSame($schemaProperties["DateOfBirth"]["format"], "date-time");
        $this->assertSame($schemaProperties["dropdown"]["type"], "string");
        $this->assertSame(count($schemaProperties["dropdown"]["enum"]), 2);
    }

    /**
     * Test profile fields reordering.
     */
    public function testProfileFieldsReordering(): void
    {
        $id = $this->memberID;
        $fieldsToPatch = [
            "text" => "foo",
            "check" => false,
            "DateOfBirth" => "1980-06-17",
            "dropdown" => "Option1",
        ];

        $this->api()->patch("/users/{$id}/extended", $fieldsToPatch);
        $profileFields = $this->profileExtender->getUserFields($this->memberID);
        $fieldsInConfig = $this->config->get("ProfileExtender.Fields");
        $configFieldsNamesAsKey = array_flip(array_column($fieldsInConfig, "Name"));

        //order is not the same
        $this->assertNotEquals(array_key_first($profileFields), array_key_first($configFieldsNamesAsKey));
        $reorderedProfileFields = $this->profileExtender->reorderProfileFields($profileFields);
        $this->assertIsArray($reorderedProfileFields);
        $this->assertCount(count($profileFields), $reorderedProfileFields);
        //reorder array should be the same as the initial one, only order is changed
        $this->assertEquals($profileFields, $reorderedProfileFields);

        //and order should match with the one in config
        $this->assertEquals(array_key_first($reorderedProfileFields), array_key_first($configFieldsNamesAsKey));

        //scenario when user has more profile fields than in this->config
        $newFieldsInConfig = array_splice($fieldsInConfig, 0, 3);
        $this->config->set("ProfileExtender.Fields", $newFieldsInConfig);
        $newFieldsInConfig = $this->config->get("ProfileExtender.Fields");
        $newConfigFieldsNamesAsKey = array_flip(array_column($newFieldsInConfig, "Name"));
        $this->assertNotEquals(array_key_first($profileFields), array_key_first($newConfigFieldsNamesAsKey));
        $newReorderedProfileFields = $this->profileExtender->reorderProfileFields($profileFields);

        //should still be the same as the initial one, even if in the this->config now we have less fields
        $this->assertEquals($profileFields, $newReorderedProfileFields);
    }

    /**
     * Test that legacy fields are properly migrated to the profileField table.
     *
     * @param array $profleField Plugin provider field.
     * @param array $expectedResult Expected migrated fields.
     *
     * @dataProvider LegacyFieldMigrationProvider
     */
    public function testLegacyFieldMigration(array $profleField, array $expectedResult)
    {
        $key = "ProfileExtender.Fields." . $profleField["Label"];

        $this->config->saveToConfig(["Feature.CustomProfileFields.Enabled" => true]);
        $this->config->saveToConfig([$key => $profleField]);
        $this->assertConfigValue($key, $profleField);

        $this->bessy()->get("utility/update");
        $this->assertConfigValue($key, null);

        $result = $this->profileFieldModel->getByLabel($expectedResult["label"]);
        $this->assertNotEmpty($result);
        $this->assertSame($expectedResult["label"], $result["label"]);
        $this->assertSame($expectedResult["apiName"], $result["apiName"]);
        $this->assertSame($expectedResult["dataType"], $result["dataType"]);
        $this->assertSame($expectedResult["formType"], $result["formType"]);
        $this->assertSame($expectedResult["mutability"], $result["mutability"]);
        $this->assertSame($expectedResult["displayOptions"], $result["displayOptions"]);
        $this->assertSame($expectedResult["registrationOptions"], $result["registrationOptions"]);
        $this->config->saveToConfig(["Feature.CustomProfileFields.Enabled" => false]);
    }

    /**
     * Data Provider for testLegacyFieldMigration
     */
    public function LegacyFieldMigrationProvider(): array
    {
        $data = [
            "textBox Field" => [
                [
                    "FormType" => "TextBox",
                    "Label" => "TextBoxField",
                    "Options" => "",
                    "Required" => false,
                    "OnRegister" => false,
                    "OnProfile" => false,
                    "Name" => "TextBoxField",
                ],
                [
                    "label" => "TextBoxField",
                    "apiName" => "TextBoxField",
                    "dataType" => ProfileFieldModel::FORM_TYPE_TEXT,
                    "formType" => ProfileFieldModel::DATA_TYPE_TEXT,
                    "mutability" => ProfileFieldModel::MUTABILITIES[1],
                    "displayOptions" => ["profiles" => false, "userCards" => false, "posts" => false],
                    "registrationOptions" => ProfileFieldModel::REGISTRATION_OPTIONAL,
                ],
            ],
            "checkBox Field" => [
                [
                    "FormType" => "CheckBox",
                    "Label" => "CheckBoxField",
                    "Options" => "",
                    "Required" => true,
                    "OnRegister" => true,
                    "OnProfile" => true,
                    "OnDiscussion" => true,
                    "Name" => "ChecktBoxField",
                ],
                [
                    "label" => "CheckBoxField",
                    "apiName" => "CheckBoxField",
                    "dataType" => ProfileFieldModel::DATA_TYPE_BOOL,
                    "formType" => ProfileFieldModel::FORM_TYPE_CHECKBOX,
                    "mutability" => ProfileFieldModel::MUTABILITIES[0],
                    "displayOptions" => ["profiles" => true, "userCards" => false, "posts" => true],
                    "registrationOptions" => ProfileFieldModel::REGISTRATION_REQUIRED,
                ],
            ],
            "dropBox Field" => [
                [
                    "FormType" => "Dropdown",
                    "Label" => "DropdownField",
                    "Options" => ["select", "not select", "nothing"],
                    "Required" => true,
                    "OnRegister" => true,
                    "OnProfile" => false,
                    "OnDiscussion" => false,
                    "Name" => "DropdownField",
                ],
                [
                    "label" => "DropdownField",
                    "apiName" => "DropdownField",
                    "dataType" => ProfileFieldModel::DATA_TYPE_TEXT,
                    "formType" => ProfileFieldModel::FORM_TYPE_DROPDOWN,
                    "mutability" => ProfileFieldModel::MUTABILITIES[0],
                    "displayOptions" => ["profiles" => false, "userCards" => false, "posts" => false],
                    "registrationOptions" => ProfileFieldModel::REGISTRATION_REQUIRED,
                ],
            ],
            "DateOfBirth Field" => [
                [
                    "FormType" => "DateOfBirth",
                    "Label" => "BirthdayField",
                    "Options" => "",
                    "Required" => false,
                    "OnRegister" => true,
                    "OnProfile" => false,
                    "Name" => "DateOfBirthField",
                ],
                [
                    "label" => "Birthday",
                    "apiName" => "DateOfBirth",
                    "dataType" => ProfileFieldModel::DATA_TYPE_DATE,
                    "formType" => ProfileFieldModel::FORM_TYPE_DATE,
                    "mutability" => ProfileFieldModel::MUTABILITIES[1],
                    "displayOptions" => ["profiles" => false, "userCards" => false, "posts" => false],
                    "registrationOptions" => ProfileFieldModel::REGISTRATION_OPTIONAL,
                ],
            ],
        ];
        return $data;
    }
}
