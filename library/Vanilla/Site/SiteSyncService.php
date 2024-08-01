<?php
/**
 * @author Dan Redman <dredman@higherlogic.com>
 * @copyright 2009-2021 Higher Logic LLC.
 * @license GPL-2.0-only
 */

namespace Vanilla\Site;

use Vanilla\Contracts\SiteSync\SiteSyncResourceTypeSynchronizerInterface;

/**
 * Service used to register and obtain a synchronizer to synchronizes content
 * for a specific resource type between a source site and a destination site.
 */
final class SiteSyncService
{
    /** @var SiteSyncResourceTypeSynchronizerInterface[] $resourceTypeSynchronizers */
    private $resourceTypeSynchronizers = [];

    /**
     * Register a synchronizer for a specific type of resource
     *
     * @param SiteSyncResourceTypeSynchronizerInterface $resourceTypeSynchronizer
     * Synchronizer specific to a resource type
     */
    public function register(SiteSyncResourceTypeSynchronizerInterface $resourceTypeSynchronizer): void
    {
        $this->resourceTypeSynchronizers[] = $resourceTypeSynchronizer;
    }

    /**
     * Get the set of synchronizers for all resource types
     *
     * @return SiteSyncResourceTypeSynchronizerInterface[]
     */
    public function getResourceTypeSynchronizers(): array
    {
        return $this->resourceTypeSynchronizers;
    }
}
