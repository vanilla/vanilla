<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Theme;

/**
 * Interface that indicates a theme provider requires additional cleanup when switched to another theme provider.
 */
interface ThemeProviderCleanupInterface {
    /**
     * Method to be called if active theme provider is changed to another theme.
     */
    public function afterCurrentProviderChange(): void;
}
