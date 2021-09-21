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
 * Abstract implementation of a consumer of resources produced from a source site
 * used when synchronizing resources between a source and destination site
 */
abstract class AbstractSiteSyncConsumer implements SiteSyncConsumerInterface {

    /** @var string $primaryIDColumnName */
    protected $primaryIDColumnName;

    /** @var string $foreignIDColumnName */
    protected $foreignIDColumnName;

    /** @var string $apiEndpointPath */
    protected $apiEndpointPath;

    /**
     * Constructor
     *
     * @param string $apiEndpointPath Path portion of URL for API v2 endpoint used to access resources
     * during consumption processing.
     * @param string $primaryIDColumnName Name of primary key column that serves as the resource's ID.
     * @param string $foreignIDColumnName Optional, name of foreignID column in destination
     * that references the sync source ID value, defaults to "foreignID"
     */
    protected function __construct(
        string $apiEndpointPath,
        string $primaryIDColumnName,
        string $foreignIDColumnName = "foreignID"
    ) {
        $this->apiEndpointPath = $apiEndpointPath;
        $this->primaryIDColumnName = $primaryIDColumnName;
        $this->foreignIDColumnName = $foreignIDColumnName;
    }

    /**
     * @inheritdoc
     */
    public function setup(): void {
        /** empty */
    }

    /**
     * @inheritdoc
     */
    public function isConsumeAllEnabled(HttpClient $destinationClient): bool {
        return true;
    }

    /**
     * Get API v2 endpoint path.
     *
     * @return string
     */
    public function getApiEndpointPath(): string {
        return $this->apiEndpointPath;
    }

    /**
     * Get the set of properties for this resource type that are not included when consuming source resources
     * during synchronization.
     * Examples of unsynced properties may include properties considered "metadata" and as such would be particular
     * to the source's representation of that resource, such as when and by whom the record was written or updated,
     * its primary key value, etc.
     *
     * @return string[]
     */
    public function getUnsyncedProperties(): array {
        // Include some sensible defaults that may be shared across multiple table schema instances;
        // these can be overridden or augmented as needed when subclassed.
        return [
            $this->primaryIDColumnName,
            $this->foreignIDColumnName,
            'dateInserted',
            'insertUserID',
            'dateUpdated',
            'updateUserID'
        ];
    }

    /**
     * @inheritdoc
     */
    public function consumeAllApi(
        HttpClient $destinationClient,
        array $sourceResources,
        ?string $foreignIDPrefix = null
    ): void {

        $destResources = $this->getDestResources($destinationClient, $this->apiEndpointPath, $foreignIDPrefix);
        if (is_null($destResources)) {
            // Null item indicates error when following pagination links,
            // return to abort synchronization.
            return;
        }

        if (empty($sourceResources)) {
            if (!empty($destResources)) {
                $deleteIDs = array_column($destResources, $this->primaryIDColumnName);
                foreach ($deleteIDs as $deleteID) {
                    $this->deleteResourceViaApi($destinationClient, $this->apiEndpointPath, strval($deleteID));
                }
            }
            // else: No source resources and no destination resources = no action required
            return;
        }

        $resourcesToCreate = $this->getResourcesToCreate($sourceResources, $destResources, $foreignIDPrefix);
        if (!empty($resourcesToCreate)) {
            foreach ($resourcesToCreate as $resourceToCreate) {
                $this->createResourceViaApi(
                    $destinationClient,
                    $this->apiEndpointPath,
                    $resourceToCreate,
                    $foreignIDPrefix
                );
            }
        }

        $deleteIDs = [];
        $resourcesToDelete = $this->getResourcesToDelete($sourceResources, $destResources, $foreignIDPrefix);
        if (!empty($resourcesToDelete)) {
            foreach (array_values($resourcesToDelete) as $resourceToDelete) {
                $deleteID = $deleteIDs[] = intval($resourceToDelete[$this->primaryIDColumnName]);
                $this->deleteResourceViaApi($destinationClient, $this->apiEndpointPath, $deleteID);
            }
        }

        // Remove any deleted elements from the destination resources as these are not eligible for update
        if (!empty($deleteIDs)) {
            $destResources = array_filter($destResources, function ($destResource) use ($deleteIDs) {
                return !in_array($destResource[$this->primaryIDColumnName], $deleteIDs);
            });
            if (empty($destResources)) {
                return;
            }
        }

        // Remove any created elements from the source resources as these are not eligible for update
        if (!empty($resourcesToCreate)) {
            $sourceCreatedIDs = array_column($resourcesToCreate, $this->primaryIDColumnName);
            $sourceResources = array_filter($sourceResources, function ($sourceResource) use ($sourceCreatedIDs) {
                return !in_array($sourceResource[$this->primaryIDColumnName], $sourceCreatedIDs);
            });
            if (empty($sourceResources)) {
                return;
            }
        }

        $resourcesToUpdate = $this->getResourcesToUpdate($sourceResources, $destResources, $foreignIDPrefix);
        if (!empty($resourcesToUpdate)) {
            foreach ($resourcesToUpdate as $destinationID => $resourceToUpdate) {
                $this->updateResourceViaApi(
                    $destinationClient,
                    $this->apiEndpointPath,
                    $destinationID,
                    $resourceToUpdate
                );
            }
        }
    }

