<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler;

/**
 * Yield this to tell the long runner the total quantity of items that can be progressed.
 */
final class LongRunnerQuantityTotal
{
    /** @var int */
    private $value;

    /**
     * Constructor.
     *
     * @param int $value The total quantity of items that can be progress.
     */
    public function __construct(int $value)
    {
        $this->value = $value;
    }

    /**
     * @return int
     */
    public function getValue(): int
    {
        return $this->value;
    }
}
