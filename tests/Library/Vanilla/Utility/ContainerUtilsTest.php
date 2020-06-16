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
    public function testReplaceAlias(): void {
        $this->container->rule(NewClass::class)->setShared(true);

        $original = $this->container->get(NewClass::class);
        $this->assertInstanceOf(NewClass::class, $original);

        ContainerUtils::replace(
            $this->container,
            NewClass::class,
            ExtendsNewClass::class,
            true
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
     * Verify replacing instance, without an alias.
     */
    public function testReplaceWithoutAlias(): void {
        $this->container->rule(NewClass::class)->setShared(true);

        $original = $this->container->get(NewClass::class);
        $this->assertInstanceOf(NewClass::class, $original);

        ContainerUtils::replace(
            $this->container,
            NewClass::class,
            ExtendsNewClass::class,
            false
        );

        // Verify replacement.
        $replacement = $this->container->get(NewClass::class);
        $this->assertInstanceOf(ExtendsNewClass::class, $replacement);

        // Verify no alias set.
        $this->container->setInstance(NewClass::class, null);
        $aliased = $this->container->get(NewClass::class);
        $this->assertSame(NewClass::class, get_class($aliased));
    }
}
