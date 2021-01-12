<?php
/**
 * @author Isis Graziatto <isis.g@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Forum\Controllers;

use PHPUnit\Framework\TestCase;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\Models\TestCategoryModelTrait;
use VanillaTests\SetupTraitsTrait;
use VanillaTests\SiteTestTrait;

/**
 * Class CategoriesControllerTest
 * @package VanillaTests\Forum\Controllers
 */
class CategoriesControllerTest extends TestCase {
    use SiteTestTrait, SetupTraitsTrait, CommunityApiTestTrait, TestCategoryModelTrait;

    /**
     * {@inheritDoc}
     */
    public function setUp(): void {
        parent::setUp();
        $this->setupTestTraits();
        /** @var \Gdn_Configuration $config */
        $config = $this->container()->get(\Gdn_Configuration::class);
        $config->saveToConfig('Vanilla.Categories.Use', true);
        $this->category = $this->insertCategories(2)[0];
    }

    /**
     * {@inheritDoc}
     */
    public function tearDown(): void {
        parent::tearDown();
        $this->tearDownTestTraits();
        $this->tearDownTestCategoryModel();
    }

    /**
     * Test get /categories
     *
     * @return array
     */
    public function testCategoriesIndex(): array {
        $data = $this->bessy()->get('/categories')->Data;
        $this->assertNotEmpty($data['CategoryTree']);

        return $data;
    }

    /**
     * Test get /categories with following filter
     *
     * @return array
     */
    public function testFollowedCategoriesIndex(): array {
        /** @var \Gdn_Configuration $config */
        $config = static::container()->get('Config');
        $config->set('Vanilla.EnableCategoryFollowing', true, true, false);
        // follow a category
        $this->followCategory(\Gdn::session()->UserID, 1, true);

        $data = $this->bessy()->get('/categories?followed=1&save=1&TransientKey='.\Gdn::request()->get('TransientKey', ''))->Data;
        $this->assertEquals(1, count($data['CategoryTree']));

        return $data;
    }

    /**
     * Test get /categories/sub-category with following filter
     * Category following should not affect the category tree when in a sub-category
     */
    public function testFollowedSubCategoriesIndex() {
        $this->insertCategories(2, ['ParentCategoryID' => 1]);

        // trigger following filter
        $this->testFollowedCategoriesIndex();

        $data = $this->bessy()->get('/categories/general')->Data;
        $this->assertEquals(2, count($data['CategoryTree']));
    }
}
