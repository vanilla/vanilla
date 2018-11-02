<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Addon;

/**
 * Interface for providing addons.
 */
interface IAddonProvider {
    /**
     * Get the enabled addons, sorted by priority with the highest priority first.
     *
     * @return IAddon[] Returns an array of {@link IAddon} objects.
     */
    public function getEnabled(): array;
}
