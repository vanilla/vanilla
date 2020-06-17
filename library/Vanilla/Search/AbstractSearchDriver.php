<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Search;

/**
 *
 */
abstract class AbstractSearchDriver {

    /** @var SearchTypeInterface[] */
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
            $type = $records['type'] ?? null;
            $id = $records['recordID'] ?? null;

            if (!is_string($type) || !is_numeric($id)) {
                trigger_error(E_USER_NOTICE, 'Search record missing a valid recordType or recordID. ' . json_encode($records));
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
                trigger_error(E_USER_NOTICE, 'Could not find registered search type for type: ' . $searchType);
                continue;
            }
            $recordIDs = array_column($recordSet, 'recordID');
            $resultItems = $searchType->getResultItems($recordIDs);
            foreach ($resultItems as $resultItem) {
                $resultsItemsByTypeAndID[$resultItem->getType().$resultItem->getRecordID()] = $resultItem;
            }
        }

        // Remap all records in their original order.
        /** @var SearchResultItem[] $orderedResultItems */
        $orderedResultItems = [];
        foreach ($records as $record) {
            $type = $record['type'] ?? '';
            $recordID = $records['recordID'] = '';
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
     * @return SearchTypeInterface|null
     */
    public function findSearchTypeByType(string $forType): ?SearchTypeInterface {
        foreach ($this->searchTypes as $searchType) {
            if ($searchType->getType() === $forType) {
                return $searchType;
            }
        }

        return null;
    }

    /**
     * @return SearchTypeInterface[]
     */
    public function getSearchTypes(): array {
        return $this->searchTypes;
    }

    /**
     * Register a search type.
     *
     * @param SearchTypeInterface $searchType
     */
    public function registerSearchType(SearchTypeInterface $searchType) {
        $this->searchTypes[] = $searchType;
    }
}
