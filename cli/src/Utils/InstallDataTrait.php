<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Cli\Utils;

/**
 * Trait for easy install data access.
 */
trait InstallDataTrait
{
    /**
     * @return InstallData
     */
    private ?InstallData $_installData = null;

    public static function installData(): InstallData
    {
        return InstallData::instance();
    }
}
