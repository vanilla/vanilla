<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler;

/**
 * Yield this to tell the long runner the total quantity of items that can be progressed.
 */
final class LongRunnerQuantityTotal
{
    /** @var int */
    private $value = null;

    /** @var callable */
    private $callback;

    /** @var array  */
    private $args;

    /**
     * Constructor.
     *
     * @param callable $callback Callable to get quantity.
     * @param array|null $args methods arguments
     */

    public function __construct(callable $callback, array $args = [])
    {
        $this->callback = $callback;
        $this->args = $args;
    }

    /**
     * @return int
     */
    public function getValue(): int
    {
        if ($this->value === null) {
            $this->value = call_user_func($this->callback, ...$this->args);
        }
        return $this->value;
    }
}
