<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Community\Events;

use Garden\Events\TrackingEventInterface;

/**
 * Discussion search event.
 */
class SearchDiscussionEvent implements TrackingEventInterface
{
    public const COLLECTION_NAME = "search";

    public const ACTION_NAME = "search_discussions";

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
     * @inheritDoc
     */
    public function getTrackableCollection(): ?string
    {
        return self::COLLECTION_NAME;
    }

    /**
     * @inheritDoc
     */
    public function getAction(): string
    {
        return self::ACTION_NAME;
    }

    /**
     * @inheritDoc
     */
    public function getPayload(): ?array
    {
        return $this->payload;
    }

    /**
     * @inheritDoc
     */
    public function getSiteSectionID(): ?string
    {
        return $this->payload["siteSectionID"] ?? null;
    }
}
