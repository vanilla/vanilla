<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Search;

use Garden\Schema\Schema;
use Garden\Schema\Validation;
use Garden\Schema\ValidationException;
use Garden\Schema\ValidationField;
use Garden\Web\Exception\ServerException;
use Vanilla\ApiUtils;
use Vanilla\DateFilterSchema;
use Vanilla\Schema\LegacyDateRangeExpression;
use Vanilla\Utility\ArrayUtils;
use Webmozart\Assert\Assert;

/**
 * A search query object.
 */
abstract class SearchQuery
{
    const LIMIT_DEFAULT = 10;
    const LIMIT_MAXIMUM = 100;

    const FILTER_OP_OR = "or";
    const FILTER_OP_AND = "and";
    const FILTER_OP_WILDCARD = "wildcard";
    const FILTER_OP_NOT = "not";

    // Match terms from the query in any order using a fulltext-language specific analyzer.
    // Supports the "queryOperator" of "or" in particular, allowing to match just one of the given terms.
    const MATCH_FULLTEXT = "fulltext";

    // Match terms from the query in any order with smart processing.
    // Support for exact match with "value" and exclusions with -"value"
    const MATCH_FULLTEXT_EXTENDED = "fulltext_extended";

    // Exact text matching with wildcard support. Only used on small text fields.
    const MATCH_WILDCARD = "wildcard";

    // Match terms from the query in any order using a fulltext-language specific analyzer.
    const MATCH_VECTORIZED = "vectorized";

    // Match terms from the query by combining the result of a fulltext match and a vectorized match.
    const MATCH_VECTORIZED_EXTENDED = "vectorized_extended";
    const TEXT_MATCH_MODES = [
        self::MATCH_FULLTEXT,
        self::MATCH_FULLTEXT_EXTENDED,
        self::MATCH_VECTORIZED,
        self::MATCH_VECTORIZED_EXTENDED,
    ];
    const SORT_RELEVANCE = "relevance";
    const SORT_ASC = "asc";
    const SORT_DESC = "desc";

    /** @var Schema */
    protected $querySchema;

    /** @var array */
    protected $queryData;

    /** @var array $indexes */
    protected $indexes;

    /** @var AbstractSearchType[]|null */
    protected $currentTypes = null;

    /** @var SearchTypeCollection|null */
    protected $filteredTypes = null;

    /** @var array The boost values. */
    private $boosts;

    /** @var string */
    protected array $highlightTextFieldNames = [];

    protected bool $canOptimizeRecordTypes = true;

