<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Search;

use Garden\Schema\Schema;
use Vanilla\ApiUtils;
use Vanilla\Contracts\Site\AbstractSiteProvider;
use Vanilla\Utility\ArrayUtils;
use Vanilla\Search\SearchService;

/**
 * Interface for a search item.
 */
abstract class AbstractSearchType {

    /** @var SearchService */
    protected $searchService;

    /**
     * @var string Class used to construct search result items.
     */
    public static $searchItemClass = SearchResultItem::class;

    /**
     * Get a unique key for the search type.
     *
     * @return string
     */
    abstract public function getKey(): string;

    /**
     * Maps to how the record lives in the database.
     *
     * Eg. discussion, article, category.
     *
     * @return string
     */
    abstract public function getSearchGroup(): string;

    /**
     * Get the type of the record.
     *
     * Eg. Discussion, Comment, Article, Category.
     *
     * @return string
     */
    abstract public function getType(): string;

    /**
     * Get search engine index
     *
     * @return string
     */
    public function getIndex(): string {
        return $this->getSearchGroup();
    }

    /**
     * Set the search service.
     *
     * @param \Vanilla\Search\SearchService $searchService
     */
    public function setSearchService(SearchService $searchService) {
        $this->searchService = $searchService;
    }

    /**
     * Get records data by their IDs
     *
     * @param array $recordIDs
     * @param SearchQuery $query
     *
     * @return SearchResultItem[]
     */
    abstract public function getResultItems(array $recordIDs, SearchQuery $query): array;

    /**
     * Convert a foreign record into a search result.
     *
     * @param array $record
     * @return SearchResultItem
     */
    public function convertForeignSearchItem(array $record): SearchResultItem {
        $record['recordType'] = $this->getSearchGroup();
        $record['type'] = $this->getType();
        unset($record['breadcrumbs']);

        // Ensure the siteID gets expanded.
        return new static::$searchItemClass($record);
    }

    /**
     * Apply a search query to the engine.
     *
     * @param SearchQuery $query The search query.
     */
    abstract public function applyToQuery(SearchQuery $query);

    /**
     * Get supported sorts for the type.
     */
    abstract public function getSorts(): array;

    /**
     * Get the schema for supported query parameters.
     *
     * @return Schema
     */
    abstract public function getQuerySchema(): Schema;

    /**
     * Get the schema extension include additional schema rules not supported by some search drivers.
     *
     * @return Schema
     */
    public function getQuerySchemaExtension(): ?Schema {
        return null;
    }
    /**
     * Validate a query.
     *
     * @param SearchQuery $query
     *
     * @return void
     */
    abstract public function validateQuery(SearchQuery $query): void;

    /**
     * Get a schema of available boost values.
     *
     * @return Schema|null
     */
    public function getBoostSchema(): ?Schema {
        return null;
    }

    /**
     * @return bool
     */
    public function supportsCollapsing(): bool {
        return false;
    }

    /**
     * Is the search template available.
     *
     * @return bool
     */
    public function isLegacyTemplateAvailable(): bool {
        return true;
    }

    /**
     * Take an existing schema and add the current types to it.
     *
     * @param Schema $schema
     * @return Schema
     */
    protected function schemaWithTypes(Schema $schema): Schema {
        return $schema->merge(Schema::parse([
            'recordTypes:a?' => [
                'items' => [
                    'type' => 'string',
                    'enum' => [$this->getSearchGroup()],
                ],
                'style' => 'form',
                'description' => 'Restrict the search to the specified main type(s) of records.',
            ],
            'types:a?' => [
                'items' => [
                    'type' => 'string',
                    'enum' => [$this->getType()]
                ],
                'style' => 'form',
                'description' => 'Restrict the search to the specified type(s) of records.',
            ],
        ]));
    }

    /**
     * If set to true, the type can only ever be searched on it's own. Never mixed in with other records.
     *
     * @return bool
     */
    public function isExclusiveType(): bool {
        return false;
    }

    /**
     * Check if the user has permission to search with this type.
     *
     * @return bool
     */
    public function userHasPermission(): bool {
        return true;
    }

    ///
    /// Legacy search
    ///

    /**
     * @return string
     */
    public function getLegacyCheckBoxID(): string {
        return $this->getSearchGroup() . '_' . $this->getType();
    }

    /**
     * Get the legacy index names to apply.
     *
     * @param SearchQuery $query
     *
     * @return string[]
     */
    public function getLegacyIndexNames(SearchQuery $query): array {
        return [ucfirst($this->getIndex())];
    }

    /**
     * @return string
     */
    abstract public function getSingularLabel(): string;

    /**
     * @return string
     */
    abstract public function getPluralLabel(): string;

    /**
     * @return int[]|null
     */
    abstract public function getDTypes(): ?array;

    /**
     * Transform a guid of this type into a recordID.
     *
     * @param int $guid
     *
     * @return int
     */
    abstract public function guidToRecordID(int $guid): ?int;
}
