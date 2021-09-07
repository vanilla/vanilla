<?php
/**
 * @author Dan Redman <dredman@higherlogic.com>
 * @copyright 2009-2021 Higher Logic LLC.
 * @license GPL-2.0-only
 */

namespace Vanilla\Contracts\SiteSync;

use Garden\Http\HttpClient;
use Vanilla\Web\Pagination\ApiPaginationIterator;

/**
 * Abstract implementation of a producer that retrieves resources from a source site
 * used when synchronizing resources between a source and destination site
 */
abstract class AbstractSiteSyncProducer implements SiteSyncProducerInterface {

    /**
     * @inheritdoc
     */
    public function setup(): void {
        /** empty */
    }

    /**
     * @inheritdoc
     */
    abstract public function produceAllApi(HttpClient $sourceClient): ?array;

    /**
     * Get all content via API v2 client from the synchronization source that can be consumed
     * by a destination site's synchronization consumer.
     *
     * @param HttpClient $sourceClient HTTP Client for API v2 specific to site serving as synchronization source
     * @param string $initialUrl URL of the API v2 endpoint from which to retrieve resources
     * @return array|null Content to be synchronized to the destination, or null if content set could not be retrieved.
     */
    protected function produceAllApiImpl(HttpClient $sourceClient, string $initialUrl): ?array {
        $resources = [];
        $iterator = new ApiPaginationIterator($sourceClient, $initialUrl);
        foreach ($iterator as $records) {
            if (is_array($records)) {
                $resources = array_merge($resources, $records);
            } elseif (is_null($records)) {
                // Null item indicates error when following pagination links,
                // return null to abort synchronization.
                return null;
            }
        }
        return $resources;
    }
}
