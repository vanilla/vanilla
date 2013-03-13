<?php if (!defined('APPLICATION')) exit();

/**
 * Contains useful functions for cleaning up the database.
 *
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.1
 */

class DBAModel extends Gdn_Model {
   public static $ChunkSize = 10000;
   
   public function Counts($Table, $Column, $From = FALSE, $To = FALSE) {
      $Model = $this->CreateModel($Table);
      
      if (!method_exists($Model, 'Counts')) {
         throw new Gdn_UserException("The $Table model does not support count recalculation.");
      }
      
      $Result = $Model->Counts($Column, $From, $To);
      return $Result;
   }
   
   /**
    * Create a model for the given table.
    * 
    * @param string $Table
    * @return Gdn_Model
    */
   public function CreateModel($Table) {
      $ModelName = $Table.'Model';
      if (class_exists($ModelName)) {
         return new $ModelName();
      } else {
         return new Gdn_Model($Table);
      }
   }
   
   /*
    * Return SQL for updating a count.
    * @param string $Aggregate count, max, min, etc.
    * @param string $ParentTable The name of the parent table.
    * @param string $ChildTable The name of the child table
    * @param type $ParentColumnName
    * @param string $ChildColumnName
    * @param string $ParentJoinColumn
    * @param string $ChildJoinColumn
    * @return type 
    */
   public static function GetCountSQL(
      $Aggregate, // count, max, min, etc.
      $ParentTable, $ChildTable, 
      $ParentColumnName = '', $ChildColumnName = '',
      $ParentJoinColumn = '', $ChildJoinColumn = '',
      $Where = array()) {

      if(!$ParentColumnName) {
         switch(strtolower($Aggregate)) {
            case 'count': $ParentColumnName = "Count{$ChildTable}s"; break;
            case 'max': $ParentColumnName = "Last{$ChildTable}ID"; break;
            case 'min': $ParentColumnName = "First{$ChildTable}ID"; break;
            case 'sum': $ParentColumnName = "Sum{$ChildTable}s"; break;
         }
      }

      if(!$ChildColumnName)
         $ChildColumnName = $ChildTable.'ID';

      if(!$ParentJoinColumn)
         $ParentJoinColumn = $ParentTable.'ID';
      if(!$ChildJoinColumn)
         $ChildJoinColumn = $ParentJoinColumn;

      $Result = "update :_$ParentTable p
                  set p.$ParentColumnName = (
                     select $Aggregate(c.$ChildColumnName)
                     from :_$ChildTable c
                     where p.$ParentJoinColumn = c.$ChildJoinColumn)";
      
      if (!empty($Where)) {
         $Wheres = array();
         $PDO = Gdn::Database()->Connection();
         foreach ($Where as $Column => $Value) {
            $Value = $PDO->quote($Value);
            $Wheres[] = "p.`$Column` = $Value";
         }
         
         $Result .= "\n where ".implode(" and ", $Wheres);
      }
      
      $Result = str_replace(':_', Gdn::Database()->DatabasePrefix, $Result);
      return $Result;
   }
   
   /**
    * Remove html entities from a column in the database.
    * 
    * @param string $Table The name of the table.
    * @param array $Column The column to decode.
    * @param int $Limit The number of records to work on.
    */
   public function HtmlEntityDecode($Table, $Column, $Limit = 100) {
      // Construct a model to save the results.
      $Model = $this->CreateModel($Table);
      
      // Get the data to decode.
      $Data = $this->SQL
         ->Select($Model->PrimaryKey)
         ->Select($Column)
         ->From($Table)
         ->Like($Column, '&%;', 'both')
         ->Limit($Limit)
         ->Get()->ResultArray();
      
      $Result = array();
      $Result['Count'] = count($Data);
      $Result['Complete'] = FALSE;
      $Result['Decoded'] = array();
      $Result['NotDecoded'] = array();
      
      // Loop through each row in the working set and decode the values.
      foreach ($Data as $Row) {
         $Value = $Row[$Column];
         $DecodedValue = HtmlEntityDecode($Value);
         
         $Item = array('From' => $Value, 'To' => $DecodedValue);
         
         if ($Value != $DecodedValue) {
            $Model->SetField($Row[$Model->PrimaryKey], $Column, $DecodedValue);
            $Result['Decoded'] = $Item;
         } else {
            $Result['NotDecoded'] = $Item;
         }
      }
      $Result['Complete'] = $Result['Count'] < $Limit;
      
      return $Result;
   }
   
   public function FixUrlCodes($Table, $Column) {
      $Model = $this->CreateModel($Table);
      
      // Get the data to decode.
      $Data = $this->SQL
         ->Select($Model->PrimaryKey)
         ->Select($Column)
         ->From($Table)
//         ->Like($Column, '&%;', 'both')
//         ->Limit($Limit)
         ->Get()->ResultArray();
      
      foreach ($Data as $Row) {
         $Value = $Row[$Column];
         $Encoded = Gdn_Format::Url($Value);
         
         if (!$Value || $Value != $Encoded) {
            $Model->SetField($Row[$Model->PrimaryKey], $Column, $Encoded);
            Gdn::Controller()->Data['Encoded'][$Row[$Model->PrimaryKey]] = $Encoded;
         }
      }
      
      return array('Complete' => TRUE);
   }
   
   public function ResetBatch($Table, $Key) {
      $Key = "DBA.Range.$Key";
      Gdn::Set($Key, NULL);
   }
   
   public function GetBatch($Table, $Key, $Limit = 10000, $Max = FALSE) {
      $Key = "DBA.Range.$Key";
      
      // See if there is already a range.
      $Current = @unserialize(Gdn::Get($Key,  ''));
      if (!is_array($Current) || !isset($Current['Min']) || !isset($Current['Max'])) {
         list($Current['Min'], $Current['Max']) = $this->PrimaryKeyRange($Table);
         
         if ($Max && $Current['Max'] > $Max) {
            $Current['Max'] = $Max;
         }
      }
      
      if (!isset($Current['To'])) {
         $Current['To'] = $Current['Max'];
      } else {
         $Current['To'] -= $Limit - 1;
      }
      $Current['From'] = $Current['To'] - $Limit;
      Gdn::Set($Key, serialize($Current));
      $Current['Complete'] = $Current['To'] < $Current['Min'];
      
      $Total = $Current['Max'] - $Current['Min'];
      if ($Total > 0) {
         $Complete = $Current['Max'] - $Current['From'];
         
         $Percent = 100 * $Complete / $Total;
         if ($Percent > 100)
            $Percent = 100;
         $Current['Percent'] = round($Percent).'%';
      }
      
      return $Current;
   }
   
   /**
    * Return the min and max values of a table's primary key.
    * 
    * @param string $Table The name of the table to look at.
    * @return array An array in the form (min, max).
    */
   public function PrimaryKeyRange($Table) {
      $Model = $this->CreateModel($Table);
      
      $Data = $this->SQL
         ->Select($Model->PrimaryKey, 'min', 'MinValue')
         ->Select($Model->PrimaryKey, 'max', 'MaxValue')
         ->From($Table)
         ->Get()->FirstRow(DATASET_TYPE_ARRAY);
      
      if ($Data)
         return array($Data['MinValue'], $Data['MaxValue']);
      else
         return array(0, 0);
   }
}