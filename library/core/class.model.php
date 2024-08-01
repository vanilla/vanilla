<?php
/**
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\EventManager;
use Vanilla\Events\LegacyDirtyRecordTrait;
use Vanilla\Formatting\DateTimeFormatter;
use Webmozart\Assert\Assert;

/**
 * Model base class.
 *
 * This generic model can be instantiated (with the table name it is intended to
 * represent) and used directly, or it can be extended and overridden for more
 * complicated procedures related to different tables.
 */
class Gdn_Model extends Gdn_Pluggable
{
    use LegacyDirtyRecordTrait;

    /** @var string[] These are text fields that extra values are collapsed into as JSON. */
    private const COLLAPSEABLE_FIELDS = ["Attributes", "Data"];

    /**  @var Gdn_DataSet An object representation of the current working dataset. */
    public $Data;

    /**  @var Gdn_Database Database object. */
    public $Database;

    /**
     * @var string The name of the field that stores the insert date for a record. This
     * field will be automatically filled by the model if it exists.
     */
    public $DateInserted = "DateInserted";

    /**
     * @var string The name of the field that stores the update date for a record. This
     * field will be automatically filled by the model if it exists.
     */
    public $DateUpdated = "DateUpdated";

    /** @var EventManager */
    private $eventManager;

    /**
     * @var array The fields that should be filtered out via {@link Gdn_Model::filterForm()}.
     */
    protected $filterFields;

    /**
     * @var string The name of the field that stores the id of the user that inserted it.
     * This field will be automatically filled by the model if it exists and
     * @@Session::UserID is a valid integer.
     */
    public $InsertUserID = "InsertUserID";

    /**
     * @var string The name of the table that this model is intended to represent. The
     * default value assigned to $this->Name will be the name that the
     * model was instantiated with (defined in $this->__construct()).
     */
    public $Name;

    /**
     * @var string The name of the primary key field of this model. The default is 'id'. If
     * $this->defineSchema() is called, this value will be automatically changed
     * to any primary key discovered when examining the table schema.
     */
    public $PrimaryKey = "id";

    /**
     * @var Gdn_Schema An object that is used to store and examine database schema information
     * related to this model. This object is defined and populated with $this->defineSchema().
     */
    public $Schema;

    /** @var Gdn_SQLDriver Contains the sql driver for the object. */
    public $SQL;

    /**
     * @var string The name of the field that stores the id of the user that updated it.
     * This field will be automatically filled by the model if it exists and @@Session::UserID is a valid integer.
     */
    public $UpdateUserID = "UpdateUserID";

    /**
     * @var Gdn_Validation An object that is used to manage and execute data integrity rules on this
     * object. By default, this object only enforces maxlength, data types, and
     * required fields (defined when $this->defineSchema() is called).
     */
    public $Validation;

    /**
     * Class constructor. Defines the related database table name.
     *
     * @param string $name Optionally define the name of the table that this model represents.
     * You can also explicitly set this value with $this->Name.
     * @param Gdn_Validation $validation The validation dependency.
     * If a validation object is not passed in the constructor then one will be created.
     */
    public function __construct($name = "", Gdn_Validation $validation = null)
    {
        if ($name == "") {
            $name = get_class($this);
        }

        $this->Database = Gdn::database();
        $this->SQL = $this->Database->sql();
        if ($validation === null) {
            $validation = Gdn::getContainer()->get(Gdn_Validation::class);
        }
        $this->Validation = $validation;
        $this->Name = $name;
        $this->PrimaryKey = $name . "ID";
        $this->filterFields = [
            "Attributes" => 0,
            "DateInserted" => 0,
            "InsertUserID" => 0,
            "InsertIPAddress" => 0,
            "CheckBoxes" => 0,
            "DateUpdated" => 0,
            "UpdateUserID" => 0,
            "UpdateIPAddress" => 0,
            "DeliveryMethod" => 0,
            "DeliveryType" => 0,
            "OK" => 0,
            "TransientKey" => 0,
            "hpt" => 0,
        ];

        $this->eventManager = Gdn::getContainer()->get(EventManager::class);

        parent::__construct();
    }

