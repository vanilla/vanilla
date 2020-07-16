<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Search;

use Garden\Schema\Schema;

/**
 *
 */
abstract class AbstractSearchDriver {

    /** @var AbstractSearchType[] */
    private $searchTypes = [];

    /**
     * Perform a query.
     *
     * @param array $queryData
     * @param SearchOptions $options
     *
     * @return SearchResults
     */
    abstract public function search(array $queryData, SearchOptions $options): SearchResults;

    /**
     * Return short form of search driver name
     *
     * @return string
     */
    public function driver(): string {
        return '';
    }

    /**
     * Get the schema for a query.
     *
     * @return Schema
     */
    public function buildQuerySchema(): Schema {
        $querySchema = new Schema();
        foreach ($this->searchTypes as $searchType) {
            $querySchema = $querySchema->merge($searchType->getQuerySchema());
        }
        return $querySchema;
    }

    /**
     * Take a set of records that were returned and convert them into result items.
     *
     * @param array[] $records An array of records to be converted. They MUST HAVE recordID and type (mapping to a SearchTypeInterface::getType()).
     *
     * @return SearchResultItem[]
     * @internal Exposed for testing only.
     */
    public function convertRecordsToResultItems(array $records): array {
        $recordsByType = [];

        foreach ($records as $record) {
            $type = $record['type'] ?? null;
            $id = $record['recordID'] ?? null;

            if (!is_string($type) || !is_numeric($id)) {
                trigger_error('Search record missing a valid recordType or recordID. ' . json_encode($records), E_USER_NOTICE);
                continue;
            }

            if (!isset($recordsByType[$type])) {
                $recordsByType[$type] = [$record];
            } else {
                $recordsByType[$type][] = $record;
            }
        }

        $resultsItemsByTypeAndID = [];

        // Convert to resultsItems.
        foreach ($recordsByType as $type => $recordSet) {
            $searchType = $this->findSearchTypeByType($type);
            if ($searchType === null) {
                trigger_error('Could not find registered search type for type: ' . $searchType, E_USER_NOTICE);
                continue;
            }
            $recordIDs = array_column($recordSet, 'recordID');
            $resultItems = $searchType->getResultItems($recordIDs, ['driver' => $this->driver()]);
            foreach ($resultItems as $resultItem) {
                $id = $resultItem->getAltRecordID() ?? $resultItem->getRecordID();
                $resultsItemsByTypeAndID[$resultItem->getType().$id] = $resultItem;
            }
        }

        // Remap all records in their original order.
        /** @var SearchResultItem[] $orderedResultItems */
        $orderedResultItems = [];
        foreach ($records as $record) {
            $type = $record['type'] ?? '';
            $recordID = $record['recordID'] ?? '';
            $key = $type.$recordID;
            $resultItemForKey = $resultsItemsByTypeAndID[$key] ?? null;
            if ($resultItemForKey !== null) {
                $orderedResultItems[] = $resultItemForKey;
            }
        }

        return $orderedResultItems;
    }

    /**
     * Get a SearchType by a string name.
     *
     * @param string $forType
     *
     * @return AbstractSearchType|null
     */
    public function findSearchTypeByType(string $forType): ?AbstractSearchType {
        foreach ($this->searchTypes as $searchType) {
            if ($searchType->getType() === $forType) {
                return $searchType;
            }
        }

        return null;
    }

    /**
     * @return AbstractSearchType[]
     */
    public function getSearchTypes(): array {
        return $this->searchTypes;
    }

    /**
     * Register a search type.
     *
     * @param AbstractSearchType $searchType
     */
    public function registerSearchType(AbstractSearchType $searchType) {
        $this->searchTypes[] = $searchType;
    }
}
