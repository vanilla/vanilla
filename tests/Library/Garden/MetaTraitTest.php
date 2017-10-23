<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\Library\Garden;

use Garden\MetaTrait;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the **Garden\MetaTrait**
 */
class MetaTraitTest extends TestCase {
    use MetaTrait;

    /**
     * Clear out the meta array before each test.
     */
    public function setUp() {
        parent::setUp();
        $this->setMetaArray([]);
    }

    /**
     * Adding a value to a non-existent key should create an array with that value.
     */
    public function testAddToEmpty() {
        $this->addMeta('foo', 'bar');

        $this->assertEquals(['bar'], $this->getMeta('foo'));
    }

    /**
     * Adding a key and value to a non-existent key should create an array with that key and value.
     */
    public function testAddKeyToEmpty() {
        $this->addMeta('foo', 'bar', 'baz');
        $this->assertEquals(['bar' => 'baz'], $this->getMeta('foo'));
    }

    /**
     * Adding a value to an existing array should add the item to the end.
     */
    public function testAddToArray() {
        $this->setMetaArray(['foo' => [1]]);
        $this->addMeta('foo', 2);
        $this->assertEquals([1, 2], $this->getMeta('foo'));
    }

    /**
     * Adding a key/value pair to an existing array should set the key with the value.
     */
    public function testAddKeyToArray() {
        $this->setMetaArray(['foo' => 1]);
        $this->addMeta('foo', 'bar', 'baz');
        $this->assertEquals([1, 'bar' => 'baz'], $this->getMeta('foo'));
    }
}
