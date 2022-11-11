<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Library\Vanilla\Web;

use VanillaTests\SiteTestCase;

/**
 * Test that Extension being fed to the API are properly handled.
 */
class ApiExtensionHandlingTest extends SiteTestCase
{
    protected static $addons = ["CustomTheme"];

    /**
     * Test that the
     * @dataProvider provideRouteWithExtension
     */
    public function testRouteWithExtension($path, $contentType, array $query = [])
    {
        $result = $this->api()->get($path, $query);
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals($contentType, $result->getHeader("Content-Type"));
    }

    /**
     * Provide data for testRouteWithExtension
     *
     * @return array
     */
    public function provideRouteWithExtension(): array
    {
        $r = [
            ["/users.csv", "application/csv; charset=utf-8", ["limit" => 5000]],
            ["/users/1.csv", "application/csv; charset=utf-8"],
            ["/categories.csv?categoryID=1..4", "application/csv; charset=utf-8"],
            ["/users", "application/json; charset=utf-8"],
            ["/users/1", "application/json; charset=utf-8"],
            ["/categories?categoryID=1..4", "application/json; charset=utf-8"],
            ["/categories?invalidParam=test.csv", "application/json; charset=utf-8"],
        ];
        return $r;
    }
}
