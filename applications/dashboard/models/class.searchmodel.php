<?php
/**
 * Search model.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Handles search data.
 */
class SearchModel extends Gdn_Model {

    /** @var array Parameters. */
    protected $_Parameters = [];

    /** @var array SQL. */
    protected $_SearchSql = [];

    /** @var string Mode. */
    protected $_SearchMode = 'match';

    /** @var bool Whether to force the mode. */
    public $ForceSearchMode = '';

    /** @var string Search string. */
    protected $_SearchText = '';

    /**
     *
     *
     * @param $sql
     */
    public function addSearch($sql) {
        $this->_SearchSql[] = $sql;
    }

    /** Add the sql to perform a search.
     *
     * @param Gdn_SQLDriver $sql
     * @param string $columns a comma seperated list of columns to search on.
     */
    public function addMatchSql($sql, $columns, $likeRelevanceColumn = '') {
        if ($this->_SearchMode == 'like') {
            if ($likeRelevanceColumn) {
                $sql->select($likeRelevanceColumn, '', 'Relevance');
            } else {
                $sql->select(1, '', 'Relevance');
            }

            $sql->beginWhereGroup();

            $columnsArray = explode(',', $columns);

            $first = true;
            foreach ($columnsArray as $column) {
                $column = trim($column);

                $param = $this->parameter();
                if ($first) {
                    $sql->where("$column like $param", null, false, false);
                    $first = false;
                } else {
                    $sql->orWhere("$column like $param", null, false, false);
                }
            }

            $sql->endWhereGroup();
        } else {
            $boolean = $this->_SearchMode == 'boolean' ? ' in boolean mode' : '';

            $param = $this->parameter();
            $sql->select($columns, "match(%s) against($param{$boolean})", 'Relevance');
            $param = $this->parameter();
            $sql->where("match($columns) against ($param{$boolean})", null, false, false);
        }
    }

    /**
     *
     *
     * @return string
     */
    public function parameter() {
        $parameter = ':Search'.count($this->_Parameters);
        $this->_Parameters[$parameter] = '';
        return $parameter;
    }

    /**
     *
     */
    public function reset() {
        $this->_Parameters = [];
        $this->_SearchSql = '';
    }

    /**
     *
     *
     * @param $search
     * @param int $offset
     * @param int $limit
     * @return array|null
     * @throws Exception
     */
    public function search($search, $offset = 0, $limit = 20) {
        // If there are no searches then return an empty array.
        if (trim($search) == '') {
            return [];
        }

        // Figure out the exact search mode.
        if ($this->ForceSearchMode) {
            $searchMode = $this->ForceSearchMode;
        } else {
            $searchMode = strtolower(c('Garden.Search.Mode', 'matchboolean'));
        }

        if ($searchMode == 'matchboolean') {
            if (strpos($search, '+') !== false || strpos($search, '-') !== false) {
                $searchMode = 'boolean';
            } else {
                $searchMode = 'match';
            }
        } else {
            $this->_SearchMode = $searchMode;
        }

        if ($forceDatabaseEngine = c('Database.ForceStorageEngine')) {
            if (strcasecmp($forceDatabaseEngine, 'myisam') != 0) {
                $searchMode = 'like';
            }
        }

        if (strlen($search) <= 4) {
            $searchMode = 'like';
        }

        $this->_SearchMode = $searchMode;

        $this->EventArguments['Search'] = $search;
        $this->fireEvent('Search');

        if (count($this->_SearchSql) == 0) {
            return [];
        }

        // Perform the search by unioning all of the sql together.
        $sql = $this->SQL
            ->select()
            ->from('_TBL_ s', false)
            ->orderBy('s.DateInserted', 'desc')
            ->limit($limit, $offset)
            ->getSelect();

        $sql = str_replace($this->Database->DatabasePrefix.'_TBL_', "(\n".implode("\nunion all\n", $this->_SearchSql)."\n)", $sql);

        $this->fireEvent('AfterBuildSearchQuery');

        if ($this->_SearchMode == 'like') {
            $search = '%'.$search.'%';
        }

        foreach ($this->_Parameters as $key => $value) {
            $this->_Parameters[$key] = $search;
        }

        $parameters = $this->_Parameters;
        $this->reset();
        $this->SQL->reset();
        $result = $this->Database->query($sql, $parameters)->resultArray();

        foreach ($result as $key => $value) {
            if (isset($value['Summary'])) {
                $value['Summary'] = condense(Gdn_Format::to($value['Summary'], $value['Format']));
                $result[$key] = $value;
            }

            switch ($value['RecordType']) {
                case 'Comment':
                    $comment = arrayTranslate($value, ['PrimaryID' => 'CommentID', 'CategoryID']);
                    $result[$key]['Url'] = commentUrl($comment);
                    break;
                case 'Discussion':
                    $discussion = arrayTranslate($value, ['PrimaryID' => 'DiscussionID', 'Title' => 'Name', 'CategoryID']);
                    $result[$key]['Url'] = discussionUrl($discussion, 1);
                    break;
            }
        }

        return $result;
    }

    /**
     *
     *
     * @param null $value
     * @return null|string
     */
    public function searchMode($value = null) {
        if ($value !== null) {
            $this->_SearchMode = $value;
        }
        return $this->_SearchMode;
    }
}
