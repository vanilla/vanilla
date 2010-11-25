<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
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
         foreach ($ColumnsArray as $Column) {
            $Column = trim($Column);

            $Param = $this->Parameter();
            $Sql->OrWhere("$Column like $Param", NULL, FALSE, FALSE);
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
			return NULL;

      // Figure out the exact search mode.
      if ($this->ForceSearchMode)
         $SearchMode = $this->ForceSearchMode;
      else
         $SearchMode = strtolower(C('Garden.Search.Mode', 'matchboolean'));
      
      if ($SearchMode == 'matchboolean') {
         if (strpos($Search, '+') !== FALSE || strpos($Search, '-') !== FALSE)
            $SearchMode = 'boolean';
         else
            $SearcMode = 'match';
      } else {
         $this->_SearchMode = $SearchMode;
      }
      $this->_SearchMode = $SearchMode;

      $this->FireEvent('Search');
      
      if(count($this->_SearchSql) == 0)
			return NULL;

		// Perform the search by unioning all of the sql together.
		$Sql = $this->SQL
			->Select()
			->From('_TBL_ s')
			->OrderBy('s.Relavence', 'desc')
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
		
		$Result = $this->Database->Query($Sql, $this->_Parameters)->ResultArray();
		foreach ($Result as $Key => $Value) {
			if (isset($Value['Summary'])) {
				$Value['Summary'] = Gdn_Format::Text($Value['Summary']);
				$Result[$Key] = $Value;
			}
		}
		$this->Reset();
		$this->SQL->Reset();
		return $Result;
	}
}