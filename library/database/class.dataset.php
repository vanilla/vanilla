<?php if (!defined('APPLICATION')) exit();

/**
 * A database-independent dataset management/manipulation class.
 *
 * This class is HEAVILY inspired by CodeIgniter (http://www.codeigniter.com).
 * My hat is off to them.
 * 
 * @author Todd Burry <todd@vanillaforums.com> 
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
 */

define('JOIN_INNER', 'inner');
define('JOIN_LEFT', 'left');

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
   public function __construct($Result = NULL, $DataSetType = NULL) {
      // Set defaults
      $this->Connection = NULL;
      $this->_Cursor = -1;
      $this->_PDOStatement = NULL;
      $this->_Result = $Result;
      if ($DataSetType !== NULL) {
         $this->_DatasetType = $DataSetType;
      } elseif ($Result) {
         if (isset($Result[0]) && is_array($Result[0])) {
            $this->_DatasetType = DATASET_TYPE_ARRAY;
         }
      }
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
				foreach($this->_Result as $Index => &$Row) {
               switch($DatasetType) {
                  case DATASET_TYPE_ARRAY:
							$Row = (array)$Row;
                     //$this->_Result[$Index] = (array)$this->_Result[$Index];
                     break;
                  case DATASET_TYPE_OBJECT:
							$Row = (object)$Row;
                     //$this->_Result[$Index] = (object)$this->_Result[$Index];
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

   /**
    * Index a result array.
    * @param array $Data The array to index. It is formatted similar to the array returned by Gdn_DataSet::Result().
    * @param string|array $Columns The name of the column to index on or an array of columns to index on.
    * @param array $Options An array of options for the method.
    *  - <b>Sep</b>: The string to seperate index columns by. Default '|'.
    *  - <b>Unique</b>: Whether or not the results are unique.
    *   - <b>true</b> (default): The index is unique.
    *   - <b>false</b>: The index is not unique and each indexed row will be an array or arrays.
    * @return type 
    */
   public static function Index($Data, $Columns, $Options = array()) {
      $Columns = (array)$Columns;
      $Result = array();
      $Options = array_change_key_case($Options);

      if (is_string($Options))
         $Options = array('sep' => $Options);

      $Sep = GetValue('sep', $Options, '|');
      $Unique = GetValue('unique', $Options, TRUE);

      foreach ($Data as $Row) {
         $IndexValues = array();
         foreach ($Columns as $Column) {
            $IndexValues[] = GetValue($Column, $Row);
         }
         $Index = implode($Sep, $IndexValues);

         if ($Unique)
            $Result[$Index] = $Row;
         else
            $Result[$Index][] = $Row;
      }
      return $Result;
   }
   
   /**
    *
    * @param array $Data
    * @param array $Columns The columns/table information for the join. Depending on the argument's index it will be interpreted differently.
    *  - <b>numeric</b>: This column will come be added to the resulting join. The value can be either a string or a two element array where the second element specifies an alias.
    *  - <b>alias</b>: The alias of the child table in the query.
    *  - <b>child</b>: The name of the child column.
    *  - <b>column</b>: The name of the column to put the joined data into. Can't be used with <b>prefix</b>.
    *  - <b>parent</b>: The name of the parent column.
    *  - <b>table</b>: The name of the child table in the join.
    *  - <b>prefix</b>: The name of the prefix to give the columns. Can't be used with <b>column</b>.
    * @param array $Options An array of extra options.
    *  - <b>sql</b>: A Gdn_SQLDriver with the child query.
    *  - <b>type</b>: The join type, either JOIN_INNER, JOIN_LEFT. This defaults to JOIN_LEFT.
    */
   public static function Join(&$Data, $Columns, $Options = array()) {
      $Options = array_change_key_case($Options);
      
      $Sql = Gdn::SQL(); //GetValue('sql', $Options, Gdn::SQL());
      $ResultColumns = array();
      
      // Grab the columns.
      foreach ($Columns as $Index => $Name) {
         if (is_numeric($Index)) {
            // This is a column being selected.
            if (is_array($Name)) {
               $Column = $Name[0];
               $ColumnAlias = $Name[1];
            } else {
               $Column = $Name;
               $ColumnAlias = '';
            }
            
            if (($Pos = strpos($Column, '.')) !== FALSE) {
               $Sql->Select($Column, '', $ColumnAlias);
               $Column = substr($Column, $Pos + 1);
            } else {
               $Sql->Select(isset($TableAlias) ? $TableAlias.'.'.$Column : $Column, '', $ColumnAlias);
            }
            if ($ColumnAlias)
               $ResultColumns[] = $ColumnAlias;
            else
               $ResultColumns[] = $Column;
         } else {
            switch (strtolower($Index)) {
               case 'alias':
                  $TableAlias = $Name;
                  break;
               case 'child':
                  $ChildColumn = $Name;
                  break;
               case 'column':
                  $JoinColumn = $Name;
                  break;
               case 'parent':
                  $ParentColumn = $Name;
                  break;
               case 'prefix':
                  $ColumnPrefix = $Name;
                  break;
               case 'table':
                  $Table = $Name;
                  break;
               case 'type':
                  // The type shouldn't be here, but handle it.
                  $Options['Type'] = $Name;
                  break;
               default:
                  throw new Exception("Gdn_DataSet::Join(): Unknown column option '$Index'.");
            }
         }
      }
      
      if (!isset($TableAlias)) {
         if (isset($Table))
            $TableAlias = 'c';
         else
            $TableAlias = 'c';
      }
      
      if (!isset($ParentColumn)) {
         if (isset($ChildColumn))
            $ParentColumn = $ChildColumn;
         elseif (isset($Table))
            $ParentColumn = $Table.'ID';
         else
            throw Exception("Gdn_DataSet::Join(): Missing 'parent' argument'.");
      }
      
      // Figure out some options if they weren't specified.
      if (!isset($ChildColumn)) {
         if (isset($ParentColumn))
            $ChildColumn = $ParentColumn;
         elseif (isset($Table))
            $ChildColumn = $Table.'ID';
         else
            throw Exception("Gdn_DataSet::Join(): Missing 'child' argument'.");
      }
      
      if (!isset($ColumnPrefix) && !isset($JoinColumn)) {
         $ColumnPrefix = StringEndsWith($ParentColumn, 'ID', TRUE, TRUE);
      }
      
      $JoinType = strtolower(GetValue('Type', $Options, JOIN_LEFT));
      
      // Start augmenting the sql for the join.
      if (isset($Table))
         $Sql->From("$Table $TableAlias");
      $Sql->Select("$TableAlias.$ChildColumn");
      
      // Get the IDs to generate an in clause with.
      $IDs = array();
      foreach ($Data as $Row) {
         $Value = GetValue($ParentColumn, $Row);
         if ($Value)
            $IDs[$Value] = TRUE;
      }
      
      $IDs = array_keys($IDs);
      $Sql->WhereIn($ChildColumn, $IDs);
      
      $ChildData = $Sql->Get()->ResultArray();
      $ChildData = self::Index($ChildData, $ChildColumn, array('unique' => GetValue('unique', $Options, isset($ColumnPrefix))));
      
      $NotFound = array();

      // Join the data in.
      foreach ($Data as $Index => &$Row) {
         $ParentID = GetValue($ParentColumn, $Row);
         if (isset($ChildData[$ParentID])) {
            $ChildRow = $ChildData[$ParentID];
            
            if (isset($ColumnPrefix)) {
               // Add the data to the columns.
               foreach ($ChildRow as $Name => $Value) {
                  SetValue($ColumnPrefix.$Name, $Row, $Value);
               }
            } else {
               // Add the result data.
               SetValue($JoinColumn, $Row, $ChildRow);
            }
         } else {
            if ($JoinType == JOIN_LEFT) {
               if (isset($ColumnPrefix)) {
                  foreach ($ResultColumns as $Name) {
                     SetValue($ColumnPrefix.$Name, $Row, NULL);
                  }
               } else {
                  SetValue($JoinColumn, $Row, array());
               }
            } else {
               $NotFound[] = $Index;
            }
         }
      }
      
      // Remove inner join rows.
      if ($JoinType == JOIN_INNER) {
         foreach ($NotFound as $Index) {
            unset($Data[$Index]);
         }
      }
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
    * Unserialize the fields in the dataset.
    * @param array $Fields 
    * @since 2.1
    */
   public function Unserialize($Fields = array('Attributes', 'Data')) {
      $Result =& $this->Result();
      $First = TRUE;
      
      foreach ($Result as $Row) {
         if ($First) {
            // Check which fields are in the dataset.
            foreach ($Fields as $Index => $Field) {
               if (GetValue($Field, $Row, FALSE) === FALSE) {
                  unset($Fields[$Index]);
               }
            }
            $First = FALSE;
         }
         
         foreach ($Fields as $Field) {
            if (is_object($Row)) {
               if (is_string($Row->$Field))
                  $Row->$Field = @unserialize($Row->$Field);
            } else {
               if (is_string($Row[$Field]))
                  $Row[$Field] = @unserialize($Row[$Field]);
            }
         }
      }
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