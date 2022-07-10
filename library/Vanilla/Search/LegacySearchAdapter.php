<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Search;

use Garden\Schema\Schema;
use Vanilla\Formatting\DateTimeFormatter;
use Vanilla\Formatting\TimeUnit;
use Vanilla\Search\MysqlSearchDriver;
use Vanilla\Search\SearchOptions;
use Vanilla\Search\SearchResultItem;
use Vanilla\Search\SearchResults;
use Vanilla\Search\SearchService;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Utility\ArrayUtils;
use Vanilla\Utility\SchemaUtils;

/**
 * Adapter class for using the search service in the legacy advanced search.
 */
class LegacySearchAdapter {

    /** @var SearchService */
    private $searchService;

    /** @var \UserModel */
    private $userModel;

    /** @var \Gdn_Session */
    private $session;

    /** @var SiteSectionModel */
    private $siteSectionModel;

    /**
     * DI.
     *
     * @param SearchService $searchService
     * @param \UserModel $userModel
     * @param \Gdn_Session $session
     * @param SiteSectionModel $siteSectionModel
     */
    public function __construct(SearchService $searchService, \UserModel $userModel, \Gdn_Session $session, SiteSectionModel $siteSectionModel) {
        $this->searchService = $searchService;
        $this->userModel = $userModel;
        $this->session = $session;
        $this->siteSectionModel = $siteSectionModel;
    }

    /**
     * Perform a search with the search service, using input/outputs of advanced search.
     *
     * @param array $advancedSearchQuery An advanced search query.
     * @param int $offset Page offset.
     * @param int $limit Amount of items per page.
     *
     * @return SearchResults
     */
    public function search(array $advancedSearchQuery, int $offset, int $limit): SearchResults {
        $searchOptions = new SearchOptions($offset, $limit);
        $serviceQuery = $this->convertQuery($advancedSearchQuery);
        $results = $this->searchService->search($serviceQuery, $searchOptions);
        $this->userModel->expandUsers($results, ['insertUser' => 'insertUserID']);

        return $results;
    }

    /**
     * Convert an advanced search query into arguments for the search service.
     *
     * @param array $advQuery
     *
     * @return array
     */
    private function convertQuery(array $advQuery): array {
        $caseInsensitiveQuery = [];
        foreach ($advQuery as $field => $value) {
            $caseInsensitiveQuery[strtolower($field)] = $value;
        }
        $advQuery = $caseInsensitiveQuery;
        $query = [
            'locale' => $this->siteSectionModel->getCurrentSiteSection()->getContentLocale(),
        ];

        if ($search = $this->getValidField($advQuery, 'Search')
            ?? $this->getValidField($advQuery, 'search')) {
            $query['query'] = $search;
        }


        if ($title = $this->getValidField($advQuery, 'title')) {
            $query['name'] = $title;
        }

        if ($author = $this->getValidField($advQuery, 'author')) {
            $userNames = explode(',', $author);
            $userNames = array_map('trim', $userNames);
            $query['insertUserNames'] = $userNames;
        }

        if ($tags = $this->getValidField($advQuery, 'tags')) {
            $tags = explode(',', $tags);
            $tags = array_map('trim', $tags);
            $query['tags'] = $tags;
            $query['tagOperator'] = 'and';
        }

        $query = array_merge(
            $query,
            $this->extractTypesQueries($advQuery),
            $this->extractDateQueries($advQuery)
        );
        $queryCategoryID = $this->getValidField($advQuery, 'categoryid') ?? null;
        $rawCat = $this->getValidField($advQuery, 'cat') ?? $queryCategoryID ?? 'all';
        $query['includeArchivedCategories'] = (bool)($this->getValidField($advQuery, 'archived') ?? false);
        $query['followedCategories'] = (bool)($this->getValidField($advQuery, 'followedcats') ?? false);
        if (is_numeric($rawCat)) {
            // If we have some categoryID to filter on apply categoryIDs.
            // Otherwise apply the defaults.
            $categoryID = (int) $rawCat;
            $subcats = (bool)($this->getValidField($advQuery, 'subcats') ?? true);
            $query['categoryID'] = $categoryID;
            $query['includeChildCategories'] = $subcats;
        } elseif ($rawCat !== 'all') {
            // Fallback to a default using our current siteSection.
            $contextualCategoryID = $this->siteSectionModel->getCurrentSiteSection()->getAttributes()['CategoryID'] ?? null;
            if (is_int($contextualCategoryID) && $contextualCategoryID > 0) {
                // we have some contextual categoryID to apply.
                $query['categoryID'] = $contextualCategoryID;
                $query['includeChildCategories'] = true;
            }
        }

        if ($discussionID = $this->getValidField($advQuery, 'discussionid')) {
            $query['discussionID'] = $discussionID;
            $query['recordTypes'] = ['discussion', 'comment'];
            unset($query['types']);
        } elseif ($this->getValidField($advQuery, 'nocollapse')) {
            $query['collapse'] = false;
        } else {
            $query['collapse'] = true;
        }

        return $query;
    }

