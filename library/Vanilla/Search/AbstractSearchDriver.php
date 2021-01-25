<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Search;

use Garden\Schema\Schema;
use Vanilla\Contracts\Site\AbstractSiteProvider;
use Vanilla\InjectableInterface;

/**
 * Base search driver class.
 */
abstract class AbstractSearchDriver implements SearchTypeCollectorInterface, InjectableInterface {

    /** @var AbstractSearchType[] */
    protected $searchTypesByType = [];

    /** @var AbstractSearchType[] */
    protected $searchTypesByDtype = [];

    /** @var AbstractSearchIndexTemplate[] */
    protected $searchIndexTemplates = [];

    /** @var AbstractSiteProvider */
    private $siteProvider;

    /**
     * @param AbstractSiteProvider $siteProvider
     */
    public function setDependencies(AbstractSiteProvider $siteProvider) {
        $this->siteProvider = $siteProvider;
    }

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
     * Get a displayable name for the driver.
     *
     * @return string
     */
    abstract public function getName(): string;

    /**
     * Determine if the driver supports siteIDs on records.
     *
     * @return bool
     */
    protected function supportsForeignRecords(): bool {
        return false;
    }

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
        return SearchQuery::buildSchema($this->getSearchTypes());
    }

    /**
     * Take a set of records that were returned and convert them into result items.
     *
     * @param array[] $records An array of records to be converted. They MUST HAVE recordID and type (mapping to a SearchTypeInterface::getType()).
     * @param SearchQuery $query The query being searched.
     *
     * @return SearchResultItem[]
     * @internal Exposed for testing only.
     */
    public function convertRecordsToResultItems(array $records, SearchQuery $query): array {
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
            $resultsItemsByTypeAndID += $this->getKeyedResultItemsOfType($type, $recordSet, $query);
        }

        // Remap all records in their original order.
        /** @var SearchResultItem[] $orderedResultItems */
        $orderedResultItems = [];
        foreach ($records as $record) {
            $siteID = $this->supportsForeignRecords() ? $record['siteID'] ?? '' : '';
            $type = $record['type'] ?? '';
            $recordID = $record['recordID'] ?? '';
            $key = $siteID.$type.$recordID;
            $resultItemForKey = $resultsItemsByTypeAndID[$key] ?? null;
            if ($resultItemForKey !== null) {
                $siteID = $resultItemForKey->getSiteID();
                if ($siteID !== null) {
                    $site = $this->siteProvider->getBySiteID($siteID);
                    $resultItemForKey->setSiteDomain($site->getWebUrl());
                }

                $resultItemForKey->setSearchScore($record[SearchResultItem::FIELD_SCORE] ?? null);
                $resultItemForKey->setSubqueryMatchCount($record[SearchResultItem::FIELD_SUBQUERY_COUNT] ?? null);
                $orderedResultItems[] = $resultItemForKey;
            }
        }

        return $orderedResultItems;
    }

    /**
     * Take records of a specific type and convert them into result items.
     *
     * @param string $type The type of the records.
     * @param array $recordSet The records.
     * @param SearchQuery $query The query being searched.
     *
     * @return SearchResultItem[]
     */
    protected function getKeyedResultItemsOfType(string $type, array $recordSet, SearchQuery $query): array {
        $resultsItemsByTypeAndID = [];
        $searchType = $this->getSearchTypeByType($type);
        if ($searchType === null) {
            trigger_error('Could not find registered search type for type: ' . $searchType, E_USER_NOTICE);
            return [];
        }

        $ownSiteIDs = [];
        $foreignRecords = [];

        /** @var AbstractSiteProvider $siteProvider */
        $siteProvider = \Gdn::getContainer()->get(AbstractSiteProvider::class);
        $ownSiteID = $siteProvider->getOwnSite()->getSiteID();
        foreach ($recordSet as $record) {
            if (isset($record['siteID']) && $record['siteID'] !== $ownSiteID && $this->supportsForeignRecords()) {
                $foreignRecords[] = $record;
            } else {
                $ownSiteIDs[] = $record['recordID'];
            }
        }

        // Handle the records from our own site.
        if (count($ownSiteIDs) > 0) {
            $ownSiteResultItems = $searchType->getResultItems($ownSiteIDs, $query);
            foreach ($ownSiteResultItems as $resultItem) {
                $id = $resultItem->getAltRecordID() ?? $resultItem->getRecordID();
                $siteID = $this->supportsForeignRecords() ? $resultItem->getSiteID() : '';
                $resultsItemsByTypeAndID[$siteID.$resultItem->getType().$id] = $resultItem;
            }
        }

        if ($this->supportsForeignRecords()) {
            // Decorate the result items from other sites.
            foreach ($foreignRecords as $foreignRecord) {
                $item = $searchType->convertForeignSearchItem($foreignRecord);
                $item->setIsForeign(true);
                $id = $item->getRecordID();
                $siteID = $item->getSiteID();
                $resultsItemsByTypeAndID[$siteID.$item->getType().$id] = $item;
            }
        }
        return $resultsItemsByTypeAndID;
    }

    /**
     * Get a SearchType by a string name.
     *
     * @param string $forType
     *
     * @return AbstractSearchType|null
     */
    public function getSearchTypeByType(string $forType): ?AbstractSearchType {
        return $this->searchTypesByType[$forType] ?? null;
    }

    /**
     * @return AbstractSearchType[]
     */
    public function getSearchTypes(): array {
        return array_values($this->searchTypesByType);
    }

    /**
     * @param SearchService $searchService
     */
    public function setSearchService(SearchService $searchService) {
        foreach ($this->getSearchTypes() as $searchType) {
            $searchType->setSearchService($searchService);
        }
    }

    /**
     * @return bool
     */
    public function supportExtensions(): bool {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function getSearchTypeByDType(int $dType): ?AbstractSearchType {
        return $this->searchTypesByDtype[$dType] ?? null;
    }

    /**
     * @inheritdoc
     */
    public function getAllWithDType(): array {
        return array_values($this->searchTypesByDtype);
    }

    /**
     * Register a search type.
     *
     * @param AbstractSearchType $searchType
     */
    public function registerSearchType(AbstractSearchType $searchType) {
        foreach ($this->searchTypesByType as $existingSearchType) {
            if (get_class($searchType) === get_class($existingSearchType)) {
                // Bailout if we've already registered the type.
                return;
            }
        }
        $this->searchTypesByType[$searchType->getType()] = $searchType;
        if ($searchType->getDTypes() !== null) {
            foreach ($searchType->getDTypes() as $dtype) {
                $this->searchTypesByDtype[$dtype] = $searchType;
            }
        }
    }

    /**
     * @return AbstractSearchIndexTemplate[]
     */
    public function getSearchIndexTemplates(): array {
        return $this->searchIndexTemplates;
    }

    /**
     * Register a search index template.
     *
     * @param AbstractSearchIndexTemplate $searchIndexTemplate
     */
    public function registerSearchIndexTemplate(AbstractSearchIndexTemplate $searchIndexTemplate) {
        foreach ($this->searchIndexTemplates as $existingSearchIndexTemplate) {
            if (get_class($searchIndexTemplate) === get_class($existingSearchIndexTemplate)) {
                return;
            }
        }
        $this->searchIndexTemplates[] = $searchIndexTemplate;
    }
}
