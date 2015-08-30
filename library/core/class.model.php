<?php
/**
 * Gdn_Model.
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

/**
 * Model base class.
 *
 * This generic model can be instantiated (with the table name it is intended to
 * represent) and used directly, or it can be extended and overridden for more
 * complicated procedures related to different tables.
 */
class Gdn_Model extends Gdn_Pluggable {

    /**  @var Gdn_DataSet An object representation of the current working dataset. */
    public $Data;

    /**  @var Gdn_Database Database object. */
    public $Database;

    /**
     * @var string The name of the field that stores the insert date for a record. This
     * field will be automatically filled by the model if it exists.
     */
    public $DateInserted = 'DateInserted';

    /**
     * @var string The name of the field that stores the update date for a record. This
     * field will be automatically filled by the model if it exists.
     */
    public $DateUpdated = 'DateUpdated';

    /**
     * @var string The name of the field that stores the id of the user that inserted it.
     * This field will be automatically filled by the model if it exists and
     * @@Session::UserID is a valid integer.
     */
    public $InsertUserID = 'InsertUserID';

    /**
     * @var string The name of the table that this model is intended to represent. The
     * default value assigned to $this->Name will be the name that the
     * model was instantiated with (defined in $this->__construct()).
     */
    public $Name;

    /**
     * @var stringThe name of the primary key field of this model. The default is 'id'. If
     * $this->DefineSchema() is called, this value will be automatically changed
     * to any primary key discovered when examining the table schema.
     */
    public $PrimaryKey = 'id';

    /**
     * @var Gdn_Schema An object that is used to store and examine database schema information
     * related to this model. This object is defined and populated with $this->DefineSchema().
     */
    public $Schema;

    /** @var Gdn_SQLDriver Contains the sql driver for the object. */
    public $SQL;

    /**
     * @var string The name of the field that stores the id of the user that updated it.
     * This field will be automatically filled by the model if it exists and @@Session::UserID is a valid integer.
     */
    public $UpdateUserID = 'UpdateUserID';

    /**
     * @var Gdn_Validation An object that is used to manage and execute data integrity rules on this
     * object. By default, this object only enforces maxlength, data types, and
     * required fields (defined when $this->DefineSchema() is called).
     */
    public $Validation;


    /**
     * Class constructor. Defines the related database table name.
     *
     * @param string $Name An optional parameter that allows you to explicitly define the name of
     * the table that this model represents. You can also explicitly set this value with $this->Name.
     */
    public function __construct($Name = '') {
        if ($Name == '') {
            $Name = get_class($this);
        }

        $this->Database = Gdn::database();
        $this->SQL = $this->Database->SQL();
        $this->Validation = new Gdn_Validation();
        $this->Name = $Name;
        $this->PrimaryKey = $Name.'ID';
        parent::__construct();
    }

    /**
     * A overridable function called before the various get queries.
     */
    protected function _beforeGet() {
    }

    /**
     * Take all of the values that aren't in the schema and put them into the attributes column.
     *
     * @param array $Data
     * @param string $Name
     * @return array
     */
    protected function collapseAttributes($Data, $Name = 'Attributes') {
        $this->defineSchema();

        $Row = array_intersect_key($Data, $this->Schema->fields());
        $Attributes = array_diff_key($Data, $Row);

        TouchValue($Name, $Row, array());
        if (isset($Row[$Name]) && is_array($Row[$Name])) {
            $Row[$Name] = array_merge($Row[$Name], $Attributes);
        } else {
            $Row[$Name] = $Attributes;
        }
        return $Row;
    }

    /**
     * Expand all of the values in the attributes column so they become part of the row.
     *
     * @param array $Row
     * @param string $Name
     * @return array
     * @since 2.2
     */
    protected function expandAttributes($Row, $Name = 'Attributes') {
        if (isset($Row[$Name])) {
            $Attributes = $Row[$Name];
            unset($Row[$Name]);

            if (is_string($Attributes)) {
                $Attributes = @unserialize($Attributes);
            }

            if (is_array($Attributes)) {
                $Row = array_merge($Row, $Attributes);
            }
        }
        return $Row;
    }

