<?php
/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Controllers;

use CategoryModel;
use Gdn_Form;
use PermissionModel;
use RoleModel;
use TagModel;
use VanillaTests\SiteTestCase;

/**
 * Test various capabilities of VanillaSettingsController.
 */
class VanillaSettingsControllerTest extends SiteTestCase
{
    /** @inheritDoc */
    protected static $addons = ["vanilla"];

    /** @var CategoryModel */
    private $categoryModel;

    /** @var PermissionModel */
    private $permissionModel;

    /** TagModel */
    private $tagModel;

    /**
     * Grab an array of category permissions, indexed by role ID.
     *
     * @param int $categoryID
     * @param bool $addDefaults
     * @return array
     */
    private function getCategoryPermissions(int $categoryID, bool $addDefaults): array
    {
        $permissions = $this->permissionModel->getJunctionPermissions(["JunctionID" => $categoryID], "Category", "", [
            "AddDefaults" => $addDefaults,
        ]);
        $permissions = array_column($permissions, null, "RoleID");
        return $permissions;
    }

    /**
     * @inheritDoc
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
     * Test "enabling/disabling including full posts within email digests" option.
     */
    public function testEnableDisableEmailFullPost(): void
    {
        $config = \Gdn::config();

        // This config defaults to false.
        $this->assertFalse($config->get("Vanilla.Email.FullPost"));

        // Setting the config to true.
        $this->bessy()->post("/dashboard/settings/toggleemailfullpost/1");
        $this->assertTrue($config->get("Vanilla.Email.FullPost"));
        $html = $this->bessy()->getHtml("/dashboard/settings/emailstyles");
        // Verify that the interface has a turned on toggle.
        $html->assertCssSelectorExists('span.toggle-wrap-on a[href$="/dashboard/settings/toggleemailfullpost/0"]');

        // Setting the config back to false again.
        $this->bessy()->post("/dashboard/settings/toggleemailfullpost/0");
        $this->assertFalse($config->get("Vanilla.Email.FullPost"));
        $html = $this->bessy()->getHtml("/dashboard/settings/emailstyles");
        // Verify that the interface has a turned off toggle.
        $html->assertCssSelectorExists('span.toggle-wrap-off a[href$="/dashboard/settings/toggleemailfullpost/1"]');
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
}
