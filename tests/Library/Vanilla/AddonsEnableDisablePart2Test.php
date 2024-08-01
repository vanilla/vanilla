<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla;

/**
 * Test enabling/disabling every 3rd addon from 1.
 */
class AddonsEnableDisablePart2Test extends AbstractAddonEnableDisableTest
{
    /**
     * @inheritdoc
     */
    public function getDisibilityRemainder(): int
    {
        return 1;
    }
}
