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
}
