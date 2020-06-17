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
class GlobalSearchType implements SearchTypeInterface {

    /** @var \UserModel */
    private $userModel;

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
        $allTextQuery = $query->getQueryParameter('body');
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
            $query->whereText($name, 'name');
        }

        if ($allTextQuery) {
            $query->whereText($allTextQuery, '(name, body)');
        }

        if ($insertUserIDs) {
            $query->setFilter('insertUserIDs', $insertUserIDs);
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
            'query:s?',
            'name:s?',
            'insertUserIDs:a?' => [
                'type' => 'int'
            ],
            'insertUserNames:a?' => [
                'type' => 'string'
            ],
            'dateInserted:dt?',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function validateQuery(SearchQuery $query): void {
        return;
    }
}
