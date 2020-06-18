<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Search;

use Garden\Schema\Schema;
use Vanilla\Adapters\SphinxClient;
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

        // TODO: Handle dates.
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
            'dateInserted:dt?' => [
                'x-search-filter' => true,
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
