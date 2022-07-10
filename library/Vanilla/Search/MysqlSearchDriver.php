<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Search;

/**
 * Mysql search driver.
 */
class MysqlSearchDriver extends AbstractSearchDriver {

    const MAX_RESULTS = 1000;

    /** @var \Gdn_Database $mysql */
    private $db;

    /**
     * DI.
     *
     * @param \Gdn_Database $db
     */
    public function __construct(\Gdn_Database $db) {
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

        $search = $this->convertRecordsToResultItems($search, $query);
        return new SearchResults(
            $search,
            count($search),
            $options->getOffset(),
            $options->getLimit()
        );
    }

    /**
     * @inheritdoc
     */
    public function getName(): string {
        return 'MySQL';
    }

    /**
     * @inheritdoc
     */
    public function createIndexes() {
    }
}
