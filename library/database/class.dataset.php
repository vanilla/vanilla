<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

/**
 * A database-independent dataset management/manipulation class.
 *
 * This class is HEAVILY inspired by CodeIgniter (http://www.codeigniter.com).
 * My hat is off to them.
 *
 * @author Mark O'Sullivan
 * @copyright 2003 Mark O'Sullivan
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @version @@GARDEN-VERSION@@
 * @namespace Garden.Database
 */

class Gdn_DataSet implements IteratorAggregate {

   /**
    * Contains a reference to the open database connection. FALSE by default.
    * This property is passed by reference from the Database object. Do not
    * manipulate or assign anything to this property!
    *
    * @var PDO
    */
   public $Connection;

   /**
    * The index of the $this->_ResultSet currently being accessed.
    *
    * @var int
    */
   private $_Cursor = -1;
	
	/**
    * Determines what type of result is returned from the various methods by default.
    *
    * @var int Either DATASET_TYPE_OBJECT or DATASET_TYPE_ARRAY.
    */
	protected $_DatasetType = DATASET_TYPE_OBJECT;
	
	protected $_EOF = FALSE;

   /**
    * Contains a PDOStatement object returned by a PDO query. FALSE by default.
    * This property is assigned by the database driver object when a query is
    * executed in $Database->Query().
    *
    * @var object
    */
   private $_PDOStatement;
	
	/**
	 * An array of either objects or associative arrays with the data in this dataset.
	 * @var array
	 */
	protected $_Result;

   /**
    * @todo Undocumented method.
    */
   public function __construct($Result = NULL) {
      // Set defaults
      $this->Connection = NULL;
      $this->_Cursor = -1;
      $this->_PDOStatement = NULL;
      $this->_Result = $Result;
   }

   public function  __destruct() {
      $this->FreePDOStatement(TRUE);
   }

   /** Clean sensitive data out of the object. */
   public function Clean() {
      $this->Connection = NULL;
      $this->FreePDOStatement(TRUE);
   }

   /**
    * Moves the dataset's internal cursor pointer to the specified RowIndex.
    *
    * @param int $RowIndex The index to seek in the result resource.
    */
   public function DataSeek($RowIndex = 0) {
      $this->_Cursor = $RowIndex;
   }
	
	public function DatasetType($DatasetType = FALSE) {
		if($DatasetType !== FALSE) {
			// Make sure the type isn't changed if the result is already fetched.
			if(!is_null($this->_Result) && $DatasetType != $this->_DatasetType) {
            // Loop through the dataset and switch the types.
            $Count = count($this->_Result);
            for($Index = 0; $Index < $Count; $Index++) {
               switch($DatasetType) {
                  case DATASET_TYPE_ARRAY:
                     $this->_Result[$Index] = (array)$this->_Result[$Index];
                     break;
                  case DATASET_TYPE_OBJECT:
                     $this->_Result[$Index] = (object)$this->_Result[$Index];
                     break;
               }
            }
			}
			
			$this->_DatasetType = $DatasetType;
			return $this;
		} else {
			return $this->_DatasetType;
		}
	}

   /**
    * Fetches all rows from the PDOStatement object into the resultset.
    *
    * @param string $DatasetType The format in which the result should be returned: object or array.
    * It will fill a different array depending on which type is specified.
    */
   protected function _FetchAllRows($DatasetType = FALSE) {
		if(!is_null($this->_Result))
			return;

      if($DatasetType)
         $this->_DatasetType = $DatasetType;
		
		$Result = array();
      if (is_null($this->_PDOStatement)) {
         $this->_Result = $Result;
         return;
      }

      $Result = $this->_PDOStatement->fetchAll($this->_DatasetType == DATASET_TYPE_ARRAY ? PDO::FETCH_ASSOC : PDO::FETCH_OBJ);

//		$this->_PDOStatement->setFetchMode($this->_DatasetType == DATASET_TYPE_ARRAY ? PDO::FETCH_ASSOC : PDO::FETCH_OBJ);
//      while($Row = $this->_PDOStatement->fetch()) {
//			$Result[] = $Row;
//		}

      $this->FreePDOStatement(TRUE);
		$this->_Result = $Result;
   }

