<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Search;

use Garden\Schema\Schema;

/**
 * A search query object.
 */
class SearchQuery {

    const DEFAULT_LIMIT = 30;

    /** @var Schema */
    private $querySchema;

    /** @var array */
    private $queryData;

    /** @var int */
    private $offset;

    /** @var int */
    private $limit;

    /**
     * Create a query.
     *
     * @param Schema[] $partialSchemas
     * @param array $queryData
     * @param int $offset Offset for pagination.
     * @param int $limit Limit for pagination.
     */
    public function __construct(array $partialSchemas, array $queryData, int $offset = 0, int $limit = self::DEFAULT_LIMIT) {
        $this->offset = $offset;
        $this->limit = $limit;
        $querySchema = $this->baseSchema();
        foreach ($partialSchemas as $partialSchema) {
            $querySchema = $querySchema->merge($partialSchema);
        }
        $this->querySchema = $querySchema;
        $this->queryData = $this->querySchema->validate($queryData);
    }

    /**
     * @return Schema
     */
    private function baseSchema() {
        return Schema::parse([
            'name:s?',
        ]);
    }

    /**
     * @return array
     */
    public function getQueryData(): array {
        return $this->queryData;
    }

    /**
     * @return int
     */
    public function getOffset(): int {
        return $this->offset;
    }

    /**
     * @return int
     */
    public function getLimit(): int {
        return $this->limit;
    }
}
