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
    private $modelCache;

    /**
     * Model constructor.
     *
     * @param string $table Database table associated with this resource.
     * @param array|null $defaultCacheOptions Default options to apply for storing cache values.
     */
    public function __construct(string $table, ?array $defaultCacheOptions = []) {
        parent::__construct($table);
        $this->modelCache = ModelCache::fromModel($this, $defaultCacheOptions);
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
     * @param array|null $cacheOptions Options for the cache storage.
     * @return array Rows matching the conditions and within the parameters specified in the options.
     */
    public function get(array $where = [], array $options = [], ?array $cacheOptions = []): array {
        return $this->modelCache->getCachedOrHydrate(
            [
                'where' => $where,
                'options' => $options,
            ],
            function () use ($where, $options) {
                return parent::get($where, $options);
            }
        );
    }

    /**
     * Get all records.
     */
    public function getAll() {
        return $this->get();
    }

    /**
     * @return ModelCache
     */
    public function getModelCache(): ModelCache {
        return $this->modelCache;
    }
}