   /**
    * Returns the first row or FALSE if there are no rows to return.
    *
    * @param string $DatasetType The format in which the result should be returned: object or array.
    */
   public function &FirstRow($DatasetType = FALSE) {
		$Result = &$this->Result($DatasetType);
      if(count($Result) == 0)
         return $this->_EOF;

      return $Result[0];
   }
	
	/**
	 * Format the resultset with the given method.
	 *
	 * @param string $FormatMethod The method to use with Gdn_Format::To().
	 * @return Gdn_Dataset $this pointer for chaining.
	 */
	public function Format($FormatMethod) {
		$Result = &$this->Result();
		foreach($Result as $Index => $Value) {
			$Result[$Index] = Gdn_Format::To($Value, $FormatMethod);
		}
		return $this;
	}

   /**
    * Free's the result resource referenced by $this->_PDOStatement.
    */
   public function FreePDOStatement($DestroyPDOStatement = TRUE) {
      if (is_object($this->_PDOStatement))
         $this->_PDOStatement->closeCursor();
         
      if ($DestroyPDOStatement)
         $this->_PDOStatement = NULL;
   }
   
   /**
    * Interface method for IteratorAggregate;
    */
   public function getIterator() {
      return new ArrayIterator($this->Result());
   }

   public static function Index($Data, $Columns, $Sep = '|') {
      $Columns = (array)$Columns;
      $Result = array();

      foreach ($Data as $Row) {
         $IndexValues = array();
         foreach ($Columns as $Column) {
            $IndexValues[] = GetValue($Column, $Row);
         }
         $Index = implode($Sep, $IndexValues);
         $Result[$Index] = $Row;
      }
      return $Result;
   }

   /**
    * Returns the last row in the or FALSE if there are no rows to return.
    *
    * @param string $DatasetType The format in which the result should be returned: object or array.
    */
   public function &LastRow($DatasetType = FALSE) {
      $Result = &$this->Result($DatasetType);
      if (count($Result) == 0)
         return $this->_EOF;

      return $Result[count($Result) - 1];
   }

   /**
    * Returns the next row or FALSE if there are no more rows.
    *
    * @param string $DatasetType The format in which the result should be returned: object or array.
    */
   public function &NextRow($DatasetType = FALSE	) {
      $Result = &$this->Result($DatasetType);
      ++$this->_Cursor;
		
      if(isset($Result[$this->_Cursor]))
         return $Result[$this->_Cursor];
      return $this->_EOF;
   }

   /**
    * Returns the number of fields in the DataSet.
    */
   public function NumFields() {
      $Result = is_object($this->_PDOStatement) ? $this->_PDOStatement->columnCount() : 0;
		return $Result;
	}

   /**
    * Returns the number of rows in the DataSet.
    *
    * @param string $DatasetType The format in which the result should be returned: object or array.
    */
   public function NumRows($DatasetType = FALSE) {
		$Result = count($this->Result($DatasetType));
		return $Result;
   }

   /**
    * Returns the previous row in the requested format.
    *
    * @param string $DatasetType The format in which the result should be returned: object or array.
    */
   public function &PreviousRow($DatasetType = FALSE) {
      $Result = &$this->Result($DatasetType);
      --$this->_Cursor;
      if (isset($Result[$this->_Cursor])) {
         return $Result[$this->_Cursor];
      }
      return $this->_EOF;
   }

   /**
    * Returns an array of data as the specified result type: object or array.
    *
    * @param string $DatasetType The format in which to return a row: object or array. The following values are supported.
    *  - <b>DATASET_TYPE_ARRAY</b>: An array of associative arrays.
    *  - <b>DATASET_TYPE_OBJECT</b>: An array of standard objects.
    *  - <b>FALSE</b>: The current value of the DatasetType property will be used.
    */
   public function &Result($DatasetType = FALSE) {
		$this->DatasetType($DatasetType);
      if(is_null($this->_Result))
			$this->_FetchAllRows();

			
		return $this->_Result;
   }

   /**
    * Returns an array of associative arrays containing the ResultSet data.
    *
    */
   public function &ResultArray() {
		return $this->Result(DATASET_TYPE_ARRAY);
   }

