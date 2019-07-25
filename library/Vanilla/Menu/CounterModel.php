<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Menu;

/**
 * Provider of counter data.
 */
class CounterModel {

    /** @var array List of counter providers. */
    private $providers = [];

    /**
     * @param CounterProviderInterface $provider
     */
    public function addProvider(CounterProviderInterface $provider) {
        $this->providers[] = $provider;
    }

    /**
     * Get all counters.
     *
     * @return Counter[]
     */
    public function getAllCounters(): array {
        $counters = [];
        foreach ($this->providers as $provider) {
            $counters = array_merge($counters, $provider->getMenuCounters());
        }
        return $counters;
    }
}
