<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Analytics;

use Garden\Web\Data;

/**
 * Implement this to decorate a trackable record.
 */
interface TrackableDecoratorInterface
{
    /**
     * Implement this method to decorate a trackable record.
     *
     * @param Data $record
     * @return Data
     */
    public function decorateTrackableRecord(Data $record): Data;
}