   /**
    * Returns an array of objects containing the ResultSet data.
    *
    */
   public function ResultObject($FormatType = '') {
		return $this->Result(DATASET_TYPE_OBJECT);
   }

   /**
    * Returns the requested row index as the requested row type.
    *
    * @param int $RowIndex The row to return from the result set. It is zero-based.
    * @return mixed The row at the given index or FALSE if there is no row at the index.
    */
   public function &Row($RowIndex) {
		$Result = &$this->Result();
      if(isset($Result[$RowIndex]))
			return $Result[$RowIndex];
      return $this->_EOF;
   }

   /**
    * Allows you to fill this object's result set with a foreign data set in
    * the form of an array of associative arrays (or objects).
    *
    * @param array $Resultset The array of arrays or objects that represent the data to be traversed.
    */
   public function ImportDataset($Resultset) {
      if (is_array($Resultset) && array_key_exists(0, $Resultset)) {
         $this->_Cursor = -1;
         $this->_PDOStatement = NULL;
         $FirstRow = $Resultset[0];
			
         if (is_array($FirstRow))
				$this->_DatasetType = DATASET_TYPE_ARRAY;
			else
				$this->_DatasetType = DATASET_TYPE_OBJECT;
			$this->_Result = $Resultset;
      }
   }

   /**
    * Assigns the pdostatement object to this object.
    *
    * @param PDOStatement $PDOStatement The PDO Statement Object being assigned.
    */
   public function PDOStatement(&$PDOStatement = FALSE) {
      if ($PDOStatement === FALSE)
         return $this->_PDOStatement;
      else
         $this->_PDOStatement = $PDOStatement;
   }

   /**
    * Advances to the next row and returns the value rom a column.
    *
    * @param string $ColumnName The name of the column to get the value from.
    * @param string $DefaultValue The value to return if there is no data.
    * @return mixed The value from the column or $DefaultValue.
    */
   public function Value($ColumnName, $DefaultValue = NULL) {
      if($Row = $this->NextRow()) {
         if(is_array($ColumnName)) {
            $Result = array();
            foreach($ColumnName as $Name => $Default) {
               if(is_object($Row) && property_exists($Row, $Name))
                     return $Row->$Name;
               elseif(is_array($Row) && array_key_exists($Name, $Row))
                     return $Row[$Name];
               else
                  $Result[] = $Default;
            }
            return $Result;
         } else {
            if(is_object($Row) && property_exists($Row, $ColumnName))
                  return $Row->$ColumnName;
            elseif(is_array($Row) && array_key_exists($ColumnName, $Row))
                  return $Row[$ColumnName];
         }
		}
      if(is_array($ColumnName))
         return array_values($ColumnName);
		return $DefaultValue;
   }
   
   /**
    * Advances to the next row and returns the value rom a column.
    *
    * @param mixed $ColumnName The name of the column to get the value from.
    *  - <b>string</b>: The argument represents the column name.
    *  - <b>array</b>: The argument is an array of column/default pairs.
    * @param string $DefaultValue The value to return if there is no data.
    * @return mixed The value from the column or $DefaultValue.
    */
//   public function Value($ColumnName, $DefaultValue = NULL) {
//      if (is_string($ColumnName))
//         $Columns = array($ColumnName => $DefaultValue);
//      else
//         $Columns = $ColumnName;
//
//
//      $this->_FetchAllRows(FALSE);
//
//      $Rows = $this->_Result;
//      if(array_key_exists($this->_Cursor, $Rows))
//         $Row = $Rows[$this->_Cursor];
//      elseif(array_key_exists(0, $Rows))
//         $Row = $Rows[0];
//      else
//         $Row = array();
//
//
//      $Result = array();
//      foreach($Columns as $ColumnName2 => $DefaultValue2) {
//         if(is_array($Row) && array_key_exists($ColumnName2, $Row))
//            $Result[] = $Row[$ColumnName2];
//         elseif(is_object($Row) && property_exists($Row, $ColumnName2))
//            $Result[] = $Row->$ColumnName2;
//         else
//            $Result[] = $DefaultValue2;
//      }
//
//      //$Result = array_values($Columns);
//      if(count($Result) == 1)
//         return $Result[0];
//      else
//         return $Result;
//   }
}