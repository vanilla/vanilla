<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures;

use Garden\Events\ResourceEvent;
use Garden\Events\TrackingEventInterface;

/**
 * Mock event implementing the TrackingEventInterface.
 */
class MockTrackingEvent extends ResourceEvent implements TrackingEventInterface
{
    const COLLECTION_NAME = "mockCollection";

    /**
     * Return the collection name associated with the event.
     *
     * @return string
     */
    public function getTrackableCollection(): string
    {
        return self::COLLECTION_NAME;
    }

    /**
     * Add a tracking-specific field to the payload.
     *
     * @return array
     */
    public function getTrackablePayload(): array
    {
        return ["tracking" => true];
    }

    /**
     * Provide a tracking-specific aciton.
     *
     * @return string
     */
    public function getTrackableAction(): string
    {
        return "track";
    }

    /**
     * @inheritDoc
     */
    public function getSiteSectionID(): ?string
    {
        return null;
    }
}
