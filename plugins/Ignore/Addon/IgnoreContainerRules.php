<?php
/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Addon;

use Garden\Container\ContainerConfigurationInterface;
use Garden\Container\Reference;
use Models\IgnoreMeta;
use Vanilla\AddonContainerRules;
use Vanilla\Models\SiteMeta;

/**
 * Container rules for Ignore.
 */
class IgnoreContainerRules extends AddonContainerRules
{
    /**
     * @inheritdoc
     */
    public function configureContainer(ContainerConfigurationInterface $container): void
    {
        $container->rule(SiteMeta::class)->addCall("addExtra", [new Reference(IgnoreMeta::class)]);
    }
}
