<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\ProfileExtender\Tests\APIv2;

use VanillaTests\EventSpyTestTrait;
use VanillaTests\SiteTestCase;

/**
 * Tests for the profile extender addon when we are going through SSO.
 */
class ProfileExtenderWithSSOTest extends SiteTestCase
{
    use EventSpyTestTrait;

    /**
     * {@inheritdoc}
     */
    public static function getAddons(): array
    {
        return ["vanilla", "profileextender"];
    }

    /**
     * Field on register.
     *
     * @return array[][]
     */
    public function dataProviderOnConnectPage(): array
    {
        return [
            [
                [
                    "Name" => "text_on_connect",
                    "Label" => "Text",
                    "FormType" => "TextBox",
                    "OnRegister" => true,
                ],
            ],
            [
                [
                    "Name" => "birthday_on_connect",
                    "Label" => "Birthday",
                    "FormType" => "DateOfBirth",
                    "OnRegister" => true,
                ],
            ],
            [
                [
                    "Name" => "check_on_connect",
                    "Label" => "Check",
                    "FormType" => "CheckBox",
                    "OnRegister" => true,
                ],
            ],
            [
                [
                    "Name" => "dropdown_on_connect",
                    "Label" => "Dropdown",
                    "FormType" => "Dropdown",
                    "Options" => "Option1\nOption2",
                    "OnRegister" => true,
                ],
            ],
        ];
    }

    /**
     * Test we have all fields on connect page.
     *
     * @param array $field
     * @dataProvider dataProviderOnConnectPage
     */
    public function testFieldsOnConnectPage(array $field)
    {
        $this->bessy()->post("/settings/profile-field-add-edit", $field);
        $fieldName = $field["Name"];
        if ($field["FormType"] === "DateOfBirth") {
            $this->bessy()
                ->getHtml("/entry/connect")
                ->assertCssSelectorExists("select[name=\"" . $fieldName . "_Day" . "\"]");
            $this->bessy()
                ->getHtml("/entry/connect")
                ->assertCssSelectorExists("select[name=\"" . $fieldName . "_Year" . "\"]");
            $this->bessy()
                ->getHtml("/entry/connect")
                ->assertCssSelectorExists("select[name=\"" . $fieldName . "_Month" . "\"]");
        } else {
            $this->bessy()
                ->getHtml("/entry/connect")
                ->assertCssSelectorExists("[name=\"$fieldName\"]");
        }
    }

    /**
     * Add provider and unique id to go through connect(SSO) process.
     *
     * @param \EntryController $controller
     */
    public function entryController_connectData_handler(\EntryController $controller)
    {
        $controller->Form->setFormValue("Provider", "my_auth");
        $controller->Form->setFormValue("UniqueID", "154dfd8d");
        $controller->setData("Verified", true);
    }
}
