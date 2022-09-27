<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Layout;

/**
 * Interface for a class that is aware of a current hydration.
 *
 * See {@link HydrateAwareTrait} for an implementation.
 */
interface HydrateAwareInterface
{
    /**
     * Set the parameters for the current hydration.
     *
     * @param array $params
     *
     * @return void
     */
    public function setHydrateParams(array $params): void;
}
