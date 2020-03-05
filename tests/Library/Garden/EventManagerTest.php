<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use VanillaTests\SharedBootstrapTestCase;
use Garden\EventManager;
use Vanilla\Addon;
use Vanilla\AddonManager;
use VanillaTests\Fixtures\BasicEventHandlers;
use VanillaTests\Fixtures\Container;

/**
 * Tests for the {@link EventManager} class.
 */
class EventManagerTest extends SharedBootstrapTestCase {

    /**
     * Creates an {@link AddonManager} against Vanilla.
     *
     * @return AddonManager Returns the manager.
     */
    private static function createVanillaManager() {
        $manager = new AddonManager(
            [
                Addon::TYPE_ADDON => ['/applications', '/plugins'],
                Addon::TYPE_THEME => '/themes',
                Addon::TYPE_LOCALE => '/locales'
            ],
            PATH_ROOT.'/tests/cache/em/vanilla-manager'
        );

        return $manager;
    }

    /**
     * Test some of the basic property accessors.
     */
    public function testGetSet() {
        $container1 = new Container();
        $em = new EventManager($container1);

        $this->assertSame($container1, $em->getContainer());

        $container2 = new Container();
        $this->assertNotSame($container1, $container2);

        $em->setContainer($container2);
        $this->assertSame($container2, $em->getContainer());
    }

    /**
     * Test to see if any events will have overlap when we refactor _overwrite and _handler events to use the same base names.
     */
    public function testEventHandlerOverlap() {
        $pm = new \Gdn_PluginManager();
        $plugins = $this->providePluginClasses();

        foreach ($plugins as $row) {
            list($class, $path) = $row;

            // Register the plugin. This will give a warning when there's overlap.
            require_once $path; // needed because no autoloader registered

            try {
                $pm->registerPlugin($class);
            } catch (\PHPUnit\Framework\Error\Notice $ex) {
                // This is okay.
                continue;
            }
        }

        // No exception so we are cool!
        $this->assertTrue(true);
    }

    /**
     * Make a closure that pushes a particular value onto an array.
     *
     * @param array &$arr The array to push to.
     * @param mixed $val The value to push.
     * @return \Closure Returns a closure.
     */
    private function makePushHandler(array &$arr, $val) {
        return function () use (&$arr, $val) {
            $arr[] = $val;
        };
    }

    /**
     * Make a closure that pushes an event call onto an array.
     *
     * This is used to collect meta events for testing.
     *
     * @param array &$arr The array to push to.
     * @return \Closure Returns a closure.
     */
    private function makeMetaHandler(array &$arr) {
        return function ($event, $args, $result) use (&$arr) {
            $arr[] = [$event, $args, $result];
        };
    }

    /**
     * Test a basic event fire.
     */
    public function testFireEvent() {
        $em = new EventManager();

        $fired = false;
        $em->bind('test', function () use (&$fired) {
            $fired = true;
        });
        $this->assertFalse($fired);

        $em->fire('test');
        $this->assertTrue($fired);
    }

    /**
     * Test that events fire in the order registered by default.
     */
    public function testFireOrderRegistered() {
        $em = new EventManager();

        $arr = [];
        $em->bind('test', $this->makePushHandler($arr, 1));
        $em->bind('test', $this->makePushHandler($arr, 2));
        $em->bind('test', $this->makePushHandler($arr, 3));
        $this->assertSame([], $arr);

        $em->fire('test');
        $this->assertSame([1, 2, 3], $arr);
    }

    /**
     * Test event firing from highest to lowest priority.
     */
    public function testFirePriorityOrder() {
        $em = new EventManager();

        $arr = [];
        $em->bind('test', $this->makePushHandler($arr, 1), EventManager::PRIORITY_LOW);
        $em->bind('test', $this->makePushHandler($arr, 2), EventManager::PRIORITY_HIGH);
        $em->bind('test', $this->makePushHandler($arr, 3), EventManager::PRIORITY_NORMAL);
        $this->assertSame([], $arr);

        $em->fire('test');
        $this->assertSame([2, 3, 1], $arr);
    }

