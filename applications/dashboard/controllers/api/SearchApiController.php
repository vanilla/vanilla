<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Controllers\Api;

use AbstractApiController;
use Garden\Schema\Schema;
use Garden\Web\Data;
use Gdn;
use UserModel;
use Vanilla\ApiUtils;
use Vanilla\Search\SearchOptions;
use Vanilla\Search\SearchService;
use Vanilla\Search\SearchResultItem;
use Vanilla\Search\SearchResults;

/**
 * Class SearchApiController
 */
class SearchApiController extends AbstractApiController {

    /** Default limit on the number of rows returned in a page. */
    const LIMIT_DEFAULT = 30;

    /** Maximum number of items that can be returned in a page. */
    const LIMIT_MAXIMUM = 100;

    /** @var UserModel */
    private $userModel;

    /** @var SearchService */
    private $searchService;

    /**
     * SearchApiController constructor.
     *
     * @inheritdoc
     */
    public function __construct(
        UserModel $userModel,
        SearchService $searchService
    ) {
        $this->userModel = $userModel;
        $this->searchService = $searchService;
    }

    /**
     * New implementation of search index. Uses the search service.
     *
     * @param array $query
     *
     * @return Data
     */
    public function index(array $query): Data {
        $in = $this->searchService->buildQuerySchema();
        $query = $in->validate($query);
        $driver = $this->searchService->getActiveDriver($query['driver'] ?? null);
        // Paging
        [$offset, $limit] = offsetLimit("p{$query['page']}", $query['limit']);

        $searchResults = $this->searchService->search($query, new SearchOptions($offset, $limit));

        $expands = $query['expand'] ?? [];
        if (isset($query['collapse']) && $query['collapse']) {
            $expands[] = 'collapse';
        }
        $this->applyExpandFields($searchResults, $expands);

        $this->userModel->expandUsers(
            $searchResults,
            $this->resolveExpandFields($query, ['insertUser' => 'insertUserID'])
        );

        $this->userModel->expandUsers(
            $searchResults,
            $this->resolveExpandFields($query, ['updateUser' => 'updateUserID'])
        );

        $totalCount = $searchResults->getTotalCount();

        return new Data(
            $searchResults,
            [
                'paging' => ApiUtils::numberedPagerInfo($totalCount, '/api/v2/search', $query, $in),
            ],
            [
                'x-search-powered-by' => $driver->getName(),
            ]
        );
    }

    /**
     * Apply expand fields to resultItems.
     *
     * @param SearchResults $rows
     * @param array|bool $expandFields
     */
    public function applyExpandFields(SearchResults &$rows, $expandFields) {
        $populate = function (SearchResultItem &$row) use ($expandFields) {
            $row->setExpands($expandFields);
        };

        foreach ($rows as &$row) {
            $populate($row);
        }
    }
}
