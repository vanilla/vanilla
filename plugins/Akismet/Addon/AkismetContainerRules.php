<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2024 Higher Logic Inc.
 * @license Proprietary
 */

namespace Vanilla\Akismet\Addon;

use AkismetPremoderator;
use Garden\Container\ContainerConfigurationInterface;
use Garden\Container\Reference;
use Vanilla\AddonContainerRules;
use Vanilla\Premoderation\PremoderationService;

/**
 * Container rules for the Akismet addon.
 */
class AkismetContainerRules extends AddonContainerRules
{
    /**
     * @inheritDoc
     */
    public function configureContainer(ContainerConfigurationInterface $container): void
    {
        $container
            ->rule(PremoderationService::class)
            ->addCall("registerHandler", [new Reference(AkismetPremoderator::class)]);
    }
}
