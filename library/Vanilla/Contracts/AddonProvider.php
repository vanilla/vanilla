<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Contracts;

/**
 * Interface for providing addons.
 */
interface AddonProvider {
    /**
     * Get the enabled addons, sorted by priority with the highest priority first.
     *
     * @return Addon[] Returns an array of {@link Addon} objects.
     */
    public function getEnabled(): array;
}
