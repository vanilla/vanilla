<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures\BadStructure\Addon;

use Vanilla\AddonStructure;

/**
 * Bad structure.
 */
class BadStructureStructure extends AddonStructure
{
    /**
     * Structure fails when we aren't enabling.
     *
     * @param bool $isEnable
     *
     * @return void
     */
    public function structure(bool $isEnable): void
    {
        if (!$isEnable) {
            throw new \Exception("Fail in structure");
        }
    }
}
