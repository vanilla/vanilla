<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla;

/**
 * Class for structuring a site for an addon.
 */
abstract class AddonStructure
{
    /**
     * Strucutre the site for the adodn.
     *
     * This is called after an addon is enabled as well as whenever new code is deployed.
     * This method is meant to take whatever the current state of the configuration or database of a site
     * and update it to work with the current version of an addon.
     *
     * @param bool $isEnable Set to true if this is during the enabling of an addon.
     */
    abstract public function structure(bool $isEnable): void;
}
