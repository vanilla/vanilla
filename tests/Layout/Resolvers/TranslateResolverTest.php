<?php
/**
 * @author Gary Pomerant gpomerant@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Layout\Resolvers;

use Garden\Hydrate\DataHydrator;
use Vanilla\Addon;
use Vanilla\Layout\LayoutHydrator;
use Vanilla\Models\AddonModel;
use VanillaTests\SiteTestCase;

/**
 * Tests for the Language Resolver.
 */
class TranslateResolverTest extends SiteTestCase
{
    /**
     * Enable the test-fr locale.
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::enableLocaleFixtures();
    }

    /**
     * Test that we can resolve from locale param.
     *
     * @return void
     */
    public function testHydrateLocaleParam()
    {
        $input = [
            "layout" => [
                '$hydrate' => "translate",
                "code" => "foo",
            ],
        ];

        $expected = [
            "layout" => "bar-fr",
        ];

        $actual = $this->getHydrator()->resolve($input, ["locale" => "fr"]);
        $this->assertEquals($expected, $actual);
    }

    /**
     * Test that we can resolve from hydrate specific locale.
     *
     * @return void
     */
    public function testHydrateFromSpecificLocale()
    {
        $input = [
            "layout" => [
                '$hydrate' => "translate",
                "code" => "foo",
                "locale" => "fr",
            ],
        ];

        $expected = [
            "layout" => "bar-fr",
        ];

        $actual = $this->getHydrator()->resolve($input);
        $this->assertEquals($expected, $actual);
    }

    /**
     * Test the implicit default value works.
     *
     * @return void
     */
    public function testImplicitDefault()
    {
        $input = [
            "layout" => [
                '$hydrate' => "translate",
                "code" => "Dashboard",
                "locale" => "es",
            ],
        ];

        $expected = [
            "layout" => "Dashboard",
        ];

        $actual = $this->getHydrator()->resolve($input);
        $this->assertEquals($expected, $actual);
    }

    /**
     * Test that the defualt paramater works.
     *
     * @return void
     */
    public function testDefault()
    {
        $input = [
            "layout" => [
                '$hydrate' => "translate",
                "code" => "Dashboard",
                "default" => "Defaulted",
                "locale" => "es",
            ],
        ];

        $expected = [
            "layout" => "Defaulted",
        ];

        $actual = $this->getHydrator()->resolve($input);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @return DataHydrator
     */
    private function getHydrator(): DataHydrator
    {
        return self::container()
            ->get(LayoutHydrator::class)
            ->getHydrator(null);
    }
}