    /**
     * Get the table name.
     *
     * @return string
     */
    public function getTableName(): string
    {
        return $this->Name;
    }

    /**
     * Get a clean SQL driver instance.
     *
     * @return \Gdn_SQLDriver
     */
    public function createSql(): \Gdn_SQLDriver
    {
        return $this->Database->createSql();
    }

    /**
     * Add one or more filter field names to the list of fields that will be removed during save.
     *
     * @param string|array $field Either a field name or an array of field names to filter.
     * @return Gdn_Model Returns $this for chaining.
     */
    public function addFilterField($field)
    {
        if (is_array($field)) {
            $this->filterFields = array_replace($this->filterFields, array_fill_keys($field, 0));
        } else {
            $this->filterFields[$field] = 0;
        }
        return $this;
    }

    /**
     * A overridable function called before the various get queries.
     */
    protected function _beforeGet()
    {
    }

    /**
     * Get the configured event manager instance.
     *
     * @return EventManager
     */
    public function getEventManager(): EventManager
    {
        return $this->eventManager;
    }

    /**
     * Get the validation object used to validate data upon saving.
     *
     * @return Gdn_Validation Returns the validation object.
     */
    public function getValidation(): Gdn_Validation
    {
        return $this->Validation;
    }

    /**
     * Set the validation object used to validate data upon saving.
     *
     * @param Gdn_Validation $Validation The new validation object.
     * @return $this
     */
    public function setValidation(Gdn_Validation $Validation)
    {
        $this->Validation = $Validation;
        return $this;
    }

    /**
     * Returns an array of fields that aren't part of the Schema & would be bundled up in the "Attributes" field.
     *
     * @param array $formPostValues
     * @return array
     */
    protected function getAttributes(array $formPostValues): array
    {
        $this->defineSchema();
        $row = array_intersect_key($formPostValues, $this->Schema->fields());
        return array_diff_key($formPostValues, $row);
    }

    /**
     * Take every values that aren't in the schema and put them into the "Attributes" column.
     * DISCLAIMER: This function is unaware of any pre-existing "Attributes" values at the record's level.
     * If $data doesn't have any extra values from the schema, this function will return an empty array.
     *
     * @param array $data
     * @param string $name
     * @return array
     */
    protected function collapseAttributes($data, $name = "Attributes")
    {
        $this->defineSchema();

        $row = array_intersect_key($data, $this->Schema->fields());
        $attributes = array_diff_key($data, $row);

        touchValue($name, $row, []);
        if (isset($row[$name]) && is_array($row[$name])) {
            $row[$name] = array_merge($row[$name], $attributes);
        } else {
            $row[$name] = $attributes;
        }
        return $row;
    }

    /**
     * Expand all of the values in the attributes column so they become part of the row.
     *
     * @param array $row
     * @param string $name
     * @return array
     * @since 2.2
     */
    protected function expandAttributes($row, $name = "Attributes")
    {
        if (isset($row[$name])) {
            $attributes = $row[$name];
            unset($row[$name]);

            if (is_string($attributes)) {
                $attributes = dbdecode($attributes);
            }

            if (is_array($attributes)) {
                $row = array_merge($row, $attributes);
            }
        }
        return $row;
    }

    /**
     * Connects to the database and defines the schema associated with $this->Name.
     *
     * Also instantiates and automatically defines $this->Validation.
     *
     * @return Gdn_Schema Returns the schema for this model.
     */
    public function defineSchema()
    {
        if (!isset($this->Schema)) {
            $this->Schema = new Gdn_Schema($this->Name, $this->Database);
            $this->PrimaryKey = $this->Schema->primaryKey($this->Name, $this->Database);
            if (is_array($this->PrimaryKey)) {
                $this->PrimaryKey = $this->PrimaryKey[0];
            }

            // Since validation happens before collapsable fields are collapsed, remove the length validation on them.
            foreach (self::COLLAPSEABLE_FIELDS as $collapsableField) {
                $field = $this->Schema->getField($collapsableField);
                if ($field) {
                    // 10 years of code is expecting this be an "empty" string to represent a null/empty value.
                    $field->Length = "";
                    // This one is new and things are aware it can be null.
                    unset($field->ByteLength);
                }
            }

            $this->Validation->setSchema($this->Schema);
        }
        return $this->Schema;
    }

