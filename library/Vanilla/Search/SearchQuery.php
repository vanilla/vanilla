<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Search;

use Garden\Schema\Schema;
use Garden\Schema\Validation;
use Garden\Schema\ValidationException;
use Garden\Web\Exception\ServerException;
use Vanilla\ApiUtils;
use Vanilla\DateFilterSchema;
use Vanilla\Utility\ArrayUtils;
use Webmozart\Assert\Assert;

/**
 * A search query object.
 */
abstract class SearchQuery {

    const LIMIT_DEFAULT = 10;
    const LIMIT_MAXIMUM = 100;

    const FILTER_OP_OR = 'or';
    const FILTER_OP_AND = 'and';
    const FILTER_OP_WILDCARD = 'wildcard';

    const MATCH_FULLTEXT = "fulltext";
    const MATCH_FULLTEXT_EXTENDED = "fulltext_extended";
    const MATCH_WILDCARD = "wildcard";
    const MATCH_PHRASE = "match_phrase";
    const MATCH_PHRASE_PREFIX = "match_phrase_prefix";

    const SORT_RELEVANCE = 'relevance';
    const SORT_ASC = 'asc';
    const SORT_DESC = 'desc';

    /** @var Schema */
    protected $querySchema;

    /** @var array */
    protected $queryData;

    /** @var array $indexes */
    protected $indexes;

    /** @var AbstractSearchType|null */
    protected $currentType = null;

    /** @var array The boost values. */
    private $boosts;

    /**
     * Create a query.
     *
     * @param AbstractSearchType[] $searchTypes The registered search types contributing to the query.
     * @param array $queryData The data making up the query.
     */
    public function __construct(array $searchTypes, array $queryData) {
        $allTypeCollection = new SearchTypeCollection($searchTypes);
        $filteredTypes = $allTypeCollection->getFilteredCollection($queryData);

        if (!$filteredTypes->hasExclusiveType()) {
            $globalType = $allTypeCollection->getByType('global')[0] ?? null;
            if ($globalType) {
                // Make sure the global type is added if possible.
                $filteredTypes->addType($globalType);
            }
        }

        $this->querySchema = $this->buildSchema($searchTypes);
        $this->queryData = $this->querySchema->validate($queryData);

        $boosts = $this->getQueryParameter('boosts', null);
        if ($boosts === null) {
            // Apply default boosts.
            $boostSchema = self::buildBoostSchema($searchTypes);
            $this->boosts = $boostSchema->validate([]);
        } else {
            // Some boosts are defined.

            if ($boosts['enabled'] === false) {
                // Boosts were explicitly disabled from this query.
                // As a result we will not apply any defaults or any other values.
                $this->boosts = [];
            } else {
                $this->boosts = $boosts;
            }
        }

        // Give each of the search types a chance to validate the query object.
        foreach ($filteredTypes as $searchType) {
            $searchType->validateQuery($this);
        }

        $hasCollapsableType = false;
        // Give each of the search types a chance to validate the query object.
        /** @var AbstractSearchType $searchType */
        foreach ($filteredTypes as $searchType) {
            if ($searchType->supportsCollapsing()) {
                $hasCollapsableType = true;
            }
            if ((!$searchType instanceof GlobalSearchType)) {
                $this->startTypeQuery($searchType);
            }
            $searchType->applyToQuery($this);
            $this->endTypeQuery();
        }

        if ($this instanceof CollapsableSerachQueryInterface && $this->getQueryParameter('collapse') && $hasCollapsableType) {
            $this->collapseField('recordCollapseID');
        }
    }

    /**
     * @param AbstractSearchType $currentType
     */
    protected function startTypeQuery(AbstractSearchType $currentType): void {
        $this->currentType = $currentType;
    }

    /**
     * End the current type-specific query.
     */
    protected function endTypeQuery(): void {
        $this->currentType = null;
    }

    /**
     * Get a specific query parameter.
     *
     * @param string $queryParam
     * @param mixed $default
     *
     * @return mixed|null
     */
    public function getQueryParameter(string $queryParam, $default = null) {
        return $this->queryData[$queryParam] ?? $default;
    }

    /**
     * Get a specific boost parameter. Note, this will fail for undefined values.
     * In order to make use of a boost value, define it and a default in the boost schema.
     *
     * This may also return null if a boost parameter does not exists.
     * MAKE SURE YOU HANDLE THIS CASE AND DON'T APPLY THE BOOST.
     *
     * @param string $boostParam
     *
     * @return mixed|null
     */
    public function getBoostParameter(string $boostParam) {
        return ArrayUtils::getByPath($boostParam, $this->boosts, null);
    }

