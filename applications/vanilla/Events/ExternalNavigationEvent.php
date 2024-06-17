<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Community\Events;

/**
 * Event that is dispatched when a user navigates away from Vanilla.
 */
class ExternalNavigationEvent
{
    private string $destinationUrl;

    /**
     * Constructor
     *
     * @param string $destinationUrl
     */
    public function __construct(string $destinationUrl)
    {
        $this->destinationUrl = $destinationUrl;
    }

    /**
     * @return string
     */
    public function getDestinationUrl(): string
    {
        return $this->destinationUrl;
    }
}
