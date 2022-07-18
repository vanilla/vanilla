<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Cli\Commands;

use Vanilla\Setup\ComposerHelper;

/**
 * Clear Caches command.
 */
class VanillaCacheCommand
{
    /**
     * Clear vanilla caches.
     */
    public function clearCaches()
    {
        ComposerHelper::clearPhpCache();
        ComposerHelper::clearTwigCache();
    }
}
