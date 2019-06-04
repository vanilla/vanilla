<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Contracts\Analytics;

/**
 * An interface for an analytics client to track events.
 */
interface ClientInterface {

    /**
     * Get configuration details relevant to the analytics service.
     *
     * @param bool $includeDangerous Include sensitive values (i.e. read keys) in the config.
     * @return array
     */
    public function config(bool $includeDangerous = false): array;

    /**
     * Get an array of default event fields (e.g. user).
     *
     * @return array
     */
    public function eventDefaults(): array;

    /**
     * Record a single event.
     *
     * @param array $data
     */
    public function recordEvent(array $data);
}