    /**
     * Build the search query schema.
     *
     * @param AbstractSearchType[] $searchTypes
     *
     * @return Schema
     */
    public static function buildSchema(array $searchTypes): Schema {
        $querySchema = Schema::parse([
            'page:i?' => [
                'description' => 'Page number. See [Pagination](https://docs.vanillaforums.com/apiv2/#pagination).',
                'default' => 1,
                'minimum' => 1,
            ],
            'limit:i?' => [
                'description' => 'Desired number of items per page.',
                'default' => self::LIMIT_DEFAULT,
                'minimum' => 1,
                'maximum' => self::LIMIT_MAXIMUM,
            ],
            'expand?' => ApiUtils::getExpandDefinition(['insertUser', 'updateUser', 'breadcrumbs', 'image', 'excerpt', '-body', 'tagIDs']),
            'recordTypes:a?' => [
                'items' => [
                    'type' => 'string',
                    'enum' => array_map(function ($searchType) {
                        return $searchType->getSearchGroup();
                    }, $searchTypes),
                ],
                'style' => 'form',
                'description' => 'Restrict the search to the specified main type(s) of records.',
            ],
            'types:a?' => [
                'items' => [
                    'type' => 'string',
                    'enum' => array_map(function ($searchType) {
                        return $searchType->getType();
                    }, $searchTypes),
                ],
                'style' => 'form',
                'description' => 'Restrict the search to the specified type(s) of records.',
            ],
            'collapse:b?' => [
                'default' => false,
            ],
            'boosts?' => self::buildBoostSchema($searchTypes),
        ]);

        foreach ($searchTypes as $searchType) {
            $querySchema = $querySchema->merge($searchType->getQuerySchema());
        }
        return $querySchema;
    }

    /**
     * Get the combined boost schema from all search types.
     *
     * @param AbstractSearchType[] $searchTypes The search types to combine from.
     *
     * @return Schema
     */
    public static function buildBoostSchema(array $searchTypes): Schema {
        $boostSchema = Schema::parse([
            'enabled:b?' => [
                'default' => true,
            ],
        ]);
        foreach ($searchTypes as $searchType) {
            $typeBoostSchema = $searchType->getBoostSchema();

            // Could be null if it doesn't provide one.
            if ($typeBoostSchema instanceof Schema) {
                $boostSchema = $boostSchema->merge($typeBoostSchema);
            }
        }
        return $boostSchema;
    }

    ///
    /// Abstract Query Functions
    ///

    /**
     * Apply a query where some text is matched.
     *
     * @param string $text The text to search.
     * @param string[] $fieldNames The fields to perform the search against. If empty, all fields will be searched.
     * @param string $matchMode Matching mode to use for this text query.
     * @param string|null $locale
     *
     * @return $this
     */
    abstract public function whereText(string $text, array $fieldNames = [], string $matchMode = self::MATCH_FULLTEXT, ?string $locale = "");

    /**
     * Add index to scan to the search query
     *
     * @param string $index
     * @return void
     */
    public function addIndex(string $index) {
        $this->indexes[$index] = true;
    }

    /**
     * Get all indexes to scan
     *
     * @return array|null
     */
    public function getIndexes(): ?array {
        return $this->indexes !== null ? array_keys($this->indexes) : null;
    }


    /**
     * Set filter values for some attribute.
     *
     * @param string $attribute
     * @param array $values Values should be numeric
     * @param bool $exclude Whether or not the values should be excluded.
     * @param string $filterOp One of the AbstractSearchQuery::FILTER_OP_* constants.
     *
     * @return $this
     */
    abstract public function setFilter(
        string $attribute,
        array $values,
        bool $exclude = false,
        string $filterOp = SearchQuery::FILTER_OP_OR
    );

    /**
     * Set int range filter
     *
     * @param string $attribute The attribute to filter.
     * @param int $min The minimum value for the attribute.
     * @param int $max The maximum value for the attribute.
     * @param bool $exclude Whether or not the values are exlusive.
     *
     * @return $this
     */
    public function setFilterRange(string $attribute, int $min, int $max, bool $exclude = false) {
        return $this;
    }

    /**
     * Set int range filter
     *
     * @param string $attribute The attribute to filter.
     * @param array $schemaFilter The output of some dates parsed with DateFilterSchema.
     *
     * @return $this
     */
    public function setDateFilterSchema(string $attribute, array $schemaFilter) {
        $schema = new DateFilterSchema();
        $schemaFilter = $schema->validate($schemaFilter);
        /**
         * @var \DateTimeImmutable $start
         * @var \DateTimeImmutable $end
         */
        [$start, $end] = $schemaFilter['inclusiveRange'];
        $this->setFilterRange($attribute, $start->getTimestamp(), $end->getTimestamp());
        return $this;
    }

    /**
     * Apply a sort mode the query.
     *
     * @param string $sort Sort mode
     * @param string|null $field
     *
     * @return $this
     */
    public function setSort(string $sort, ?string $field = null) {
        return $this;
    }

    /**
     * Return driver compatibility
     *
     * @return string
     */
    public function driver(): string {
        return '';
    }

    /**
     * Does query and driver support extenders
     *
     * @return bool
     */
    public function supportsExtenders(): bool {
        return false;
    }
}