    /**
     * Connects to the database and defines the schema associated with
     * $this->Name. Also instantiates and automatically defines
     * $this->Validation.
     * @return Gdn_Schema Returns the schema for this model.
     */
    public function defineSchema() {
        if (!isset($this->Schema)) {
            $this->Schema = new Gdn_Schema($this->Name, $this->Database);
            $this->PrimaryKey = $this->Schema->primaryKey($this->Name, $this->Database);
            if (is_array($this->PrimaryKey)) {
                //print_r($this->PrimaryKey);
                $this->PrimaryKey = $this->PrimaryKey[0];
            }

            $this->Validation->setSchema($this->Schema);
        }
        return $this->Schema;
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
    public function save($FormPostValues, $Settings = false) {
        // Define the primary key in this model's table.
        $this->defineSchema();

        // See if a primary key value was posted and decide how to save
        $PrimaryKeyVal = val($this->PrimaryKey, $FormPostValues, false);
        $Insert = $PrimaryKeyVal == false ? true : false;
        if ($Insert) {
            $this->addInsertFields($FormPostValues);
        } else {
            $this->addUpdateFields($FormPostValues);
        }

        // Validate the form posted values
        if ($this->validate($FormPostValues, $Insert) === true) {
            $Fields = $this->Validation->validationFields();
            $Fields = removeKeyFromArray($Fields, $this->PrimaryKey); // Don't try to insert or update the primary key
            if ($Insert === false) {
                $this->update($Fields, array($this->PrimaryKey => $PrimaryKeyVal));
            } else {
                $PrimaryKeyVal = $this->insert($Fields);
            }
        } else {
            $PrimaryKeyVal = false;
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
    public function setField($RowID, $Property, $Value = false) {
        if (!is_array($Property)) {
            $Property = array($Property => $Value);
        }

        $this->defineSchema();
        $Set = array_intersect_key($Property, $this->Schema->fields());
        self::serializeRow($Set);
        $this->SQL->put($this->Name, $Set, array($this->PrimaryKey => $RowID));
    }

    /**
     * Serialize Attributes and Data columns in a row.
     *
     * @param array $Row
     * @since 2.1
     */
    public static function serializeRow(&$Row) {
        foreach ($Row as $Name => &$Value) {
            if (is_array($Value) && in_array($Name, array('Attributes', 'Data'))) {
                $Value = empty($Value) ? null : serialize($Value);
            }
        }
    }


    /**
     *
     *
     * @param array $Fields
     * @return bool
     */
    public function insert($Fields) {
        $Result = false;
        $this->addInsertFields($Fields);
        if ($this->validate($Fields, true)) {
            // Strip out fields that aren't in the schema.
            // This is done after validation to allow custom validations to work.
            $SchemaFields = $this->Schema->fields();
            $Fields = array_intersect_key($Fields, $SchemaFields);

            // Quote all of the fields.
            $QuotedFields = array();
            foreach ($Fields as $Name => $Value) {
                if (is_array($Value) && in_array($Name, array('Attributes', 'Data'))) {
                    $Value = empty($Value) ? null : serialize($Value);
                }

                $QuotedFields[$this->SQL->quoteIdentifier(trim($Name, '`'))] = $Value;
            }

            $Result = $this->SQL->insert($this->Name, $QuotedFields);
        }
        return $Result;
    }


    /**
     *
     *
     * @param array $Fields
     * @param array $Where
     * @param array $Limit
     * @return Gdn_Dataset
     */
    public function update($Fields, $Where = false, $Limit = false) {
        $Result = false;

        // primary key (always included in $Where when updating) might be "required"
        $AllFields = $Fields;
        if (is_array($Where)) {
            $AllFields = array_merge($Fields, $Where);
        }

        if ($this->validate($AllFields)) {
            $this->addUpdateFields($Fields);

            // Strip out fields that aren't in the schema.
            // This is done after validation to allow custom validations to work.
            $SchemaFields = $this->Schema->fields();
            $Fields = array_intersect_key($Fields, $SchemaFields);

            // Quote all of the fields.
            $QuotedFields = array();
            foreach ($Fields as $Name => $Value) {
                if (is_array($Value) && in_array($Name, array('Attributes', 'Data'))) {
                    $Value = empty($Value) ? null : serialize($Value);
                }

                $QuotedFields[$this->SQL->quoteIdentifier(trim($Name, '`'))] = $Value;
            }

            $Result = $this->SQL->put($this->Name, $QuotedFields, $Where, $Limit);
        }
        return $Result;
    }


    /**
     *
     *
     * @param unknown_type $Where
     * @param unknown_type $Limit
     * @param unknown_type $ResetData
     * @return Gdn_Dataset
     */
    public function delete($Where = '', $Limit = false, $ResetData = false) {
        if (is_numeric($Where)) {
            $Where = array($this->PrimaryKey => $Where);
        }

        if ($ResetData) {
            $Result = $this->SQL->delete($this->Name, $Where, $Limit);
        } else {
            $Result = $this->SQL->noReset()->delete($this->Name, $Where, $Limit);
        }
        return $Result;
    }

    /**
     * Filter out any potentially insecure fields before they go to the database.
     *
     * @param array $Data
     * @return array
     */
    public function filterForm($Data) {
        $Data = array_diff_key($Data, array(
            'Attributes' => 0,
            'DateInserted' => 0,
            'InsertUserID' => 0,
            'InsertIPAddress' => 0,
            'CheckBoxes' => 0,
            'DateUpdated' => 0,
            'UpdateUserID' => 0,
            'UpdateIPAddress' => 0,
            'DeliveryMethod' => 0,
            'DeliveryType' => 0,
            'OK' => 0,
            'TransientKey' => 0,
            'hpt' => 0
        ));
        return $Data;
    }

    /**
     * Returns an array with only those keys that are actually in the schema.
     *
     * @param array $Data An array of key/value pairs.
     * @return array The filtered array.
     */
    public function filterSchema($Data) {
        $Fields = $this->Schema->fields($this->Name);

        $Result = array_intersect_key($Data, $Fields);
        return $Result;
    }


    /**
     *
     *
     * @param string $OrderFields
     * @param string $OrderDirection
     * @param int|bool $Limit
     * @param int|bool $Offset
     * @return Gdn_Dataset
     */
    public function get($OrderFields = '', $OrderDirection = 'asc', $Limit = false, $PageNumber = false) {
        $this->_beforeGet();

        return $this->SQL->get($this->Name, $OrderFields, $OrderDirection, $Limit, $PageNumber);
    }

    /**
     * Returns a count of the # of records in the table.
     *
     * @param array $Wheres
     * @return Gdn_Dataset
     */
    public function getCount($Wheres = '') {
        $this->_beforeGet();

        $this->SQL
            ->select('*', 'count', 'Count')
            ->from($this->Name);

        if (is_array($Wheres)) {
            $this->SQL->Where($Wheres);
        }

        $Data = $this->SQL
            ->get()
            ->firstRow();

        return $Data === false ? 0 : $Data->Count;
    }

    /**
     * Get the data from the model based on its primary key.
     *
     * @param mixed $ID The value of the primary key in the database.
     * @param string $DatasetType The format of the result dataset.
     * @param array $Options options to pass to the database.
     * @return array|object
     *
     * @since 2.3 Added the $Options parameter.
     */
    public function getID($ID, $DatasetType = false, $Options = array()) {
        $this->options($Options);
        $Result = $this->getWhere(array($this->PrimaryKey => $ID))->firstRow($DatasetType);

        $Fields = array('Attributes', 'Data');

        foreach ($Fields as $Field) {
            if (is_array($Result)) {
                if (isset($Result[$Field]) && is_string($Result[$Field])) {
                    $Val = unserialize($Result[$Field]);
                    if ($Val) {
                        $Result[$Field] = $Val;
                    } else {
                        $Result[$Field] = $Val;
                    }
                }
            } elseif (is_object($Result)) {
                if (isset($Result->$Field) && is_string($Result->$Field)) {
                    $Val = unserialize($Result->$Field);
                    if ($Val) {
                        $Result->$Field = $Val;
                    } else {
                        $Result->$Field = null;
                    }
                }
            }
        }

        return $Result;
    }

    /**
     * Get a dataset for the model with a where filter.
     *
     * @param array|bool $Where A filter suitable for passing to Gdn_SQLDriver::Where().
     * @param string $OrderFields A comma delimited string to order the data.
     * @param string $OrderDirection One of <b>asc</b> or <b>desc</b>
     * @param int|bool $Limit
     * @param int|bool $Offset
     * @return Gdn_DataSet
     */
    public function getWhere($Where = false, $OrderFields = '', $OrderDirection = 'asc', $Limit = false, $Offset = false) {
        $this->_beforeGet();
        return $this->SQL->getWhere($this->Name, $Where, $OrderFields, $OrderDirection, $Limit, $Offset);
    }

    /**
     * Returns the $this->Validation->ValidationResults() array.
     *
     * @return array
     */
    public function validationResults() {
        return $this->Validation->results();
    }


    /**
     * @param array $FormPostValues
     * @param bool $Insert
     * @return bool
     */
    public function validate($FormPostValues, $Insert = false) {
        $this->defineSchema();
        return $this->Validation->validate($FormPostValues, $Insert);
    }


    /**
     * Adds $this->InsertUserID and $this->DateInserted fields to an associative
     * array of fieldname/values if those fields exist on the table being
     * inserted.
     *
     * @param array $Fields The array of fields to add the values to.
     */
    protected function addInsertFields(&$Fields) {
        $this->defineSchema();
        if ($this->Schema->fieldExists($this->Name, $this->DateInserted)) {
            if (!isset($Fields[$this->DateInserted])) {
                $Fields[$this->DateInserted] = Gdn_Format::toDateTime();
            }
        }

        $Session = Gdn::session();
        if ($Session->UserID > 0 && $this->Schema->fieldExists($this->Name, $this->InsertUserID)) {
            if (!isset($Fields[$this->InsertUserID])) {
                $Fields[$this->InsertUserID] = $Session->UserID;
            }
        }

        if ($this->Schema->fieldExists($this->Name, 'InsertIPAddress') && !isset($Fields['InsertIPAddress'])) {
            $Fields['InsertIPAddress'] = Gdn::request()->ipAddress();
        }
    }


    /**
     * Adds $this->UpdateUserID and $this->DateUpdated fields to an associative
     * array of fieldname/values if those fields exist on the table being updated.
     *
     * @param array $Fields The array of fields to add the values to.
     */
    protected function addUpdateFields(&$Fields) {
        $this->defineSchema();
        if ($this->Schema->fieldExists($this->Name, $this->DateUpdated)) {
            if (!isset($Fields[$this->DateUpdated])) {
                $Fields[$this->DateUpdated] = Gdn_Format::toDateTime();
            }
        }

        $Session = Gdn::session();
        if ($Session->UserID > 0 && $this->Schema->fieldExists($this->Name, $this->UpdateUserID)) {
            if (!isset($Fields[$this->UpdateUserID])) {
                $Fields[$this->UpdateUserID] = $Session->UserID;
            }
        }

        if ($this->Schema->FieldExists($this->Name, 'UpdateIPAddress') && !isset($Fields['UpdateIPAddress'])) {
            $Fields['UpdateIPAddress'] = Gdn::request()->ipAddress();
        }
    }

    /**
     * Gets/sets an option on the object.
     *
     * @param string|array $Key The key of the option.
     * @param mixed $Value The value of the option or not specified just to get the current value.
     * @return mixed The value of the option or $this if $Value is specified.
     * @since 2.3
     */
    public function options($Key, $Value = null) {
        if (is_array($Key)) {
            foreach ($Key as $K => $V) {
                $this->SQL->options($K, $V);
            }
        } else {
            $this->SQL->options($Key, $Value);
        }
        return $this;
    }

    /**
     *
     *
     * @param string $Column
     * @param int $RowID
     * @param string $Name
     * @param string $Value
     * @return bool|Gdn_DataSet|object|string
     * @throws Exception
     */
    public function saveToSerializedColumn($Column, $RowID, $Name, $Value = '') {

        if (!isset($this->Schema)) {
            $this->defineSchema();
        }
        // TODO: need to be sure that $this->PrimaryKey is only one primary key
        $FieldName = $this->PrimaryKey;

        // Load the existing values
        $Row = $this->SQL
            ->select($Column)
            ->from($this->Name)
            ->where($FieldName, $RowID)
            ->get()
            ->firstRow();

        if (!$Row) {
            throw new Exception(T('ErrorRecordNotFound'));
        }
        $Values = Gdn_Format::unserialize($Row->$Column);

        if (is_string($Values) && $Values != '') {
            throw new Exception(T('Serialized column failed to be unserialized.'));
        }

        if (!is_array($Values)) {
            $Values = array();
        }
        if (!is_array($Name)) {
            // Assign the new value(s)
            $Name = array($Name => $Value);
        }

        $Values = Gdn_Format::serialize(array_merge($Values, $Name));

        // Save the values back to the db
        return $this->SQL
            ->from($this->Name)
            ->where($FieldName, $RowID)
            ->set($Column, $Values)
            ->put();
    }

    /**
     *
     *
     * @param int $RowID
     * @param string $Property
     * @param bool $ForceValue
     * @return bool|string
     * @throws Exception
     */
    public function setProperty($RowID, $Property, $ForceValue = false) {
        if (!isset($this->Schema)) {
            $this->defineSchema();
        }
        $PrimaryKey = $this->PrimaryKey;

        if ($ForceValue !== false) {
            $Value = $ForceValue;
        } else {
            $Row = $this->getID($RowID);
            $Value = ($Row->$Property == '1' ? '0' : '1');
        }
        $this->SQL
            ->update($this->Name)
            ->set($Property, $Value)
            ->where($PrimaryKey, $RowID)
            ->put();
        return $Value;
    }

    /**
     * Get something from $Record['Attributes'] by dot-formatted key.
     *
     * Pass record byref.
     *
     * @param array $Record
     * @param string $Attribute
     * @param mixed $Default Optional.
     * @return mixed
     */
    public static function getRecordAttribute(&$Record, $Attribute, $Default = null) {
        $RV = "Attributes.{$Attribute}";
        return valr($RV, $Record, $Default);
    }

    /**
     * Set something on $Record['Attributes'] by dot-formatted key.
     *
     * Pass record byref.
     *
     * @param array $Record
     * @param string $Attribute
     * @param mixed $Value
     * @return mixed
     */
    public static function setRecordAttribute(&$Record, $Attribute, $Value) {
        if (!array_key_exists('Attributes', $Record)) {
            $Record['Attributes'] = array();
        }

        if (!is_array($Record['Attributes'])) {
            return null;
        }

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
