<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Garden\Events;

/**
 * Provide a standard way for making an event trackable.
 */
interface TrackingEventInterface
{
    /**
     * Get the name of the collection.
     *
     * @return string|null If null is returned the item won't be tracked.
     */
    public function getTrackableCollection(): ?string;

    /**
     * Get the event action.
     *
     * @return string
     */
    public function getAction(): string;

    /**
     * Get the event payload.
     *
     * @return array|null
     */
    public function getPayload(): ?array;

    /**
     * Get the site sectionID the event originated from.
     */
    public function getSiteSectionID(): ?string;

    /**
     * If the tracking payload differs from the event payload, implement this method. This method will be called by the container,
     * and can take any number of arguments.
     *
     * @return array
     */
    //    public function getTrackablePayload(...dependencies): array;

    /**
     * If the expected tracking action differs from our event action, implement this method to return the desired action.
     *
     * @return string
     */
    //    public function getTrackableAction(): string;
}