    /**
     * Get a field if it exists from an advanced search query.
     * Notably these queries can have a lot of empty strings which are equivalent to null for this type of form.
     *
     * @param array $advQuery
     * @param string $field
     *
     * @return mixed
     */
    private function getValidField(array $advQuery, string $field) {
        $field = strtolower($field);
        $field = $advQuery[$field] ?? null;
        return !empty($field) ? $field : null;
    }

    /**
     * Extract dateInserted part of a search service query from advanced search if possible.
     *
     * @param array $advQuery
     * @return array
     */
    private function extractDateQueries(array $advQuery): array {
        $date = $this->getValidField($advQuery, 'date');
        $within = $this->getValidField($advQuery, 'within');
        if (!$date) {
            return [];
        }

        $timestamp = strtotime($advQuery['date']);
        if (!$timestamp) {
            return [];
        }

        $timestamp -= $this->session->hourOffset() * TimeUnit::ONE_HOUR;

        if ($within !== null) {
            $timestampStart = strtotime('-' . $within, $timestamp);
            $timestampEnd =  strtotime('+' . $within, $timestamp);
        } else {
            $timestampStart = $timestamp;
            $timestampEnd = strtotime('+1 day', $timestamp);
        }

        $start = DateTimeFormatter::timeStampToDateTime($timestampStart);
        $end = DateTimeFormatter::timeStampToDateTime($timestampEnd);

        return [
            'dateInserted' => "[$start,$end]",
        ];
    }

    /**
     * Extract recordType and type part of a search service query from advanced search if possible.
     *
     * @param array $advQuery
     * @return array
     */
    private function extractTypesQueries(array $advQuery): array {
        $types = [];
        $recordTypes = [];
        foreach ($advQuery as $field => $value) {
            if (str_contains($field, '_') && $value === "1" || $value === 1 || $value === true) {
                // Likely a type query.
                $pieces = explode("_", $field);
                if (count($pieces) === 2) {
                    [$recordType, $type] = $pieces;

                    // Legacy name mappings.
                    if ($type === "c") {
                        $type = "comment";
                    } elseif ($type === "d") {
                        $type = "discussion";
                    }

                    $types[] = $type;
                    $recordTypes[] = $recordType;
                }
            }
        }

        $result = [];
        if (!empty($types)) {
            $result['types'] = $types;
        }

        if (!empty($recordTypes)) {
            $result['recordTypes'] = $recordTypes;
        }
        return $result;
    }

    /**
     * The MySQL search driver does not support autocomplete.
     *
     * @return bool
     */
    public function supportsAutoComplete(): bool {
        return !is_a($this->searchService->getActiveDriver(), MysqlSearchDriver::class);
    }
}