    /**
     * Test a basic call to {@link EventManager::fireFilter()}.
     */
    public function testFireFilter() {
        $em = new EventManager();

        $val = 0;
        $em->bind('add', function ($val) {
            return $val + 1;
        });

        $val2 = $em->fireFilter('add', $val);
        $this->assertSame(0, $val);
        $this->assertSame(1, $val2);
    }

    /**
     * Filters with no handlers should return their input value.
     */
    public function testFireEmptyFilter() {
        $em = new EventManager();

        $vals = [123, 'foo'];
        foreach ($vals as $val) {
            $this->assertSame($val, $em->fireFilter('baz', $val));
        }
    }

    /**
     * Test that firing an event with multiple filters will chain.
     */
    public function testFireFilterChaining() {
        $em = new EventManager();

        $add = function ($val) {
            return $val + 1;
        };

        $val = 0;
        $em->bind('add', $add);
        $em->bind('add', $add);

        $val2 = $em->fireFilter('add', $val);
        $this->assertSame(0, $val);
        $this->assertSame(2, $val2);
    }

    /**
     * Test some basic use cases of {@link EventManager::bindClass()}.
     */
    public function testBindClassBasics() {
        $em = new EventManager(new Container());

        $em->bindClass('VanillaTests\Fixtures\BasicEventHandlers');

        $val = $em->fireFilter('filter', '');
        $this->assertSame('filter', $val);

        $val2 = $em->fireFilter('somecontroller_somemethod_method', '');
        $this->assertSame('someController_someCreate', $val2);

        $val3 = $em->fireFilter('somecontroller_someendpoint_method', '');
        $this->assertSame('someController_someMethod', $val3);

        $val4 = $em->fire('setFoo');
        $this->assertSame([], $val4);

        $val5 = $em->fire('event_before');
        $this->assertSame('event_before', $val5[0]);

        $val6 = $em->fire('event_after');
        $this->assertSame('event_after', $val6[0]);
    }

    /**
     * Calling {@link EventManager::bindClass()} with an instance should create handlers for that instance.
     */
    public function testBindClassInstance() {
        $em = new EventManager();

        $val = 'test';
        $handlers = new BasicEventHandlers();
        $this->assertNotSame($val, $handlers->getFoo());
        $handlers->setFoo($val);
        $this->assertSame($val, $handlers->getFoo());

        $em->bindClass($handlers);
        $r = $em->fire('foo');
        $this->assertSame($val, $r[0]);
    }

    /**
     * Test {@link EventManager::fireArray()} with an element as a reference that gets modified.
     */
    public function testFireArrayWithReference() {
        $em = new EventManager();

        $em->bind('test', function (&$val) {
            $val = 'foo';
        });

        $val = '';
        $em->fireArray('test', [&$val]);
        $this->assertSame('foo', $val);
    }

    /**
     * Test {@link EventManager::bindLazy()}.
     */
    public function testBindLazy() {
        $em = new EventManager(new Container());

        $em->bindLazy('getFoo', 'VanillaTests\Fixtures\BasicEventHandlers', 'getFoo');

        $r = $em->fire('getfoo');
        $this->assertSame('foo', $r[0]);
    }

    /**
     * Test some meta event handlers.
     */
    public function testMetaHandler() {
        $em = new EventManager();

        $events = [];
        $em->bind(EventManager::EVENT_META, $this->makeMetaHandler($events));
        $em->bind('foo', function ($v = null) {
            return "foo $v";
        });

        $em->fireFilter('foo', 'bar');
        $em->fire('none', 'baz');
        $em->fireArray('foo', ['baz']);

        $expected = [
            ['foo', ['bar'], 'foo bar'],
            ['none', ['baz'], []],
            ['foo', ['baz'], ['foo baz']],
        ];
        $this->assertEquals($expected, $events);
    }

