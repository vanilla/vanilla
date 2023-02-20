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
use Vanilla\Utility\DebugUtils;

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

        // This used to be in the top level of the file but doesn't working with preloading there.
        // Notably we can't actually do tests for this because function declarations live for the whole duration
        // Of the test suit. This will need a full refactor to work in tests.
        $addonManager = \Gdn::addonManager();
        if (!DebugUtils::isTestMode() && !function_exists("writeReactions")) {
            $path =
                PATH_ROOT .
                "/" .
                $addonManager->lookupAsset("/views/reaction_functions.php", $addonManager->lookupAddon("Reactions"));
            include $path;
        }
    }
}
