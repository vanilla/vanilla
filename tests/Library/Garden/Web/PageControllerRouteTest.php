<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Garden\Web;

use Garden\Container\Container;
use Garden\Web\Dispatcher;
use Garden\Web\PageControllerRoute;
use VanillaTests\Fixtures\Request;
use VanillaTests\Fixtures\Web\TestPageController;
use VanillaTests\SiteTestCase;

/**
 * Tests for the page controller route.
 */
class PageControllerRouteTest extends SiteTestCase
{
    /**
     * Configure the container before addons are started.
     *
     * @param Container $container
     */
    public static function configureContainerBeforeStartup(Container $container)
    {
        PageControllerRoute::configurePageRoutes(self::container(), [
            "/test/nested" => TestPageController::class,
            "/not-nested" => TestPageController::class,
        ]);
    }

    /**
     * Test that expected routes are resolved by the dispatcher.
     *
     * @return void
     */
    public function testResolvesRoute()
    {
        $dispatcher = self::container()->get(Dispatcher::class);

        $expectedTitles = [
            "/test/nested/hello" => 200,
            "/test/nested/world" => 200,
            "/not-nested/hello" => 200,
            "/not-nested/world" => 200,

            // Bad
            "/test/nested/test/nested/hello" => 404,
            "/test/nested" => 404,
            "/test/nested/other" => 404,
        ];

        foreach ($expectedTitles as $url => $responseCode) {
            $response = $dispatcher->dispatch(new Request($url));
            $this->assertEquals($responseCode, $response->getStatus());
        }
    }
}
