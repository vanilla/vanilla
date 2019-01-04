<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Library\Core;

use VanillaTests\SharedBootstrapTestCase;
use Gdn;
use VanillaTests\Fixtures\Tuple;

/**
 * Tests for the {@link Gdn_Factory}.
 */
class FactoryTest extends SharedBootstrapTestCase {
    const TUPLE = 'VanillaTests\Fixtures\Tuple';

    /**
     * The factory should create new instances with arguments.
     */
    public function testFactoryInstances() {
        Gdn::factoryInstall('t', self::TUPLE, '', Gdn::FactoryInstance);

        /* @var Tuple $i1 */
        $i1 = Gdn::factory('t', 1, 2);
        /* @var Tuple $i2 */
        $i2 = Gdn::factory('t', 3, 4);

        $this->assertSame(1, $i1->a);
        $this->assertSame(2, $i1->b);
        $this->assertSame(3, $i2->a);
        $this->assertSame(4, $i2->b);
    }

    /**
     * The factory should create a single shared instance.
     */
    public function testFactorySingleton() {
        Gdn::factoryInstall('sing', self::TUPLE, '', Gdn::FactorySingleton, ['sing1', 'sing2']);

        /* @var Tuple $i1 */
        $i1 = Gdn::factory('sing');
        /* @var Tuple $i2 */
        $i2 = Gdn::factory('sing');

        $this->assertSame('sing1', $i1->a);
        $this->assertSame('sing2', $i1->b);
        $this->assertSame($i1, $i2);
    }

    /**
     * Quote-unquote real singletons should call the appropriate static method to create.
     */
    public function testFactoryRealSingleton() {
        Gdn::factoryInstall('rsing', self::TUPLE, '', Gdn::FactoryRealSingleton, 'Create');

        /* @var Tuple $i1 */
        $i1 = Gdn::factory('rsing');
        /* @var Tuple $i2 */
        $i2 = Gdn::factory('rsing');

        $this->assertSame('a', $i1->a);
        $this->assertSame('b', $i1->b);
        $this->assertSame($i1, $i2);
    }

    /**
     * I should be able to install an object instance.
     */
    public function testFactoryInstallInstance() {
        $instance = new Tuple('i1', 'i2');

        Gdn::factoryInstall('MentionsFormatter', self::TUPLE, '', Gdn::FactorySingleton, $instance);

        $r = Gdn::factory('MentionsFormatter');
        $this->assertSame($instance, $r);
    }

    /**
     * Factory prototypes should clone an original object.
     */
    public function testFactoryPrototypes() {
        Gdn::factoryInstall('proto', self::TUPLE, '', Gdn::FactoryPrototype, new Tuple('p1', 'p2'));

        /* @var Tuple $i1 */
        $i1 = Gdn::factory('proto');
        /* @var Tuple $i2 */
        $i2 = Gdn::factory('proto');

        $this->assertSame('p1', $i1->a);
        $this->assertSame('p2', $i1->b);
        $this->assertSame('p1', $i2->a);
        $this->assertSame('p2', $i2->b);
        $this->assertNotSame($i1, $i2);
    }

    /**
     * A non-entry should return null from the factory.
     */
    public function testFactoryNull() {
        $className = '0Baloocale';
        $this->assertFalse(class_exists($className));

        $o = Gdn::factory($className);
        $this->assertNull($o);
    }
}