    /**
     * Make sure that meta event inception doesn't cause anything crazy.
     */
    public function testFireMetaEvent() {
        $em = new EventManager();

        $events = [];
        $em->bind(EventManager::EVENT_META, $this->makeMetaHandler($events));
        $r = $em->fire(EventManager::EVENT_META, 'foo', ['args'], 'result');

        // Since this shouldn't be done we are not specifying a defined behaviour right now.
        // Let's just make sure that only the two events are fired though.
        $this->assertSame(2, count($events));
    }

    /**
     * Test firing events with no handlers return an empty array of results.
     */
    public function testEmptyHandlerResults() {
        $em = new EventManager();

        $this->assertSame([], $em->fire('noop'));
        $this->assertSame([], $em->fireArray('noop', []));
    }

    /**
     * Test {@link EventManager::hasHandler()}
     */
    public function testHasHandler() {
        $em = new EventManager();

        $em->bind('foo', function () {
            return 1;
        });

        $this->assertTrue($em->hasHandler('foo'));
        $this->assertFalse($em->hasHandler('bar'));
    }

    /**
     * Make sure an event with higher than max priority just goes down to max priority.
     */
    public function testMaxPriority() {
        $this->expectNotice();
        $em = new EventManager();

        $arr = [];
        $em->bind('foo', $this->makePushHandler($arr, 1), EventManager::PRIORITY_MAX);
        $em->bind('foo', $this->makePushHandler($arr, 2), EventManager::PRIORITY_MAX + 1);

        $r = $em->fire('foo');
        $this->assertSame([1, 2], $arr);

        $arr2 = [];
        $em->bind('foo', $this->makePushHandler($arr2, 1), EventManager::PRIORITY_MAX - 1);
        $em->bind('foo', $this->makePushHandler($arr2, 2), EventManager::PRIORITY_MAX + 1);
        $this->assertSame([2, 1], $arr2);
    }

    /**
     * Test the old EventArguments kludge.
     */
//    public function testEventArgumentsKludge() {
//        $em = new eventManager();
//
//        $events = [];
//        $em->bind(EventManager::EVENT_META, $this->makeMetaHandler($events));
//
//        $sender = new \stdClass();
//        $sender->EventArguments = ['foo' => 1];
//
//        // fire() has the kludge.
//        $em->fire('foo', $sender);
//        // fireArray() doesn't have the kludge.
//        $em->fireArray('foo', [$sender]);
//        // fireFilter() doesn't have the kludge.
//        $r = $em->fireFilter('foo', $sender);
//
//        $expected = [
//            ['foo', [$sender, $sender->EventArguments], []],
//            ['foo', [$sender], []],
//            ['foo', [$sender], $sender]
//        ];
//
//        $this->assertSame($expected, $events);
//    }

    /**
     * Get a list of plugin classes suitable for tests.
     *
     * The result is a data set with each row in the form: [class, path]. The **hasRedefines** element
     * can be used to determine whether or not the plugin can be included.
     *
     * @param bool $includeRedefines Whether or not to include plugins that redefine global functions.
     * @return array Returns a data provider array.
     * @throws \Exception Throws an exception when the plugin class path doesn't exist.
     */
    public function providePluginClasses($includeRedefines = false) {
        $am = self::createVanillaManager();

        $result = [];

        $types = [Addon::TYPE_ADDON, Addon::TYPE_THEME];
        foreach ($types as $type) {
            $addons = $am->lookupAllByType($type);
            /** @var Addon $addon */
            foreach ($addons as $addon) {
                $pluginClass = $addon->getPluginClass();
                if (!empty($pluginClass)) {
                    $path = $addon->getClassPath($pluginClass, Addon::PATH_FULL);
                    if (empty($path)) {
                        throw new \Exception("Missing path for $pluginClass.");
                    }

                    // Kludge: Check for the userPhoto() function.
                    $fileContents = file_get_contents($path);
                    if (preg_match('`function userPhoto`i', $fileContents) && !$includeRedefines) {
                        continue;
                    }

                    $result[$pluginClass] = [
                        $pluginClass,
                        $path
                    ];
                }
            }
        }

        return $result;
    }

