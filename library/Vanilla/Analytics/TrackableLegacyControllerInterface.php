<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Analytics;

/**
 * For tracking groups/events.
 */
interface TrackableLegacyControllerInterface
{
    /**
     * Get trackable data from groups/events.
     *
     * @return array|null
     */
    public function getTrackableData(): array;
}
