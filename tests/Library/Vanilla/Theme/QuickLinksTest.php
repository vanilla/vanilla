<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Theme;

use Vanilla\Theme\VariableProviders\QuickLink;
use VanillaTests\SiteTestCase;
use VanillaTests\Layout\LayoutTestTrait;

/**
 * Class QuickLinksTest
 *
 * @package VanillaTests\Library\Vanilla\Theme
 */
class QuickLinksTest extends SiteTestCase
{
    use LayoutTestTrait;

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
    public function addQuickLink($name = "MockLink", $url = "/mocklink", $count = null, $sort = null)
    {
        return new QuickLink($name, $url, $count, $sort);
    }

    /**
     * Test quickLink methods.
     */
    public function testQuickLink()
    {
        $name = "test";
        $url = "/test";
        $newQuickLink = $this->addQuickLink($name, $url, 2, 11);
        $this->assertEquals(slugify($name), $newQuickLink->getID());
        $this->assertEquals(2, $newQuickLink->getCount());
        $this->assertEquals(11, $newQuickLink->getSort());

        $name = "test2";
        $url = "/test2";
        $newQuickLink = $this->addQuickLink($name, $url, null, null);

        $this->assertEquals(slugify($name), $newQuickLink->getID());
        $this->assertEquals(null, $newQuickLink->getCount());
        $this->assertEquals(null, $newQuickLink->getSort());
    }

    /**
     * Verify setting and getting a QuickLink's count limit.
     */
    public function testSetCountLimit(): void
    {
        $limit = 100;
        $quickLink = new QuickLink(__FUNCTION__, "/path/to/page");
        $quickLink->setCountLimit($limit);
        $serialized = json_decode(json_encode($quickLink), true);

        $this->assertSame($limit, $quickLink->getCountLimit());
        $this->assertSame($limit, $serialized["countLimit"]);
    }

    /**
     * Verify default value of the countLimit field.
     */
    public function testNoCountLimit(): void
    {
        $quickLink = new QuickLink(__FUNCTION__, "/path/to/page");
        $serialized = json_decode(json_encode($quickLink), true);

        $this->assertSame(null, $quickLink->getCountLimit());
        $this->assertSame(null, $serialized["countLimit"]);
    }

    /**
     * Test that we can hydrate Quick Links Widget.
     */
    public function testHydrateQuickLinksWidget()
    {
        $spec = [
            '$hydrate' => "react.quick-links",
            "title" => "Quick Links",
            "containerOptions" => [
                "borderType" => "border",
            ],
            "links" => [
                [
                    "name" => "Item1",
                    "url" => "http://something",
                ],
                [
                    "name" => "Item2",
                    "url" => "http://something2",
                ],
            ],
        ];
        $expected = [
            '$reactComponent' => "QuickLinks",
            '$reactProps' => [
                "containerOptions" => $spec["containerOptions"],
                "title" => $spec["title"],
                "links" => $spec["links"],
            ],
        ];
        $this->assertHydratesTo($spec, [], $expected);
    }
}
