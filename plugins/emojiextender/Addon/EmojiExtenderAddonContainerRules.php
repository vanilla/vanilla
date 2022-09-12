<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\EmojiExtender\Addon;

use Garden\Container\ContainerConfigurationInterface;
use Vanilla\AddonContainerRules;
use Vanilla\EmojiExtender\ExtendedEmoji;

/**
 * Container rules for the emoji extender.
 */
class EmojiExtenderAddonContainerRules extends AddonContainerRules
{
    /**
     * @inheritDoc
     */
    public function configureContainer(ContainerConfigurationInterface $container): void
    {
        $container->rule(\Emoji::class)->setClass(ExtendedEmoji::class);
    }
}
