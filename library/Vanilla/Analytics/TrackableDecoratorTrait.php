<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Analytics;

use Garden\Web\Data;

/**
 * Trait for applying trackable decorators.
 */
trait TrackableDecoratorTrait
{
    /**
     * Apply a set of decorators to a record.
     *
     * @param array|Data $record The record to decorate.
     * @param TrackableDecoratorInterface[] $decorators
     *
     * @return array
     */
    protected function applyDecorators($record, array $decorators): array
    {
        $record = Data::box($record);
        foreach ($decorators as $decorator) {
            $record = $decorator->decorateTrackableRecord($record);
        }
        return $record->getData();
    }
}