    /**
     * Get all of the field names that will be filtered out during save.
     *
     * @return array Returns an array of field names.
     */
    public function getFilterFields()
    {
        return array_keys($this->filterFields);
    }

    /**
     * Get the default page size limit.
     *
     * @return int
     */
    public function getDefaultLimit()
    {
        return 30;
    }

    /**
     * Remove one or more fields from the filter field array.
     *
     * @param string|array $field One or more field names to remove.
     * @return Gdn_Model Returns $this for chaining.
     */
    public function removeFilterField($field)
    {
        if (is_array($field)) {
            $this->filterFields = array_diff_key($this->filterFields, array_fill_keys($field, 0));
        } else {
            unset($this->filterFields[$field]);
        }
        return $this;
    }

    /**
     * Takes a set of form data ($Form->_PostValues), validates them, and
     * inserts or updates them to the datatabase.
     *
     * @param array $formPostValues An associative array of $Field => $Value pairs that represent data posted
     * from the form in the $_POST or $_GET collection.
     * @param array|false $settings If a custom model needs special settings in order to perform a save, they
     * would be passed in using this variable as an associative array.
     * @return mixed
     */
    public function save($formPostValues, $settings = false)
    {
        // Define the primary key in this model's table.
        $this->defineSchema();

        // See if a primary key value was posted and decide how to save
        $primaryKeyVal = val($this->PrimaryKey, $formPostValues, false);
        $insert = $primaryKeyVal == false ? true : false;
        if ($insert) {
            $this->addInsertFields($formPostValues);
        } else {
            $this->addUpdateFields($formPostValues);
        }

        // Validate the form posted values
        if ($this->validate($formPostValues, $insert) === true) {
            $fields = $this->Validation->validationFields();
            $fields = $this->coerceData($fields, false);
            unset($fields[$this->PrimaryKey]); // Don't try to insert or update the primary key
            if ($insert === false) {
                $this->update($fields, [$this->PrimaryKey => $primaryKeyVal]);
            } else {
                $primaryKeyVal = $this->insert($fields);
            }
        } else {
            $primaryKeyVal = false;
        }
        return $primaryKeyVal;
    }

    /**
     * Update a row in the database.
     *
     * @param int $rowID
     * @param array|string $property
     * @param mixed $value
     */
    public function setField($rowID, $property, $value = false)
    {
        if (!is_array($property)) {
            $property = [$property => $value];
        }

        $this->defineSchema();
        $set = array_intersect_key($property, $this->Schema->fields());
        self::serializeRow($set);
        $this->SQL->put($this->Name, $set, [$this->PrimaryKey => $rowID]);
        $this->onUpdate();
    }

    /**
     * Set the array of filter field names.
     *
     * @param array $fields An array of field names.
     * @return Gdn_Model Returns $this for chaining.
     */
    public function setFilterFields(array $fields)
    {
        $this->filterFields = array_fill_keys($fields, 0);
        return $this;
    }

    /**
     * Serialize Attributes and Data columns in a row.
     *
     * @param array $row
     */
    public static function serializeRow(&$row)
    {
        foreach ($row as $name => &$value) {
            if (is_array($value) && in_array($name, self::COLLAPSEABLE_FIELDS)) {
                $value = empty($value) ? null : dbencode($value);
            }
        }
    }

