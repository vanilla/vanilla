<?php if (!defined('APPLICATION')) exit();

/**
 * Model base class
 * 
 * This generic model can be instantiated (with the table name it is intended to
 * represent) and used directly, or it can be extended and overridden for more
 * complicated procedures related to different tables.
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
 */

class Gdn_Model extends Gdn_Pluggable {


   /**
    * An object representation of the current working dataset.
    *
    * @var Gdn_DataSet
    */
   public $Data;


   /**
    * Database object
    *
    * @var Gdn_Database The database object.
    */
   public $Database;


   /**
    * The name of the field that stores the insert date for a record. This
    * field will be automatically filled by the model if it exists.
    *
    * @var string
    */
   public $DateInserted = 'DateInserted';


   /**
    * The name of the field that stores the update date for a record. This
    * field will be automatically filled by the model if it exists.
    *
    * @var string
    */
   public $DateUpdated = 'DateUpdated';


   /**
    * The name of the field that stores the id of the user that inserted it.
    * This field will be automatically filled by the model if it exists and
    * @@Session::UserID is a valid integer.
    *
    * @var string
    */
   public $InsertUserID = 'InsertUserID';


   /**
    * The name of the table that this model is intended to represent. The
    * default value assigned to $this->Name will be the name that the
    * model was instantiated with (defined in $this->__construct()).
    *
    * @var string
    */
   public $Name;


   /**
    * The name of the primary key field of this model. The default is 'id'. If
    * $this->DefineSchema() is called, this value will be automatically changed
    * to any primary key discovered when examining the table schema.
    *
    * @var string
    */
   public $PrimaryKey = 'id';


   /**
    * An object that is used to store and examine database schema information
    * related to this model. This object is defined and populated with
    * $this->DefineSchema().
    *
    * @var Gdn_Schema
    */
   public $Schema;
   
   /**
    * Contains the sql driver for the object.
    *
    * @var Gdn_SQLDriver
    */
   public $SQL;


   /**
    * The name of the field that stores the id of the user that updated it.
    * This field will be automatically filled by the model if it exists and
    * @@Session::UserID is a valid integer.
    *
    * @var string
    */
   public $UpdateUserID = 'UpdateUserID';


   /**
    * An object that is used to manage and execute data integrity rules on this
    * object. By default, this object only enforces maxlength, data types, and
    * required fields (defined when $this->DefineSchema() is called).
    *
    * @var Gdn_Validation
    */
   public $Validation;


   /**
    * Class constructor. Defines the related database table name.
    *
    * @param string $Name An optional parameter that allows you to explicitly define the name of
    * the table that this model represents. You can also explicitly set this
    * value with $this->Name.
    */
   public function __construct($Name = '') {
      if ($Name == '')
         $Name = get_class($this);

      $this->Database = Gdn::Database();
      $this->SQL = $this->Database->SQL();
      $this->Validation = new Gdn_Validation();
      $this->Name = $Name;
      $this->PrimaryKey = $Name.'ID';
      parent::__construct();
   }

   /**
    * A overridable function called before the various get queries.
    */
   protected function _BeforeGet() {
   }


   /**
    * Connects to the database and defines the schema associated with
    * $this->Name. Also instantiates and automatically defines
    * $this->Validation.
    *
    */
   public function DefineSchema() {
      if (!isset($this->Schema)) {
         $this->Schema = new Gdn_Schema($this->Name, $this->Database);
         $this->PrimaryKey = $this->Schema->PrimaryKey($this->Name, $this->Database);
         if (is_array($this->PrimaryKey)) {
            //print_r($this->PrimaryKey);
            $this->PrimaryKey = $this->PrimaryKey[0];
         }

         $this->Validation->ApplyRulesBySchema($this->Schema);
      }
   }


   /**
    *  Takes a set of form data ($Form->_PostValues), validates them, and
    * inserts or updates them to the datatabase.
    *
    * @param array $FormPostValues An associative array of $Field => $Value pairs that represent data posted
    * from the form in the $_POST or $_GET collection.
    * @param array $Settings If a custom model needs special settings in order to perform a save, they
    * would be passed in using this variable as an associative array.
    * @return unknown
    */
   public function Save($FormPostValues, $Settings = FALSE) {
      // Define the primary key in this model's table.
      $this->DefineSchema();

      // See if a primary key value was posted and decide how to save
      $PrimaryKeyVal = GetValue($this->PrimaryKey, $FormPostValues, FALSE);
      $Insert = $PrimaryKeyVal === FALSE ? TRUE : FALSE;
      if ($Insert) {
         $this->AddInsertFields($FormPostValues);
      } else {
         $this->AddUpdateFields($FormPostValues);
      }

      // Validate the form posted values
      if ($this->Validate($FormPostValues, $Insert) === TRUE) {
         $Fields = $this->Validation->ValidationFields();
         $Fields = RemoveKeyFromArray($Fields, $this->PrimaryKey); // Don't try to insert or update the primary key
         if ($Insert === FALSE) {
            $this->Update($Fields, array($this->PrimaryKey => $PrimaryKeyVal));
         } else {
            $PrimaryKeyVal = $this->Insert($Fields);
         }
      } else {
         $PrimaryKeyVal = FALSE;
      }
      return $PrimaryKeyVal;
   }
   
