<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests;

use Garden\Container\Container;
use PHPUnit\Framework\TestCase;

/**
 * A very minimal PHPUnit test case using Garden\Container.
 */
class ContainerTestCase extends TestCase {

    /** @var Container */
    protected $container;

    /**
     * Setup the container.
     */
    public function setUp() {
        $container = new Container();
        \Gdn::setContainer($container);
    }
}