    /**
     * Test {@link EventManager::classBasename()}.
     *
     * @param mixed $class The class name or instance to test.
     * @param string $expected The expected result.
     * @dataProvider provideClassBasenameTests
     */
    public function testClassBasename($class, $expected) {
        $basename = EventManager::classBasename($class);

        $this->assertEquals($expected, $basename);
    }

    /**
     * Generate test data for {@link EventManagerTest::testClassBasename()}.
     *
     * @return array Returns a data provider array.
     */
    public function provideClassBasenameTests() {
        $r = [
            'object' => [$this, 'EventManagerTest'],
            'no namespace' => ['Foo', 'Foo'],
            'namespace' => ['Foo\Bar', 'Bar']
        ];

        return $r;
    }

    /**
     * Test {@link EventManager::unbind()}.
     */
    public function testUnbind() {
        $em = new EventManager();

        $fired = false;
        $fn = function () use (&$fired) {
            $fired = true;
        };

        $em->bind('e', $fn);
        $this->assertTrue($em->hasHandler('e'));

        $em->unbind('e', $fn);
        $this->assertFalse($em->hasHandler('e'));

        $r = $em->fire('e');
        $this->assertEmpty($r);
    }

    /**
     * Test unbinding a class from the event manager by instance.
     */
    public function testUnbindClassInstance() {
        $em = new EventManager();

        $handlers = new BasicEventHandlers();
        $em->bindClass($handlers);

        $this->assertTrue($em->hasHandler('foo'));

        $em->unbindClass($handlers);
        $this->assertFalse($em->hasHandler('foo'));
    }

    /**
     * Test unbinding a class from the event manager by instance.
     */
    public function testUnbindClassInstanceName() {
        $em = new EventManager();

        $handlers = new BasicEventHandlers();
        $em->bindClass($handlers);

        $this->assertTrue($em->hasHandler('foo'));

        $em->unbindClass(BasicEventHandlers::class);
        $this->assertFalse($em->hasHandler('foo'));
    }

    /**
     * Test unbinding a class from the event manager by class name.
     */
    public function testUnbindClassName() {
        $em = new EventManager(new Container());

        $em->bindClass(BasicEventHandlers::class);

        $this->assertTrue($em->hasHandler('foo'));

        $em->unbindClass(BasicEventHandlers::class);
        $this->assertFalse($em->hasHandler('foo'));
    }

    /**
     * Test unbinding a class from the event manager by class name after the handlers have been fetched..
     */
    public function testUnbindClassExpanded() {
        $em = new EventManager(new Container());

        $em->bindClass(BasicEventHandlers::class);

        $this->assertNotEmpty($em->getHandlers('foo'));

        $em->unbindClass(BasicEventHandlers::class);
        $this->assertFalse($em->hasHandler('foo'));
    }

    /**
     * If multiple instances are bound then I should not unbind both.
     */
    public function testUnbindClassInstanceMultiple() {
        $em = new EventManager();

        $handlers = new BasicEventHandlers();
        $em->bindClass($handlers);
        $handlers2 = new BasicEventHandlers();
        $em->bindClass($handlers2);

        $this->assertTrue($em->hasHandler('foo'));

        $em->unbindClass($handlers);
        $this->assertTrue($em->hasHandler('foo'));

        $em->unbindClass($handlers2);
        $this->assertFalse($em->hasHandler('foo'));
    }

    /**
     * If multiple instances are bound then I should not unbind both.
     */
    public function testUnbindClassInstanceMultipleByName() {
        $em = new EventManager();

        $handlers = new BasicEventHandlers();
        $em->bindClass($handlers);
        $handlers2 = new BasicEventHandlers();
        $em->bindClass($handlers2);

        $this->assertTrue($em->hasHandler('foo'));

        $em->unbindClass(BasicEventHandlers::class);
        $this->assertFalse($em->hasHandler('foo'));
    }
}