   /**
    * Update a row in the database.
    * 
    * @since 2.1
    * @param int $RowID
    * @param array|string $Property
    * @param atom $Value 
    */
   public function SetField($RowID, $Property, $Value = FALSE) {
      if (!is_array($Property))
         $Property = array($Property => $Value);
      
      $this->DefineSchema();      
      $Set = array_intersect_key($Property, $this->Schema->Fields());
      self::SerializeRow($Set);
      $this->SQL->Put($this->Name, $Set, array($this->PrimaryKey => $RowID));
   }
   
   /**
    * Serialize Attributes and Data columns in a row.
    * 
    * @param array $Row
    * @since 2.1 
    */
   public static function SerializeRow(&$Row) {
      foreach ($Row as $Name => &$Value) {
         if (is_array($Value) && in_array($Name, array('Attributes', 'Data')))
            $Value = empty($Value) ? NULL : serialize($Value);
      }
   }


   /**
    * @param unknown_type $Fields
    * @return unknown
    * @todo add doc
    */
   public function Insert($Fields) {
      $Result = FALSE;
      $this->AddInsertFields($Fields);
      if ($this->Validate($Fields, TRUE)) {
         // Strip out fields that aren't in the schema.
         // This is done after validation to allow custom validations to work.
         $SchemaFields = $this->Schema->Fields();
         $Fields = array_intersect_key($Fields, $SchemaFields);
         
         // Quote all of the fields.
         $QuotedFields = array();
         foreach ($Fields as $Name => $Value) {
            if (is_array($Value) && in_array($Name, array('Attributes', 'Data'))) {
               $Value = empty($Value) ? NULL : serialize($Value);
            }

            // Make sure integers are not empty for MySQL strict mode.
            if (empty($Value) && stristr($SchemaFields[$Name]->Type, 'int') !== FALSE) {
               $Value = 0;
            }

            $QuotedFields[$this->SQL->QuoteIdentifier(trim($Name, '`'))] = $Value;
         }

         $Result = $this->SQL->Insert($this->Name, $QuotedFields);
      }
      return $Result;
   }


   /**
    * @param unknown_type $Fields
    * @param unknown_type $Where
    * @param unknown_type $Limit
    * @todo add doc
    */
   public function Update($Fields, $Where = FALSE, $Limit = FALSE) {
      $Result = FALSE;

      // primary key (always included in $Where when updating) might be "required"
      $AllFields = $Fields;
      if (is_array($Where))
         $AllFields = array_merge($Fields, $Where); 
         
      if ($this->Validate($AllFields)) {
         $this->AddUpdateFields($Fields);

         // Strip out fields that aren't in the schema.
         // This is done after validation to allow custom validations to work.
         $SchemaFields = $this->Schema->Fields();
         $Fields = array_intersect_key($Fields, $SchemaFields);

         // Quote all of the fields.
         $QuotedFields = array();
         foreach ($Fields as $Name => $Value) {
            if (is_array($Value) && in_array($Name, array('Attributes', 'Data'))) {
               $Value = empty($Value) ? NULL : serialize($Value);
            }

            // Make sure integers are not empty for MySQL strict mode.
            if (empty($Value) && stristr($SchemaFields[$Name]->Type, 'int') !== FALSE) {
               $Value = 0;
            }
            
            $QuotedFields[$this->SQL->QuoteIdentifier(trim($Name, '`'))] = $Value;
         }

         $Result = $this->SQL->Put($this->Name, $QuotedFields, $Where, $Limit);
      }
      return $Result;
   }


   /**
    * @param unknown_type $Where
    * @param unknown_type $Limit
    * @param unknown_type $ResetData
    * @todo add doc
    */
   public function Delete($Where = '', $Limit = FALSE, $ResetData = FALSE) {
      if(is_numeric($Where))
         $Where = array($this->PrimaryKey => $Where);

      if($ResetData) {
         $Result = $this->SQL->Delete($this->Name, $Where, $Limit);
      } else {
         $Result = $this->SQL->NoReset()->Delete($this->Name, $Where, $Limit);
      }
      return $Result;
   }
   
