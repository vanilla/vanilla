<?php
/**
 * Class Gdn_ConfigurationModel
 *
 * @author Mark O'Sullivan <mark@vanillaforums.com>
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

/**
 * Represents and manages configuration data
 *
 * This generic model can be instantiated (with the configuration array
 * name it is intended to represent) and used directly, or it can be extended
 * and overridden for more complicated procedures related to different
 * configuration arrays.
 */
class Gdn_ConfigurationModel {

    /**
     * @var string The name of the configuration array that this model is intended to
     * represent. The default value assigned to $this->Name will be the name
     * that the model was instantiated with (defined in $this->__construct()).
     */
    public $Name;

    /** @var object An object that is used to manage and execute data integrity rules on this object. */
    public $Validation;

    /** @var object The actual array of data being worked on. */
    public $Data;

    /**
     * @var string A collection of Field => Values that will NOT be validated and WILL be
     * saved as long as validation succeeds. You can add to this collection with
     * $this->ForceSetting();
     */
    private $_ForceSettings = array();

    /**
     * Class constructor. Defines the related database table name.
     *
     * @param string $ConfigurationArrayName The name of the configuration array that is being manipulated.
     * @param object $Validation
     */
    public function __construct($Validation) {
        $this->Name = 'Configuration';
        $this->Data = array();
        $this->Validation = $Validation;
    }

    /**
     * Allows the user to declare which values are being manipulated in the
     * $this->Name configuration array.
     *
     * @param mixed $FieldName The name of the field (or array of field names) to ensure.
     */
    public function setField($FieldName) {
        $Config = Gdn::factory(Gdn::AliasConfig);
        if (is_array($FieldName) === false) {
            $FieldName = array($FieldName);
        }

        foreach ($FieldName as $Index => $Value) {
            if (is_numeric($Index)) {
                $NameKey = $Value;
                $Default = '';
            } else {
                $NameKey = $Index;
                $Default = $Value;
            }
            /*
            if ($this->Name != 'Configuration')
               $Name = $NameKey;
            else
               $Name = $this->Name.'.'.$NameKey;
            */

            $this->Data[$NameKey] = $Config->get($NameKey, $Default);
        }
    }

    /**
     * Adds a new Setting => Value pair that will NOT be validated and WILL be
     * saved to the configuration array.
     *
     * @param mixed $FieldName The name of the field (or array of field names) to save.
     * @param mixed $FieldValue The value of FieldName to be saved.
     */
    public function forceSetting($FieldName, $FieldValue) {
        $this->_ForceSettings[$FieldName] = $FieldValue;
    }

    /**
     * Takes an associative array and munges it's keys together with a dot
     * delimiter. For example:
     *  $Array['Database']['Host'] = 'dbhost';
     *  ... becomes ...
     *  $Array['Database.Host'] = 'dbhost';
     *
     * @param array $Array The array to be normalized.
     */
    private function normalizeArray($Array) {
        $Return = array();
        foreach ($Array as $Key => $Value) {
            if (is_array($Value) === true && array_key_exists(0, $Value) === false) {
                foreach ($Value as $k => $v) {
                    $Return[$Key.'.'.$k] = $v;
                }
            } else {
                $Return[$Key] = $Value;
            }
        }
        return $Return;
    }

    /**
     * Takes a set of form data ($Form->_PostValues), validates them, and
     * inserts or updates them to the configuration file.
     *
     * @param array $FormPostValues An associative array of $Field => $Value pairs that represent data posted
     * from the form in the $_POST or $_GET collection.
     */
    public function save($FormPostValues, $Live = false) {
        // Fudge your way through the schema application. This will allow me to
        // force the validation object to expect the fieldnames contained in
        // $this->Data.
        $this->Validation->setSchema($this->Data);
        // Validate the form posted values
        if ($this->Validation->validate($FormPostValues)) {
            // Merge the validation fields and the forced settings into a single array
            $Settings = $this->Validation->validationFields();
            if (is_array($this->_ForceSettings)) {
                $Settings = mergeArrays($Settings, $this->_ForceSettings);
            }

            $SaveResults = saveToConfig($Settings);

            // If the Live flag is true, set these in memory too
            if ($SaveResults && $Live) {
                Gdn::config()->set($Settings, true);
            }

            return $SaveResults;
        } else {
            return false;
        }
    }

    /**
     * A convenience method to check that the form-posted data is valid; just
     * in case you don't want to jump directly to the save if the data *is* valid.
     *
     * @param string $FormPostValues
     * @return bool
     */
    public function validate($FormPostValues) {
        $this->Validation->setSchema($this->Data);
        // Validate the form posted values
        return $this->Validation->validate($FormPostValues);
    }

    /**
     * Returns the $this->Validation->ValidationResults() array.
     */
    public function validationResults() {
        return $this->Validation->results();
    }
}
