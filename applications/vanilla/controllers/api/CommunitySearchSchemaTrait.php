<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\Schema\Schema;
use Vanilla\Search\SearchService;

/**
 * Trait for common search schema on the community apis.
 */
trait CommunitySearchSchemaTrait {
    /**
     * Get a generic search schema.
     *
     * @return Schema
     */
    private function searchSchema() {
        if (!isset($this->searchSchema)) {
            $this->searchSchema = Schema::parse([
                'query:s' => 'Search terms.',
                'page:i?' => [
                    'description' => 'Page number. See [Pagination](https://docs.vanillaforums.com/apiv2/#pagination).',
                    'default' => 1,
                    'minimum' => 1,
                    'maximum' => \DiscussionModel::instance()->getMaxPages(),
                ],
                'limit:i?' => [
                    'description' => 'Desired number of items per page.',
                    'default' => \DiscussionModel::instance()->getDefaultLimit(),
                    'minimum' => 1,
                    'maximum' => 100,
                ],
                'expand:b?' => [
                    'default' => false,
                    'description' => 'Expand associated records.',
                ],
            ]);
        }

        return $this->searchSchema;
    }

    /**
     * @return SearchService
     */
    private function getSearchService(): SearchService {
        return \Gdn::getContainer()->get(SearchService::class);
    }
}
