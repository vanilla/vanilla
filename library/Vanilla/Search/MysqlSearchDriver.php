<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Search;

use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Contracts\Search\SearchRecordTypeProviderInterface;

/**
 * Mysql search driver.
 */
class MysqlSearchDriver extends AbstractSearchDriver {

    const MAX_RESULTS = 1000;

    /** @var SearchRecordTypeProviderInterface */
    private $searchTypeRecordProvider;

    /** @var \Gdn_Database $mysql */
    private $db;

    /**
     * DI.
     *
     * @param SearchRecordTypeProviderInterface $searchRecordProvider
     * @param \Gdn_Database $db
     */
    public function __construct(SearchRecordTypeProviderInterface $searchRecordProvider, \Gdn_Database $db) {
        $this->searchTypeRecordProvider = $searchRecordProvider;
        $this->db  = $db;
    }

    /**
     * Perform a search.
     *
     * @param array $queryData The query to search for.
     * @param SearchOptions $options Options for the query.
     *
     * @return SearchResults
     */
    public function search(array $queryData, SearchOptions $options): SearchResults {
        $query = new MysqlSearchQuery($this->getSearchTypes(), $queryData, $this->db);

        $sql = $query->getSql();
        if (empty($sql)) {
            $search = [];
        } else {
            $search = $this->db->query($sql)->resultArray();
        }

        $search = $this->convertRecordsToResultItems($search);
        return new SearchResults(
            $search,
            count($search),
            $options->getOffset(),
            $options->getLimit()
        );
    }
}
