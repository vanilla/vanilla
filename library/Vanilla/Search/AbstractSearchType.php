<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Search;

use Garden\Schema\Schema;
use Vanilla\ApiUtils;

/**
 * Interface for a search item.
 */
abstract class AbstractSearchType {

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
     * Get records data by their IDs
     *
     * @param array $recordIDs
     *
     * @return SearchResultItem[]
     */
    abstract public function getResultItems(array $recordIDs): array;

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
     * Validate a query.
     *
     * @param SearchQuery $query
     *
     * @return void
     */
    abstract public function validateQuery(SearchQuery $query): void;

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
            'page:i?' => [
                'description' => 'Page number. See [Pagination](https://docs.vanillaforums.com/apiv2/#pagination).',
                'default' => 1,
                'minimum' => 1,
            ],
            'limit:i?' => [
                'description' => 'Desired number of items per page.',
                'default' => 20,
                'minimum' => 1,
                'maximum' => 1000,
            ],
            'expandBody:b?' => [
                'default' => true,
            ],
            'expand?' => ApiUtils::getExpandDefinition(['insertUser', 'breadcrumbs']),
        ]));
    }

//    /**
//     * Get a global identifier for across multiple sites.
//     *
//     *
//     *
//     * @return string
//     */
//    public function getGlobalID(): string;
}