    /**
     * Strip database prefixes off a where clause.
     *
     * This method is mainly for backwards compatibility with model methods that demand a database prefix.
     *
     * @param array|false $where The where to strip.
     * @return array Returns a where array without database prefixes.
     */
    protected function stripWherePrefixes($where)
    {
        if (empty($where)) {
            return [];
        }

        $result = [];
        foreach ((array) $where as $key => $value) {
            $parts = explode(".", $key);
            $key = $parts[count($parts) === 1 ? 0 : 1];
            $result[$key] = $value;
        }
        return $result;
    }

    /**
     * Split a where array into where values and options.
     *
     * Some model methods don't have an options parameter so their options are carried in the where clause.
     * This method splits those options out
     *
     * @param array|false $where The where clause.
     * @param array $options An array of option keys to default values.
     * @return array Returns an array in the form `[$where, $options]`.
     */
    protected function splitWhere($where, array $options)
    {
        if (empty($where)) {
            return [[], $options];
        }

        $result = [];
        foreach ($where as $key => $value) {
            if (array_key_exists($key, $options)) {
                $options[$key] = $value;
            } else {
                $result[$key] = $value;
            }
        }

        return [$result, $options];
    }

    /**
     *
     *
     * @param array $fields
     * @return bool
     */
    public function insert($fields)
    {
        $result = false;
        $this->addInsertFields($fields);
        if ($this->validate($fields, true)) {
            // Strip out fields that aren't in the schema.
            // This is done after validation to allow custom validations to work.
            $schemaFields = $this->Schema->fields();
            $fields = array_intersect_key($fields, $schemaFields);

            // Quote all of the fields.
            $quotedFields = [];
            foreach ($fields as $name => $value) {
                if (is_array($value) && in_array($name, self::COLLAPSEABLE_FIELDS)) {
                    $value = empty($value) ? null : dbencode($value);
                }

                $quotedFields[$name] = $value;
            }

            $result = $this->SQL->insert($this->Name, $quotedFields);
            $this->onUpdate();
        }
        return $result;
    }

    /**
     * Update a record or records.
     *
     * @param array $fields
     * @param array|false $where
     * @param int|false $limit
     * @return Gdn_Dataset
     */
    public function update($fields, $where = false, $limit = false)
    {
        $result = false;

        // primary key (always included in $Where when updating) might be "required"
        $allFields = $fields;
        if (is_array($where)) {
            $allFields = array_merge($fields, $where);
        }

        if ($this->validate($allFields)) {
            $this->addUpdateFields($fields);

            // Strip out fields that aren't in the schema.
            // This is done after validation to allow custom validations to work.
            $schemaFields = $this->Schema->fields();
            $fields = array_intersect_key($fields, $schemaFields);

            // Quote all of the fields.
            $quotedFields = [];
            foreach ($fields as $name => $value) {
                if (is_array($value) && in_array($name, self::COLLAPSEABLE_FIELDS)) {
                    $value = empty($value) ? null : dbencode($value);
                }

                $quotedFields[$name] = $value;
            }

            $result = $this->SQL->put($this->Name, $quotedFields, $where, $limit);
            $this->onUpdate();
        }
        return $result;
    }

    /**
     * Delete records from a table.
     *
     * @param array|int $where The where clause to delete or an integer value.
     * @param array $options An array of options to control the delete.
     *
     *  - limit: A limit to the number of records to delete.
     *  - reset: Deprecated. Whether or not to reset this SQL statement after the delete. Defaults to false.
     * @return int|false Returns the number of deleted records or **false** on failure.
     */
    public function delete($where = [], $options = [])
    {
        if (is_numeric($where)) {
            deprecated("Gdn_Model->delete(int)", "Gdn_Model->deleteID()");
            $where = [$this->PrimaryKey => $where];
        }

        if (is_numeric($options)) {
            deprecated('The $limit parameter is deprecated in Gdn_Model->delete(). Use the limit option.');
            $options = ["limit" => $options];
        } elseif (!is_array($options)) {
            $options = [];
        }
        $options += ["limit" => null];

        $result = $this->SQL->delete($this->Name, $where, $options["limit"]);
        $this->onUpdate();
        return $result;
    }

