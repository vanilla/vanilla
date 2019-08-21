<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla;

use Deeply\Nested\Namespaced\Fixture\NamespacedPlugin;
use Garden\Container\Container;
use Garden\EventManager;
use Vanilla\Addon;
use Vanilla\AddonManager;
use VanillaTests\Fixtures\BasicEventHandlers;
use Vanilla\VanillaClassLocator;
use VanillaTests\Fixtures\SomeController;
use VanillaTests\Library\Garden\ClassLocatorTest;

class VanillaClassLocatorTest extends ClassLocatorTest {

    public function testFindMethodWithOverride() {
        $container = new Container();
        $container
            ->setInstance(Container::class, $container)
            ->setInstance(\Psr\Container\ContainerInterface::class, $container)

            ->defaultRule()
            ->setShared(true)
            ->rule(EventManager::class)
            ->addCall('bindClass', [BasicEventHandlers::class]);

        $vanillaClassLocator = $container->get(VanillaClassLocator::class);
        $handler = $vanillaClassLocator->findMethod($container->get(SomeController::class), 'someEndpoint_method');

        $this->assertTrue(is_callable($handler));

        list($object, $method) = $handler;
        $this->assertSame($container->get(BasicEventHandlers::class), $object);
        $this->assertSame(strtolower('someController_someEndpoint_method'), strtolower($method));
    }

    public function testFindClassWithWildcard() {
        $addonManager = new AddonManager([
            Addon::TYPE_ADDON => '/tests/fixtures/plugins',
        ], PATH_ROOT.'/tests/cache/'.EventManager::classBasename(__CLASS__));
        $addonManager->startAddonsByKey('namespaced-plugin', Addon::TYPE_ADDON);

        $classLocator = new VanillaClassLocator(new EventManager(), $addonManager);

        $className = $classLocator->findClass('*\\NamespacedPlugin');
        $this->assertEquals(NamespacedPlugin::class, $className);

        // The class locator should still locate regular classes.
        $myClassName = $classLocator->findClass(__CLASS__);
        $this->assertEquals(__CLASS__, $myClassName);
    }
}
