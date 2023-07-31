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

    public function installData(): InstallData
    {
        /** @var InstallData $installData */
        if ($this->_installData === null) {
            $this->_installData = new InstallData();
        }
        return $this->_installData;
    }
}
