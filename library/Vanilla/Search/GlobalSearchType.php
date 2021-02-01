<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Search;

use Garden\Schema\Schema;
use Vanilla\Adapters\SphinxClient;
use Vanilla\Adapters\SphinxClient as SphinxAdapter;
use Vanilla\ApiUtils;
use Vanilla\Contracts\Site\AbstractSiteProvider;
use Vanilla\DateFilterSchema;
use Vanilla\Models\CrawlableRecordSchema;
use Vanilla\Sphinx\Search\SphinxSearchQuery;

/**
 * Search type for global parameters.
 */
class GlobalSearchType extends AbstractSearchType {

    /** @var \UserModel */
    private $userModel;

    /**
     * DI.
     *
     * @param \UserModel $userModel
     */
    public function __construct(\UserModel $userModel) {
        $this->userModel = $userModel;
    }

    /**
     * @inheritdoc
     */
    public function getKey(): string {
        return 'global';
    }

    /**
     * @inheritdoc
     */
    public function getSearchGroup(): string {
        return 'global';
    }

    /**
     * @inheritdoc
     */
    public function getType(): string {
        return 'global';
    }

    /**
     * @inheritdoc
     */
    public function getResultItems(array $recordIDs, SearchQuery $query): array {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function applyToQuery(SearchQuery $query) {
        ///
        /// Prepare data from the query.
        ///
        $insertUserIDs = $query->getQueryParameter('insertUserIDs', false);
        $insertUserNames = $query->getQueryParameter('insertUserNames', false);
        if (!$insertUserIDs && $insertUserNames) {
            $users = $this->userModel->getWhere([
                'name' => $insertUserNames,
            ])->resultArray();
            $insertUserIDs = array_column($users, 'UserID');
            $insertUserIDs[] = 0;
        }

        if ($dateInserted = $query->getQueryParameter('dateInserted')) {
            $query->setDateFilterSchema('dateInserted', $dateInserted);
        }
        $types = $query->getQueryParameter('types');

        ///
        /// Apply the query.
        ///

        if (!empty($types)) {
            $query->setFilter('type.keyword', $types);
        }

        if ($insertUserIDs) {
            $query->setFilter('insertUserID', $insertUserIDs);
        }


        $locale = $query->getQueryParameter('locale');
        if ($locale) {
            $query->setFilter('locale', [$locale, strtolower($locale), CrawlableRecordSchema::ALL_LOCALES]);
        }

        // Sorts
        $sort = $query->getQueryParameter('sort', 'relevance');
        $sortField = ltrim($sort, '-');

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
    public function getSorts(): array {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getQuerySchema(): Schema {
        return Schema::parse([
            'query:s?' => [
                'x-search-scope' => true,
            ],
            'name:s?' => [
                'x-search-scope' => true,
            ],
            'insertUserIDs:a?' => [
                'items' => [
                    'type' => 'integer',
                ],
                'style' => 'form',
                'x-search-filter' => true,
            ],
            'insertUserNames:a?' => [
                'items' => [
                    'type' => 'string',
                ],
                'style' => 'form',
                'x-search-filter' => true,
            ],
            'dateInserted?' => new DateFilterSchema([
                'x-search-filter' => true,
            ]),
            'driver?' => [
                "enum" => $this->searchService->getDriverNames()
            ],
            "sort:s?" => [
                "enum" => [
                    "relevance",
                    "dateInserted",
                    "-dateInserted",
                ],
            ],
            "locale:s?" => [
                'description' => 'The locale articles are published in.',
                'x-search-scope' => true
            ],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function validateQuery(SearchQuery $query): void {
        return;
    }

    /**
     * @inheritdoc
     */
    public function getSingularLabel(): string {
        return '';
    }

    /**
     * @inheritdoc
     */
    public function getPluralLabel(): string {
        return '';
    }

    /**
     * @inheritdoc
     */
    public function getDTypes(): ?array {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function guidToRecordID(int $guid): ?int {
        return null;
    }
}
