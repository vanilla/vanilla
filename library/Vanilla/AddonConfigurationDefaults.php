<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla;

/**
 * Class for implementing addon specific configuration defaults.
 */
abstract class AddonConfigurationDefaults
{
    /**
     * Get an array of configuration defaults.
     *
     * @return array An array of configuration values. These may be provided in dot notation for nested arrays.
     */
    abstract public function getDefaults(): array;
}
