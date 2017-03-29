<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\Library\Vanilla;

use Garden\Container\Container;
use Interop\Container\ContainerInterface;
use Garden\EventManager;
use VanillaTests\Fixtures\BasicEventHandlers;
use Vanilla\VanillaClassLocator;
use VanillaTests\Fixtures\SomeController;
use VanillaTests\Library\Garden\ClassLocatorTest;

class VanillaClassLocatorTest extends ClassLocatorTest {

    public function testFindMethodWithOverride() {
        $root = '/tests/fixtures';

        $container = new Container();
        $container
            ->setInstance(ContainerInterface::class, $container)
            ->defaultRule()
            ->setShared(true)

            // EventManager
            ->rule(EventManager::class)
            ->addCall('bindClass', [BasicEventHandlers::class])

            // AddonManager
            ->rule(\Vanilla\AddonManager::class)
            ->setConstructorArgs([
                [
                    \Vanilla\Addon::TYPE_ADDON => "$root/empty",
                    \Vanilla\Addon::TYPE_THEME => "$root/empty",
                    \Vanilla\Addon::TYPE_LOCALE => "$root/empty"
                ],
                PATH_ROOT.'/tests/cache/am/empty-manager'
            ])
        ;

        $vanillaClassLocator = $container->get(VanillaClassLocator::class);
        $handler = $vanillaClassLocator->findMethod($container->get(SomeController::class), 'someEndpoint');

        $this->assertTrue(is_callable($handler));

        list($object, $method) = $handler;
        $this->assertSame($container->get(BasicEventHandlers::class), $object);
        $this->assertSame(strtolower('someController_someEndpoint_method'), strtolower($method));
    }
}
