<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Events;

use Garden\Events\TrackingEventInterface;

/**
 * Event for tracking searches.
 */
class SearchAllEvent implements TrackingEventInterface
{
    public const COLLECTION_NAME = "search";

    public const ACTION_NAME = "search_all";

    /** @var array */
    protected $payload;

    /**
     * @inheritDoc
     */
    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    /**
     * Get the event action.
     *
     * @return string
     */
    public function getAction(): string
    {
        return self::ACTION_NAME;
    }

    /**
     * Get the event payload.
     *
     * @return array
     */
    public function getPayload(): array
    {
        return $this->payload;
    }

    /**
     * @inheritDoc
     */
    public function getTrackableCollection(): ?string
    {
        return self::COLLECTION_NAME;
    }

    /**
     * @inheritDoc
     */
    public function getSiteSectionID(): ?string
    {
        return $this->payload["siteSectionID"] ?? null;
    }
}
