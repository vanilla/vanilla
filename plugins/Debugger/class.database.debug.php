<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

require_once(PATH_LIBRARY.DS.'database'.DS.'class.database.php');

class Gdn_DatabaseDebug extends Gdn_Database {
	/// PROPERTIES ///
	
	protected $_ExecutionTime = 0;
	
	protected $_Queries = array();
	
	protected $_QueryTimes = array();
	
	/// METHODS ///
	
	public function ExecutionTime() {
		return $this->_ExecutionTime;
	}
	
	private static function FormatArgs($Args) {
		if(!is_array($Args))
			return '';
		
		$Result = '';
		
		foreach($Args as $i => $Expr) {
			if(strlen($Result) > 0)
				$Result .= ', ';
			$Result .= self::FormatExpr($Expr);
		}
		return $Result;
	}
	
	private static function FormatExpr($Expr) {
		if(is_array($Expr)) {
			$Result = '';
			foreach($Expr as $Key => $Value) {
				if(strlen($Result) > 0)
					$Result .= ', ';
				$Result .= '\''.str_replace('\'', '\\\'', $Key).'\' => '.self::FormatExpr($Value);
			}
			return 'array(' . $Result . ')';
		} elseif(is_null($Expr)) {
			return 'NULL';
		} elseif(is_string($Expr)) {
			return '\''.str_replace('\'', '\\\'', $Expr).'\'';
		} elseif(is_object($Expr)) {
			return '?OBJECT?';
		} else {
			return $Expr;
		}
	}
	
	public function Queries() {
		return $this->_Queries;
	}
	
	/**
    * @todo Put the query debugging logic into the debug plugin.
    * 1. Create a subclass of this object where Query() does the debugging stuff.
    * 2. Install that class to Gdn to override the database.
    */
   public function Query($Sql, $InputParameters = NULL) {
		$Trace = debug_backtrace();
		$Method = '';
		foreach($Trace as $Info) {
			$Class = GetValue('class', $Info, '');
			if($Class === '' || StringEndsWith($Class, 'Model', TRUE)) {
				$Type = ArrayValue('type', $Info, '');
				
				$Method = $Class.$Type.$Info['function'].'('.self::FormatArgs($Info['args']).')';
            break;
			}
		}
		
      // Save the query for debugging
      // echo '<br />adding to queries: '.$Sql;
      $this->_Queries[] = array('Sql' => $Sql, 'Parameters' => $InputParameters, 'Method' => $Method);
      
      // Start the Query Timer
      $TimeStart = list($sm, $ss) = explode(' ', microtime());
      
      $Result = parent::Query($Sql, $InputParameters);
      
      // Aggregate the query times
      $TimeEnd = list($em, $es) = explode(' ', microtime());
      $this->_ExecutionTime += ($em + $es) - ($sm + $ss);
      $this->_QueryTimes[] = ($em + $es) - ($sm + $ss);
      
      return $Result;
   }
	
	public function QueryTimes() {
		return $this->_QueryTimes;
	}
}