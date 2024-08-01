<?php
/**
 * @author Dan Redman <dredman@higherlogic.com>
 * @copyright 2009-2021 Higher Logic LLC.
 * @license GPL-2.0-only
 */

namespace Vanilla\Layout\Providers;

use Garden\Web\Exception\NotFoundException;

/**
 * Defines methods implemented by classes that provide layouts whose definitions are mutable, i.e. LayoutModel.
 * Assumes successful schema validation prior to method invocation.
 */
interface MutableLayoutProviderInterface extends LayoutProviderInterface
{
    /**
     * Update an existing layout, i.e. insert a new layout row
     *
     * @param mixed $layoutID ID of layout to update
     * @param array $fields Set of fields to update within the layout
     * @return array updated layout
     * @throws NotFoundException Layout to update not found.
     * @throws \Exception Error on update.
     */
    public function updateLayout($layoutID, array $fields): array;

    /**
     * Delete layout by ID
     *
     * @param mixed $layoutID
     * @throws NotFoundException Layout to delete not found.
     * @throws \Exception Error on deletion.
     */
    public function deleteLayout($layoutID): void;
}
