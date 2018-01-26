<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\Library\Vanilla\Web;

use PHPUnit\Framework\TestCase;
use Vanilla\Web\EbiView;
use VanillaTests\SiteTestTrait;

class EbiViewTest extends TestCase {
    use SiteTestTrait;

    public function testNormalizeCategoryTree() {
        /* @var EbiView $view */
        $view = static::container()->get(EbiView::class);

        $categories = [
            ['name' => 'a'],
            ['displayAs' => 'heading', 'name' => 'b'],
            ['name' => 'c'],
            ['name' => 'd']
        ];

        $r = $view->normalizeCategoryTree($categories);

        $expected = [
            [
                'children' => [
                    ['name' => 'a']
                ]
            ],
            ['displayAs' => 'heading', 'name' => 'b'],
            [
                'children' => [
                    ['name' => 'c'],
                    ['name' => 'd']
                ]
            ],
        ];

        $this->assertEquals($expected, $r);
    }
}
