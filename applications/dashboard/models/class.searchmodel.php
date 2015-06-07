<?php
/**
 * Search model.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Handles search data.
 */
class SearchModel extends Gdn_Model {

    /** @var array Parameters. */
    protected $_Parameters = array();

    /** @var array SQL. */
    protected $_SearchSql = array();

    /** @var string Mode. */
    protected $_SearchMode = 'match';

    /** @var bool Whether to force the mode. */
    public $ForceSearchMode = '';

    /** @var string Search string. */
    protected $_SearchText = '';

    /**
     *
     *
     * @param $Sql
     */
    public function addSearch($Sql) {
        $this->_SearchSql[] = $Sql;
    }

    /** Add the sql to perform a search.
     *
     * @param Gdn_SQLDriver $Sql
     * @param string $Columns a comma seperated list of columns to search on.
     */
    public function addMatchSql($Sql, $Columns, $LikeRelavenceColumn = '') {
        if ($this->_SearchMode == 'like') {
            if ($LikeRelavenceColumn) {
                $Sql->select($LikeRelavenceColumn, '', 'Relavence');
            } else {
                $Sql->select(1, '', 'Relavence');
            }

            $Sql->beginWhereGroup();

            $ColumnsArray = explode(',', $Columns);

            $First = true;
            foreach ($ColumnsArray as $Column) {
                $Column = trim($Column);

                $Param = $this->Parameter();
                if ($First) {
                    $Sql->where("$Column like $Param", null, false, false);
                    $First = false;
                } else {
                    $Sql->orWhere("$Column like $Param", null, false, false);
                }
            }

            $Sql->endWhereGroup();
        } else {
            $Boolean = $this->_SearchMode == 'boolean' ? ' in boolean mode' : '';

            $Param = $this->Parameter();
            $Sql->select($Columns, "match(%s) against($Param{$Boolean})", 'Relavence');
            $Param = $this->Parameter();
            $Sql->where("match($Columns) against ($Param{$Boolean})", null, false, false);
        }
    }

    /**
     *
     *
     * @return string
     */
    public function parameter() {
        $Parameter = ':Search'.count($this->_Parameters);
        $this->_Parameters[$Parameter] = '';
        return $Parameter;
    }

    /**
     *
     */
    public function reset() {
        $this->_Parameters = array();
        $this->_SearchSql = '';
    }

    /**
     *
     *
     * @param $Search
     * @param int $Offset
     * @param int $Limit
     * @return array|null
     * @throws Exception
     */
    public function search($Search, $Offset = 0, $Limit = 20) {
        // If there are no searches then return an empty array.
        if (trim($Search) == '') {
            return array();
        }

        // Figure out the exact search mode.
        if ($this->ForceSearchMode) {
            $SearchMode = $this->ForceSearchMode;
        } else {
            $SearchMode = strtolower(c('Garden.Search.Mode', 'matchboolean'));
        }

        if ($SearchMode == 'matchboolean') {
            if (strpos($Search, '+') !== false || strpos($Search, '-') !== false) {
                $SearchMode = 'boolean';
            } else {
                $SearchMode = 'match';
            }
        } else {
            $this->_SearchMode = $SearchMode;
        }

        if ($ForceDatabaseEngine = c('Database.ForceStorageEngine')) {
            if (strcasecmp($ForceDatabaseEngine, 'myisam') != 0) {
                $SearchMode = 'like';
            }
        }

        if (strlen($Search) <= 4) {
            $SearchMode = 'like';
        }

        $this->_SearchMode = $SearchMode;

        $this->EventArguments['Search'] = $Search;
        $this->fireEvent('Search');

        if (count($this->_SearchSql) == 0) {
            return array();
        }

        // Perform the search by unioning all of the sql together.
        $Sql = $this->SQL
            ->select()
            ->from('_TBL_ s')
            ->orderBy('s.DateInserted', 'desc')
            ->limit($Limit, $Offset)
            ->GetSelect();

        $Sql = str_replace($this->Database->DatabasePrefix.'_TBL_', "(\n".implode("\nunion all\n", $this->_SearchSql)."\n)", $Sql);

        $this->fireEvent('AfterBuildSearchQuery');

        if ($this->_SearchMode == 'like') {
            $Search = '%'.$Search.'%';
        }

        foreach ($this->_Parameters as $Key => $Value) {
            $this->_Parameters[$Key] = $Search;
        }

        $Parameters = $this->_Parameters;
        $this->reset();
        $this->SQL->reset();
        $Result = $this->Database->query($Sql, $Parameters)->resultArray();

        foreach ($Result as $Key => $Value) {
            if (isset($Value['Summary'])) {
                $Value['Summary'] = Condense(Gdn_Format::to($Value['Summary'], $Value['Format']));
                $Result[$Key] = $Value;
            }

            switch ($Value['RecordType']) {
                case 'Discussion':
                    $Discussion = arrayTranslate($Value, array('PrimaryID' => 'DiscussionID', 'Title' => 'Name', 'CategoryID'));
                    $Result[$Key]['Url'] = DiscussionUrl($Discussion, 1);
                    break;
            }
        }

        return $Result;
    }

    /**
     *
     *
     * @param null $Value
     * @return null|string
     */
    public function searchMode($Value = null) {
        if ($Value !== null) {
            $this->_SearchMode = $Value;
        }
        return $this->_SearchMode;
    }
}