   /**
    * Filter out any potentially insecure fields before they go to the database.
    * @param array $Data 
    */
   public function FilterForm($Data) {
      $Data = array_diff_key($Data, array('Attributes' => 0, 'DateInserted' => 0, 'InsertUserID' => 0, 'InsertIPAddress' => 0,
            'DateUpdated' => 0, 'UpdateUserID' => 0, 'UpdateIPAddress' => 0));
      return $Data;
   }

   /**
    * Returns an array with only those keys that are actually in the schema.
    *
    * @param array $Data An array of key/value pairs.
    * @return array The filtered array.
    */
   public function FilterSchema($Data) {
      $Fields = $this->Schema->Fields($this->Name);

      $Result = array_intersect_key($Data, $Fields);
      return $Result;
   }


   /**
    * @param unknown_type $OrderFields
    * @param unknown_type $OrderDirection
    * @param unknown_type $Limit
    * @param unknown_type $Offset
    * @return unknown
    * @todo add doc
    */
   public function Get($OrderFields = '', $OrderDirection = 'asc', $Limit = FALSE, $PageNumber = FALSE) {
      $this->_BeforeGet();

      return $this->SQL->Get($this->Name, $OrderFields, $OrderDirection, $Limit, $PageNumber);
   }
   
   /**
    * Returns a count of the # of records in the table
    * @param array $Wheres
    */
   public function GetCount($Wheres = '') {
      $this->_BeforeGet();
      
      $this->SQL
         ->Select('*', 'count', 'Count')
         ->From($this->Name);

      if (is_array($Wheres))
         $this->SQL->Where($Wheres);

      $Data = $this->SQL
         ->Get()
         ->FirstRow();

      return $Data === FALSE ? 0 : $Data->Count;
   }

   /**
    * Get the data from the model based on its primary key.
    *
    * @param mixed $ID The value of the primary key in the database.
    * @param string $DatasetType The format of the result dataset.
    * @return Gdn_DataSet
    */
   public function GetID($ID, $DatasetType = FALSE) {
      $Result = $this->GetWhere(array($this->PrimaryKey => $ID))->FirstRow($DatasetType);
      
      $Fields = array('Attributes', 'Data');
      
      foreach ($Fields as $Field) {
         if (is_array($Result)) {
            if (isset($Result[$Field]) && is_string($Result[$Field])) {
               $Val = unserialize($Result[$Field]);
               if ($Val)
                  $Result[$Field] = $Val; 
               else
                  $Result[$Field] = $Val;
            }               
         } elseif (is_object($Result)) {
            if (isset($Result->$Field) && is_string($Result->$Field)) {
               $Val = unserialize($Result->$Field);
               if ($Val)
                  $Result->$Field = $Val;
               else
                  $Result->$Field = NULL;
            }
         }
      }
      
      return $Result;
   }

   /**
    * Get a dataset for the model with a where filter.
    *
    * @param array $Where A filter suitable for passing to Gdn_SQLDriver::Where().
    * @param string $OrderFields A comma delimited string to order the data.
    * @param string $OrderDirection One of <b>asc</b> or <b>desc</b>
    * @param int $Limit
    * @param int $Offset
    * @return Gdn_DataSet
    */
   public function GetWhere($Where = FALSE, $OrderFields = '', $OrderDirection = 'asc', $Limit = FALSE, $Offset = FALSE) {
      $this->_BeforeGet();
      
      return $this->SQL->GetWhere($this->Name, $Where, $OrderFields, $OrderDirection, $Limit, $Offset);
   }

   /**
    * Returns the $this->Validation->ValidationResults() array.
    *
    * @return unknown
    * @todo add return type
    */
   public function ValidationResults() {
      return $this->Validation->Results();
   }


   /**
    * @param unknown_type $FormPostValues
    * @param unknown_type $Insert
    * @return unknown
    * @todo add doc
    */
   public function Validate($FormPostValues, $Insert = FALSE) {
      $this->DefineSchema();
      return $this->Validation->Validate($FormPostValues, $Insert);
   }


