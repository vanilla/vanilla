<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\Library\Garden\Web;

use Garden\Web\Data;
use Garden\Web\Dispatcher;
use Garden\Web\RequestInterface;
use PHPUnit\Framework\TestCase;
use VanillaTests\Fixtures\Request;

/**
 * Test methods on the Dispatcher class.
 */
class DispatcherTest extends TestCase {
    /**
     * Test Dispatcher::callMiddlewares
     */
    public function testCallMiddlewares() {
        $mw = function ($v): callable {
            return function (Request $r, callable $next) use ($v): Data {
                $r->setHeader("test", $r->getHeader("test").$v);

                /* @var \Garden\Web\Data $response */
                $response = $next($r);
                $response->setHeader("test", $response->getHeader("test").$v);

                return $response;
            };
        };

        $r = Dispatcher::callMiddlewares(
            new Request(),
            [
                $mw('a'),
                $mw('b'),
                $mw('c'),
            ], function (RequestInterface $request): Data {
                $response = new Data();
                $response->setHeader("test", $request->getHeader("test").'o');
                return $response;
            }
        );

        $this->assertEquals("abcocba", $r->getHeader("test"));
    }
}