    /**
     * Create a query.
     *
     * @param AbstractSearchType[] $searchTypes The registered search types contributing to the query.
     * @param array $queryData The data making up the query.
     */
    public function __construct(array $searchTypes, array $queryData)
    {
        $allTypeCollection = new SearchTypeCollection($searchTypes);
        $filteredTypes = $allTypeCollection->getFilteredCollection($queryData);

        if (!$filteredTypes->hasExclusiveType()) {
            $globalType = $allTypeCollection->getByType("global")[0] ?? null;
            if ($globalType) {
                // Make sure the global type is added if possible.
                $filteredTypes->addType($globalType);
            }
        }

        $this->querySchema = $this->buildSchema($searchTypes);
        $this->queryData = $this->querySchema->validate($queryData);

        $boosts = $this->getQueryParameter("boosts", null);
        if ($boosts === null) {
            // Apply default boosts.
            $boostSchema = self::buildBoostSchema($searchTypes);
            $this->boosts = $boostSchema->validate([]);
        } else {
            // Some boosts are defined.

            if ($boosts["enabled"] === false) {
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

        // Ensure we apply a type filter.
        // It could be implied from recordTypes
        $this->filteredTypes = $filteredTypes;
        if (empty($this->queryData["types"])) {
            $types = [];
            /** @var AbstractSearchType $searchType */
            foreach ($filteredTypes as $searchType) {
                $types[] = $searchType->getType();
            }
            $this->queryData["types"] = $types;
        }

        $types = $this->getQueryParameter("types");
        if (!empty($types)) {
            $this->setFilter("type.keyword", $types, false, SearchQuery::FILTER_OP_OR, false);
        }

        // Give each of the search types a chance to validate the query object.
        $recordTypes = $this->canOptimizeRecordTypes
            ? $filteredTypes->getAsOptimizedRecordTypes()
            : $filteredTypes->getAsUnoptimizedRecordTypes();
        foreach ($recordTypes as $searchTypeGroup) {
            $hasGlobal = false;
            foreach ($searchTypeGroup as $searchType) {
                if ($searchType instanceof GlobalSearchType) {
                    $hasGlobal = true;
                }

                if ($searchType->supportsCollapsing()) {
                    $hasCollapsableType = true;
                }
            }

            if (!$hasGlobal) {
                $this->startTypeQuery($searchTypeGroup);
            }

            // This index has to exist, because getAsOptimizedGroups() won't create an empty group.
            // Since these groups were optimized together, the first one should be able to generate the query for all of them.
            $firstType = $searchTypeGroup[0];
            $firstType->applyToQuery($this);
            $this->endTypeQuery();
        }

        if (
            $this instanceof CollapsableSearchQueryInterface &&
            $this->getQueryParameter("collapse") &&
            $hasCollapsableType
        ) {
            $this->collapseField("recordCollapseID");
        }
    }

    /**
     * @return SearchTypeCollection|null
     */
    public function getFilteredTypes(): ?SearchTypeCollection
    {
        return $this->filteredTypes;
    }

    /**
     * Get field names that were highlighted.
     *
     * @return array
     */
    public function getHighlightTextFieldNames(): array
    {
        return $this->highlightTextFieldNames;
    }

    /**
     * @param AbstractSearchType[] $currentTypes
     */
    protected function startTypeQuery(array $currentTypes): void
    {
        $this->currentTypes = $currentTypes;
    }

    /**
     * End the current type-specific query.
     */
    protected function endTypeQuery(): void
    {
        $this->currentTypes = null;
    }

    /**
     * Get a specific query parameter.
     *
     * @param string $queryParam
     * @param mixed $default
     *
     * @return mixed|null
     */
    public function getQueryParameter(string $queryParam, mixed $default = null): mixed
    {
        return ArrayUtils::getByPath($queryParam, $this->queryData, $default);
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
    public function getBoostParameter(string $boostParam)
    {
        return ArrayUtils::getByPath($boostParam, $this->boosts, null);
    }

    /**
     * Build the search query schema.
     *
     * @param AbstractSearchType[] $searchTypes
     *
     * @return Schema
     */
    public static function buildSchema(array $searchTypes): Schema
    {
        $querySchema = Schema::parse([
            "page:i?" => [
                "description" => "Page number. See [Pagination](https://docs.vanillaforums.com/apiv2/#pagination).",
                "default" => 1,
                "minimum" => 1,
            ],
            "limit:i?" => [
                "description" => "Desired number of items per page.",
                "default" => self::LIMIT_DEFAULT,
                "minimum" => 1,
                "maximum" => self::LIMIT_MAXIMUM,
            ],
            "expand?" => ApiUtils::getExpandDefinition([
                "insertUser",
                "updateUser",
                "breadcrumbs",
                "image",
                "excerpt",
                "-body",
                "tagIDs",
                "vectors",
                "vectors_debug",
                "summary",
            ]),
            "recordTypes:a?" => [
                "items" => [
                    "type" => "string",
                    "enum" => array_map(function ($searchType) {
                        return $searchType->getRecordType();
                    }, $searchTypes),
                ],
                "style" => "form",
                "description" => "Restrict the search to the specified main type(s) of records.",
            ],
            "types:a?" => [
                "items" => [
                    "type" => "string",
                    "enum" => array_map(function ($searchType) {
                        return $searchType->getType();
                    }, $searchTypes),
                ],
                "style" => "form",
                "description" => "Restrict the search to the specified type(s) of records.",
            ],
            "collapse:b?" => [
                "default" => false,
            ],
            "boosts?" => self::buildBoostSchema($searchTypes),
            "cursor:s?" => [
                "description" =>
                    "Token used to fetch next page of results. Cannot be combined with page. Warning: May lead to duplicate results if not sorted by primary key.",
            ],
            "includeTypeaheads:b" => [
                "default" => false,
            ],
            "includeResults:b" => [
                "default" => true,
            ],
            "matchMode:s?" => [
                "enum" => [
                    self::MATCH_FULLTEXT,
                    self::MATCH_FULLTEXT_EXTENDED,
                    self::MATCH_WILDCARD,
                    self::MATCH_VECTORIZED,
                    self::MATCH_VECTORIZED_EXTENDED,
                ],
            ],
        ]);

        foreach ($searchTypes as $searchType) {
            $querySchema = $querySchema->merge($searchType->getQuerySchema());
        }
        return $querySchema->addValidator("", function (array $data, ValidationField $field) {
            if (isset($data["cursor"]) && intval($data["page"]) > 1) {
                $field->addError("`cursor` parameter cannot be combined with `page` parameter");
            }
        });
    }

    /**
     * Get the combined boost schema from all search types.
     *
     * @param AbstractSearchType[] $searchTypes The search types to combine from.
     *
     * @return Schema
     */
    public static function buildBoostSchema(array $searchTypes): Schema
    {
        $boostSchema = Schema::parse([
            "enabled:b?" => [
                "default" => true,
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
     *
     * @return $this
     */
    abstract public function whereText(string $text, array $fieldNames, string $matchMode);

    /**
     * Add index to scan to the search query
     *
     * @param string $index
     * @return void
     */
    public function addIndex(string $index)
    {
        $this->indexes[$index] = true;
    }

    /**
     * Get all indexes to scan
     *
     * @return array
     */
    public function getIndexes(): array
    {
        $indexes = $this->indexes !== null ? array_keys($this->indexes) : [];
        // Combined index. In the future this will be the only index.
        // Right now the individual index names are kept until the new combined index is created everywhere.
        $indexes[] = "vanilla";
        return $indexes;
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
     * @param int|null $min The minimum value for the attribute.
     * @param int|null $max The maximum value for the attribute.
     * @param bool $exclude Whether the values are exclusive.
     * @param bool $isDate Whether the attribute is a date and min and max are timestamps
     *
     * @return $this
     */
    public function setFilterRange(string $attribute, ?int $min, ?int $max, bool $exclude = false, bool $isDate = true)
    {
        return $this;
    }

    /**
     * Set int range filter
     *
     * @param string $attribute The attribute to filter.
     * @param array|LegacyDateRangeExpression $schemaFilter The output of some dates parsed with DateFilterSchema.
     *
     * @return $this
     */
    final public function setDateFilterSchema(string $attribute, $schemaFilter)
    {
        if ($schemaFilter instanceof LegacyDateRangeExpression) {
            $schemaFilter = $schemaFilter->toLegacyArray();
        }
        Assert::isArray($schemaFilter, "Argument 2 passed to __METHOD__ must be of type array.");

        $schema = new DateFilterSchema();
        $schemaFilter = $schema->validate($schemaFilter);
        /**
         * @var \DateTimeImmutable $start
         * @var \DateTimeImmutable $end
         */
        [$start, $end] = $schemaFilter["inclusiveRange"];
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
    public function setSort(string $sort, ?string $field = null)
    {
        return $this;
    }

    /**
     * Return driver compatibility
     *
     * @return string
     */
    public function driver(): string
    {
        return "";
    }

    /**
     * Does query and driver support extenders
     *
     * @return bool
     */
    public function supportsExtenders(): bool
    {
        return false;
    }
}
