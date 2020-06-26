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
use Vanilla\DateFilterSchema;
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
    public function getResultItems(array $recordIDs): array {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function applyToQuery(SearchQuery $query) {
        ///
        /// Prepare data from the query.
        ///
        $allTextQuery = $query->getQueryParameter('query');
        $name = $query->getQueryParameter('name');
        $insertUserIDs = $query->getQueryParameter('insertUserIDs', false);
        $insertUserNames = $query->getQueryParameter('insertUserNames', false);
        if (!$insertUserIDs && $insertUserNames) {
            $users = $this->userModel->getWhere([
                'name' => $insertUserNames,
            ])->resultArray();
            $insertUserIDs = array_column($users, 'UserID');
        }
        $dateInserted = $query->getQueryParameter('dateInserted');

        /** @var $startDate \DateTimeImmutable|null */
        $startDate = $dateInserted['date'][0] ?? null;

        /** @var $endDate \DateTimeImmutable|null */
        $endDate = $dateInserted['date'][1] ?? null;


        $sort = $query->getQueryParameter('sort', 'relevance');

        ///
        /// Apply the query.
        ///

        if ($name) {
            $query->whereText($name, ['name']);
        }

        if ($allTextQuery) {
            $query->whereText($allTextQuery);
        }

        if ($insertUserIDs) {
            $query->setFilter('insertUserID', $insertUserIDs);
        }

        /** @psalm-suppress UndefinedClass */
        if ($query instanceof SphinxSearchQuery) {
            if ($startDate && $endDate) {
                $query->setFilterRange('dateInserted', $startDate->getTimestamp(), $endDate->getTimestamp());
            }

            // Sorts
            $sortField = ltrim($sort, '-');

            if ($sortField === SearchQuery::SORT_RELEVANCE) {
                $query->setSort(SearchQuery::SORT_RELEVANCE);
            } elseif ($sortField === $sort) {
                $query->setSort(SphinxAdapter::SORT_ATTR_ASC, $sortField);
            } else {
                $query->setSort(SphinxAdapter::SORT_ATTR_DESC, $sortField);
            }
        } else {
            // TODO implement for mysql.
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
            "sort:s?" => [
                "enum" => [
                    "relevance",
                    "dateInserted",
                    "-dateInserted",
                ],
            ],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function validateQuery(SearchQuery $query): void {
        return;
    }
}