    /**
     * Get the set of synchronizable resources from the destination site. If a foreign ID prefix is provided,
     * the set is filtered based on that value in the foreign ID column at the destination site.
     *
     * @param HttpClient $destinationClient Authenticated API v2 HTTP client for the destination site
     * @param string $apiEndpointPath API v2 endpoint to which to connect to access resources at destination site.
     * @param string|null $foreignIDPrefix Optional string prepended to value written to the foreignID for the
     * destination resource during synchronization that references the ID of the corresponding resource at the source.
     * @return array|null Set of synchronizable resources from the destination, or null if error encountered accessing
     * resources at the destination.
     */
    protected function getDestResources(
        HttpClient $destinationClient,
        string $apiEndpointPath,
        ?string $foreignIDPrefix
    ): ?array {
        $iterator = new ApiPaginationIterator($destinationClient, $apiEndpointPath);
        $destResources = [];
        foreach ($iterator as $records) {
            if (is_array($records)) {
                // Only concerned with resources on the destination site that originate from the source site,
                // as indicated by a non-null foreign ID column value that begins with foreignID prefix, if provided.
                $destResources = array_merge(
                    $destResources,
                    array_filter(
                        $records,
                        function (array $record) use ($foreignIDPrefix) {
                            return isset($record[$this->foreignIDColumnName])
                                && (empty($foreignIDPrefix)
                                    || str_starts_with($record[$this->foreignIDColumnName], $foreignIDPrefix));
                        }
                    )
                );
            } elseif (is_null($records)) {
                // Null item indicates error when following pagination links,
                // return to abort synchronization.
                return null;
            }
        }
        return $destResources;
    }

    /**
     * Partition the source resources into a set that is not represented at the destination.
     * These are the resources to be created at the destination as part of the sync consumption.
     *
     * @param array $sourceResources Resources to sync from site sync source
     * @param array $destResources Resources that exist at site sync destination
     * @param string|null $foreignIDPrefix Optional string to prepend to value written to the foreignID for the
     * destination resource that references the ID of the corresponding resource at the source.
     * @return array Set of source resources to be created at destination site
     */
    protected function getResourcesToCreate(
        array $sourceResources,
        array $destResources,
        ?string $foreignIDPrefix = null
    ): array {

        // Return the set of IDs from the source that aren't referenced as a foreign ID in the destination.
        $createResourceIDs = array_diff(
            array_column($sourceResources, $this->primaryIDColumnName),
            array_map(
                function ($foreignID) use ($foreignIDPrefix) {
                    return str_replace($foreignIDPrefix, "", $foreignID);
                },
                array_column($destResources, $this->foreignIDColumnName)
            )
        );

        // Return the set of source resources whose IDs are in the set produced above.
        return array_filter($sourceResources, function ($sourceResource) use ($createResourceIDs) {
            return in_array($sourceResource[$this->primaryIDColumnName], $createResourceIDs);
        });
    }

