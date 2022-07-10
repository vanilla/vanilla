<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Models;

/**
 * A model that caches all queries that come through, and invalidates the cache on every change.
 *
 * Ideal for DB tables that are infrequently updated and have smaller record sets.
 */
class FullRecordCacheModel extends PipelineModel {

    /** @var ModelCache $cache */
    protected $modelCache;

    /**
     * Model constructor.
     *
     * @param string $table Database table associated with this resource.
     * @param \Gdn_Cache $cache The cache instance.
     * @param array $defaultCacheOptions Default options to apply for storing cache values.
     */
    public function __construct(string $table, \Gdn_Cache $cache, array $defaultCacheOptions = []) {
        parent::__construct($table);
        $this->modelCache = new ModelCache($this->getTable(), $cache, $defaultCacheOptions);
        $this->addPipelineProcessor($this->modelCache->createInvalidationProcessor());
    }

    /**
     * Get resource rows from the cache or a database table.
     *
     * @param array $where Conditions for the select query.
     * @param array $options Options for the select query.
     *    - orderFields (string, array): Fields to sort the result by.
     *    - orderDirection (string): Sort direction for the order fields.
     *    - limit (int): Limit on the total results returned.
     *    - offset (int): Row offset before capturing the result.
     *    - cacheOptions (array): Feature options from `Gdn_Cache::FEATURE_*`.
     * @return array Rows matching the conditions and within the parameters specified in the options.
     */
    public function select(array $where = [], array $options = []): array {
        $cacheOptions = $options['cacheOptions'] ?? [];
        return $this->modelCache->getCachedOrHydrate(
            [
                'where' => $where,
                'options' => $options,
                'function' => __FUNCTION__, // For uniqueness.
            ],
            function () use ($where, $options) {
                return parent::select($where, $options);
            },
            $cacheOptions
        );
    }

    /**
     * Get all records.
     */
    public function getAll() {
        return $this->get();
    }

    /**
     * Clear the cache for the model.
     */
    public function clearCache() {
        $this->modelCache->invalidateAll();
    }
}
