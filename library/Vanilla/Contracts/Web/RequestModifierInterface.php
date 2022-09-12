<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Contracts\Web;

/**
 * Interface for modifying a request in place.
 */
interface RequestModifierInterface
{
    /**
     * Modify the given request.
     *
     * @param \Gdn_Request $request
     */
    public function modifyRequest(\Gdn_Request $request): void;
}
