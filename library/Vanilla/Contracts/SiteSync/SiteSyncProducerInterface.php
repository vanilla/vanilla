<?php
/**
 * @author Dan Redman <dredman@higherlogic.com>
 * @copyright 2009-2021 Higher Logic LLC.
 * @license GPL-2.0-only
 */

namespace Vanilla\Contracts\SiteSync;

use Garden\Http\HttpClient;

/**
 * Defines interface for a source that produces data to be synchronized to a destination
 */
interface SiteSyncProducerInterface
{
    /**
     * Perform any necessary setup on sync source prior to producing data to be synced from source to destination
     */
    public function setup(): void;

    /**
     * Determine whether the source site is enabled to produce all resources to be consumed by destination site.
     *
     * @param HttpClient $destinationClient Authenticated API v2 HTTP client for the sync source site
     * @return bool
     */
    public function isProduceAllEnabled(HttpClient $destinationClient): bool;

    /**
     * Get all content via API v2 client from the synchronization source that can be consumed
     * by a destination site's synchronization consumer.
     *
     * @param HttpClient $sourceClient HTTP Client for API v2 specific to site serving as synchronization source
     * @return array|null Content to be synchronized to the destination, or null if content set could not be retrieved.
     */
    public function produceAllApi(HttpClient $sourceClient): ?array;
}