    /**
     * Delete a record by primary key.
     *
     * @param mixed $id The primary key value of the record to delete.
     * @param array $options An array of options to affect the delete behaviour. Reserved for future use.
     * @return bool Returns **true** if the delete was successful or **false** otherwise.
     */
    public function deleteID($id, $options = [])
    {
        $r = $this->delete([$this->PrimaryKey => $id]);
        return $r;
    }

    /**
     * Filter out any potentially insecure fields before they go to the database.
     *
     * @param array $data The array of data to filter.
     * @return array Returns a copy of {@link $data} with fields removed.
     */
    public function filterForm($data)
    {
        $data = array_diff_key($data, $this->filterFields);
        return $data;
    }

    /**
     * Returns an array with only those keys that are actually in the schema.
     *
     * @param array $data An array of key/value pairs.
     * @return array The filtered array.
     */
    public function filterSchema($data)
    {
        $fields = $this->Schema->fields($this->Name);

        $result = array_intersect_key($data, $fields);
        return $result;
    }

    /**
     *
     *
     * @param string $orderFields
     * @param string $orderDirection
     * @param int|bool $limit
     * @param int|bool $Offset
     * @return Gdn_Dataset
     */
    public function get($orderFields = "", $orderDirection = "asc", $limit = false, $pageNumber = false)
    {
        $this->_beforeGet();

        return $this->SQL->get($this->Name, $orderFields, $orderDirection, $limit, $pageNumber);
    }

    /**
     * Returns a count of the # of records in the table.
     *
     * @param array|string $wheres
     * @return int
     */
    public function getCount($wheres = "")
    {
        $this->_beforeGet();

        $this->SQL->select("*", "count", "Count")->from($this->Name);

        if (is_array($wheres)) {
            $this->SQL->where($wheres);
        }

        $data = $this->SQL->get()->firstRow();

        return $data === false ? 0 : $data->Count;
    }

