<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Premoderation;

/**
 * Interface for premoderation handlers.
 */
interface PremoderationHandlerInterface
{
    /**
     * Premoderate an item.
     *
     * @param PremoderationItem $item
     * @return PremoderationResponse
     *
     * @throws \Throwable
     */
    public function premoderateItem(PremoderationItem $item): PremoderationResponse;
}
