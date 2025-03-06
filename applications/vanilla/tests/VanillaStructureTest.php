<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Forum;

use PHPUnit\Framework\TestCase;
use Vanilla\Forum\Models\PostTypeModel;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\SiteTestTrait;

/**
 * Test the migration of the hero image plugin.
 */
class VanillaStructureTest extends SiteTestCase
{
    use CommunityApiTestTrait;

    public static function getAddons(): array
    {
        $addons = parent::getAddons();
        $addons[] = "QnA";
        $addons[] = "ideation";
        $addons[] = "polls";
        return $addons;
    }

    /**
     * Test that the old
     */
    public function testHeroImageMigration()
    {
        $structure = \Gdn::structure();
        $config = \Gdn::config();

        // Write the old config setting.
        $config->saveToConfig("Garden.HeroImage", "config.png");

        // Drop the old column so we can simulate the migration.
        $structure->table("Category")->dropColumn("BannerImage");

        // Make the category setup column.
        $structure
            ->table("Category")
            ->column("HeroImage", "varchar(255)", true)
            ->set();

        /** @var \CategoryModel $categoryModel */
        $categoryModel = $this->container()->get(\CategoryModel::class);

        $categoryID = $categoryModel->save([
            "Name" => "Category Custom",
            "HeroImage" => "category.png",
            "UrlCode" => randomString(20),
        ]);

        // Apply the structure file.
        include PATH_APPLICATIONS . "/vanilla/settings/structure.php";
        include_once PATH_LIBRARY . "/SmartyPlugins/function.hero_image_link.php";
        include_once PATH_LIBRARY . "/SmartyPlugins/function.banner_image_url.php";

        $uploadBase = "https://vanilla.test/vanillastructuretest/uploads/";

        // Test that we can still fetch our information using the old functions after the migrations.
        $this->assertEquals("category.png", @\HeroImagePlugin::getHeroImageSlug($categoryID));
        $this->assertEquals("config.png", @\HeroImagePlugin::getHeroImageSlug(null));

        $smarty = new \stdClass();
        $this->assertEquals("${uploadBase}config.png", @\smarty_function_hero_image_link([], $smarty));

        $ctrl = new \Gdn_Controller();
        $ctrl->setData("Category.CategoryID", $categoryID);
        \Gdn::controller($ctrl);

        $this->assertEquals("${uploadBase}category.png", @\smarty_function_hero_image_link([], $smarty));
    }

    /**
     * Test that we properly fix input formatters that were improperly named.
     */
    public function testInputFormatRename()
    {
        $config = \Gdn::config();
        $config->set("Garden.InputFormatter", "rich");
        $config->set("Garden.MobileInputFormatter", "rich");

        // Run the structure.
        include PATH_APPLICATIONS . "/vanilla/settings/structure.php";

        $this->assertSame("Rich", $config->get("Garden.InputFormatter"));
        $this->assertSame("Rich", $config->get("Garden.MobileInputFormatter"));
    }

    /**
     * Test migration of category allowed discussion types to the post type category junction table.
     *
     * @return void
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     * @throws \Throwable
     */
    public function testMigrateCategoryDiscussionTypesToPostTypes()
    {
        \Gdn::sql()->truncate("Category");
        \Gdn::config()->removeFromConfig(["postTypes.2025_003.wasMigrated", "postTypes.2025_003.wasMigratedV2"]);

        // Category with custom permissions and a child
        $category1 = $this->createCategory(["allowedDiscussionTypes" => ["Idea"], "customPermissions" => true]);

        // Category that inherits from category1 above.
        $category2 = $this->createCategory();

        // Category with custom permissions but didn't have the permissionCategoryID set.
        $category3 = $this->createCategory([
            "parentCategoryID" => -1,
            "name" => __FUNCTION__,
            "allowedDiscussionTypes" => ["Discussion", "Question", "Poll"],
        ]);

        \Gdn::database()
            ->createSql()
            ->truncate("postTypeCategoryJunction");

        include PATH_APPLICATIONS . "/vanilla/settings/structure.php";

        $rows = \Gdn::database()
            ->createSql()
            ->getWhere("postTypeCategoryJunction")
            ->resultArray();

        $this->assertRowsLike(
            [
                "categoryID" => [
                    $category1["categoryID"],
                    $category2["categoryID"],
                    $category3["categoryID"],
                    $category3["categoryID"],
                    $category3["categoryID"],
                ],
                "postTypeID" => ["idea", "idea", "discussion", "question", "poll"],
            ],
            $rows,
            strictOrder: false
        );

        // Run full structure to re-create addon-specific post types.
        \Gdn::getContainer()
            ->get(\UpdateModel::class)
            ->runStructure();

        // Test with category api endpoints with postTypeID filter.
        $this->api()
            ->get("/categories", ["postTypeID" => "idea", "outputFormat" => "flat"])
            ->assertJsonArrayValues(["categoryID" => [$category1["categoryID"], $category2["categoryID"]]]);

        $this->api()
            ->get("/categories", ["postTypeID" => "question", "outputFormat" => "flat"])
            ->assertJsonArrayValues(["categoryID" => [$category3["categoryID"]]]);
    }
}
