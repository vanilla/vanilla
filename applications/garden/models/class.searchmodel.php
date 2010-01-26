<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class Gdn_SearchModel extends Gdn_Model {
	/// PROPERTIES ///
	protected $_Parameter = '';
	
	protected $_SearchSql = array();
	
	
	/// METHODS ///
	public function AddSearch($Sql) {
		$this->_SearchSql[] = $Sql;
	}
	
	public function AddMatchSql($Sql, $Columns) {
		$Param = $this->Parameter();
		
		$Sql
			->Select($Columns, "match(%s) against($Param)", 'Relavence')
			->Where("match($Columns) against ($Param)", NULL, FALSE, FALSE);
	}
	
	public function Parameter($Search = NULL) {
		if($Search)
			$this->_Parameter = $this->SQL->NamedParameter('Search', FALSE, $Search);
		else
			$this->_Parameter = $this->SQL->NamedParameter('Search');
			
		return $this->_Parameter;
	}
	
	public function Reset() {
		$this->_Parameter = '';
		$this->_SearchSql = '';
	}
	
	public function Search($Search, $Offset = 0, $Limit = 20) {
		// If there are no searches then return an empty array.
		if(count($this->_SearchSql) == 0)
			return NULL;
			
		// Perform the search by unioning all of the sql together.
		$this->Parameter($Search);
		
		$Sql = $this->SQL
			->Select()
			->From('_TBL_ s')
			->OrderBy('s.Relavence', 'desc')
			->Limit($Limit, $Offset)
			->GetSelect();
		$Sql = str_replace($this->Database->DatabasePrefix.'_TBL_', "(\n".implode("\nunion all\n", $this->_SearchSql)."\n)", $Sql);
		
		$Result = $this->SQL->Query($Sql);
		return $Result;
	}
}