    /**
     * Create the resource at the destination site given the resource's representation from the source site.
     *
     * @param HttpClient $destinationClient Authenticated API v2 HTTP client for the destination site
     * @param string $apiEndpointPath API v2 endpoint to which to connect to access resources at destination site.
     * @param array $resourceToCreate Resource from source to create at destination as part of sync consumption
     * @param string|null $foreignIDPrefix Optional string to prepend to value written to the foreignID for the
     * destination resource that references the ID of the corresponding resource at the source.
     */
    protected function createResourceViaApi(
        HttpClient $destinationClient,
        string $apiEndpointPath,
        array $resourceToCreate,
        ?string $foreignIDPrefix = null
    ): void {
        // Form the foreign ID from the source resource's ID.
        $foreignID = ($foreignIDPrefix ?? "").$resourceToCreate[$this->primaryIDColumnName];
        // Exclude any unsynced properties from the source representation
        $unsyncedProperties = $this->getUnsyncedProperties();
        $resourceToCreate = array_filter($resourceToCreate, function ($key) use ($unsyncedProperties) {
            return !in_array($key, $unsyncedProperties);
        }, ARRAY_FILTER_USE_KEY);
        // The foreignID may have been included in the unsynced properties and stripped out.
        // We want to set that in the destination resource but not with the source's value
        // but with a value derived from the source as above.
        $resourceToCreate[$this->foreignIDColumnName] = $foreignID;
        // Assumes any source properties that are not to be set via resource POST that are not in unsynced properties
        // are omitted at the destination prior to persisting the resource via the API's input schema validation
        $destinationClient->post($apiEndpointPath, $resourceToCreate);
    }

    /**
     * Partition the destination resources into a set that is not represented at the source.
     * These are the resources to be deleted at the destination as part of the sync consumption.
     *
     * @param array $sourceResources Resources to sync from site sync source
     * @param array $destResources Resources that exist at site sync destination
     * @param string|null $foreignIDPrefix Optional string to prepend to value written to the foreignID for the
     * destination resource that references the ID of the corresponding resource at the source.
     * @return array Set of resources to delete at destination site
     */
    protected function getResourcesToDelete(
        array $sourceResources,
        array $destResources,
        ?string $foreignIDPrefix = null
    ): array {

        // Return the set of foreign IDs from the destination that aren't referenced as a primary ID in the source.
        $destDeleteForeignIDs = array_diff(
            array_column($destResources, $this->foreignIDColumnName),
            array_map(
                function ($primaryID) use ($foreignIDPrefix) {
                    return ($foreignIDPrefix ?? "").$primaryID;
                },
                array_column($sourceResources, $this->primaryIDColumnName)
            )
        );

        // Return the set of destination resources whose IDs are in the set produced above.
        return array_filter($destResources, function ($destResource) use ($destDeleteForeignIDs) {
            return in_array($destResource[$this->foreignIDColumnName], $destDeleteForeignIDs);
        });
    }

    /**
     * Delete the resource at the destination site given its ID
     *
     * @param HttpClient $destinationClient Authenticated API v2 HTTP client for the destination site
     * @param string $apiEndpointPath API v2 endpoint to which to connect to access resources at destination site.
     * @param string $resourceIDToDelete ID of resource to delete at destination site
     */
    protected function deleteResourceViaApi(
        HttpClient $destinationClient,
        string $apiEndpointPath,
        string $resourceIDToDelete
    ): void {
        $destinationClient->delete(trim($apiEndpointPath, "/")."/{$resourceIDToDelete}");
    }

