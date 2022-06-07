<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\ProfileExtender\Addon;

use Garden\Container\ContainerConfigurationInterface;
use Garden\Container\Reference;
use Vanilla\AddonContainerRules;
use Vanilla\ProfileExtender\Models\ExtendedUsersExpander;
use Vanilla\Web\APIExpandMiddleware;

/**
 * Container rules for the profile extender plugin.
 */
class ProfileExtenderContainerRules extends AddonContainerRules
{
    /**
     * @inheritdoc
     */
    public function configureContainer(ContainerConfigurationInterface $container): void
    {
        $container
            ->rule(APIExpandMiddleware::class)
            ->addCall("addExpander", [new Reference(ExtendedUsersExpander::class)]);
    }
}
