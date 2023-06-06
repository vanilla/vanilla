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
abstract class AbstractSiteSyncProducer implements SiteSyncProducerInterface
{
    /** @var string $apiEndpointPath */
    protected $apiEndpointPath;

    /**
     * Constructor
     *
     * @param string $apiEndpointPath Path portion of URL for API v2 endpoint used to access resources
     * at source site when producing items to be synchronized with the destination site.
     */
    protected function __construct(string $apiEndpointPath)
    {
        $this->apiEndpointPath = $apiEndpointPath;
    }

    /**
     * @inheritdoc
     */
    public function setup(): void
    {
        /** empty */
    }

    /**
     * @inheritdoc
     */
    public function isProduceAllEnabled(HttpClient $destinationClient): bool
    {
        return true;
    }

    /**
     * Get API v2 endpoint path.
     *
     * @return string
     */
    public function getApiEndpointPath(): string
    {
        return $this->apiEndpointPath;
    }

    /**
     * @inheritdoc
     */
    public function produceAllApi(HttpClient $sourceClient): ?array
    {
        $resources = [];
        $iterator = new ApiPaginationIterator($sourceClient, $this->apiEndpointPath);
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