    /**
     * Get the set of resources contained both at the source and destination where the source representation
     * differs from the representation at the destination.
     *
     * @param array $sourceResources Resources to sync from site sync source
     * @param array $destResources Resources that exist at site sync destination
     * @param string|null $foreignIDPrefix Optional string to prepend to value written to the foreignID for the
     * destination resource that references the ID of the corresponding resource at the source.
     * @return array Associative array, indexed by ID of resource at destination, of the set of properties at source
     * that differ from corresponding set of properties at destination.
     */
    protected function getResourcesToUpdate(
        array $sourceResources,
        array $destResources,
        ?string $foreignIDPrefix = null
    ): array {

        // Create a dictionary indexed by the destination's foreign ID of destination resource IDs
        $destinationIDLookup = array_combine(
            array_column($destResources, $this->foreignIDColumnName),
            array_column($destResources, $this->primaryIDColumnName)
        );

        $unsyncedProperties = $this->getUnsyncedProperties();
        // Reindex the source resources by their primary key value, only including sync-able properties in the result
        $syncableSourceResources = array_combine(
            array_column($sourceResources, $this->primaryIDColumnName),
            array_map(function ($sourceResource) use ($unsyncedProperties) {
                return array_filter($sourceResource, function ($key) use ($unsyncedProperties) {
                    return !in_array($key, $unsyncedProperties);
                }, ARRAY_FILTER_USE_KEY);
            }, array_values($sourceResources))
        );

        // Reindex the destination resources by their foreign ID value, which references the source's ID value,
        // including only sync-able properties in the result
        $syncableDestResources = array_combine(
            array_column($destResources, $this->foreignIDColumnName),
            array_map(function ($destResource) use ($unsyncedProperties) {
                return array_filter($destResource, function ($key) use ($unsyncedProperties) {
                    return !in_array($key, $unsyncedProperties);
                }, ARRAY_FILTER_USE_KEY);
            }, array_values($destResources))
        );

        // This should produce only those source resources, indexed by the source resource's ID,
        // that corresponds to a synchronized destination resource and where at least one of the values
        // in the source resource's properties doesn't match the corresponding value
        // in the destination resource's properties.
        $sourceSyncCandidates = array_udiff_uassoc(
            $syncableSourceResources,
            $syncableDestResources,
            function (array $sourceResource, array $destResource) {
                return array_udiff_assoc(
                    $sourceResource,
                    $destResource,
                    [$this, 'compareResourceValues']
                );
            },
            function ($sourceResourceKey, $destResourceKey) use ($foreignIDPrefix) {
                $destResourceKeyToCompare =
                    empty($foreignIDPrefix) ? $destResourceKey : str_replace($foreignIDPrefix, "", $destResourceKey);
                return intval($sourceResourceKey) <=> intval($destResourceKeyToCompare);
            }
        );

        // Iterate through each source item requiring sync to destination to create an associative array,
        // indexed by the corresponding destination resource's ID, of the syncable properties in the source item
        // whose value(s) differ from the syncable properties in the corresponding destination item.
        $syncCandidates = [];
        foreach ($sourceSyncCandidates as $id => $sourceSyncCandidate) {
            $index = ($foreignIDPrefix ?? "").$id;
            if (array_key_exists($index, $destinationIDLookup) && array_key_exists($index, $syncableDestResources)) {
                $syncCandidates[$destinationIDLookup[$index]] =
                    array_udiff_assoc(
                        $sourceSyncCandidate,
                        $syncableDestResources[$index],
                        [$this, 'compareResourceValues']
                    );
            }
        }

        return $syncCandidates;
    }

    /**
     * Compare values of a property from a source resource and a destination resource,
     * used when diff'ing a source resource with a destination resource
     *
     * @param mixed $srcVal Property value at source
     * @param mixed $destVal Property value at destination
     * @return int integer less than, equal to, or greater than zero if the first argument is considered to be
     * respectively less than, equal to, or greater than the second
     */
    protected function compareResourceValues($srcVal, $destVal) : int {
        return is_scalar($srcVal) && is_scalar($destVal)
            ? $srcVal <=> $destVal
            : (is_array($srcVal) && is_array($destVal)
                ? count(array_udiff_assoc($srcVal, $destVal, [$this, 'compareResourceValues']))
                : -1);  // data types do not have the same "shape" so they're irreconcilably different
                        // and all we care about is different or same, as order isn't useful in this context,
                        // so use -1 to denote they're different.
    }

    /**
     * Update the resource at the destination site given its ID and the properties to update
     *
     * @param HttpClient $destinationClient Authenticated API v2 HTTP client for the destination site
     * @param string $apiEndpointPath API v2 endpoint to which to connect to access resources at destination site.
     * @param string $resourceIDToUpdate ID of resource to update at destination site
     * @param array $resourcePropsToUpdate Properties of resource to update at destination site
     */
    protected function updateResourceViaApi(
        HttpClient $destinationClient,
        string $apiEndpointPath,
        string $resourceIDToUpdate,
        array $resourcePropsToUpdate
    ): void {
        $destinationClient->patch(trim($apiEndpointPath, "/")."/{$resourceIDToUpdate}", $resourcePropsToUpdate);
    }
}