    /**
     * Get the data from the model based on its primary key.
     *
     * @param mixed $id The value of the primary key in the database.
     * @param string|false $datasetType The format of the result dataset.
     * @param array $options options to pass to the database.
     * @return array|object
     */
    public function getID($id, $datasetType = false, $options = [])
    {
        $this->options($options);
        $result = $this->getWhere([$this->PrimaryKey => $id])->firstRow($datasetType);

        $fields = self::COLLAPSEABLE_FIELDS;

        foreach ($fields as $field) {
            if (is_array($result)) {
                if (isset($result[$field]) && is_string($result[$field])) {
                    $val = dbdecode($result[$field]);
                    if ($val) {
                        $result[$field] = $val;
                    } else {
                        $result[$field] = $val;
                    }
                }
            } elseif (is_object($result)) {
                if (isset($result->$field) && is_string($result->$field)) {
                    $val = dbdecode($result->$field);
                    if ($val) {
                        $result->$field = $val;
                    } else {
                        $result->$field = null;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Get a dataset for the model with a where filter.
     *
     * @param array|bool $where A filter suitable for passing to Gdn_SQLDriver::where().
     * @param string $orderFields A comma delimited string to order the data.
     * @param string $orderDirection One of **asc** or **desc**.
     * @param int|false $limit The database limit.
     * @param int|false $offset The database offset.
     * @return Gdn_DataSet
     */
    public function getWhere(
        $where = false,
        $orderFields = "",
        $orderDirection = "asc",
        $limit = false,
        $offset = false
    ) {
        $this->_beforeGet();
        return $this->SQL->getWhere($this->Name, $where, $orderFields, $orderDirection, $limit, $offset);
    }

    /**
     * Iterator version of Gdn_Model::getWhere() where results are fetched in batches.
     *
     * @param array $where
     * @param string $orderFields
     * @param string $orderDirection
     * @param bool $expand
     * @param int $batchSize
     * @return Generator<int, array>
     */
    public function getWhereIterator(
        array $where = [],
        string $orderFields = "",
        string $orderDirection = "",
        bool $expand = true,
        int $batchSize = 100
    ): Generator {
        $offset = 0;
        while (true) {
            $results = $this->getWhere(
                $where,
                $orderFields,
                $orderDirection,
                $batchSize,
                $offset,
                $expand
            )->resultArray();
            foreach ($results as $result) {
                $primaryKey = $result[$this->PrimaryKey];
                yield $primaryKey => $result;
            }

            $offset += $batchSize;

            if (count($results) < $batchSize) {
                // We made it to the end.
                return;
            }
        }
    }

    /**
     * Returns the $this->Validation->validationResults() array.
     *
     * @return array
     */
    public function validationResults()
    {
        return $this->Validation->results();
    }

    /**
     * @param array $formPostValues
     * @param bool $insert
     * @return bool
     */
    public function validate($formPostValues, $insert = false)
    {
        $this->defineSchema();
        return $this->Validation->validate($formPostValues, $insert);
    }

    /**
     * Coerce the data in a given row to conform to the data types in this model's schema.
     *
     * This method doesn't do full data cleansing yet because that is too much of a change at this point. For this
     * reason this method has been kept protected. The main purpose here is to clean the data enough to work better with
     * MySQL's strict mode.
     *
     * @param array $row The row of data to coerce.
     * @param bool $strip Whether or not to strip missing columns.
     * @return array Returns a new data row with cleansed data.
     */
    protected function coerceData($row, $strip = true)
    {
        $columns = array_change_key_case($this->defineSchema()->fields());

        $result = [];
        foreach ($row as $key => $value) {
            $name = strtolower($key);

            if (isset($columns[$name])) {
                $column = $columns[$name];

                switch ($column->Type) {
                    case "int":
                    case "tinyint":
                    case "smallint":
                    case "bigint":
                        if ($value === "" || $value === null) {
                            $value = null;
                        } else {
                            $value = (int) $value;
                        }
                        break;
                    case "enum":
                        $enums = array_change_key_case(array_combine($column->Enum, $column->Enum));
                        if (isset($enums[strtolower($value)])) {
                            $value = $enums[strtolower($value)];
                        } elseif (!$value) {
                            $value = null;
                        } else {
                            trigger_error("Enum value of '$value' not valid for {$column->Key}", E_USER_NOTICE);
                            $value = null;
                        }
                        break;
                    case "float":
                        if ($value === "" || $value === null) {
                            $value = null;
                        } else {
                            $value = (float) $value;
                        }
                        break;
                    case "double":
                        if ($value === "" || $value === null) {
                            $value = null;
                        } else {
                            $value = (float) $value;
                        }
                        break;
                    case "datetime":
                    case "timestamp":
                        $value = $value ?: null;
                        break;
                    case "varchar":
                    case "text":
                    case "mediumtext":
                    case "longtext":
                        // Should already have been validated.
                        break;
                }
                $result[$column->Name] = $value;
            } elseif (!$strip) {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    /**
     * Adds $this->InsertUserID and $this->DateInserted fields to an associative
     * array of fieldname/values if those fields exist on the table being
     * inserted.
     *
     * @param array $fields The array of fields to add the values to.
     */
    protected function addInsertFields(&$fields)
    {
        $this->defineSchema();
        if ($this->Schema->fieldExists($this->Name, $this->DateInserted)) {
            if (!isset($fields[$this->DateInserted])) {
                $fields[$this->DateInserted] = DateTimeFormatter::getCurrentDateTime();
            }
        }

        $session = Gdn::session();
        if ($session->UserID > 0 && $this->Schema->fieldExists($this->Name, $this->InsertUserID)) {
            if (!isset($fields[$this->InsertUserID])) {
                $fields[$this->InsertUserID] = $session->UserID;
            }
        }

        if ($this->Schema->fieldExists($this->Name, "InsertIPAddress") && !isset($fields["InsertIPAddress"])) {
            $fields["InsertIPAddress"] = ipEncode(Gdn::request()->ipAddress());
        }
    }

    /**
     * Adds $this->UpdateUserID and $this->DateUpdated fields to an associative
     * array of fieldname/values if those fields exist on the table being updated.
     *
     * @param array $fields The array of fields to add the values to.
     */
    protected function addUpdateFields(&$fields)
    {
        $this->defineSchema();
        if ($this->Schema->fieldExists($this->Name, $this->DateUpdated)) {
            if (!isset($fields[$this->DateUpdated])) {
                $fields[$this->DateUpdated] = DateTimeFormatter::getCurrentDateTime();
            }
        }

        $session = Gdn::session();
        if ($session->UserID > 0 && $this->Schema->fieldExists($this->Name, $this->UpdateUserID)) {
            if (!isset($fields[$this->UpdateUserID])) {
                $fields[$this->UpdateUserID] = $session->UserID;
            }
        }

        if ($this->Schema->fieldExists($this->Name, "UpdateIPAddress") && !isset($fields["UpdateIPAddress"])) {
            $fields["UpdateIPAddress"] = ipEncode(Gdn::request()->ipAddress());
        }
    }

    /**
     * Gets/sets an option on the object.
     *
     * @param string|array $key The key of the option.
     * @param mixed $value The value of the option or not specified just to get the current value.
     * @return mixed The value of the option or $this if $value is specified.
     */
    public function options($key, $value = null)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->SQL->options($k, $v);
            }
        } else {
            $this->SQL->options($key, $value);
        }
        return $this;
    }

    /**
     *
     *
     * @param string $column
     * @param int $rowID
     * @param string $name
     * @param string $value
     * @return bool|Gdn_DataSet|object|string
     * @throws Exception
     */
    public function saveToSerializedColumn($column, $rowID, $name, $value = "")
    {
        if (!isset($this->Schema)) {
            $this->defineSchema();
        }
        // TODO: need to be sure that $this->PrimaryKey is only one primary key
        $fieldName = $this->PrimaryKey;

        // Load the existing values
        $row = $this->SQL
            ->select($column)
            ->from($this->Name)
            ->where($fieldName, $rowID)
            ->get()
            ->firstRow();

        if (!$row) {
            throw new Exception(t("ErrorRecordNotFound"));
        }
        $values = dbdecode($row->$column);

        if (is_string($values) && $values != "") {
            throw new Exception(t("Serialized column failed to be unserialized."));
        }

        if (!is_array($values)) {
            $values = [];
        }
        if (!is_array($name)) {
            // Assign the new value(s)
            $name = [$name => $value];
        }

        $values = dbencode(array_merge($values, $name));

        // Save the values back to the db
        $result = $this->SQL
            ->from($this->Name)
            ->where($fieldName, $rowID)
            ->set($column, $values)
            ->put();
        $this->onUpdate();
        return $result;
    }

    /**
     *
     *
     * @param int $rowID
     * @param string $property
     * @param bool $forceValue
     * @return bool|string
     * @throws Exception
     */
    public function setProperty($rowID, $property, $forceValue = false)
    {
        if (!isset($this->Schema)) {
            $this->defineSchema();
        }
        $primaryKey = $this->PrimaryKey;

        if ($forceValue !== false) {
            $value = $forceValue;
        } else {
            $row = $this->getID($rowID);
            $value = $row->$property == "1" ? "0" : "1";
        }
        $this->SQL
            ->update($this->Name)
            ->set($property, $value)
            ->where($primaryKey, $rowID)
            ->put();
        $this->onUpdate();
        return $value;
    }

    /**
     * Get something from $record['Attributes'] by dot-formatted key.
     *
     * Pass record byref.
     *
     * @param array $record
     * @param string $attribute
     * @param mixed $default Optional.
     * @return mixed
     */
    public static function getRecordAttribute(&$record, $attribute, $default = null)
    {
        $rV = "Attributes.{$attribute}";
        return valr($rV, $record, $default);
    }

    /**
     * Set something on $record['Attributes'] by dot-formatted key.
     *
     * Pass record byref.
     *
     * @param array $record
     * @param string $attribute
     * @param mixed $value
     * @return mixed
     */
    public static function setRecordAttribute(&$record, $attribute, $value)
    {
        if (!array_key_exists("Attributes", $record)) {
            $record["Attributes"] = [];
        }

        if (!is_array($record["Attributes"])) {
            return null;
        }

        $work = &$record["Attributes"];
        $parts = explode(".", $attribute);
        while ($part = array_shift($parts)) {
            $setValue = sizeof($parts) ? [] : $value;
            $work[$part] = $setValue;
            $work = &$work[$part];
        }

        return $value;
    }

    /**
     * Checks whether the time frame for editing content has passed.
     *
     * @param object|array $data The content data to examine.
     * @param int $timeLeft Sets the time left to edit or 0 if not applicable.
     * @return bool Whether the time to edit the discussion has passed.
     */
    public static function editContentTimeout($data, &$timeLeft = 0)
    {
        // Determine if we still have time to edit.
        $timeInserted = strtotime(val("DateInserted", $data));
        $editContentTimeout = c("Garden.EditContentTimeout", -1);
        $canEdit = false;

        if ($editContentTimeout == -1) {
            $canEdit = true;
        } elseif ($timeInserted + $editContentTimeout > time()) {
            $canEdit = true;
        }

        if ($canEdit && $editContentTimeout > 0) {
            $timeLeft = $timeInserted + $editContentTimeout - time();
        }

        return $canEdit;
    }

    /**
     * Locks a resource so that it can only be accessed one at a time.
     *
     * @param string $lockKey Cache key to be assigned.
     * @param int $gracePeriod Period of time the key will stay valid.
     * @return bool Whether a master key has been assigned.
     */
    protected static function buildCacheLock(string $lockKey, int $gracePeriod = 60): bool
    {
        // If caching isn't enabled bail out
        $cacheEnabled = Gdn_Cache::activeEnabled();
        if (!$cacheEnabled) {
            return true;
        }

        /**
         * Attempt to add lock using our process ID. A failure likely means the
         * cache key already exists, which would mean the lock is already in place.
         */
        $instanceKey = getmypid();
        $added = Gdn::cache()->add($lockKey, $instanceKey, [
            Gdn_Cache::FEATURE_EXPIRY => $gracePeriod,
        ]);
        if ($added) {
            return true;
        } else {
            return $instanceKey === Gdn::cache()->get($lockKey);
        }
    }

    /**
     * Releases a locked resource so that it can be used again.
     *
     * @param string $lockKey Cache key to be assigned.
     * @return bool Whether a master key has been released.
     */
    protected function releaseCacheLock(string $lockKey): bool
    {
        // If caching isn't enabled bail out
        $cacheEnabled = Gdn_Cache::activeEnabled();
        if (!$cacheEnabled) {
            return true;
        }
        $keyReleased = Gdn::cache()->remove($lockKey);
        return $keyReleased;
    }

    /**
     * Get the approximate total row count of the model's table.
     *
     * @return int
     */
    public function getTotalRowCount(): int
    {
        return $this->Database->getEstimatedRowCount($this->Name);
    }

    /**
     * Called whenever a record is updated.
     */
    protected function onUpdate()
    {
        $this->fireEvent("onUpdate");
    }

    /**
     * Return a model.
     *
     * @return Gdn_Model
     */
    public function getLegacyModel(): \Gdn_Model
    {
        return $this;
    }

    /**
     * Filter a list of recordIDs to only ones that currently exist.
     *
     * @param int[] $recordIDs The incoming recordIDs.
     *
     * @return array The filtered recordIDs.
     */
    public function filterExistingRecordIDs(array $recordIDs): array
    {
        $primaryKey = $this->PrimaryKey;
        $existing = $this->createSql()
            ->select($primaryKey)
            ->from($this->Name)
            ->where($primaryKey, $recordIDs)
            ->get()
            ->column($primaryKey);
        return $existing;
    }
}
