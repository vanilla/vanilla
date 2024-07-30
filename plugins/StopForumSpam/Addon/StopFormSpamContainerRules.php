<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace StopForumSpam\Addon;

use Garden\Container\ContainerConfigurationInterface;
use Garden\Container\Reference;
use Vanilla\AddonContainerRules;
use Vanilla\Premoderation\PremoderationService;
use Vanilla\StopForumSpam\StopForumSpamPremoderator;

/**
 * Container rules.
 */
class StopFormSpamContainerRules extends AddonContainerRules
{
    /**
     * @inheritDoc
     */
    public function configureContainer(ContainerConfigurationInterface $container): void
    {
        $container
            ->rule(PremoderationService::class)
            ->addCall("registerHandler", [new Reference(StopForumSpamPremoderator::class)]);
    }
}
