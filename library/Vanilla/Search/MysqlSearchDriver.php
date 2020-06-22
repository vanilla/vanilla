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
     * @param ConfigurationInterface $config
     * @param SearchRecordTypeProviderInterface $searchRecordProvider
     */
    public function __construct(SearchRecordTypeProviderInterface $searchRecordProvider, \Gdn_Database $db ) {
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
        $queryData['db'] = $this->db;
        $query = new MysqlSearchQuery($this->getSearchTypes(), $queryData, $this->db);

        //$sphinxClient->setLimits($options->getOffset(), $options->getLimit(), self::MAX_RESULTS);

        //$indexes = $this->getIndexNames($query);

        //$search = $sphinxClient->query($query->getQuery(), implode(' ', $indexes));
//        if (!is_array($search)) {
//            $error = $sphinxClient->getLastError();
//            throw new SphinxSearchException($error);
//        }
        $search = $this->db->query($query->getSql())->resultArray();
//        $records = $this->extractRecordsFromSphinxResult($search);
//        $results = $this->convertRecordsToResultItems($records);

        return new SearchResults(
            $search,
            count($search),
            $options->getOffset(),
            $options->getLimit()
        );

        $pdo = Gdn::database()->connection();

        $csearch = true;
        $dsearch = true;

        $cwhere = [];
        $dwhere = [];

                    // $dfields = ['d.Name', 'd.Body'];
        $cfields = 'c.Body';

        /// Search query ///

//                        $terms = $query['search'] ?? false;
//                        if ($terms) {
//                            $terms = $pdo->quote('%'.str_replace(['%', '_'], ['\%', '\_'], $terms).'%');
//                        }

        // Only search if we have term, user, date, or title to search
        $hasDateFilter = (isset($search['date-filters']));
        if (!$terms && !isset($search['users']) && !$hasDateFilter && !isset($search['title'])) {
            return [];
        }

        /// Title ///

        if (isset($search['title'])) {
            $csearch = false;
            $dwhere['d.Name like'] = $pdo->quote('%'.str_replace(['%', '_'], ['\%', '\_'], $search['title']).'%');

            // In case comments search are enabled late on.
            $cwhere['d.Name like'] = $dwhere['d.Name like'];
        }

        /// Author ///
        if (isset($search['users'])) {
            $author = array_column($search['users'], 'UserID');

            $cwhere['c.InsertUserID'] = $author;
            $dwhere['d.InsertUserID'] = $author;
        }

        /// Discussion ///
        if (isset($search['discussionid'])) {
            $cwhere['d.DiscussionID'] = (int)$search['discussionid'];
        }

        /// Category ///
        if (isset($search['cat'])) {
            $cats = (array)$search['cat'];

            $cwhere['CategoryID'] = $cats;
            $dwhere['CategoryID'] = $cats;
        }

        /// Type ///
        if (!empty($search['types'])) {
            $disableComments = true;
            $disableDiscussions = true;
//            /** @var SearchRecordTypeInterface $recordType */
//            foreach ($search['types'] as $recordType) {
//                if ($recordType instanceof SearchRecordTypeDiscussion) {
//                    $disableDiscussions = false;
//                } elseif ($recordType instanceof SearchRecordTypeComment) {
//                    $disableComments = false;
//                }
//            }

            $dsearch = !$disableDiscussions;
            $csearch = !$disableComments;
        }
        /// Date ///
//        if (isset($search['date-filters'])) {
//            $dtZone = new DateTimeZone('UTC');
//            foreach($search['date-filters'] as $field => $value) {
//                $dt = new DateTime('@'.$value->getTimestamp());
//                $dt->setTimezone($dtZone);
//                $value = $pdo->quote($dt->format(MYSQL_DATE_FORMAT));
//
//                $dwhere['d.'.$field] = $value;
//                $cwhere['c.'.$field] = $value;
//            }
//        }


        // Now that we have the wheres, lets do the search.
        $vanillaSearch = new  \VanillaSearchModel();

        $searchModel->EventArguments['Limit'] = $options->getLimit();
        $searchModel->EventArguments['Offset'] = $options->getOffset();
        $searches = [];

        if ($dsearch) {
            foreach ($this->getSearchTypes() as $searchType ) {
                ;// $sql = $searchType->generateSql()
            }
            $sql = $vanillaSearch->discussionSql($searchModel, false);
//            $sql->select('d.Type');
//
//            if ($terms) {
//                $sql->beginWhereGroup();
//                foreach ((array)$dfields as $field) {
//                    $sql->orWhere("$field like", $terms, false, false);
//                }
//                $sql->endWhereGroup();
//            }
//
//            foreach ($dwhere as $field => $value) {
//                if (is_array($value)) {
//                    $sql->whereIn($field, $value);
//                } else {
//                    $sql->where($field, $value, false, false);
//                }
//            }
//
//            $searches[] = $sql->getSelect();
//            $sql->reset();
        }

//        if ($csearch) {
//            $sql = $vanillaSearch->commentSql($searchModel, false);
//            $sql->select('null as Type');
//
//            if ($terms) {
//                foreach ((array)$cfields as $field) {
//                    $sql->orWhere("$field like", $terms, false, false);
//                }
//            }
//
//            foreach ($cwhere as $field => $value) {
//                if (is_array($value)) {
//                    $sql->whereIn($field, $value);
//                } else {
//                    $sql->where($field, $value, false, false);
//                }
//            }
//
//            $searches[] = $sql->getSelect();
//            $sql->reset();
//        }

        // Perform the search by unioning all of the sql together.
        $Sql = Gdn::sql()
            ->select()
            ->from('_TBL_ s', false)
            ->orderBy('s.DateInserted', 'desc')
            ->limit($limit, $offset)
            ->getSelect();
        Gdn::sql()->reset();

        $union = '';
        foreach ($searches as $subQuery) {
            $union .= empty($union) ? '' : ' union all ';
            $union .= ' ( '.$subQuery.' ) ';
        }
        $Sql = str_replace(Gdn::database()->DatabasePrefix.'_TBL_', "(\n".$union."\n)", $Sql);

        $Result = Gdn::database()->query($Sql)->resultArray();

        foreach ($Result as &$row) {
            if ($row['RecordType'] === 'Comment') {
                $row['Title'] = sprintft('Re: %s', $row['Title']);
            }
        }

        //return $Result;

        $total = $search['total'] ?? 0;

        return new SearchResults(
            $results,
            $total,
            $options->getOffset(),
            $options->getLimit()
        );
    }


}
