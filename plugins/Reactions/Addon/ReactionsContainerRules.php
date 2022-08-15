<?php
/**
 * @author David Barbier <dbarbier@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Reactions\Addon;

use Garden\Container\ContainerConfigurationInterface;
use Gdn;
use Vanilla\AddonContainerRules;
use Vanilla\Dashboard\UserLeaderService;
use Vanilla\Utility\ContainerUtils;

class ReactionsContainerRules extends AddonContainerRules
{
    /**
     * @inheritdoc
     */
    public function configureContainer(ContainerConfigurationInterface $container): void
    {
        $container
            ->rule(UserLeaderService::class)
            ->addCall("setTrackPointsSeparately", [
                ContainerUtils::config("Plugins.Reactions.TrackPointsSeparately", false),
            ]);
    }
}
