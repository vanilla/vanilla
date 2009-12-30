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
    * @var resource
    */
   public $Connection;

   /**
    * The index of the $this->_ResultSet currently being accessed.
    *
    * @var int
    */
   private $_Cursor;
   
   /**
    * Determines what type of result is returned from the various methods by default.
    *
    * @var int Either DATASET_TYPE_OBJECT or DATASET_TYPE_ARRAY.
    */
   public $DefaultDatasetType = DATASET_TYPE_OBJECT;

   /**
    * Contains a PDOStatement object returned by a PDO query. FALSE by default.
    * This property is assigned by the database driver object when a query is
    * executed in $Database->Query().
    *
    * @var object
    */
   private $_PDOStatement;

   /**
    * A boolean value indicating if the PDOStatement's result set has been fetched.
    *
    * @var boolean
    */
   private $_PDOStatementFetched;

   /**
    * An array of objects containing result->fieldname values from
    * the result resource. This array is filled by $this->ResultObject().
    *
    * @var array
    */
   private $_ResultObject;

   /**
    * A boolean value indicating if the PDOStatement's result set was fetched as objects.
    *
    * @var boolean
    */
   private $_ResultObjectFetched;

   /**
    * An array of arrays containing result->fieldname values from
    * the result resource. This array is filled by $this->ResultArray().
    *
    * @var array
    */
   private $_ResultArray;

   /**
    * A boolean value indicating if the PDOStatement's result set was fetched as arrays.
    *
    * @var boolean
    */
   private $_ResultArrayFetched;

   /**
    * @todo Undocumented method.
    */
   public function __construct() {
      // Set defaults
      $this->Connection = FALSE;
      $this->_Cursor = -1;
      $this->_PDOStatement = FALSE;
      $this->_PDOStatementFetched = FALSE;
      $this->_ResultObject = array();
      $this->_ResultObjectFetched = FALSE;
      $this->_ResultArray = array();
      $this->_ResultArrayFetched = FALSE;
   }

   /**
    * Moves the dataset's internal cursor pointer to the specified RowIndex.
    *
    * @param int $RowIndex The index to seek in the result resource.
    */
   public function DataSeek($RowIndex = 0) {
      $this->_Cursor = $RowIndex;
   }

   /**
    * Fetches all rows from the PDOStatement object into one of our result
    * arrays ($this->_ResultObject or $this->_ResultArray).
    *
    * @param string $RowType The format in which the result should be returned: object or array. It
    * will fill a different array depending on which type is specified.
    */
   public function FetchAllRows($RowType = FALSE) {
      if($RowType === FALSE) $RowType = $this->DefaultDatasetType;
      
      if ($this->_PDOStatementFetched === FALSE) {
         if (is_object($this->_PDOStatement)) {
            // Get all records from the pdostatement's result set.
            if ($RowType == DATASET_TYPE_OBJECT) {
               $this->ResultObjectFetched = TRUE;
               $this->_PDOStatement->setFetchMode(PDO::FETCH_OBJ);
               while ($Row = $this->_PDOStatement->fetch()) {
                  $this->_ResultObject[] = $Row;
               }
            } else {
               $this->ResultArrayFetched = TRUE;
               $this->_PDOStatement->setFetchMode(PDO::FETCH_ASSOC);
               while ($Row = $this->_PDOStatement->fetch()) {
                  $this->_ResultArray[] = $Row;
               }
            }
         }
         $this->_PDOStatementFetched = TRUE;
      }
   }

   /**
    * Returns the first row in the requested format or FALSE if there are no
    * rows to return.
    *
    * @param string $FormatType The type of formatting to use on each of the result fields. Defaults to none.
    * @param string $RowType The format in which the result should be returned: object or array.
    */
   public function FirstRow($FormatType = '', $RowType = FALSE) {
      if($RowType === FALSE) $RowType = $this->DefaultDatasetType;
      
      $Result = $this->Result('', $RowType);
      if (count($Result) == 0)
         return FALSE;

      return Format::To($Result[0], $FormatType);
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
    *
    */
   public function getIterator() {
      return new ArrayIterator($this->Result());
   }

   /**
    * Returns the last row in the requested format or FALSE if there are no
    * rows to return.
    *
    * @param string $FormatType The type of formatting to use on each of the result fields. Defaults to none.
    * @param string $RowType The format in which the result should be returned: object or array.
    */
   public function LastRow($FormatType = '', $RowType = FALSE) {
      if($RowType === FALSE) $RowType = $this->DefaultDatasetType;
      
      $Result = $this->Result('', $RowType);
      if (count($Result) == 0)
         return FALSE;

      return Format::To($Result[count($Result)-1], $FormatType);
   }

   /**
    * Returns the next row in the requested format. FALSE if there are no more
    * rows.
    *
    * @param string $FormatType The type of formatting to use on each of the result fields. Defaults to none.
    * @param string $RowType The format in which the result should be returned: object or array.
    */
   public function NextRow($FormatType = '', $RowType = FALSE) {
      if($RowType === FALSE) $RowType = $this->DefaultDatasetType;
      
      $Result = $this->Result('', $RowType);
      ++$this->_Cursor;
      if (isset($Result[$this->_Cursor])) {
         return Format::To($Result[$this->_Cursor], $FormatType);
      }
      return FALSE;
   }

   /**
    * Returns the number of fields in the DataSet.
    */
   public function NumFields() {
      return is_object($this->_PDOStatement) ? $this->_PDOStatement->columnCount() : 0;
   }

   /**
    * Returns the number of rows in the DataSet.
    *
    * @param string $RowTypeHint If this method is called before the records are pulled out of the
    * PDOStatement object, this hint allows you to specify how you want the
    * records retrieved (they have to be retrieved so we can count how many
    * there are). Hinting this way can save processing time later so we don't
    * need to convert from object to array or vice-versa.
    */
   public function NumRows($RowTypeHint = FALSE) {
      if($RowTypeHint === FALSE) $RowTypeHint = $this->DefaultDatasetType;
      
      if ($this->_ResultArrayFetched === TRUE)
         return count($this->_ResultArray);
      else if ($this->_ResultObjectFetched === TRUE)
         return count($this->_ResultObject);

      // Failing everything else, retrieve and count everything.
      return count($this->Result('', $RowTypeHint));
   }

   /**
    * Returns the previous row in the requested format.
    *
    * @param string $FormatType The type of formatting to use on each of the result fields. Defaults to none.
    * @param string $RowType The format in which the result should be returned: object or array.
    */
   public function PreviousRow($FormatType = '', $RowType = FALSE) {
      if($RowType === FALSE) $RowType = $this->DefaultDatasetType;
      
      $Result = $this->Result('', $RowType);
      --$this->_Cursor;
      if (isset($Result[$this->_Cursor])) {
         return Format::To($Result[$this->_Cursor], $FormatType);
      }
      return FALSE;
   }

   /**
    * Returns an array of data as the specified result type: object or array.
    * If "object" is specified as RowType, it will return an array of
    * objects with the fieldnames as properties. If "array" is specified, it
    * will return an array of associative arrays with the field names as array
    * keys. Called by $this->ResultObject() and $this->ResultArray().
    *
    * @param string $FormatType The type of formatting to use on each of the result fields. Defaults to none.
    * @param string $RowType The format in which to return a row: object or array.
    */
   public function Result($FormatType = '', $RowType = FALSE) {
      if($RowType === FALSE) $RowType = $this->DefaultDatasetType;
      
      if($RowType == DATASET_TYPE_OBJECT)
         return $this->ResultObject($FormatType);
      else
         return $this->ResultArray($FormatType);
   }

   /**
    * Returns an array of associative arrays containing the ResultSet data.
    *
    * @param string $FormatType The type of formatting to use on each of the result fields. Defaults to none.
    */
   public function ResultArray($FormatType = '') {
      if ($this->_PDOStatementFetched === TRUE) {
         if ($this->_ResultArrayFetched === FALSE) {
            foreach ($this->_ResultObject as $Object) {
               $this->_ResultArray[] = Format::ObjectAsArray($Object);
            }
         }
      } else {
         $this->FetchAllRows(DATASET_TYPE_ARRAY);
      }
      $this->_ResultArrayFetched = TRUE;

      return Format::To($this->_ResultArray, $FormatType);
   }

   /**
    * Returns an array of objects containing the ResultSet data.
    *
    * @param string $FormatType The type of formatting to use on each of the result fields. Defaults to none.
    */
   public function ResultObject($FormatType = '') {
      if ($this->_PDOStatementFetched === TRUE) {
         if ($this->_ResultObjectFetched === FALSE) {
            foreach ($this->_ResultArray as $Array) {
               $this->_ResultObject[] = Format::ArrayAsObject($Array);
            }
         }
      } else {
         $this->FetchAllRows(DATASET_TYPE_OBJECT);
      }
      $this->_ResultObjectFetched = TRUE;

      return Format::To($this->_ResultObject, $FormatType);
   }

   /**
    * Returns the requested row index as the requested row type.
    *
    * @param int $RowIndex The row to return from the result set. It is zero-based.
    * @param string $FormatType The type of formatting to use on each of the result fields. Defaults to none.
    * @param string $RowType The format in which the result should be returned: object or array.
    */
   public function Row($RowIndex, $FormatType = '', $RowType = FALSE) {
      if($RowType === FALSE) $RowType = $this->DefaultDatasetType;
      
      $Result = $this->Result('', $RowType);
      if (count($Result) == 0)
         return $Result;

      if (isset($Result[$RowIndex])) {
         return Format::To($Result[$RowIndex], $FormatType);
      }

      return FALSE;
   }

   /**
    * Allows you to fill this object's result set with a foreign data set in
    * the form of an array of associative arrays (or objects).
    *
    * @param array $ResultSet The array of arrays or objects that represent the data to be traversed.
    */
   public function ImportDataSet($ResultSet) {
      if (is_array($ResultSet) && array_key_exists(0, $ResultSet)) {
         $this->_Cursor = -1;
         $this->_PDOStatement = FALSE;
         $this->_PDOStatementFetched = TRUE;
         $FirstRow = $ResultSet[0];
         if (is_array($FirstRow)) {
            $this->_ResultArrayFetched = TRUE;
            $this->_ResultObjectFetched = FALSE;
            $this->_ResultArray = $ResultSet;
         } else {
            $this->_ResultArrayFetched = FALSE;
            $this->_ResultObjectFetched = TRUE;
            $this->_ResultObject = $ResultSet;
         }
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
      if($Row = $this->NextRow('', DATASET_TYPE_ARRAY)) {
         return $Row[$ColumnName];
      } else {
         return $DefaultValue;
      }
   }
}
