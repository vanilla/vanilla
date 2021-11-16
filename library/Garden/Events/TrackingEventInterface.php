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
interface TrackingEventInterface {

    /**
     * Get the name of the collection.
     *
     * @return string
     */
    public function getCollectionName(): string;

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
}
