<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla;

use Interop\Container\ContainerInterface as InteropContainerInterface;
use Psr\Container\ContainerInterface;

/**
 * Adapts a PSR container to an interop container.
 */
class InteropContainer implements InteropContainerInterface {
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * InteropContainer constructor.
     *
     * @param ContainerInterface $container The container to adapt to.
     */
    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function get($id) {
        return $this->container->get($id);
    }

    /**
     * {@inheritdoc}
     */
    public function has($id) {
        return $this->container->has($id);
    }

}
