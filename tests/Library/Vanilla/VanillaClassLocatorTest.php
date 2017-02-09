<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\Library\Vanilla;

use Garden\Container\Container;

class VanillaClassLocatorTest extends \PHPUnit_Framework_TestCase {

    public function testFindMethod() {
        $container = new Container();
        $eventManager = $container->get('Garden\\EventManager');
        $container->setInstance('Garden\\EventManager', $eventManager);

        $basicEventHandlers = $container->get('VanillaTests\\Fixtures\\BasicEventHandlers');
        $eventManager->bindClass($basicEventHandlers);

        $vanillaClassLocator = $container->get('Vanilla\\VanillaClassLocator');
        $someController = $container->get('VanillaTests\\Fixtures\\SomeController');
        $method = $vanillaClassLocator->findMethod($someController, 'someEndpoint');

        $this->assertNotNull($method);
        $this->assertSame($basicEventHandlers, $method[0]);
        $this->assertSame('somecontroller_someendpoint_method', $method[1]);
    }
}
