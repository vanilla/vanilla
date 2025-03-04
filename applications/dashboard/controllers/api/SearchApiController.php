<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Controllers\Api;

use AbstractApiController;
use Garden\Schema\ValidationException;
use Garden\Web\Data;
use Garden\Web\Exception\ServerException;
use UserModel;
use Vanilla\ApiUtils;
use Vanilla\Dashboard\Models\RagSummaryService;
use Vanilla\Search\SearchOptions;
use Vanilla\Search\SearchService;
use Vanilla\Search\SearchResultItem;
use Vanilla\Search\SearchResults;
use Vanilla\Search\SearchTypeaheadResult;

/**
 * Class SearchApiController
 */
class SearchApiController extends AbstractApiController
{
    /** Default limit on the number of rows returned in a page. */
    const LIMIT_DEFAULT = 30;

    /** Maximum number of items that can be returned in a page. */
    const LIMIT_MAXIMUM = 100;

    /**
     * SearchApiController constructor.
     *
     * @inheritdoc
     */
    public function __construct(
        private UserModel $userModel,
        private SearchService $searchService,
        private RagSummaryService $ragSummaryService
    ) {
    }

    /**
     * New implementation of search index. Uses the search service.
     *
     * @param array $query
     *
     * @return Data
     * @throws ValidationException
     * @throws ServerException
     */
    public function index(array $query): Data
    {
        $in = $this->searchService->buildQuerySchema();
        $query = $in->validate($query);
        $driver = $this->searchService->getActiveDriver($query["driver"] ?? null);
        // Paging
        [$offset, $limit] = offsetLimit("p{$query["page"]}", $query["limit"]);

        $searchResults = $driver->search(
            $query,
            new SearchOptions(
                $offset,
                $limit,
                includeTypeaheads: $query["includeTypeaheads"] ?? false,
                includeResults: $query["includeResults"] ?? true
            )
        );

        $expands = $query["expand"] ?? [];
        if (isset($query["collapse"]) && $query["collapse"]) {
            $expands[] = "collapse";
        }

        $this->applyExpandFields($searchResults, $expands);

        $this->userModel->expandUsers(
            $searchResults,
            $this->resolveExpandFields($query, ["insertUser" => "insertUserID"])
        );

        $this->userModel->expandUsers(
            $searchResults,
            $this->resolveExpandFields($query, ["updateUser" => "updateUserID"])
        );

        $searchResults->applyUtmParams($query["query"] ?? "");

        $totalCount = $searchResults->getTotalCount();

        return new Data(
            $searchResults,
            [
                "paging" => ApiUtils::numberedPagerInfo($totalCount, "/api/v2/search", $query, $in),
            ],
            [
                "x-search-powered-by" => $driver->getName(),
            ]
        );
    }

    /**
     * Uses the search service to get rag of search results.
     *
     * @param array $query
     *
     * @return Data
     * @throws ValidationException
     * @throws ServerException
     */
    public function get_rag(array $query): Data
    {
        $in = $this->searchService->buildQuerySchema();
        $query = $in->validate($query);
        $driver = $this->searchService->getActiveDriver($query["driver"] ?? null);
        // Paging
        [$offset, $limit] = offsetLimit("p{$query["page"]}", $query["limit"]);

        $searchResults = $driver->search(
            $query,
            new SearchOptions(
                $offset,
                $limit,
                includeTypeaheads: $query["includeTypeaheads"] ?? false,
                includeResults: $query["includeResults"] ?? true
            )
        );

        $expands = $query["expand"] ?? [];

        $ragResult = $this->ragSummaryService->ragSummary(
            $searchResults->getResultItems(),
            $query["query"],
            in_array("summary", $expands)
        );

        return new Data($ragResult);
    }

    /**
     * Apply expand fields to resultItems.
     *
     * @param SearchResults $rows
     * @param array|bool $expandFields
     */
    public function applyExpandFields(SearchResults &$rows, $expandFields)
    {
        $populate = function (SearchResultItem|SearchTypeaheadResult &$row) use ($expandFields) {
            if ($row instanceof SearchResultItem) {
                $row->setExpands($expandFields);
            }
        };

        foreach ($rows as &$row) {
            $populate($row);
        }
    }
}
