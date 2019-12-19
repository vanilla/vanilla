<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Dashboard\Models;

use PHPUnit\Framework\TestCase;
use Vanilla\Dashboard\Models\BannerImageModel;
use VanillaTests\SiteTestTrait;

/**
 * Tests for the banner image model.
 */
class BannerImageModelTest extends TestCase {

    use SiteTestTrait;

    /** @var array */
    private $cat1;

    /** @var array */
    private $cat1_1;

    /** @var array */
    private $cat2;

    /** @var array */
    private $cat2_1;

    /** @var array */
    private $cat2_2;

    /** @var array */
    private $cat2_2_1;

    /**
     * Setup some categories.
     */
    public function setUp(): void {
        parent::setUp();
        \Gdn::config()->saveToConfig('Garden.BannerImage', 'default.png');

        $model = new \CategoryModel();
        $this->cat1 = $model->save([
            'Name' => 'Cat 1',
            'UrlCode' => randomString(5),
        ]);
        $this->cat1_1 = $model->save([
            'Name' => 'Cat 1.1',
            'ParentCategoryID' => $this->cat1,
            'UrlCode' => randomString(5),
        ]);

        $this->cat2 = $model->save([
            'Name' => 'Cat 2',
            'BannerImage' => "2.png",
            'UrlCode' => randomString(5),
        ]);

        $this->cat2_1 = $model->save([
            'Name' => 'Cat 2.1',
            'ParentCategoryID' => $this->cat2,
            'BannerImage' => "2_1.png",
            'UrlCode' => randomString(5),
        ]);

        $this->cat2_2 = $model->save([
            'Name' => 'Cat 2.2',
            'ParentCategoryID' => $this->cat2,
            'UrlCode' => randomString(5),
        ]);

        $this->cat2_2_1 = $model->save([
            'Name' => 'Cat 2.2.1',
            'ParentCategoryID' => $this->cat2_2,
            'UrlCode' => randomString(5),
        ]);
    }

    /**
     * Test getting a banner image slug.
     *
     * @param mixed $testLookup
     * @param string $expectedImage
     *
     * @dataProvider provideCategories
     */
    public function testGetBannerImageSlug($testLookup, string $expectedImage) {
        // Workaround from data provider.
        $id = $this->{$testLookup};
        $this->assertEquals($expectedImage, BannerImageModel::getBannerImageSlug($id));
    }

    /**
     * Test that bad values return default.
     */
    public function testDefaultValues() {
        $this->assertEquals('default.png', BannerImageModel::getBannerImageSlug(null));
        $this->assertEquals('default.png', BannerImageModel::getBannerImageSlug(-1));
        $this->assertEquals('default.png', BannerImageModel::getBannerImageSlug('asdfasdf'));
    }

    /**
     * Test getting contextual controller values.
     */
    public function testGetCurrent() {
        $uploadPrefix = 'http://vanilla.test/bannerimagemodeltest/uploads/';

        // No controller
        $this->assertEquals(
            $uploadPrefix.'default.png',
            BannerImageModel::getCurrentBannerImageLink(),
            'It works with no controller'
        );

        $controller = new \Gdn_Controller();
        $controller->setData('ContextualCategoryID', $this->cat2_1);
        \Gdn::controller($controller);
        $this->assertEquals(
            $uploadPrefix.'2_1.png',
            BannerImageModel::getCurrentBannerImageLink(),
            'It can pull the contextual categoryID'
        );


        $controller = new \Gdn_Controller();
        $controller->setData('Category.CategoryID', $this->cat2);
        \Gdn::controller($controller);
        $this->assertEquals(
            $uploadPrefix.'2.png',
            BannerImageModel::getCurrentBannerImageLink(),
            'It can pull the main set category ID'
        );
    }

    /**
     * @return array
     */
    public function provideCategories(): array {
        return [
            ['cat1', 'default.png'],
            ['cat1_1', 'default.png'],
            ['cat2', '2.png'],
            ['cat2_1', '2_1.png'],
            ['cat2_2', '2.png'],
            ['cat2_2_1', '2.png'],
        ];
    }
}
