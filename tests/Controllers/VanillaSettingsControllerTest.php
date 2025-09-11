<?php
/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Controllers;

use CategoryModel;
use Gdn_Form;
use PermissionModel;
use RoleModel;
use TagModel;
use Vanilla\FeatureFlagHelper;
use Vanilla\Forum\Models\PostTypeModel;
use VanillaTests\Fixtures\TestUploader;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;

/**
 * Test various capabilities of VanillaSettingsController.
 */
class VanillaSettingsControllerTest extends SiteTestCase
{
    use CommunityApiTestTrait;

    /** @inheritdoc */
    protected static $addons = ["vanilla"];

    /** @var CategoryModel */
    protected $categoryModel;

    /** @var PermissionModel */
    protected $permissionModel;

    /** TagModel */
    protected $tagModel;

    /**
     * Grab an array of category permissions, indexed by role ID.
     *
     * @param int $categoryID
     * @param bool $addDefaults
     * @return array
     */
    protected function getCategoryPermissions(int $categoryID, bool $addDefaults): array
    {
        $permissions = $this->permissionModel->getJunctionPermissions(["JunctionID" => $categoryID], "Category", "", [
            "AddDefaults" => $addDefaults,
        ]);
        $permissions = array_column($permissions, null, "RoleID");
        return $permissions;
    }

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->container()->call(function (
            CategoryModel $categoryModel,
            PermissionModel $permissionModel,
            TagModel $tagModel
        ) {
            $this->categoryModel = $categoryModel;
            $this->permissionModel = $permissionModel;
            $this->tagModel = $tagModel;
        });
    }

    /**
     * Verify ability to update category permissions by providing a permission config array.
     */
    public function testEditCategoryCustomPermissions(): void
    {
        $id = $this->categoryModel->save([
            "Name" => __FUNCTION__,
            "UrlCode" => strtolower(__FUNCTION__),
        ]);

        $permissions = $this->getCategoryPermissions($id, true);
        $discussionsView = !$permissions[RoleModel::MEMBER_ID]["Vanilla.Discussions.View"];

        $this->bessy()->postHtml("vanilla/settings/editcategory.json", [
            "CategoryID" => $id,
            "Permissions" => [
                [
                    "RoleID" => RoleModel::MEMBER_ID,
                    "Vanilla.Discussions.View" => $discussionsView,
                ],
            ],
        ]);

        $row = $this->categoryModel->getID($id, \DATASET_TYPE_ARRAY);
        $this->assertSame((int) $id, $row["PermissionCategoryID"]);

        $result = $this->getCategoryPermissions($id, false);

        $this->assertSame($discussionsView, (bool) $result[RoleModel::MEMBER_ID]["Vanilla.Discussions.View"]);
    }

    /**
     * Verify ability to reset custom permissions on a category.
     *
     * @param array $request
     * @dataProvider provideEditCategoryResetCustomPermissionsData
     */
    public function testEditCategoryResetCustomPermissions(array $request): void
    {
        $name = __FUNCTION__ . md5(serialize($request));
        $id = $this->categoryModel->save([
            "Name" => $name,
            "UrlCode" => strtolower($name),
        ]);

        $this->bessy()->postHtml("vanilla/settings/editcategory.json", [
            "CategoryID" => $id,
            "Permissions" => [],
        ]);
        $row = $this->categoryModel->getID($id, \DATASET_TYPE_ARRAY);
        $this->assertSame((int) $id, $row["PermissionCategoryID"]);

        $this->bessy()->postHtml("vanilla/settings/editcategory.json", ["CategoryID" => $id] + $request);
        $row = $this->categoryModel->getID($id, \DATASET_TYPE_ARRAY);
        $this->assertSame(CategoryModel::ROOT_ID, $row["PermissionCategoryID"]);

        $this->assertTrue(true);
    }

    /**
     * Provide valid request parameters for resetting a category's permissions via the Edit Category page.
     *
     * @return array
     */
    public function provideEditCategoryResetCustomPermissionsData(): array
    {
        return [
            "CustomPermissions: false" => [["CustomPermissions" => false]],
            "Permissions: null" => [["Permissions" => null]],
        ];
    }

    /**
     * Verify custom category permissions persist during a sparse update.
     */
    public function testSparseEditCategoryWithCustomPermissions(): void
    {
        $id = $this->categoryModel->save([
            "Name" => __FUNCTION__,
            "UrlCode" => strtolower(__FUNCTION__),
        ]);

        $permissions = $this->getCategoryPermissions($id, true);
        $discussionsView = !$permissions[RoleModel::MEMBER_ID]["Vanilla.Discussions.View"];

        $this->bessy()->postHtml("vanilla/settings/editcategory.json", [
            "CategoryID" => $id,
            "Permissions" => [
                [
                    "RoleID" => RoleModel::MEMBER_ID,
                    "Vanilla.Discussions.View" => $discussionsView,
                ],
            ],
        ]);

        $updatedRow = $this->categoryModel->getID($id, \DATASET_TYPE_ARRAY);
        $this->assertSame((int) $id, $updatedRow["PermissionCategoryID"]);

        $updatedPermissions = $this->getCategoryPermissions($id, false);
        $this->assertSame(
            $discussionsView,
            (bool) $updatedPermissions[RoleModel::MEMBER_ID]["Vanilla.Discussions.View"]
        );

        $updatedName = md5(time());
        $this->bessy()->postHtml("vanilla/settings/editcategory.json", [
            "CategoryID" => $id,
            "Name" => $updatedName,
        ]);
        $resultRow = $this->categoryModel->getID($id, \DATASET_TYPE_ARRAY);
        $resultPermissions = $this->getCategoryPermissions($id, false);

        $this->assertSame($updatedName, $resultRow["Name"]);
        $this->assertSame((int) $id, $resultRow["PermissionCategoryID"]);
        $this->assertSame(
            $discussionsView,
            (bool) $resultPermissions[RoleModel::MEMBER_ID]["Vanilla.Discussions.View"]
        );
    }

    /**
     * Test deleting a reserved tag.
     */
    public function testDeleteTags(): void
    {
        $tagIDReserved = $this->tagModel->save([
            "Name" => "test tag1",
            "FullName" => "test tag1",
            "Type" => "reaction",
        ]);
        $tagID = $this->tagModel->save([
            "Name" => "test tag2",
            "FullName" => "test tag2",
        ]);
        $this->bessy()->post("/settings/tags/delete/{$tagID}");
        $this->bessy()->post("/settings/tags/delete/{$tagIDReserved}");
        $resultReserved = $this->tagModel->getID($tagIDReserved, DATASET_TYPE_ARRAY);
        $resultNotReserved = $this->tagModel->getID($tagID, DATASET_TYPE_ARRAY);
        $this->assertNotEmpty($resultReserved);
        $this->assertFalse($resultNotReserved);
    }

    /**
     * Test saving/loading custom domains for Kaltura embeds.
     */
    public function testSaveLoadKalturaCustomDomains(): void
    {
        $customDomains = ["mycustomdomain.ca", "yourcustomdomain.com"];
        $this->runWithConfig([\VanillaSettingsController::CONFIG_KALTURA_DOMAINS => $customDomains], function () use (
            $customDomains
        ) {
            $postingUrl = "/vanilla/settings/posting";

            $PostingSettingsHtml = $this->bessy()->getHtml($postingUrl);
            foreach ($customDomains as $customDomain) {
                $PostingSettingsHtml->assertContainsString($customDomain);
            }

            $newCustomDomains = ["hiscustomdomain.org", "hercustomdomain.co.uk"];
            $postingFormValues = [
                "Vanilla.Comment.MaxLength" => 100,
                "Garden.InputFormatter" => "rich",
                "Garden.MobileInputFormatter" => "rich",
                "Vanilla.Discussions.PerPage" => 10,
                "Vanilla.Comments.PerPage" => 10,
                "Vanilla.Discussion.Title.MaxLength" => 100,
                \VanillaSettingsController::CONFIG_KALTURA_DOMAINS => implode("\n", $newCustomDomains),
            ];

            $this->bessy()->post($postingUrl, $postingFormValues);

            \Gdn::config()->get(\VanillaSettingsController::CONFIG_KALTURA_DOMAINS);
            $this->assertEquals(
                $newCustomDomains,
                \Gdn::config()->get(\VanillaSettingsController::CONFIG_KALTURA_DOMAINS)
            );
        });
    }

    /**
     * Test showing Captcha option fields depending on Garden.Registration.ManageCaptcha value.
     */
    public function testShowingCaptchaOptions(): void
    {
        $interfaceUrl = "/dashboard/settings/registration";

        $interfaceHtml = $this->runWithConfig(["Garden.Registration.ManageCaptcha" => true], function () use (
            $interfaceUrl
        ) {
            return $this->bessy()->getHtml($interfaceUrl);
        });
        $interfaceHtml->assertCssSelectorExists("#CaptchaSettings");

        $interfaceHtml = $this->runWithConfig(["Garden.Registration.ManageCaptcha" => false], function () use (
            $interfaceUrl
        ) {
            return $this->bessy()->getHtml($interfaceUrl);
        });
        $interfaceHtml->assertCssSelectorNotExists("#CaptchaSettings");
    }

    /**
     * Test saving custom Captcha option values.
     */
    public function testSavingCaptchaOptions(): void
    {
        // The administrative dashboard that has the Recaptcha config fields.
        $interfaceUrl = "/dashboard/settings/registration";

        // Set of new Recaptcha values.
        $newConfigValues = [
            "Recaptcha.PublicKey" => "MyNewRecaptchaPublicKey",
            "Recaptcha.PrivateKey" => "MyNewRecaptchaPrivateKey",
            "RecaptchaV3.PublicKey" => "MyNewRecaptchaV3PublicKey",
            "RecaptchaV3.PrivateKey" => "MyNewRecaptchaV3PrivateKey",
        ];

        // Get the form's values & merge it with our new ones.
        $formValues = $this->bessy()
            ->getHtml($interfaceUrl)
            ->getFormValues();
        $formValues = array_merge($formValues, $newConfigValues);

        // Submit the form with the new values.
        $this->bessy()->postBackHtml($formValues);
        $html = $this->bessy()->getHtml($interfaceUrl);

        $form = new Gdn_Form();
        // For each new values.
        foreach ($newConfigValues as $newConfigField => $newConfigValue) {
            // Check if the corresponding form field has the correct value.
            $html->assertFormInput($form->escapeFieldName($newConfigField), $newConfigValue);
            // Check if the new value has been saved as a config.
            $this->assertConfigValue($newConfigField, $newConfigValue);
        }
    }

    /**
     * Test uploading default avatar
     */
    public function testDefaultAvatarUpload(): void
    {
        $defaultAvatar = \Gdn::config("Garden.DefaultAvatar");
        $this->assertEmpty($defaultAvatar);
        // Upload a new svg avatar.
        TestUploader::uploadFile("DefaultAvatar", PATH_ROOT . "/tests/fixtures/insightful.svg");
        $this->bessy()->post("dashboard/settings/defaultavatar");
        $defaultAvatar = \Gdn::config("Garden.DefaultAvatar");
        $this->assertNotEmpty($defaultAvatar);
        $this->assertFileExists(PATH_UPLOADS . "/" . $defaultAvatar);

        // Now upload a new jpg avatar.
        TestUploader::uploadFile("DefaultAvatar", PATH_ROOT . "/tests/fixtures/apple.jpg");
        $this->bessy()->post("dashboard/settings/defaultavatar");
        $newDefaultAvatar = \Gdn::config("Garden.DefaultAvatar");
        $this->assertNotEmpty($defaultAvatar);
        $this->assertNotEquals($defaultAvatar, $newDefaultAvatar);
        $this->assertFileExists(PATH_UPLOADS . "/" . $newDefaultAvatar);
    }

    /**
     * Smoke test of the add category page when custom post types is enabled.
     *
     * @return void
     */
    public function testAddCategoryWithCustomPostTypes()
    {
        $this->runWithConfig(
            [FeatureFlagHelper::featureConfigKey(PostTypeModel::FEATURE_POST_TYPES) => true],
            function () {
                $response = $this->bessy()->getJsonData(
                    "vanilla/settings/addcategory.json",
                    options: ["deliveryType" => DELIVERY_TYPE_ALL]
                );
                $data = $response->getData();
                $this->assertArrayHasKey("postTypeProps", $data);
            }
        );
    }

    /**
     * Smoke test of the edit category page when custom post types is enabled.
     *
     * @return void
     */
    public function testEditCategoryWithCustomPostTypes()
    {
        $this->runWithConfig(
            [FeatureFlagHelper::featureConfigKey(PostTypeModel::FEATURE_POST_TYPES) => true],
            function () {
                $postType = $this->createPostType();
                $category = $this->createCategory(["allowedPostTypeIDs" => [$postType["postTypeID"]]]);

                $response = $this->bessy()->getJsonData(
                    "vanilla/settings/editcategory.json",
                    ["categoryid" => $category["categoryID"]],
                    options: ["deliveryType" => DELIVERY_TYPE_ALL]
                );
                $data = $response->getData();
                $this->assertArrayHasKey("postTypeProps", $data);
                $this->assertDataLike(
                    [
                        "postTypeProps.instance.hasRestrictedPostTypes" => true,
                        "postTypeProps.instance.allowedPostTypeIDs" => [$postType["postTypeID"]],
                    ],
                    $data
                );
            }
        );
    }

    /**
     * Test that invalid discussion types are filtered out when editing a category.
     *
     * This test verifies that when a category has invalid discussion types stored in the database,
     * they are automatically filtered out when the category data is retrieved for the edit form.
     * Only valid discussion types should be returned.
     *
     * @return void
     */
    public function testEditCategoryNonExistentDiscussionType()
    {
        // Create a category with 2 post types, one of which does not exist.
        $newCategory = $this->createCategory(["Name" => "Test Category"]);
        // Force in 2 post types, one of which does not exist.
        $this->categoryModel->save([
            "CategoryID" => $newCategory["categoryID"],
            "AllowedDiscussionTypes" => ["Discussion", "Poll"],
        ]);

        // Grab the data for the edit category page using bessy.
        $jsonEncodedData = $this->bessy()
            ->getHtml(
                "vanilla/settings/editcategory/{$newCategory["categoryID"]}",
                [],
                ["deliveryType" => DELIVERY_TYPE_DATA]
            )
            ->getRawHtml();
        $jsonDecodedData = json_decode($jsonEncodedData, true);

        $this->assertCount(1, $jsonDecodedData["AllowedDiscussionTypes"]);
        $this->assertArrayHasKey("Discussion", $jsonDecodedData["AllowedDiscussionTypes"]);
        $this->assertArrayNotHasKey("Poll", $jsonDecodedData["AllowedDiscussionTypes"]);
    }
}
