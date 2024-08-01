<?php
/**
 * @author Dan Redman <dredman@higherlogic.com>
 * @copyright 2009-2021 Higher Logic LLC.
 * @license GPL-2.0-only
 */

namespace Vanilla\Layout\Providers;

use Garden\Web\Exception\NotFoundException;

/**
 * Defines methods for objects that can provide layout definitions
 */
interface LayoutProviderInterface
{
    /**
     * Determine whether a layout with the ID specified is supported by the layout provider using the id's type
     *
     * @param mixed $layoutID ID of layout
     * @return bool True if layout provider supports the ID specified given its format, false otherwise
     */
    public function isIDFormatSupported($layoutID): bool;

    /**
     * Get layout corresponding to the provided ID.
     *
     * @param mixed $layoutID ID of layout for which to retrieve its definition
     * @return array Layout definition corresponding to the provided ID
     * @throws NotFoundException Layout with given ID not found.
     */
    public function getByID($layoutID): array;

    /**
     * Get all layouts defined
     *
     * @return array
     */
    public function getAll(): array;
}
