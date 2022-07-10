<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Utility;

use Garden\Container\Container;
use PHPUnit\Framework\TestCase;
use Vanilla\Utility\ContainerUtils;
use VanillaTests\Fixtures\Aliases\ExtendsNewClass;
use VanillaTests\Fixtures\Aliases\NewClass;
use VanillaTests\Fixtures\Request;

/**
 * Tests for the container utilities class.
 */
class ContainerUtilsTest extends TestCase {

    /** @var Container */
    private $container;

    /**
     * @inheritDoc
     */
    public function setUp(): void {
        parent::setUp();
        $this->container = new Container();
    }

    /**
     * Verify replacing instance, including an alias.
     */
    public function testReplace(): void {
        $this->container->rule(NewClass::class)->setShared(true);

        $original = $this->container->get(NewClass::class);
        $this->assertInstanceOf(NewClass::class, $original);

        ContainerUtils::replace(
            $this->container,
            NewClass::class,
            ExtendsNewClass::class
        );

        // Verify replacement.
        $replacement = $this->container->get(NewClass::class);
        $this->assertInstanceOf(ExtendsNewClass::class, $replacement);

        // Verify alias.
        $this->container->setInstance(NewClass::class, null);
        $aliased = $this->container->get(NewClass::class);
        $this->assertInstanceOf(ExtendsNewClass::class, $aliased);
    }

    /**
     * Test `ContainerUtils::addCall()`.
     */
    public function testAdCall(): void {
        $this->container->setInstance(Request::class, null);

        $this->container->rule(Request::class)->setShared(true);
        ContainerUtils::addCall($this->container, Request::class, 'setPath', ['/foo']);

        /** @var Request $request */
        $request = $this->container->get(Request::class);
        $this->assertSame('/foo', $request->getPath());

        ContainerUtils::addCall($this->container, Request::class, 'setPath', ['/bar']);
        $this->assertSame('/bar', $request->getPath());
    }
}
