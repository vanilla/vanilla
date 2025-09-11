<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Analytics;

use Vanilla\Community\Events\ExternalNavigationEvent;

class ExternalNavigationEventProvider implements EventProviderInterface
{
    /**
     * @inheritdoc
     */
    public function getEvent(array $body): object
    {
        $destinationUrl = $body["destinationUrl"] ?? "";
        return new ExternalNavigationEvent($destinationUrl);
    }

    /**
     * @inheritdoc
     */
    public function canHandleRequest(array $body): bool
    {
        return isset($body["destinationUrl"]) && is_string($body["destinationUrl"]);
    }
}
