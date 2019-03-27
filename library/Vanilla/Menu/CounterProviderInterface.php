<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Menu;

/**
 * Counter provider interface
 */
interface CounterProviderInterface {
    /**
     * Get menu counters.
     *
     * @return Counter[]
     */
    public function getMenuCounters(): array;
}
