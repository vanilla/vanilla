<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Theme;

use Vanilla\Theme\VariableProviders\QuickLink;
use VanillaTests\SiteTestCase;

/**
 * Class QuickLinksTest
 *
 * @package VanillaTests\Library\Vanilla\Theme
 */
class QuickLinksTest extends SiteTestCase {

    /**
     * Setup.
     */
    public static function setUpBeforeClass(): void {
        self::$addons = ['vanilla'];
        parent::setUpBeforeClass();
    }

    /**
     * Setup.
     */
    public function setUp(): void {
        parent::setUp();
    }

    /**
     * Test quickLink methods.
     */
    public function testQuickLink() {
        $name = 'test';
        $url = '/test';
        $newQuickLink = $this->addQuickLink($name, $url, 2, 11);
        $this->assertEquals(slugify($name), $newQuickLink->getID());
        $this->assertEquals(2, $newQuickLink->getCount());
        $this->assertEquals(11, $newQuickLink->getSort());

        $name = 'test2';
        $url = '/test2';
        $newQuickLink = $this->addQuickLink($name, $url, null, null);

        $this->assertEquals(slugify($name), $newQuickLink->getID());
        $this->assertEquals(null, $newQuickLink->getCount());
        $this->assertEquals(null, $newQuickLink->getSort());
    }


    /**
     * Make a simple Quick Link
     *
     * @param string $name
     * @param string $url
     * @param null $count
     * @param null $sort
     *
     * @return QuickLink
     */
    public function addQuickLink(
        $name = 'MockLink',
        $url = '/mocklink',
        $count = null,
        $sort = null
    ) {
        return new QuickLink(
            $name,
            $url,
            $count,
            $sort
        );
    }
}
