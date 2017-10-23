<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\Library\Vanilla;

use Vanilla\PluralizationTrait;

class PluralizationTraitTest extends \PHPUnit\Framework\TestCase {
    use PluralizationTrait;

    /**
     * The **plural** method should convert known singular words to their plural forms.
     *
     * @param string $singular The word's singular form.
     * @param string $plural The word's plural form.
     * @dataProvider provideWordForms
     */
    public function testSingularToPlural($singular, $plural) {
        $r = $this->plural($singular);
        $this->assertEquals($plural, $r);
    }

    /**
     * The **singular** method should convert known plural words to their singular forms.
     *
     * @param string $singular The word's singular form.
     * @param string $plural The word's plural form.
     * @dataProvider provideWordForms
     */
    public function testPluralToSingular($singular, $plural) {
        $r = $this->singular($plural);
        $this->assertEquals($singular, $r);
    }

    /**
     * Provide word forms for pluralization tests.
     *
     * @return array Returns a data provider array.
     */
    public function provideWordForms() {
        $r = [
            ['activity', 'activities'],
            ['addon', 'addons'],
            ['asset', 'assets'],
            ['category', 'categories'],
            ['conversation', 'conversations'],
            ['discussion', 'discussions'],
            ['draft', 'drafts'],
            ['event', 'events'],
            ['group', 'groups'],
            ['message', 'messages'],
            ['module', 'modules'],
            ['notification', 'notifications'],
            ['post', 'posts'],
            ['profile', 'profiles'],
            ['role', 'roles'],
            ['route', 'routes'],
            ['search', 'searches'],
            ['setting', 'settings'],
            ['user', 'users'],
            ['utility', 'utilities'],
        ];

        return array_column($r, null, 0);
    }

}
