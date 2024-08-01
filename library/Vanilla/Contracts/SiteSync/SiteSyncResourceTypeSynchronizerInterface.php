<?php
/**
 * @author Dan Redman <dredman@higherlogic.com>
 * @copyright 2009-2021 Higher Logic LLC.
 * @license GPL-2.0-only
 */

namespace Vanilla\Contracts\SiteSync;

use Garden\Http\HttpClient;

/**
 * Associates a content producer for a resource type with a content consumer for use in
 * synchronizing resources of a specified type between two sites.
 *
 * Resource type synchronization may not necessarily match all properties of a resource type between
 * source and destination, but it will match properties specific to that resource type.
 * In contrast, properties, such as metadata, may differ between the representation contained on the source
 * and that which is contained at the destination, e.g. its unique identifier, foreign identifier, etc.
 */
interface SiteSyncResourceTypeSynchronizerInterface
{
    /**
     * Get producer of resource type specific data from source to be consumed at destination
     * as part of content synchronization between two sites.
     *
     * @return SiteSyncProducerInterface
     */
    public function getSiteSyncProducer(): SiteSyncProducerInterface;

    /**
     * Get consumer of resource type specific data at destination obtained from synchronization source
     * by producer.
     *
     * @return SiteSyncConsumerInterface
     */
    public function getSiteSyncConsumer(): SiteSyncConsumerInterface;

    /**
     * Determine whether synchronizer is able to sync all resources from source site to destination site.
     *
     * @param HttpClient $producerClient Authenticated HTTP client to interface with source site via API v2.
     * @param HttpClient $consumerClient Authenticated HTTP client to interface with destination site via API v2.
     * @return bool true if sync all is enabled on both source and destination, false otherwise.
     */
    public function isSyncAllEnabled(HttpClient $producerClient, HttpClient $consumerClient): bool;

    /**
     * Synchronize entire data set as obtained via an authenticated HTTP API v2 client for site sync source
     * by producer for consumption via an authenticated HTTP API v2 client for the site sync destination
     * by consumer.
     * Note that this is intended primarily for use cases where the set of resources to synchronize
     * is relatively small (< ~100-200 items). Use an alternate synchronization mechanism for larger data sets.
     *
     * @param HttpClient $producerClient Authenticated HTTP client that obtains the entire data set
     * from source site via API v2.
     * @param HttpClient $consumerClient Authenticated HTTP client that synchronizes the resource set
     * between source and destination via API v2 by consuming the data set provided by the producer.
     * @param string|null $foreignIDPrefix Optional string to prepend to value written to the foreignID for the
     * destination resource that references the ID of the corresponding resource at the source.
     */
    public function syncAllApi(
        HttpClient $producerClient,
        HttpClient $consumerClient,
        ?string $foreignIDPrefix = null
    ): void;
}
