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

    /** @var Schema */
    private $querySchema;

    /** @var array */
    private $queryData;

    /**
     * Create a query.
     *
     * @param Schema[] $partialSchemas
     * @param array $queryData
     */
    public function __construct(array $partialSchemas, array $queryData) {
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
}
