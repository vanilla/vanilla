<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Search;

use Garden\Schema\Schema;
use Vanilla\ApiUtils;
use Vanilla\Contracts\Site\VanillaSiteProvider;
use Vanilla\DateFilterSchema;
use Vanilla\Models\CrawlableRecordSchema;

/**
 * Search type for global parameters.
 */
class GlobalSearchType extends AbstractSearchType
{
    /** @var \UserModel */
    private $userModel;

    /**
     * DI.
     *
     * @param \UserModel $userModel
     */
    public function __construct(\UserModel $userModel)
    {
        $this->userModel = $userModel;
    }

    /**
     * @inheritdoc
     */
    public function getKey(): string
    {
        return "global";
    }

    /**
     * @inheritdoc
     */
    public function getRecordType(): string
    {
        return "global";
    }

    /**
     * @inheritdoc
     */
    public function getType(): string
    {
        return "global";
    }

    /**
     * @inheritdoc
     */
    public function getResultItems(array $recordIDs, SearchQuery $query): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function applyToQuery(SearchQuery $query)
    {
        ///
        /// Prepare data from the query.
        ///
        $insertUserIDs = $query->getQueryParameter("insertUserIDs", false);
        $insertUserNames = $query->getQueryParameter("insertUserNames", false);
        if (!$insertUserIDs && $insertUserNames) {
            $users = $this->userModel
                ->getWhere([
                    "name" => $insertUserNames,
                ])
                ->resultArray();
            $insertUserIDs = array_column($users, "UserID");
            $insertUserIDs[] = 0;
        }

        ///
        /// Apply the query.
        ///
        ///

        // Global query
        if ($dateInserted = $query->getQueryParameter("dateInserted")) {
            $query->setDateFilterSchema("dateInserted", $dateInserted);
        }

        if ($dateUpdated = $query->getQueryParameter("dateUpdated")) {
            $query->setDateFilterSchema("dateUpdated", $dateUpdated);
        }

        $locale = $query->getQueryParameter("locale");
        if ($locale) {
            $query->setFilter(
                "locale",
                [$locale, strtolower($locale), CrawlableRecordSchema::ALL_LOCALES],
                false,
                SearchQuery::FILTER_OP_OR,
                false
            );
        }

        // Fulltext matching. These apply to everything except users which does queries name in a different way.
        if (!in_array("user", $query->getQueryParameter("types"))) {
            $name = $query->getQueryParameter("name");
            if ($name) {
                $query->whereText($name, ["name"], $query::MATCH_FULLTEXT_EXTENDED);
            }

            $allTextQuery = $query->getQueryParameter("query");
            if ($allTextQuery) {
                $fields = ["name", "bodyPlainText", "description"];
                $query->whereText($allTextQuery, $fields, $query::MATCH_FULLTEXT_EXTENDED);
            }

            if ($description = $query->getQueryParameter("description", false)) {
                $query->whereText($description, ["description"], SearchQuery::MATCH_FULLTEXT_EXTENDED);
            }
        }

        // Site specific query.
        if ($insertUserIDs) {
            $query->setFilter("insertUserID", $insertUserIDs);
        }

        // Sorts
        $sort = $query->getQueryParameter("sort", "relevance");
        $sortField = ltrim($sort, "-");

        if ($sortField === SearchQuery::SORT_RELEVANCE) {
            $query->setSort(SearchQuery::SORT_RELEVANCE);
        } elseif ($sortField === $sort) {
            $query->setSort(SearchQuery::SORT_ASC, $sortField);
        } else {
            $query->setSort(SearchQuery::SORT_DESC, $sortField);
        }
    }

    /**
     * @inheritdoc
     */
    public function getSorts(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getQuerySchema(): Schema
    {
        return Schema::parse([
            "query:s?" => [
                "x-search-scope" => true,
            ],
            "queryOperator:s?" => [
                "x-search-filter" => true,
            ],
            "siteSectionID:s?",
            "name:s?" => [
                "x-search-scope" => true,
            ],
            "categoryIDs:a?" => [
                "items" => [
                    "type" => "integer",
                ],
            ],
            "insertUserIDs:a?" => [
                "items" => [
                    "type" => "integer",
                ],
                "style" => "form",
                "x-search-filter" => true,
            ],
            "insertUserNames:a?" => [
                "items" => [
                    "type" => "string",
                ],
                "style" => "form",
                "x-search-filter" => true,
            ],
            "dateInserted?" => new DateFilterSchema(),
            "dateUpdated?" => new DateFilterSchema(),
            "driver?" => [
                "enum" => $this->searchService->getDriverNames(),
            ],
            "sort:s?" => [
                "enum" => ["relevance", "dateInserted", "-dateInserted", "dateUpdated", "-dateUpdated"],
            ],
            "locale:s?" => [
                "description" => "The locale articles are published in.",
                "x-search-scope" => true,
            ],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function validateQuery(SearchQuery $query): void
    {
        return;
    }

    /**
     * @inheritdoc
     */
    public function getSingularLabel(): string
    {
        return "";
    }

    /**
     * @inheritdoc
     */
    public function getPluralLabel(): string
    {
        return "";
    }

    /**
     * @inheritdoc
     */
    public function getDTypes(): ?array
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function guidToRecordID(int $guid): ?int
    {
        return null;
    }
}
