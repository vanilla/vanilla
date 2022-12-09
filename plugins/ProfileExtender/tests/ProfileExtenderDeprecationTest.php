<?php
/**
 * @author David Barbier <dbarbier@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\ProfileExtender\Tests;

use UserMetaModel;
use UserModel;
use Vanilla\Dashboard\Models\ProfileFieldModel;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Tests for the various processes related to the deprecation of the profile extender addon.
 */
class ProfileExtenderDeprecationTest extends SiteTestCase
{
    //    use ProfileExtenderTestTrait;
    use UsersAndRolesApiTestTrait;

    public static $addons = ["ProfileExtender"];

    /** @var \Gdn_SQLDriver */
    protected $sql;

    /** @var \ProfileExtenderPlugin */
    private $profileExtenderPlugin;

    /** @var ProfileFieldModel */
    private $profileFieldModel;

    /** @var \UserMetaModel */
    private $userMetaModel;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->sql = $this->userModel->SQL;
        $this->container()->call(function (\ProfileExtenderPlugin $profileExtenderPlugin) {
            $this->profileExtenderPlugin = $profileExtenderPlugin;
        });

        $this->profileFieldModel = self::container()->get(ProfileFieldModel::class);
        $this->userMetaModel = self::container()->get(UserMetaModel::class);

        $this->createUserFixtures();
    }

    /*
     * Test impacts of creating Built-in fields through the plugin on accessing the user's `Location`, `Title` and `Gender` fields.
     */
    public function testImpactOfCreatingBuiltInFields(): void
    {
        // We have `Title` & `Location` as default core profile fields.
        $coreProfileFields = $this->profileFieldModel->getProfileFields();
        $this->assertCount(2, $coreProfileFields);

        // We grab the custom fields at the plugin's level.
        $pluginProfileFields = $this->profileExtenderPlugin->getProfileFields();
        // There shouldn't be any pre-existing plugin's custom profile fields.
        $this->assertCount(0, $pluginProfileFields);

        // Create plugin-level "built-in" `Title` & `Location` fields.
        $this->createBuiltInPluginFields();
        // We still have `Title` & `Location` as default core profile fields.
        $coreProfileFields = $this->profileFieldModel->getProfileFields();
        $this->assertCount(2, $coreProfileFields);
        $this->assertArraySubsetRecursive(
            [
                ["apiName" => ProfileFieldModel::DEFAULT_FIELD_TITLE["apiName"]],
                ["apiName" => ProfileFieldModel::DEFAULT_FIELD_LOCATION["apiName"]],
            ],
            $coreProfileFields
        );

        // We grab the custom fields at the plugin's level.
        $pluginProfileFields = $this->profileExtenderPlugin->getProfileFields();
        $this->assertCount(2, $pluginProfileFields);

        // We set a member user's `Title`, `Location`.
        $userNewValues = [
            "Title" => "Mr Awesome",
            "Location" => "Right Here",
        ];
        $this->userModel->setField($this->memberID, $userNewValues);
        // It shouldn't be saved in the `User` table.
        $userTableValues = $this->sql
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
            UserModel::USERMETA_FIELDS_PREFIX
        );
        $this->assertArraySubsetRecursive($userNewValues, $userMetaTableValues);

        // UserModel's `getID()` function should return correct `Title`, `Location` & `Gender` values.
        $userModelData = $this->userModel->getID($this->memberID, DATASET_TYPE_ARRAY);
        $this->assertArraySubsetRecursive($userNewValues, $userModelData);

        // Getting the user's data through the API should return the appropriate `Title`, but not the sensitive
        // `Location` and `Gender` fields.
        $userApiData = $response = $this->api()
            ->get("users/{$this->memberID}")
            ->getBody();
        $this->assertEquals($userNewValues["Title"], $userApiData["title"]);
        $this->assertFalse(array_key_exists("location", $userApiData));
        $this->assertFalse(array_key_exists("gender", $userApiData));
    }

    /*
     * Create Profile Extender fields for `Title` & `Location`.
     */
    private function createBuiltInPluginFields(): void
    {
        self::bessy()->post("/settings/profile-field-add-edit", [
            "Name" => "Title",
            "Label" => "The user's title (new)",
            "FormType" => "TextBox",
            "Required" => "1",
            "OnRegister" => true,
        ]);
        self::bessy()->post("/settings/profile-field-add-edit", [
            "Name" => "Location",
            "Label" => "The user's location (new)",
            "FormType" => "TextBox",
            "Required" => "1",
            "OnRegister" => true,
        ]);
    }
}
