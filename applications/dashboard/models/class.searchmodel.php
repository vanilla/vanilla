<?php if (!defined('APPLICATION')) exit();

/**
 * Search Model
 * 
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
 */

class SearchModel extends Gdn_Model {
	/// PROPERTIES ///
	protected $_Parameters = array();
	
	protected $_SearchSql = array();

   protected $_SearchMode = 'match';

   public $ForceSearchMode = '';

   protected $_SearchText = '';
	
	/// METHODS ///
	public function AddSearch($Sql) {
		$this->_SearchSql[] = $Sql;
	}

   /** Add the sql to perform a search.
    *
    * @param Gdn_SQLDriver $Sql
    * @param string $Columns a comma seperated list of columns to search on.
    */
	public function AddMatchSql($Sql, $Columns, $LikeRelavenceColumn = '') {
      if ($this->_SearchMode == 'like') {
         if ($LikeRelavenceColumn)
            $Sql->Select($LikeRelavenceColumn, '', 'Relavence');
         else
            $Sql->Select(1, '', 'Relavence');

         $Sql->BeginWhereGroup();

         $ColumnsArray = explode(',', $Columns);
         
         $First = TRUE;
         foreach ($ColumnsArray as $Column) {
            $Column = trim($Column);

            $Param = $this->Parameter();
            if ($First) {
               $Sql->Where("$Column like $Param", NULL, FALSE, FALSE);
               $First = FALSE;
            } else {
               $Sql->OrWhere("$Column like $Param", NULL, FALSE, FALSE);
            }
         }

         $Sql->EndWhereGroup();
      } else {
         $Boolean = $this->_SearchMode == 'boolean' ? ' in boolean mode' : '';

         $Param = $this->Parameter();
         $Sql->Select($Columns, "match(%s) against($Param{$Boolean})", 'Relavence');
         $Param = $this->Parameter();
         $Sql->Where("match($Columns) against ($Param{$Boolean})", NULL, FALSE, FALSE);
      }
	}
	
	public function Parameter() {
		$Parameter = ':Search'.count($this->_Parameters);
		$this->_Parameters[$Parameter] = '';
		return $Parameter;
	}
	
	public function Reset() {
		$this->_Parameters = array();
		$this->_SearchSql = '';
	}
	
	public function Search($Search, $Offset = 0, $Limit = 20) {
		// If there are no searches then return an empty array.
		if(trim($Search) == '')
			return array();

      // Figure out the exact search mode.
      if ($this->ForceSearchMode)
         $SearchMode = $this->ForceSearchMode;
      else
         $SearchMode = strtolower(C('Garden.Search.Mode', 'matchboolean'));
      
      if ($SearchMode == 'matchboolean') {
         if (strpos($Search, '+') !== FALSE || strpos($Search, '-') !== FALSE)
            $SearchMode = 'boolean';
         else
            $SearchMode = 'match';
      } else {
         $this->_SearchMode = $SearchMode;
      }
      
      if ($ForceDatabaseEngine = C('Database.ForceStorageEngine')) {
         if (strcasecmp($ForceDatabaseEngine, 'myisam') != 0)
            $SearchMode = 'like';
      }
      
      $this->_SearchMode = $SearchMode;

      $this->FireEvent('Search');
      
      if(count($this->_SearchSql) == 0)
			return array();

		// Perform the search by unioning all of the sql together.
		$Sql = $this->SQL
			->Select()
			->From('_TBL_ s')
			->OrderBy('s.DateInserted', 'desc')
			->Limit($Limit, $Offset)
			->GetSelect();
		
		$Sql = str_replace($this->Database->DatabasePrefix.'_TBL_', "(\n".implode("\nunion all\n", $this->_SearchSql)."\n)", $Sql);
		
		$this->EventArguments['Search'] = $Search;
		$this->FireEvent('AfterBuildSearchQuery');

      if ($this->_SearchMode == 'like')
         $Search = '%'.$Search.'%';

		foreach($this->_Parameters as $Key => $Value) {
			$this->_Parameters[$Key] = $Search;
		}
		
      $Parameters= $this->_Parameters;
      $this->Reset();
      $this->SQL->Reset();
		$Result = $this->Database->Query($Sql, $Parameters)->ResultArray();
      
		foreach ($Result as $Key => $Value) {
			if (isset($Value['Summary'])) {
				$Value['Summary'] = Condense(Gdn_Format::To($Value['Summary'], $Value['Format']));
				$Result[$Key] = $Value;
			}
         
         switch ($Value['RecordType']) {
            case 'Discussion':
               $Discussion = ArrayTranslate($Value, array('PrimaryID' => 'DiscussionID', 'Title' => 'Name', 'CategoryID'));
               $Result[$Key]['Url'] = DiscussionUrl($Discussion, 1);
               break;
         }
		}
      
		return $Result;
	}
}