   /**
    * Adds $this->InsertUserID and $this->DateInserted fields to an associative
    * array of fieldname/values if those fields exist on the table being
    * inserted.
    *
    * @param array $Fields The array of fields to add the values to.
    */
   protected function AddInsertFields(&$Fields) {
      $this->DefineSchema();
      if ($this->Schema->FieldExists($this->Name, $this->DateInserted)) {
         if (!isset($Fields[$this->DateInserted]))
            $Fields[$this->DateInserted] = Gdn_Format::ToDateTime();
      }

      $Session = Gdn::Session();
      if ($Session->UserID > 0 && $this->Schema->FieldExists($this->Name, $this->InsertUserID))
         if (!isset($Fields[$this->InsertUserID]))
            $Fields[$this->InsertUserID] = $Session->UserID;

      if ($this->Schema->FieldExists($this->Name, 'InsertIPAddress') && !isset($Fields['InsertIPAddress'])) {
         $Fields['InsertIPAddress'] = Gdn::Request()->IpAddress();
      }
   }


   /**
    * Adds $this->UpdateUserID and $this->DateUpdated fields to an associative
    * array of fieldname/values if those fields exist on the table being
    * updated.
    *
    * @param array $Fields The array of fields to add the values to.
    */
   protected function AddUpdateFields(&$Fields) {
      $this->DefineSchema();
      if ($this->Schema->FieldExists($this->Name, $this->DateUpdated)) {
         if (!isset($Fields[$this->DateUpdated]))
            $Fields[$this->DateUpdated] = Gdn_Format::ToDateTime();
      }

      $Session = Gdn::Session();
      if ($Session->UserID > 0 && $this->Schema->FieldExists($this->Name, $this->UpdateUserID))
         if (!isset($Fields[$this->UpdateUserID]))
            $Fields[$this->UpdateUserID] = $Session->UserID;

      if ($this->Schema->FieldExists($this->Name, 'UpdateIPAddress') && !isset($Fields['UpdateIPAddress'])) {
         $Fields['UpdateIPAddress'] = Gdn::Request()->IpAddress();
      }
   }

	public function SaveToSerializedColumn($Column, $RowID, $Name, $Value = '') {
		
		if (!isset($this->Schema)) $this->DefineSchema();
		// TODO: need to be sure that $this->PrimaryKey is only one primary key
		$FieldName = $this->PrimaryKey;
		
		// Load the existing values
		$Row = $this->SQL
			->Select($Column)
			->From($this->Name)
			->Where($FieldName, $RowID)
			->Get()
			->FirstRow();
		
		if(!$Row) throw new Exception(T('ErrorRecordNotFound'));
		$Values = Gdn_Format::Unserialize($Row->$Column);
		
		if (is_string($Values) && $Values != '')
			throw new Exception(T('Serialized column failed to be unserialized.'));
		
		if (!is_array($Values)) $Values = array();
		if (!is_array($Name)) $Name = array($Name => $Value); // Assign the new value(s)

		$Values = Gdn_Format::Serialize(array_merge($Values, $Name));
		
		// Save the values back to the db
		return $this->SQL
			->From($this->Name)
			->Where($FieldName, $RowID)
			->Set($Column, $Values)
			->Put();
	}
    
    
	public function SetProperty($RowID, $Property, $ForceValue = FALSE) {
		if (!isset($this->Schema)) $this->DefineSchema();
		$PrimaryKey = $this->PrimaryKey;
        
		if ($ForceValue !== FALSE) {
            $Value = $ForceValue;
		} else {
            $Row = $this->GetID($RowID);
            $Value = ($Row->$Property == '1' ? '0' : '1');
		}
		$this->SQL
            ->Update($this->Name)
            ->Set($Property, $Value)
            ->Where($PrimaryKey, $RowID)
            ->Put();
		return $Value;
   }
   
   /**
    * Get something from $Record['Attributes'] by dot-formatted key
    * 
    * Pass record byref
    * 
    * @param array $Record
    * @param string $Attribute
    * @param mixed $Default Optional.
    * @return mixed
    */
   public static function GetRecordAttribute(&$Record, $Attribute, $Default = NULL) {
      $RV = "Attributes.{$Attribute}";
      return GetValueR($RV, $Record, $Default);
   }
   
   /**
    * Set something on $Record['Attributes'] by dot-formatted key
    * 
    * Pass record byref
    * 
    * @param array $Record
    * @param string $Attribute
    * @param mixed $Value
    * @return mixed 
    */
   public static function SetRecordAttribute(&$Record, $Attribute, $Value) {
      if (!array_key_exists('Attributes', $Record))
         $Record['Attributes'] = array();
      
      if (!is_array($Record['Attributes'])) return NULL;
      
      $Work = &$Record['Attributes'];
      $Parts = explode('.', $Attribute);
      while ($Part = array_shift($Parts)) {
         $SetValue = sizeof($Parts) ? array() : $Value;
         $Work[$Part] = $SetValue;
         $Work = &$Work[$Part];
      }
      
      return $Value;
   }
   
}

