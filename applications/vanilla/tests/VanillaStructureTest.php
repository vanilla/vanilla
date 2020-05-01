<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Forum;

use PHPUnit\Framework\TestCase;
use VanillaTests\SiteTestTrait;

/**
 * Test the migration of the hero image plugin.
 */
class VanillaStructureTest extends TestCase {

    use SiteTestTrait;

    /**
     * Test that the old
     */
    public function testHeroImageMigration() {
        $structure = \Gdn::structure();
        $config = \Gdn::config();

        // Write the old config setting.
        $config->saveToConfig('Garden.HeroImage', 'config.png');

        // Drop the old column so we can simulate the migration.
        $structure->table('Category')->dropColumn('BannerImage');

        // Make the category setup column.
        $structure
            ->table('Category')
            ->column("HeroImage", 'varchar(255)', true)
            ->set()
        ;

        /** @var \CategoryModel $categoryModel */
        $categoryModel = $this->container()->get(\CategoryModel::class);

        $categoryID = $categoryModel->save([
            'Name' => 'Category Custom',
            'HeroImage' => "category.png",
            'UrlCode' => randomString(5),
        ]);

        // Apply the structure file.
        include PATH_APPLICATIONS.'/vanilla/settings/structure.php';
        include_once PATH_LIBRARY.'/SmartyPlugins/function.hero_image_link.php';
        include_once PATH_LIBRARY.'/SmartyPlugins/function.banner_image_url.php';

        $uploadBase = 'http://vanilla.test/vanillastructuretest/uploads/';

        // Test that we can still fetch our information using the old functions after the migrations.
        $this->assertEquals("category.png", @\HeroImagePlugin::getHeroImageSlug($categoryID));
        $this->assertEquals("config.png", @\HeroImagePlugin::getHeroImageSlug(null));

        $smarty = new \stdClass();
        $this->assertEquals("${uploadBase}config.png", @\smarty_function_hero_image_link([], $smarty));

        $ctrl = new \Gdn_Controller();
        $ctrl->setData('Category.CategoryID', $categoryID);
        \Gdn::controller($ctrl);

        $this->assertEquals("${uploadBase}category.png", @\smarty_function_hero_image_link([], $smarty));
    }

    /**
     * Test that we properly fix input formatters that were improperly named.
     */
    public function testInputFormatRename() {
        $config = \Gdn::config();
        $config->set('Garden.InputFormatter', 'rich');
        $config->set('Garden.MobileInputFormatter', 'rich');

        // Run the structure.
        include PATH_APPLICATIONS.'/vanilla/settings/structure.php';

        $this->assertSame('Rich', $config->get('Garden.InputFormatter'));
        $this->assertSame('Rich', $config->get('Garden.MobileInputFormatter'));
    }
}
