<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla;

use Garden\Container\Container;
use Garden\Container\ContainerConfigurationInterface;

/**
 * Class for implementing container configuration in an addon.
 */
abstract class AddonContainerRules
{
    /**
     * You may NOT dependency inject anything in an addon configuration.
     */
    final public function __construct()
    {
    }

    /**
     * Implement this for configuring the container from an addon.
     *
     * Notably this provides a configuration-only container. You may not fetch any instances at this point.
     *
     * @param ContainerConfigurationInterface $container A container configuration instance.
     */
    abstract public function configureContainer(ContainerConfigurationInterface $container): void;

    /**
     * Apply container rules for an addon to a production environment.
     *
     * Notably this provides a configuration-only container. You may not fetch any instances at this point.
     *
     * @param ContainerConfigurationInterface $container A container configuration instance.
     */
    public function configureProductionContainer(ContainerConfigurationInterface $container): void
    {
        $this->configureContainer($container);
    }

    /**
     * Apply container rules for an addon to a testing environment.
     *
     * While the other methods provide a configuration-only container, having a full container can make it significantly
     * easier to configure mocks or preconfigured instances.
     *
     * @param Container $container A full container instance.
     */
    public function configureTestContainer(Container $container): void
    {
        $this->configureContainer($container);
    }
}
